<?php
// Archivo: local/conocer_cert/classes/dashboard/admin_dashboard.php
// Dashboard personalizado para administradores

namespace local_conocer_cert\dashboard;

defined('MOODLE_INTERNAL') || die();

/**
 * Dashboard para administradores
 */
class admin_dashboard extends base_dashboard {
    /** @var array Estadísticas generales */
    protected $general_stats = [];
    
    /** @var array Solicitudes recientes */
    protected $recent_requests = [];
    
    /** @var array Empresas pendientes */
    protected $pending_companies = [];
    
    /** @var array Candidatos pendientes de evaluador */
    protected $pending_evaluator_assignments = [];
    
    /**
     * Inicializa el dashboard
     */
    protected function init() {
        global $DB;
        
        // Verificar permisos de administración
        $context = \context_system::instance();
        if (!\local_conocer_cert\util\security::verify_capability('local/conocer_cert:managecandidates', $context->id)) {
            $this->data = [
                'user' => $this->user,
                'is_admin' => false
            ];
            return;
        }
        
        // Obtener estadísticas generales
        $this->general_stats = [
            'total_candidates' => $DB->count_records('local_conocer_candidatos'),
            'total_companies' => $DB->count_records('local_conocer_empresas'),
            'total_evaluators' => $DB->count_records('local_conocer_evaluadores', ['estatus' => 'activo']),
            'total_competencies' => $DB->count_records('local_conocer_competencias'),
            'pending_documents' => $DB->count_records_select(
                'local_conocer_candidatos', 
                "id NOT IN (SELECT DISTINCT candidato_id FROM {local_conocer_documentos})"
            ),
            'pending_evaluation' => $DB->count_records_select(
                'local_conocer_procesos',
                "etapa = 'evaluacion' AND evaluador_id IS NULL"
            ),
            'pending_companies' => $DB->count_records('local_conocer_empresas', ['estado' => 'pendiente']),
            'approved_certifications' => $DB->count_records('local_conocer_procesos', ['resultado' => 'aprobado']),
            'rejected_certifications' => $DB->count_records('local_conocer_procesos', ['resultado' => 'rechazado'])
        ];
        
        // Obtener solicitudes recientes (últimos 7 días)
        $oneWeekAgo = time() - (7 * 24 * 60 * 60);
        $this->recent_requests = $DB->get_records_select(
            'local_conocer_candidatos',
            "fecha_solicitud > :time",
            ['time' => $oneWeekAgo],
            'fecha_solicitud DESC',
            '*',
            0,
            10
        );
        
        // Procesar cada solicitud reciente
        foreach ($this->recent_requests as $request) {
            // Obtener datos del usuario
            $user = $DB->get_record('user', ['id' => $request->userid]);
            $request->user_fullname = $user ? fullname($user) : '';
            
            // Obtener competencia
            $competencia = $DB->get_record('local_conocer_competencias', ['id' => $request->competencia_id]);
            $request->competencia_nombre = $competencia ? $competencia->nombre : '';
            $request->competencia_codigo = $competencia ? $competencia->codigo : '';
        }
        
        // Obtener empresas pendientes
        $this->pending_companies = $DB->get_records(
            'local_conocer_empresas',
            ['estado' => 'pendiente'],
            'fecha_solicitud ASC'
        );
        
        // Obtener candidatos pendientes de asignación de evaluador
        $this->pending_evaluator_assignments = $DB->get_records_sql(
            "SELECT p.id, p.candidato_id, p.fecha_inicio, c.competencia_id, c.nivel, c.userid,
                    u.firstname, u.lastname, comp.nombre as competencia_nombre
             FROM {local_conocer_procesos} p
             JOIN {local_conocer_candidatos} c ON p.candidato_id = c.id
             JOIN {user} u ON c.userid = u.id
             JOIN {local_conocer_competencias} comp ON c.competencia_id = comp.id
             WHERE p.etapa = 'evaluacion' AND p.evaluador_id IS NULL
             ORDER BY p.fecha_inicio ASC",
            [],
            0,
            10
        );
        
        // Construir datos del dashboard
        $this->data = [
            'user' => $this->user,
            'is_admin' => true,
            'general_stats' => $this->general_stats,
            'recent_requests' => $this->recent_requests,
            'pending_companies' => $this->pending_companies,
            'pending_evaluator_assignments' => $this->pending_evaluator_assignments,
            'has_recent_requests' => !empty($this->recent_requests),
            'has_pending_companies' => !empty($this->pending_companies),
            'has_pending_evaluator_assignments' => !empty($this->pending_evaluator_assignments)
        ];
    }
    
