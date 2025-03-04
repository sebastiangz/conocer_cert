<?php
// Archivo: local/conocer_cert/classes/output/company_dashboard_page.php
// Página para mostrar el dashboard de la empresa

namespace local_conocer_cert\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use templatable;
use renderer_base;

/**
 * Clase para la página del dashboard de la empresa
 */
class company_dashboard_page implements renderable, templatable {
    /** @var \local_conocer_cert\dashboard\company_dashboard Dashboard de la empresa */
    protected $dashboard;
    
    /** @var string Título de la página */
    protected $title;
    
    /** @var array Datos adicionales */
    protected $data;
    
    /**
     * Constructor
     *
     * @param \local_conocer_cert\dashboard\company_dashboard $dashboard
     * @param string $title Título de la página
     * @param array $data Datos adicionales
     */
    public function __construct($dashboard, $title, $data = []) {
        $this->dashboard = $dashboard;
        $this->title = $title;
        $this->data = $data;
    }
    
    /**
     * Exporta los datos para la plantilla
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output) {
        $data = $this->dashboard->export_for_template($output);
        $data['title'] = $this->title;
        
        // Añadir datos adicionales
        foreach ($this->data as $key => $value) {
            $data[$key] = $value;
        }
        
        return $data;
    }
}
