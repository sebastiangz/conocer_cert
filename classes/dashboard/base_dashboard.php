<?php
// Archivo: local/conocer_cert/classes/dashboard/base_dashboard.php
// Clase base para dashboards personalizados

namespace local_conocer_cert\dashboard;

defined('MOODLE_INTERNAL') || die();

/**
 * Clase base para dashboards
 */
abstract class base_dashboard {
    /** @var \stdClass Usuario actual */
    protected $user;
    
    /** @var array Datos para mostrar en el dashboard */
    protected $data;
    
    /**
     * Constructor
     *
     * @param \stdClass $user Usuario para el que se genera el dashboard
     */
    public function __construct($user = null) {
        global $USER;
        
        $this->user = $user ?? $USER;
        $this->data = [];
        
        $this->init();
    }
    
    /**
     * Inicializa el dashboard
     */
    abstract protected function init();
    
    /**
     * Obtiene los datos del dashboard
     *
     * @return array Datos del dashboard
     */
    public function get_data() {
        return $this->data;
    }
    
    /**
     * Exporta los datos para renderizado
     *
     * @param \renderer_base $output Renderer
     * @return array Datos para la plantilla
     */
    abstract public function export_for_template($output);
}
