<?php
// Archivo: local/conocer_cert/classes/util/notification.php
// 2025 Sebastian Gonzalez Zepeda sgonzalez@infraestructuragis.com
// Sistema de notificaciones automatizadas para el plugin CONOCER

namespace local_conocer_cert\util;

defined('MOODLE_INTERNAL') || die();

/**
 * Clase para gestionar notificaciones automatizadas
 */
class notification {
    /** @var array Tipos de notificaciones disponibles */
    const NOTIFICATION_TYPES = [
        'candidato_registrado' => [
            'subject' => 'notif_candidato_registrado_subject',
            'message' => 'notif_candidato_registrado_message'
        ],
        'documentos_aprobados' => [
            'subject' => 'notif_documentos_aprobados_subject',
            'message' => 'notif_documentos_aprobados_message'
        ],
        'documentos_rechazados' => [
            'subject' => 'notif_documentos_rechazados_subject',
            'message' => 'notif_documentos_rechazados_message'
        ],
        'evaluador_asignado' => [
            'subject' => 'notif_evaluador_asignado_subject',
            'message' => 'notif_evaluador_asignado_message'
        ],
        'proceso_completado' => [
            'subject' => 'notif_proceso_completado_subject',
            'message' => 'notif_proceso_completado_message'
        ],
        'certificado_disponible' => [
            'subject' => 'notif_certificado_disponible_subject',
            'message' => 'notif_certificado_disponible_message'
        ],
        'empresa_registrada' => [
            'subject' => 'notif_empresa_registrada_subject',
            'message' => 'notif_empresa_registrada_message'
        ],
        'empresa_aprobada' => [
            'subject' => 'notif_empresa_aprobada_subject',
            'message' => 'notif_empresa_aprobada_message'
        ],
        'recordatorio_evaluador' => [
            'subject' => 'notif_recordatorio_evaluador_subject',
            'message' => 'notif_recordatorio_evaluador_message'
        ],
        'recordatorio_documentos' => [
            'subject' => 'notif_recordatorio_documentos_subject',
            'message' => 'notif_recordatorio_documentos_message'
        ]
    ];
    
    /**
     * Envía una notificación por email y sistema interno
     *
     * @param int $userid ID del usuario destinatario
     * @param string $type Tipo de notificación
     * @param array $data Datos para la plantilla
     * @param int $fromid ID del usuario remitente (opcional)
     * @return bool Éxito del envío
     */
    public static function send($userid, $type, $data = [], $fromid = null) {
        global $DB;
        
        if (!isset(self::NOTIFICATION_TYPES[$type])) {
            return false;
        }
        
        $user = $DB->get_record('user', ['id' => $userid]);
        if (!$user) {
            return false;
        }
        
        // Obtener emisor del mensaje
        if ($fromid) {
            $from = $DB->get_record('user', ['id' => $fromid]);
        } else {
            $from = \core_user::get_noreply_user();
        }
        
        // Obtener textos de la notificación
        $subjectTemplate = get_string(self::NOTIFICATION_TYPES[$type]['subject'], 'local_conocer_cert');
        $messageTemplate = get_string(self::NOTIFICATION_TYPES[$type]['message'], 'local_conocer_cert');
        
        // Reemplazar variables en las plantillas
        $subject = self::parse_template($subjectTemplate, $data);
        $messageText = self::parse_template($messageTemplate, $data);
        
        // Crear mensaje para el sistema de mensajería interna
        $message = new \core\message\message();
        $message->component = 'local_conocer_cert';
        $message->name = $type;
        $message->userfrom = $from;
        $message->userto = $user;
        $message->subject = $subject;
        $message->fullmessage = $messageText;
        $message->fullmessageformat = FORMAT_HTML;
        $message->fullmessagehtml = $messageText;
        $message->smallmessage = $subject;
        $message->notification = 1;
        
        // Añadir URL de contexto si está disponible
        if (isset($data['contexturl'])) {
            $message->contexturl = $data['contexturl'];
            $message->contexturlname = isset($data['contexturlname']) ? $data['contexturlname'] : '';
        }
        
        // Enviar mensaje
        $result = message_send($message);
        
        // Registrar notificación
        $notificacion = new \stdClass();
        $notificacion->userid = $userid;
        $notificacion->remitente_id = $from->id;
        $notificacion->tipo = $type;
        $notificacion->asunto = $subject;
        $notificacion->mensaje = $messageText;
        $notificacion->leido = 0;
        $notificacion->timecreated = time();
        
        $DB->insert_record('local_conocer_notificaciones', $notificacion);
        
        return $result;
    }
    
