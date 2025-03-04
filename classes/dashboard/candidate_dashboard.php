<?php
// Archivo: local/conocer_cert/classes/dashboard/candidate_dashboard.php
// Dashboard personalizado para candidatos a certificación

namespace local_conocer_cert\dashboard;

defined('MOODLE_INTERNAL') || die();

/**
 * Dashboard para candidatos a certificación
 */
class candidate_dashboard extends base_dashboard {
    /** @var array Solicitudes del candidato */
    protected $requests = [];
    
    /** @var array Documentos pendientes */
    protected $pending_documents = [];
    
    /** @var array Procesos activos */
    protected $active_processes = [];
    
    /** @var array Certificaciones completadas */
    protected $completed_certifications = [];
    
    /**
     * Inicializa el dashboard
     */
    protected function init() {
        global $DB;
        
        // Obtener solicitudes del candidato
        $this->requests = $DB->get_records('local_conocer_candidatos', ['userid' => $this->user->id], 'fecha_solicitud DESC');
        
        // Procesar cada solicitud
        foreach ($this->requests as $request) {
            // Obtener competencia
            $competencia = $DB->get_record('local_conocer_competencias', ['id' => $request->competencia_id]);
            $request->competencia_nombre = $competencia ? $competencia->nombre : '';
            $request->competencia_codigo = $competencia ? $competencia->codigo : '';
            
            // Obtener documentos pendientes
            $tiposDocs = ['id_oficial', 'curp_doc', 'comprobante_domicilio', 'evidencia_laboral', 'fotografia'];
            $documentos = $DB->get_records('local_conocer_documentos', ['candidato_id' => $request->id]);
            
            $docsUploaded = [];
            foreach ($documentos as $doc) {
                $docsUploaded[] = $doc->tipo;
            }
            
            $pendingDocs = array_diff($tiposDocs, $docsUploaded);
            
            if (!empty($pendingDocs)) {
                $this->pending_documents[$request->id] = [
                    'request' => $request,
                    'pending' => $pendingDocs
                ];
            }
            
            // Obtener procesos activos
            $proceso = $DB->get_record('local_conocer_procesos', [
                'candidato_id' => $request->id,
                'etapa' => ['solicitud', 'evaluacion', 'pendiente_revision']
            ]);
            
            if ($proceso) {
                // Añadir información del evaluador si está asignado
                if (!empty($proceso->evaluador_id)) {
                    $evaluador = $DB->get_record('user', ['id' => $proceso->evaluador_id]);
                    $proceso->evaluador_nombre = $evaluador ? fullname($evaluador) : '';
                }
                
                $this->active_processes[$request->id] = [
                    'request' => $request,
                    'process' => $proceso
                ];
            }
            
            // Obtener certificaciones completadas
            $certificacion = $DB->get_record('local_conocer_procesos', [
                'candidato_id' => $request->id,
                'etapa' => ['aprobado', 'rechazado']
            ]);
            
            if ($certificacion) {
                $this->completed_certifications[$request->id] = [
                    'request' => $request,
                    'certification' => $certificacion
                ];
            }
        }
        
        // Construir datos del dashboard
        $this->data = [
            'user' => $this->user,
            'total_requests' => count($this->requests),
            'pending_documents' => $this->pending_documents,
            'active_processes' => $this->active_processes,
            'completed_certifications' => $this->completed_certifications,
            'has_pending_documents' => !empty($this->pending_documents),
            'has_active_processes' => !empty($this->active_processes),
            'has_completed_certifications' => !empty($this->completed_certifications)
        ];
    }
    
    /**
     * Exporta los datos para renderizado
     *
     * @param \renderer_base $output Renderer
     * @return array Datos para la plantilla
     */
    public function export_for_template($output) {
        $data = [
            'fullname' => fullname($this->user),
            'requests' => [],
            'pending_documents' => [],
            'active_processes' => [],
            'completed_certifications' => [],
            'has_pending_documents' => !empty($this->pending_documents),
            'has_active_processes' => !empty($this->active_processes),
            'has_completed_certifications' => !empty($this->completed_certifications),
            'notifications' => $this->get_notifications()
        ];
        
        // Procesar solicitudes
        foreach ($this->requests as $request) {
            $data['requests'][] = [
                'id' => $request->id,
                'competencia' => $request->competencia_nombre,
                'codigo' => $request->competencia_codigo,
                'nivel' => $request->nivel,
                'estado' => get_string('estado_' . $request->estado, 'local_conocer_cert'),
                'fecha' => userdate($request->fecha_solicitud),
                'view_url' => new \moodle_url('/local/conocer_cert/candidate/view_request.php', ['id' => $request->id])
            ];
        }
        
        // Procesar documentos pendientes
        foreach ($this->pending_documents as $id => $info) {
            $pendingDocsNames = [];
            foreach ($info['pending'] as $docType) {
                $pendingDocsNames[] = get_string('doc_' . $docType, 'local_conocer_cert');
            }
            
            $data['pending_documents'][] = [
                'request_id' => $id,
                'competencia' => $info['request']->competencia_nombre,
                'nivel' => $info['request']->nivel,
                'documentos' => implode(', ', $pendingDocsNames),
                'upload_url' => new \moodle_url('/local/conocer_cert/candidate/upload_documents.php', ['id' => $id])
            ];
        }
        
        // Procesar procesos activos
        foreach ($this->active_processes as $id => $info) {
            $data['active_processes'][] = [
                'request_id' => $id,
                'process_id' => $info['process']->id,
                'competencia' => $info['request']->competencia_nombre,
                'nivel' => $info['request']->nivel,
                'etapa' => get_string('etapa_' . $info['process']->etapa, 'local_conocer_cert'),
                'evaluador' => $info['process']->evaluador_nombre ?? get_string('no_assigned', 'local_conocer_cert'),
                'fecha_inicio' => userdate($info['process']->fecha_inicio),
                'view_url' => new \moodle_url('/local/conocer_cert/candidate/view_process.php', ['id' => $info['process']->id])
            ];
        }
        
        // Procesar certificaciones completadas
        foreach ($this->completed_certifications as $id => $info) {
            $data['completed_certifications'][] = [
                'request_id' => $id,
                'certification_id' => $info['certification']->id,
                'competencia' => $info['request']->competencia_nombre,
                'nivel' => $info['request']->nivel,
                'resultado' => get_string('resultado_' . $info['certification']->resultado, 'local_conocer_cert'),
                'fecha_fin' => userdate($info['certification']->fecha_fin),
                'view_url' => new \moodle_url('/local/conocer_cert/candidate/view_certification.php', ['id' => $info['certification']->id])
            ];
            
            // Agregar enlace para descargar certificado si fue aprobado
            if ($info['certification']->resultado == 'aprobado') {
                $data['completed_certifications'][count($data['completed_certifications']) - 1]['download_url'] = 
                    new \moodle_url('/local/conocer_cert/candidate/download_certificate.php', ['id' => $info['certification']->id]);
            }
        }
        
        return $data;
    }
    
    /**
     * Obtiene las notificaciones para el dashboard
     *
     * @return array Notificaciones
     */
    protected function get_notifications() {
        global $DB;
        
        $notifications = \local_conocer_cert\util\notification::get_unread_notifications($this->user->id, 5);
        $result = [];
        
        foreach ($notifications as $notification) {
            $result[] = [
                'id' => $notification->id,
                'message' => $notification->asunto,
                'time' => userdate($notification->timecreated),
                'is_new' => true
            ];
        }
        
        return $result;
    }
}
