<?php
// Archivo: local/conocer_cert/classes/dashboard/company_dashboard.php
// Dashboard personalizado para empresas avales

namespace local_conocer_cert\dashboard;

defined('MOODLE_INTERNAL') || die();

/**
 * Dashboard para empresas avales
 */
class company_dashboard extends base_dashboard {
    /** @var \stdClass Datos de la empresa */
    protected $company;
    
    /**
     * Inicializa el dashboard
     */
    protected function init() {
        global $DB;
        
        // Verificar si el usuario está asociado a una empresa
        $this->company = $DB->get_record('local_conocer_empresas', ['contacto_userid' => $this->user->id]);
        
        if (!$this->company) {
            $this->data = [
                'user' => $this->user,
                'has_company' => false
            ];
            return;
        }
        
        // Obtener competencias asociadas
        $competencias = json_decode($this->company->competencias, true);
        $competenciasList = [];
        
        if (!empty($competencias)) {
            list($sql, $params) = $DB->get_in_or_equal($competencias);
            $competenciasList = $DB->get_records_select('local_conocer_competencias', "id $sql", $params);
        }
        
        // Obtener estadísticas
        $stats = [
            'candidates_total' => 0,
            'candidates_approved' => 0,
            'candidates_rejected' => 0,
            'candidates_in_process' => 0
        ];
        
        if (!empty($competencias)) {
            // Total de candidatos en las competencias de la empresa
            list($sql, $params) = $DB->get_in_or_equal($competencias);
            $stats['candidates_total'] = $DB->count_records_select(
                'local_conocer_candidatos',
                "competencia_id $sql",
                $params
            );
            
            // Candidatos aprobados
            $sql = "SELECT COUNT(*) 
                    FROM {local_conocer_candidatos} c
                    JOIN {local_conocer_procesos} p ON c.id = p.candidato_id
                    WHERE c.competencia_id $sql
                    AND p.resultado = 'aprobado'";
            $stats['candidates_approved'] = $DB->count_records_sql($sql, $params);
            
            // Candidatos rechazados
            $sql = "SELECT COUNT(*) 
                    FROM {local_conocer_candidatos} c
                    JOIN {local_conocer_procesos} p ON c.id = p.candidato_id
                    WHERE c.competencia_id $sql
                    AND p.resultado = 'rechazado'";
            $stats['candidates_rejected'] = $DB->count_records_sql($sql, $params);
            
            // Candidatos en proceso
            $sql = "SELECT COUNT(*) 
                    FROM {local_conocer_candidatos} c
                    JOIN {local_conocer_procesos} p ON c.id = p.candidato_id
                    WHERE c.competencia_id $sql
                    AND p.etapa IN ('solicitud', 'evaluacion', 'pendiente_revision')";
            $stats['candidates_in_process'] = $DB->count_records_sql($sql, $params);
        }
        
        // Construir datos del dashboard
        $this->data = [
            'user' => $this->user,
            'has_company' => true,
            'company' => $this->company,
            'competencias' => $competenciasList,
            'stats' => $stats
        ];
    }
    
    /**
     * Exporta los datos para renderizado
     *
     * @param \renderer_base $output Renderer
     * @return array Datos para la plantilla
     */
    public function export_for_template($output) {
        if (!$this->company) {
            return [
                'has_company' => false,
                'fullname' => fullname($this->user),
                'register_url' => new \moodle_url('/local/conocer_cert/company/register.php')
            ];
        }
        
        $data = [
            'has_company' => true,
            'fullname' => fullname($this->user),
            'company_name' => $this->company->nombre,
            'company_rfc' => $this->company->rfc,
            'status' => get_string('estado_' . $this->company->estado, 'local_conocer_cert'),
            'competencias' => [],
            'has_competencias' => !empty($this->data['competencias']),
            'stats' => [
                'candidates_total' => $this->data['stats']['candidates_total'],
                'candidates_approved' => $this->data['stats']['candidates_approved'],
                'candidates_rejected' => $this->data['stats']['candidates_rejected'],
                'candidates_in_process' => $this->data['stats']['candidates_in_process']
            ],
            'notifications' => $this->get_notifications(),
            'company_profile_url' => new \moodle_url('/local/conocer_cert/company/profile.php'),
            'competencies_url' => new \moodle_url('/local/conocer_cert/company/competencies.php')
        ];
        
        foreach ($this->data['competencias'] as $competencia) {
            $data['competencias'][] = [
                'id' => $competencia->id,
                'nombre' => $competencia->nombre,
                'codigo' => $competencia->codigo
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
