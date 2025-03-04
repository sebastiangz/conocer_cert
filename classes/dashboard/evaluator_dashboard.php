<?php
// Archivo: local/conocer_cert/classes/dashboard/evaluator_dashboard.php
// Dashboard personalizado para evaluadores

namespace local_conocer_cert\dashboard;

defined('MOODLE_INTERNAL') || die();

/**
 * Dashboard para evaluadores
 */
class evaluator_dashboard extends base_dashboard {
    /** @var \stdClass Datos del evaluador */
    protected $evaluator;
    
    /** @var array Candidatos asignados */
    protected $assigned_candidates = [];
    
    /** @var array Evaluaciones completadas */
    protected $completed_evaluations = [];
    
    /** @var array Estadísticas */
    protected $stats = [];
    
    /**
     * Inicializa el dashboard
     */
    protected function init() {
        global $DB;
        
        // Verificar si el usuario es evaluador
        $this->evaluator = $DB->get_record('local_conocer_evaluadores', ['userid' => $this->user->id, 'estatus' => 'activo']);
        
        if (!$this->evaluator) {
            $this->data = [
                'user' => $this->user,
                'is_evaluator' => false
            ];
            return;
        }
        
        // Obtener candidatos asignados
        $this->assigned_candidates = \local_conocer_cert\evaluator\manager::get_assigned_candidates(
            $this->user->id, 
            'pendientes'
        );
        
        // Obtener evaluaciones completadas
        $this->completed_evaluations = \local_conocer_cert\evaluator\manager::get_assigned_candidates(
            $this->user->id, 
            'completados'
        );
        
        // Obtener estadísticas
        $this->stats = \local_conocer_cert\evaluator\manager::get_evaluator_workload($this->evaluator->id);
        
        // Construir datos del dashboard
        $this->data = [
            'user' => $this->user,
            'is_evaluator' => true,
            'evaluator' => $this->evaluator,
            'assigned_candidates' => $this->assigned_candidates,
            'completed_evaluations' => $this->completed_evaluations,
            'stats' => $this->stats,
            'has_assigned_candidates' => !empty($this->assigned_candidates),
            'has_completed_evaluations' => !empty($this->completed_evaluations)
        ];
    }
    
    /**
     * Exporta los datos para renderizado
     *
     * @param \renderer_base $output Renderer
     * @return array Datos para la plantilla
     */
    public function export_for_template($output) {
        if (!$this->evaluator) {
            return [
                'is_evaluator' => false,
                'fullname' => fullname($this->user)
            ];
        }
        
        $data = [
            'is_evaluator' => true,
            'fullname' => fullname($this->user),
            'assigned_candidates' => [],
            'completed_evaluations' => [],
            'has_assigned_candidates' => !empty($this->assigned_candidates),
            'has_completed_evaluations' => !empty($this->completed_evaluations),
            'stats' => [
                'total_asignados' => $this->stats['total_asignados'],
                'pendientes' => $this->stats['pendientes'],
                'en_progreso' => $this->stats['en_progreso'],
                'completados' => $this->stats['completados'],
                'ultimos_7_dias' => $this->stats['ultimos_7_dias']
            ],
            'notifications' => $this->get_notifications(),
            'pending_url' => new \moodle_url('/local/conocer_cert/evaluator/pending.php'),
            'completed_url' => new \moodle_url('/local/conocer_cert/evaluator/completed.php'),
            'profile_url' => new \moodle_url('/local/conocer_cert/evaluator/profile.php')
        ];
        
        // Procesar candidatos asignados
        foreach ($this->assigned_candidates as $candidate) {
            $data['assigned_candidates'][] = [
                'id' => $candidate->id,
                'candidate_name' => $candidate->firstname . ' ' . $candidate->lastname,
                'competencia' => $candidate->competencia_nombre,
                'nivel' => $candidate->nivel,
                'fecha_asignacion' => userdate($candidate->fecha_inicio),
                'days_pending' => floor((time() - $candidate->fecha_inicio) / 86400),
                'is_urgent' => ((time() - $candidate->fecha_inicio) > (3 * 86400)),
                'view_url' => new \moodle_url('/local/conocer_cert/evaluator/evaluate.php', ['id' => $candidate->id])
            ];
        }
        
        // Procesar evaluaciones completadas
        foreach ($this->completed_evaluations as $evaluation) {
            $data['completed_evaluations'][] = [
                'id' => $evaluation->id,
                'candidate_name' => $evaluation->firstname . ' ' . $evaluation->lastname,
                'competencia' => $evaluation->competencia_nombre,
                'nivel' => $evaluation->nivel,
                'resultado' => get_string('resultado_' . $evaluation->resultado, 'local_conocer_cert'),
                'fecha_evaluacion' => userdate($evaluation->fecha_evaluacion),
                'view_url' => new \moodle_url('/local/conocer_cert/evaluator/view_evaluation.php', ['id' => $evaluation->id])
            ];
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
