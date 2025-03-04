<?php
// Archivo: local/conocer_cert/classes/evaluator/manager.php
// Sebastian Gonzalez Zepeda sgonzalez@infraestructuragis.com
// Gestión de evaluadores externos para certificaciones CONOCER

namespace local_conocer_cert\evaluator;

defined('MOODLE_INTERNAL') || die();

/**
 * Clase para gestionar evaluadores externos
 */
class manager {
    /**
     * Registra un nuevo evaluador en el sistema
     *
     * @param \stdClass $data Datos del evaluador
     * @return int ID del evaluador registrado o false en caso de error
     */
    public static function register_evaluator($data) {
        global $DB, $USER, $CFG;
        
        // Comprobar si ya existe el usuario con ese email
        $existinguser = $DB->get_record('user', ['email' => $data->email]);
        $userid = null;
        
        if ($existinguser) {
            $userid = $existinguser->id;
        } else {
            // Crear un nuevo usuario
            $user = new \stdClass();
            $user->username = self::generate_username($data->firstname, $data->lastname);
            $user->firstname = $data->firstname;
            $user->lastname = $data->lastname;
            $user->email = $data->email;
            $user->password = generate_password(10);
            $user->confirmed = 1;
            $user->mnethostid = $CFG->mnet_localhost_id;
            $user->lang = current_language();
            $user->auth = 'manual';
            $user->timemodified = time();
            $user->timecreated = time();
            
            $userid = user_create_user($user, false, false);
            
            if (!$userid) {
                return false;
            }
            
            // Enviar credenciales de acceso
            $tempuser = $DB->get_record('user', ['id' => $userid]);
            $supportuser = core_user::get_support_user();
            
            $a = new \stdClass();
            $a->firstname = $tempuser->firstname;
            $a->lastname = $tempuser->lastname;
            $a->username = $tempuser->username;
            $a->password = $user->password;
            $a->sitename = format_string($SITE->fullname);
            $a->siteurl = $CFG->wwwroot;
            
            $subject = get_string('newusernewpasswordsubj', '', format_string($SITE->fullname));
            $message = get_string('newusernewpasswordtext', '', $a);
            
            email_to_user($tempuser, $supportuser, $subject, $message);
        }
        
        // Asignar rol de evaluador
        $systemcontext = \context_system::instance();
        role_assign(self::get_evaluator_role_id(), $userid, $systemcontext->id);
        
        // Registrar en la tabla de evaluadores
        $evaluator = new \stdClass();
        $evaluator->userid = $userid;
        $evaluator->competencias = isset($data->competencias) ? json_encode($data->competencias) : '';
        $evaluator->experiencia = isset($data->experiencia) ? $data->experiencia : '';
        $evaluator->certificaciones = isset($data->certificaciones) ? $data->certificaciones : '';
        $evaluator->disponibilidad = isset($data->disponibilidad) ? $data->disponibilidad : 'completa';
        $evaluator->timecreated = time();
        $evaluator->timemodified = time();
        $evaluator->estatus = 'activo';
        
        // Almacenar datos adicionales
        $evaluator->curp = !empty($data->curp) ? $data->curp : '';
        $evaluator->telefono = !empty($data->telefono) ? $data->telefono : '';
        $evaluator->direccion = !empty($data->direccion) ? $data->direccion : '';
        $evaluator->cedula = !empty($data->cedula) ? $data->cedula : '';
        
        $evaluatorid = $DB->insert_record('local_conocer_evaluadores', $evaluator);
        
        if ($evaluatorid) {
            // Registrar evento
            $event = \local_conocer_cert\event\evaluator_created::create([
                'objectid' => $evaluatorid,
                'context' => $systemcontext,
                'relateduserid' => $userid
            ]);
            $event->trigger();
        }
        
        return $evaluatorid;
    }
    