    /**
     * Envía notificaciones en lote a múltiples usuarios
     *
     * @param array $userids IDs de usuarios destinatarios
     * @param string $type Tipo de notificación
     * @param array $data Datos para la plantilla
     * @param int $fromid ID del usuario remitente (opcional)
     * @return int Número de mensajes enviados correctamente
     */
    public static function send_batch($userids, $type, $data = [], $fromid = null) {
        $count = 0;
        foreach ($userids as $userid) {
            if (self::send($userid, $type, $data, $fromid)) {
                $count++;
            }
        }
        return $count;
    }
    
    /**
     * Procesa una plantilla reemplazando variables
     *
     * @param string $template Plantilla de texto
     * @param array $data Datos para reemplazar
     * @return string Texto procesado
     */
    private static function parse_template($template, $data) {
        // Reemplazar variables en formato {$nombre}
        $result = $template;
        
        foreach ($data as $key => $value) {
            $result = str_replace('{$' . $key . '}', $value, $result);
        }
        
        return $result;
    }
    
    /**
     * Envía notificaciones programadas (para ejecución como tarea cron)
     *
     * @return bool Resultado de la operación
     */
    public static function send_scheduled_notifications() {
        global $DB;
        
        // Enviar recordatorios a evaluadores con tareas pendientes por más de 3 días
        $threeDaysAgo = time() - (3 * 24 * 60 * 60);
        
        $sql = "SELECT p.id, p.evaluador_id, p.candidato_id, c.competencia_id, c.nivel, c.userid as candidate_userid,
                       u.firstname, u.lastname, comp.nombre as competencia_nombre
                FROM {local_conocer_procesos} p
                JOIN {local_conocer_candidatos} c ON p.candidato_id = c.id
                JOIN {user} u ON c.userid = u.id
                JOIN {local_conocer_competencias} comp ON c.competencia_id = comp.id
                WHERE p.etapa = 'evaluacion' 
                AND p.fecha_inicio < :timelimit
                AND (p.fecha_evaluacion IS NULL OR p.fecha_evaluacion = 0)";
        
        $pendingEvaluations = $DB->get_records_sql($sql, ['timelimit' => $threeDaysAgo]);
        
        foreach ($pendingEvaluations as $record) {
            // Verificar si ya se ha enviado un recordatorio en las últimas 24 horas
            $recentNotification = $DB->record_exists_select(
                'local_conocer_notificaciones',
                "userid = :userid AND tipo = 'recordatorio_evaluador' AND timecreated > :timelimit",
                ['userid' => $record->evaluador_id, 'timelimit' => time() - (24 * 60 * 60)]
            );
            
            if (!$recentNotification) {
                $candidatouser = $DB->get_record('user', ['id' => $record->candidate_userid]);
                
                self::send($record->evaluador_id, 'recordatorio_evaluador', [
                    'firstname' => $record->firstname,
                    'lastname' => $record->lastname,
                    'competencia' => $record->competencia_nombre,
                    'nivel' => $record->nivel,
                    'contexturl' => new \moodle_url('/local/conocer_cert/evaluator/view_candidate.php', ['id' => $record->candidato_id]),
                    'contexturlname' => get_string('view_candidate_details', 'local_conocer_cert')
                ]);
            }
        }
        
        // Enviar recordatorios a candidatos con documentos pendientes por más de 7 días
        $sevenDaysAgo = time() - (7 * 24 * 60 * 60);
        
        $sql = "SELECT c.id, c.userid, c.competencia_id, c.nivel, c.fecha_solicitud,
                       u.firstname, u.lastname, comp.nombre as competencia_nombre
                FROM {local_conocer_candidatos} c
                JOIN {user} u ON c.userid = u.id
                JOIN {local_conocer_competencias} comp ON c.competencia_id = comp.id
                LEFT JOIN {local_conocer_documentos} d ON c.id = d.candidato_id
                WHERE c.estado = 'pendiente' 
                AND c.fecha_solicitud < :timelimit
                AND d.id IS NULL
                GROUP BY c.id, c.userid, c.competencia_id, c.nivel, c.fecha_solicitud, 
                         u.firstname, u.lastname, comp.nombre";
        
        $pendingDocuments = $DB->get_records_sql($sql, ['timelimit' => $sevenDaysAgo]);
        
        foreach ($pendingDocuments as $record) {
            // Verificar si ya se ha enviado un recordatorio en las últimas 48 horas
            $recentNotification = $DB->record_exists_select(
                'local_conocer_notificaciones',
                "userid = :userid AND tipo = 'recordatorio_documentos' AND timecreated > :timelimit",
                ['userid' => $record->userid, 'timelimit' => time() - (48 * 60 * 60)]
            );
            
            if (!$recentNotification) {
                self::send($record->userid, 'recordatorio_documentos', [
                    'firstname' => $record->firstname,
                    'lastname' => $record->lastname,
                    'competencia' => $record->competencia_nombre,
                    'nivel' => $record->nivel,
                    'contexturl' => new \moodle_url('/local/conocer_cert/candidate/upload_documents.php', ['id' => $record->id]),
                    'contexturlname' => get_string('upload_documents', 'local_conocer_cert')
                ]);
            }
        }
        
        return true;
    }
    
