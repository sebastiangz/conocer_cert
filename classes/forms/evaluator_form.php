<?php
// Archivo: local/conocer_cert/classes/forms/evaluator_form.php
// Formulario para gestión de evaluadores externos

namespace local_conocer_cert\forms;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Formulario para gestionar evaluadores externos
 */
class evaluator_form extends \moodleform {
    
    function definition() {
        global $DB;
        
        $mform = $this->_form;
        $evaluator = $this->_customdata['evaluator'] ?? null;
        $edit_mode = !empty($evaluator);
        
        // Información personal
        $mform->addElement('header', 'personalinfo', get_string('personalinfo', 'local_conocer_cert'));
        
        // Si es edición, mostrar usuario existente
        if ($edit_mode) {
            $user = $DB->get_record('user', ['id' => $evaluator->userid]);
            
            // Mostrar nombre completo
            $mform->addElement('static', 'fullname', get_string('fullname', 'local_conocer_cert'), fullname($user));
            
            // Mostrar email
            $mform->addElement('static', 'email', get_string('email'), $user->email);
            
            // Campo oculto para el ID de usuario
            $mform->addElement('hidden', 'userid', $evaluator->userid);
            $mform->setType('userid', PARAM_INT);
        } else {
            // Si es nuevo evaluador, solicitar datos para crear usuario
            
            // Nombre
            $mform->addElement('text', 'firstname', get_string('firstname'), ['size' => 30]);
            $mform->setType('firstname', PARAM_TEXT);
            $mform->addRule('firstname', get_string('required'), 'required');
            
            // Apellidos
            $mform->addElement('text', 'lastname', get_string('lastname'), ['size' => 30]);
            $mform->setType('lastname', PARAM_TEXT);
            $mform->addRule('lastname', get_string('required'), 'required');
            
            // Email
            $mform->addElement('text', 'email', get_string('email'), ['size' => 50]);
            $mform->setType('email', PARAM_EMAIL);
            $mform->addRule('email', get_string('required'), 'required');
        }
        
        // CURP
        $mform->addElement('text', 'curp', get_string('curp', 'local_conocer_cert'), ['size' => 30]);
        $mform->setType('curp', PARAM_ALPHANUMEXT);
        $mform->addRule('curp', get_string('required'), 'required');
        $mform->addHelpButton('curp', 'curpformat', 'local_conocer_cert');
        
        // Teléfono
        $mform->addElement('text', 'telefono', get_string('phone', 'local_conocer_cert'), ['size' => 20]);
        $mform->setType('telefono', PARAM_ALPHANUMEXT);
        $mform->addRule('telefono', get_string('required'), 'required');
        
        // Dirección
        $mform->addElement('textarea', 'direccion', get_string('address', 'local_conocer_cert'), 
            ['rows' => 3, 'cols' => 50]);
        $mform->setType('direccion', PARAM_TEXT);
        
        // Calificaciones profesionales
        $mform->addElement('header', 'professionalinfo', get_string('professionalinfo', 'local_conocer_cert'));
        
        // Cédula profesional
        $mform->addElement('text', 'cedula', get_string('professionallicense', 'local_conocer_cert'), ['size' => 30]);
        $mform->setType('cedula', PARAM_ALPHANUMEXT);
        $mform->addHelpButton('cedula', 'professionalformat', 'local_conocer_cert');
        
        // Grado académico
        $grados = [
            'licenciatura' => get_string('bachelor', 'local_conocer_cert'),
            'maestria' => get_string('master', 'local_conocer_cert'),
            'doctorado' => get_string('phd', 'local_conocer_cert'),
            'tecnico' => get_string('technician', 'local_conocer_cert'),
            'otro' => get_string('other', 'local_conocer_cert')
        ];
        $mform->addElement('select', 'grado_academico', get_string('academicgrade', 'local_conocer_cert'), $grados);
        
        // Años de experiencia
        $mform->addElement('text', 'experiencia_anios', get_string('yearsofexperience', 'local_conocer_cert'), ['size' => 5]);
        $mform->setType('experiencia_anios', PARAM_INT);
        $mform->addRule('experiencia_anios', get_string('required'), 'required');
        
        // Experiencia profesional
        $mform->addElement('textarea', 'experiencia', get_string('professionalexperience', 'local_conocer_cert'), 
            ['rows' => 5, 'cols' => 50]);
        $mform->setType('experiencia', PARAM_TEXT);
        $mform->addRule('experiencia', get_string('required'), 'required');
        
        // Certificaciones propias
        $mform->addElement('textarea', 'certificaciones', get_string('owncertifications', 'local_conocer_cert'), 
            ['rows' => 5, 'cols' => 50]);
        $mform->setType('certificaciones', PARAM_TEXT);
        
        // Competencias CONOCER
        $mform->addElement('header', 'competencies', get_string('conocercompetencies', 'local_conocer_cert'));
        
        // Competencias que puede evaluar
        $competencias = $DB->get_records_menu('local_conocer_competencias', ['activo' => 1], 'nombre', 'id, nombre');
        $mform->addElement('select', 'competencias', get_string('competencies', 'local_conocer_cert'), 
            $competencias, ['multiple' => 'multiple']);
        $mform->addRule('competencias', get_string('required'), 'required');
        
        // Disponibilidad
        $disponibilidad = [
            'completa' => get_string('availability_full', 'local_conocer_cert'),
            'parcial' => get_string('availability_partial', 'local_conocer_cert'),
            'fines_semana' => get_string('availability_weekends', 'local_conocer_cert'),
            'limitada' => get_string('availability_limited', 'local_conocer_cert')
        ];
        $mform->addElement('select', 'disponibilidad', get_string('availability', 'local_conocer_cert'), $disponibilidad);
        
        // Documentos del evaluador
        $mform->addElement('header', 'documents', get_string('evaluatordocuments', 'local_conocer_cert'));
        
        // ID Oficial
        $mform->addElement('filepicker', 'id_oficial', get_string('officialid', 'local_conocer_cert'));
        if (!$edit_mode) {
            $mform->addRule('id_oficial', get_string('required'), 'required');
        }
        
        // CURP documento
        $mform->addElement('filepicker', 'curp_doc', get_string('curpdocument', 'local_conocer_cert'));
        if (!$edit_mode) {
            $mform->addRule('curp_doc', get_string('required'), 'required');
        }
        
        // Cédula profesional documento
        $mform->addElement('filepicker', 'cedula_doc', get_string('professionallicensedoc', 'local_conocer_cert'));
        if (!$edit_mode) {
            $mform->addRule('cedula_doc', get_string('required'), 'required');
        }
        
        // CV
        $mform->addElement('filepicker', 'cv', get_string('curriculum', 'local_conocer_cert'));
        if (!$edit_mode) {
            $mform->addRule('cv', get_string('required'), 'required');
        }
        
        // Certificados
        $mform->addElement('filepicker', 'certificados', get_string('certificationdocuments', 'local_conocer_cert'));
        
        // Estado
        $mform->addElement('header', 'status', get_string('evaluatorstatus', 'local_conocer_cert'));
        
        // Estatus
        $status_options = [
            'activo' => get_string('status_active', 'local_conocer_cert'),
            'inactivo' => get_string('status_inactive', 'local_conocer_cert'),
            'pendiente' => get_string('status_pending', 'local_conocer_cert'),
            'suspendido' => get_string('status_suspended', 'local_conocer_cert')
        ];
        $mform->addElement('select', 'estatus', get_string('status', 'local_conocer_cert'), $status_options);
        $mform->setDefault('estatus', 'activo');
        
        // Notas adicionales
        $mform->addElement('textarea', 'notas', get_string('notes', 'local_conocer_cert'), 
            ['rows' => 3, 'cols' => 50]);
        $mform->setType('notas', PARAM_TEXT);
        
        // Fecha límite (si es temporal)
        $mform->addElement('date_selector', 'fecha_limite', get_string('expiration', 'local_conocer_cert'), ['optional' => true]);
        $mform->disabledIf('fecha_limite', 'estatus', 'eq', 'activo');
        
        // Límite de candidatos simultáneos
        $mform->addElement('text', 'max_candidatos', get_string('maxcandidates', 'local_conocer_cert'), ['size' => 5]);
        $mform->setType('max_candidatos', PARAM_INT);
        $mform->setDefault('max_candidatos', 10);
        
        // Cargar datos existentes si se está editando
        if ($evaluator) {
            $this->set_data($evaluator);
            
            // Convertir competencias de JSON a array
            if (!empty($evaluator->competencias)) {
                $competencias_array = json_decode($evaluator->competencias);
                $mform->setDefault('competencias', $competencias_array);
            }
        }
        
        // Botones de acción
        $this->add_action_buttons();
    }
    