    /**
     * Exporta los datos para renderizado
     *
     * @param \renderer_base $output Renderer
     * @return array Datos para la plantilla
     */
    public function export_for_template($output) {
        if (!isset($this->data['is_admin']) || !$this->data['is_admin']) {
            return [
                'is_admin' => false,
                'fullname' => fullname($this->user)
            ];
        }
        
        $data = [
            'is_admin' => true,
            'fullname' => fullname($this->user),
            'stats' => [
                'total_candidates' => $this->general_stats['total_candidates'],
                'total_companies' => $this->general_stats['total_companies'],
                'total_evaluators' => $this->general_stats['total_evaluators'],
                'total_competencies' => $this->general_stats['total_competencies'],
                'pending_documents' => $this->general_stats['pending_documents'],
                'pending_evaluation' => $this->general_stats['pending_evaluation'],
                'pending_companies' => $this->general_stats['pending_companies'],
                'approved_certifications' => $this->general_stats['approved_certifications'],
                'rejected_certifications' => $this->general_stats['rejected_certifications']
            ],
            'recent_requests' => [],
            'pending_companies' => [],
            'pending_evaluator_assignments' => [],
            'has_recent_requests' => !empty($this->recent_requests),
            'has_pending_companies' => !empty($this->pending_companies),
            'has_pending_evaluator_assignments' => !empty($this->pending_evaluator_assignments),
            'candidates_url' => new \moodle_url('/local/conocer_cert/pages/candidates.php'),
            'companies_url' => new \moodle_url('/local/conocer_cert/pages/companies.php'),
            'evaluators_url' => new \moodle_url('/local/conocer_cert/pages/evaluators.php'),
            'competencies_url' => new \moodle_url('/local/conocer_cert/pages/competencies.php'),
            'reports_url' => new \moodle_url('/local/conocer_cert/pages/reports.php')
        ];
        
        // Procesar solicitudes recientes
        foreach ($this->recent_requests as $request) {
            $data['recent_requests'][] = [
                'id' => $request->id,
                'fullname' => $request->user_fullname,
                'competencia' => $request->competencia_nombre,
                'nivel' => $request->nivel,
                'estado' => get_string('estado_' . $request->estado, 'local_conocer_cert'),
                'fecha' => userdate($request->fecha_solicitud),
                'view_url' => new \moodle_url('/local/conocer_cert/pages/view_candidate.php', ['id' => $request->id])
            ];
        }
        
        // Procesar empresas pendientes
        foreach ($this->pending_companies as $company) {
            $data['pending_companies'][] = [
                'id' => $company->id,
                'nombre' => $company->nombre,
                'rfc' => $company->rfc,
                'contacto' => $company->contacto_nombre,
                'fecha' => userdate($company->fecha_solicitud),
                'view_url' => new \moodle_url('/local/conocer_cert/pages/view_company.php', ['id' => $company->id])
            ];
        }
        
        // Procesar candidatos pendientes de evaluador
        foreach ($this->pending_evaluator_assignments as $assignment) {
            $data['pending_evaluator_assignments'][] = [
                'id' => $assignment->id,
                'candidato_id' => $assignment->candidato_id,
                'fullname' => $assignment->firstname . ' ' . $assignment->lastname,
                'competencia' => $assignment->competencia_nombre,
                'nivel' => $assignment->nivel,
                'fecha' => userdate($assignment->fecha_inicio),
                'days_pending' => floor((time() - $assignment->fecha_inicio) / 86400),
                'is_urgent' => ((time() - $assignment->fecha_inicio) > (5 * 86400)),
                'assign_url' => new \moodle_url('/local/conocer_cert/pages/assign_evaluator.php', ['id' => $assignment->candidato_id])
            ];
        }
        
        return $data;
    }
    
    /**
     * Obtiene las notificaciones importantes para el dashboard del administrador
     *
     * @return array Notificaciones
     */
    protected function get_admin_notifications() {
        global $DB;
        
        $notifications = [];
        
        // Alertas de sistema
        // 1. Candidatos esperando más de 5 días por un evaluador
        $fiveDaysAgo = time() - (5 * 24 * 60 * 60);
        $sql = "SELECT COUNT(*) 
                FROM {local_conocer_procesos} 
                WHERE etapa = 'evaluacion' 
                AND evaluador_id IS NULL
                AND fecha_inicio < :timelimit";
        
        $waitingCount = $DB->count_records_sql($sql, ['timelimit' => $fiveDaysAgo]);
        
        if ($waitingCount > 0) {
            $notifications[] = [
                'type' => 'warning',
                'message' => get_string('admin_alert_candidates_waiting', 'local_conocer_cert', $waitingCount),
                'url' => new \moodle_url('/local/conocer_cert/pages/pending_assignments.php')
            ];
        }
        
        // 2. Empresas pendientes de revisión por más de 3 días
        $threeDaysAgo = time() - (3 * 24 * 60 * 60);
        $sql = "SELECT COUNT(*) 
                FROM {local_conocer_empresas} 
                WHERE estado = 'pendiente' 
                AND fecha_solicitud < :timelimit";
        
        $pendingCompanies = $DB->count_records_sql($sql, ['timelimit' => $threeDaysAgo]);
        
        if ($pendingCompanies > 0) {
            $notifications[] = [
                'type' => 'warning',
                'message' => get_string('admin_alert_companies_pending', 'local_conocer_cert', $pendingCompanies),
                'url' => new \moodle_url('/local/conocer_cert/pages/pending_companies.php')
            ];
        }
        
        // 3. Alertas de seguridad (intentos no autorizados)
        $oneDayAgo = time() - (24 * 60 * 60);
        $sql = "SELECT COUNT(*) 
                FROM {local_conocer_security_log} 
                WHERE timecreated > :timelimit";
        
        $securityIncidents = $DB->count_records_sql($sql, ['timelimit' => $oneDayAgo]);
        
        if ($securityIncidents > 0) {
            $notifications[] = [
                'type' => 'danger',
                'message' => get_string('admin_alert_security_incidents', 'local_conocer_cert', $securityIncidents),
                'url' => new \moodle_url('/local/conocer_cert/pages/security_log.php')
            ];
        }
        
        return $notifications;
    }
}