    /**
     * Marca una notificación como leída
     *
     * @param int $notificationid ID de la notificación
     * @return bool Resultado de la operación
     */
    public static function mark_as_read($notificationid) {
        global $DB, $USER;
        
        $notification = $DB->get_record('local_conocer_notificaciones', ['id' => $notificationid]);
        
        if (!$notification) {
            return false;
        }
        
        // Verificar que el usuario actual sea el destinatario
        if ($notification->userid != $USER->id) {
            return false;
        }
        
        $notification->leido = 1;
        $notification->timemodified = time();
        
        return $DB->update_record('local_conocer_notificaciones', $notification);
    }
    
    /**
     * Obtiene las notificaciones no leídas para un usuario
     *
     * @param int $userid ID del usuario
     * @param int $limit Límite de notificaciones a devolver
     * @return array Lista de notificaciones
     */
    public static function get_unread_notifications($userid, $limit = 10) {
        global $DB;
        
        $sql = "SELECT n.*, u.firstname, u.lastname, u.picture
                FROM {local_conocer_notificaciones} n
                LEFT JOIN {user} u ON n.remitente_id = u.id
                WHERE n.userid = :userid AND n.leido = 0
                ORDER BY n.timecreated DESC";
        
        return $DB->get_records_sql($sql, ['userid' => $userid], 0, $limit);
    }
    
    /**
     * Obtiene el recuento de notificaciones no leídas para un usuario
     *
     * @param int $userid ID del usuario
     * @return int Número de notificaciones no leídas
     */
    public static function count_unread_notifications($userid) {
        global $DB;
        
        return $DB->count_records('local_conocer_notificaciones', [
            'userid' => $userid,
            'leido' => 0
        ]);
    }
    