    function validation($data, $files) {
        global $DB;
        
        $errors = parent::validation($data, $files);
        
        // Validar CURP (formato mexicano)
        if (!empty($data['curp'])) {
            if (strlen($data['curp']) != 18 || !preg_match('/^[A-Z]{4}[0-9]{6}[HM][A-Z]{5}[0-9A-Z]{2}$/', $data['curp'])) {
                $errors['curp'] = get_string('invalidcurp', 'local_conocer_cert');
            }
        }
        
        // Validar teléfono
        if (!empty($data['telefono']) && !preg_match('/^[0-9]{10}$/', $data['telefono'])) {
            $errors['telefono'] = get_string('invalidphone', 'local_conocer_cert');
        }
        
        // Validar cédula profesional
        if (!empty($data['cedula']) && !preg_match('/^[0-9]{7,8}$/', $data['cedula'])) {
            $errors['cedula'] = get_string('invalidprofessionallicense', 'local_conocer_cert');
        }
        
        // Si es nuevo evaluador, validar email
        if (empty($data['userid'])) {
            // Validar que el email no exista
            if ($DB->record_exists('user', ['email' => $data['email']])) {
                $errors['email'] = get_string('emailexists');
            }
            
            // Validar formato de email
            if (!validate_email($data['email'])) {
                $errors['email'] = get_string('invalidemail');
            }
        }
        
        // Validar competencias
        if (empty($data['competencias']) || !is_array($data['competencias']) || count($data['competencias']) == 0) {
            $errors['competencias'] = get_string('selectatleastonecompetency', 'local_conocer_cert');
        }
        
        // Validar años de experiencia
        if (!empty($data['experiencia_anios']) && (!is_numeric($data['experiencia_anios']) || $data['experiencia_anios'] < 0)) {
            $errors['experiencia_anios'] = get_string('invalidyearsofexperience', 'local_conocer_cert');
        }
        
        // Validar máximo de candidatos
        if (!empty($data['max_candidatos']) && (!is_numeric($data['max_candidatos']) || $data['max_candidatos'] < 1)) {
            $errors['max_candidatos'] = get_string('invalidmaxcandidates', 'local_conocer_cert');
        }
        
        return $errors;
    }
    
    /**
     * Procesa los datos del formulario antes de guardar
     * 
     * @param array $data Datos del formulario
     * @return array Datos procesados
     */
    public function process_data($data) {
        // Convertir competencias a JSON
        if (!empty($data['competencias']) && is_array($data['competencias'])) {
            $data['competencias'] = json_encode($data['competencias']);
        } else {
            $data['competencias'] = '[]';
        }
        
        return $data;
    }
}