    /**
     * Asigna un evaluador a un candidato
     *
     * @param int $candidatoid ID del candidato
     * @param int $evaluatorid ID del evaluador
     * @param string $comentarios Comentarios sobre la asignación
     * @return bool Resultado de la operación
     */
    public static function assign_evaluator_to_candidate($candidatoid, $evaluatorid, $comentarios = '') {
        global $DB, $USER;
        
        // Verificar si el candidato existe
        $candidato = $DB->get_record('local_conocer_candidatos', ['id' => $candidatoid]);
        if (!$candidato) {
            return false;
        }
        
        // Verificar si el evaluador existe
        $evaluador = $DB->get_record('local_conocer_evaluadores', ['id' => $evaluatorid]);
        if (!$evaluador) {
            return false;
        }
        
        // Verificar si ya hay un proceso en curso
        $proceso = $DB->get_record('local_conocer_procesos', [
            'candidato_id' => $candidatoid,
            'etapa' => ['solicitud', 'evaluacion']
        ]);
        
        if (!$proceso) {
            // Crear nuevo proceso
            $proceso = new \stdClass();
            $proceso->candidato_id = $candidatoid;
            $proceso->etapa = 'evaluacion';
            $proceso->fecha_inicio = time();
            $proceso->id = $DB->insert_record('local_conocer_procesos', $proceso);
        }
        
        // Actualizar el evaluador asignado
        $proceso->evaluador_id = $evaluador->userid;
        $proceso->notas = $comentarios;
        $proceso->timemodified = time();
        
        $result = $DB->update_record('local_conocer_procesos', $proceso);
        
        if ($result) {
            // Notificar al evaluador
            $evaluatoruser = $DB->get_record('user', ['id' => $evaluador->userid]);
            $candidatouser = $DB->get_record('user', ['id' => $candidato->userid]);
            $competencia = $DB->get_record('local_conocer_competencias', ['id' => $candidato->competencia_id]);
            
            $subject = get_string('new_evaluation_subject', 'local_conocer_cert');
            $messagetext = get_string('new_evaluation_message', 'local_conocer_cert', [
                'candidate' => fullname($candidatouser),
                'competency' => $competencia->nombre,
                'level' => $candidato->nivel,
                'assignedby' => fullname($USER)
            ]);
            
            $message = new \core\message\message();
            $message->component = 'local_conocer_cert';
            $message->name = 'evaluator_assignment';
            $message->userfrom = core_user::get_noreply_user();
            $message->userto = $evaluatoruser;
            $message->subject = $subject;
            $message->fullmessage = $messagetext;
            $message->fullmessageformat = FORMAT_HTML;
            $message->fullmessagehtml = $messagetext;
            $message->smallmessage = get_string('new_evaluation_small', 'local_conocer_cert');
            $message->notification = 1;
            $message->contexturl = new \moodle_url('/local/conocer_cert/evaluator/view_candidate.php', ['id' => $candidatoid]);
            $message->contexturlname = get_string('view_candidate_details', 'local_conocer_cert');
            
            message_send($message);
            
            // Registrar evento
            $context = \context_system::instance();
            $event = \local_conocer_cert\event\evaluator_assigned::create([
                'objectid' => $proceso->id,
                'context' => $context,
                'relateduserid' => $candidato->userid,
                'other' => [
                    'evaluatorid' => $evaluador->userid,
                    'candidatoid' => $candidatoid
                ]
            ]);
            $event->trigger();
        }
        
        return $result;
    }
    
    /**
     * Obtiene los evaluadores disponibles para una competencia
     *
     * @param int $competenciaid ID de la competencia
     * @return array Lista de evaluadores
     */
    public static function get_available_evaluators($competenciaid = null) {
        global $DB;
        
        $sql = "SELECT e.*, u.firstname, u.lastname, u.email 
                FROM {local_conocer_evaluadores} e
                JOIN {user} u ON e.userid = u.id
                WHERE e.estatus = 'activo'";
        $params = [];
        
        if ($competenciaid) {
            $sql .= " AND (e.competencias LIKE :comp1 OR e.competencias LIKE :comp2 OR e.competencias LIKE :comp3)";
            $params['comp1'] = '%"' . $competenciaid . '"%';
            $params['comp2'] = '%[' . $competenciaid . ',%';
            $params['comp3'] = '%,' . $competenciaid . ',%';
        }
        
        return $DB->get_records_sql($sql, $params);
    }
    