    /**
     * Configura las preferencias de notificación para un usuario
     *
     * @param int $userid ID del usuario
     * @param array $preferences Preferencias de notificación
     * @return bool Resultado de la operación
     */
    public static function set_notification_preferences($userid, $preferences) {
        foreach ($preferences as $name => $value) {
            set_user_preference('local_conocer_cert_notif_' . $name, $value, $userid);
        }
        return true;
    }
    
    /**
     * Verifica si un usuario ha habilitado un tipo de notificación
     *
     * @param int $userid ID del usuario
     * @param string $type Tipo de notificación
     * @return bool Verdadero si está habilitada
     */
    public static function is_notification_enabled($userid, $type) {
        $preference = get_user_preferences('local_conocer_cert_notif_' . $type, 1, $userid);
        return (bool)$preference;
    }
    
    /**
     * Envía notificaciones cuando un documento ha sido revisado
     * 
     * @param int $documentid ID del documento
     * @param string $estado Estado de la revisión (aprobado/rechazado)
     * @param string $comentarios Comentarios de la revisión
     * @return bool Resultado de la operación
     */
    public static function notify_document_review($documentid, $estado, $comentarios = '') {
        global $DB, $USER;
        
        $documento = $DB->get_record('local_conocer_documentos', ['id' => $documentid]);
        if (!$documento) {
            return false;
        }
        
        $candidato = $DB->get_record('local_conocer_candidatos', ['id' => $documento->candidato_id]);
        if (!$candidato) {
            return false;
        }
        
        $user = $DB->get_record('user', ['id' => $candidato->userid]);
        $competencia = $DB->get_record('local_conocer_competencias', ['id' => $candidato->competencia_id]);
        
        $tipo = ($estado == 'aprobado') ? 'documentos_aprobados' : 'documentos_rechazados';
        
        return self::send($user->id, $tipo, [
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'documento' => $documento->nombre_archivo,
            'competencia' => $competencia->nombre,
            'nivel' => $candidato->nivel,
            'comentarios' => $comentarios,
            'revisor' => fullname($USER),
            'contexturl' => new \moodle_url('/local/conocer_cert/candidate/document_status.php', ['id' => $candidato->id]),
            'contexturlname' => get_string('view_document_status', 'local_conocer_cert')
        ], $USER->id);
    }
    
    /**
     * Envía notificación cuando se completa un proceso de certificación
     * 
     * @param int $procesoid ID del proceso
     * @return bool Resultado de la operación
     */
    public static function notify_certification_completed($procesoid) {
        global $DB, $USER;
        
        $proceso = $DB->get_record('local_conocer_procesos', ['id' => $procesoid]);
        if (!$proceso) {
            return false;
        }
        
        $candidato = $DB->get_record('local_conocer_candidatos', ['id' => $proceso->candidato_id]);
        if (!$candidato) {
            return false;
        }
        
        $user = $DB->get_record('user', ['id' => $candidato->userid]);
        $competencia = $DB->get_record('local_conocer_competencias', ['id' => $candidato->competencia_id]);
        
        // Notificar al candidato
        $result1 = self::send($user->id, 'proceso_completado', [
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'competencia' => $competencia->nombre,
            'nivel' => $candidato->nivel,
            'resultado' => get_string('result_' . $proceso->resultado, 'local_conocer_cert'),
            'contexturl' => new \moodle_url('/local/conocer_cert/candidate/certification.php', ['id' => $proceso->id]),
            'contexturlname' => get_string('view_certification', 'local_conocer_cert')
        ]);
        
        // Si fue aprobado, notificar que el certificado está disponible
        if ($proceso->resultado == 'aprobado') {
            $result2 = self::send($user->id, 'certificado_disponible', [
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'competencia' => $competencia->nombre,
                'nivel' => $candidato->nivel,
                'contexturl' => new \moodle_url('/local/conocer_cert/candidate/download_certificate.php', ['id' => $proceso->id]),
                'contexturlname' => get_string('download_certificate', 'local_conocer_cert')
            ]);
            
            return $result1 && $result2;
        }
        
        return $result1;
    }
}