    /**
     * Registra una evaluación para un candidato
     *
     * @param int $procesoid ID del proceso de certificación
     * @param array $data Datos de la evaluación
     * @return bool Resultado de la operación
     */
    public static function submit_evaluation($procesoid, $data) {
        global $DB, $USER;
        
        // Verificar que el proceso exista
        $proceso = $DB->get_record('local_conocer_procesos', ['id' => $procesoid]);
        if (!$proceso) {
            return false;
        }
        
        // Verificar que el usuario actual sea el evaluador asignado
        if ($proceso->evaluador_id != $USER->id) {
            return false;
        }
        
        // Actualizar el proceso con los resultados
        $proceso->resultado = $data->resultado;
        $proceso->notas = $data->notas;
        $proceso->fecha_evaluacion = time();
        
        if ($data->resultado == 'aprobado') {
            $proceso->etapa = 'aprobado';
        } else if ($data->resultado == 'rechazado') {
            $proceso->etapa = 'rechazado';
        } else {
            $proceso->etapa = 'pendiente_revision';
        }
        
        $proceso->timemodified = time();
        
        $result = $DB->update_record('local_conocer_procesos', $proceso);
        
        if ($result) {
            // Registrar los detalles de la evaluación
            $evaluacion = new \stdClass();
            $evaluacion->proceso_id = $procesoid;
            $evaluacion->evaluador_id = $USER->id;
            $evaluacion->calificacion = $data->calificacion;
            $evaluacion->comentarios = $data->comentarios;
            $evaluacion->recomendaciones = $data->recomendaciones;
            $evaluacion->timecreated = time();
            
            $DB->insert_record('local_conocer_evaluaciones', $evaluacion);
            
            // Notificar al candidato
            $candidato = $DB->get_record('local_conocer_candidatos', ['id' => $proceso->candidato_id]);
            $candidatouser = $DB->get_record('user', ['id' => $candidato->userid]);
            
            $competencia = $DB->get_record('local_conocer_competencias', ['id' => $candidato->competencia_id]);
            
            $subject = get_string('evaluation_result_subject', 'local_conocer_cert');
            $messagetext = get_string('evaluation_result_message_' . $data->resultado, 'local_conocer_cert', [
                'competency' => $competencia->nombre,
                'level' => $candidato->nivel,
                'evaluator' => fullname($USER)
            ]);
            
            $message = new \core\message\message();
            $message->component = 'local_conocer_cert';
            $message->name = 'evaluation_result';
            $message->userfrom = $USER;
            $message->userto = $candidatouser;
            $message->subject = $subject;
            $message->fullmessage = $messagetext;
            $message->fullmessageformat = FORMAT_HTML;
            $message->fullmessagehtml = $messagetext;
            $message->smallmessage = get_string('evaluation_result_small', 'local_conocer_cert');
            $message->notification = 1;
            $message->contexturl = new \moodle_url('/local/conocer_cert/candidate/view_evaluation.php', ['id' => $procesoid]);
            $message->contexturlname = get_string('view_evaluation_details', 'local_conocer_cert');
            
            message_send($message);
            
            // Registrar evento
            $context = \context_system::instance();
            $event = \local_conocer_cert\event\evaluation_submitted::create([
                'objectid' => $procesoid,
                'context' => $context,
                'relateduserid' => $candidato->userid,
                'other' => [
                    'result' => $data->resultado
                ]
            ]);
            $event->trigger();
        }
        
        return $result;
    }
    
    /**
     * Genera un nombre de usuario basado en nombres y apellidos
     *
     * @param string $firstname Nombre
     * @param string $lastname Apellido
     * @return string Nombre de usuario único
     */
    private static function generate_username($firstname, $lastname) {
        global $DB;
        
        // Normalizar caracteres y reemplazar espacios
        $firstname = clean_param(strtolower($firstname), PARAM_USERNAME);
        $lastname = clean_param(strtolower($lastname), PARAM_USERNAME);
        
        // Crear propuesta de username
        $username = substr($firstname, 0, 1) . $lastname;
        
        // Verificar si ya existe
        if (!$DB->record_exists('user', ['username' => $username])) {
            return $username;
        }
        
        // Si existe, añadir número
        $i = 1;
        while ($DB->record_exists('user', ['username' => $username . $i])) {
            $i++;
        }
        
        return $username . $i;
    }
    
    /**
     * Obtiene el ID del rol de evaluador
     *
     * @return int ID del rol
     */
    public static function get_evaluator_role_id() {
        global $DB;
        
        $role = $DB->get_record('role', ['shortname' => 'conocerevaluator']);
        if (!$role) {
            // Crear el rol si no existe
            $role = create_role(
                get_string('evaluatorrole', 'local_conocer_cert'),
                'conocerevaluator',
                get_string('evaluatorrole_desc', 'local_conocer_cert'),
                'editingteacher'
            );
            
            // Asignar capacidades
            $capabilities = [
                'local/conocer_cert:evaluatecandidates' => CAP_ALLOW,
                'local/conocer_cert:viewevaluations' => CAP_ALLOW,
                'local/conocer_cert:gradecandidates' => CAP_ALLOW,
                'moodle/user:viewdetails' => CAP_ALLOW,
                'moodle/course:view' => CAP_ALLOW,
                'moodle/site:viewparticipants' => CAP_ALLOW
            ];
            
            $systemcontext = \context_system::instance();
            foreach ($capabilities as $capability => $permission) {
                assign_capability($capability, $permission, $role, $systemcontext->id);
            }
