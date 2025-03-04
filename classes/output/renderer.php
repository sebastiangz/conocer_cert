<?php
// Archivo: local/conocer_cert/classes/output/renderer.php
// Renderer para el plugin de certificaciones CONOCER

namespace local_conocer_cert\output;

defined('MOODLE_INTERNAL') || die();

use plugin_renderer_base;

/**
 * Renderer para el plugin de certificaciones CONOCER
 *
 * Esta clase es responsable de renderizar las vistas del plugin
 */
class renderer extends plugin_renderer_base {
    
    /**
     * Renderiza el dashboard del candidato
     *
     * @param \local_conocer_cert\output\candidate_dashboard_page $page
     * @return string HTML
     */
    public function render_candidate_dashboard_page(\local_conocer_cert\output\candidate_dashboard_page $page) {
        $data = $page->export_for_template($this);
        return $this->render_from_template('local_conocer_cert/candidate_dashboard', $data);
    }
    
    /**
     * Renderiza el dashboard de la empresa
     *
     * @param \local_conocer_cert\output\company_dashboard_page $page
     * @return string HTML
     */
    public function render_company_dashboard_page(\local_conocer_cert\output\company_dashboard_page $page) {
        $data = $page->export_for_template($this);
        return $this->render_from_template('local_conocer_cert/company_dashboard', $data);
    }
    
    /**
     * Renderiza el dashboard del evaluador
     *
     * @param \local_conocer_cert\output\evaluator_dashboard_page $page
     * @return string HTML
     */
    public function render_evaluator_dashboard_page(\local_conocer_cert\output\evaluator_dashboard_page $page) {
        $data = $page->export_for_template($this);
        return $this->render_from_template('local_conocer_cert/evaluator_dashboard', $data);
    }
    
    /**
     * Renderiza el dashboard del administrador
     *
     * @param \local_conocer_cert\output\admin_dashboard_page $page
     * @return string HTML
     */
    public function render_admin_dashboard_page(\local_conocer_cert\output\admin_dashboard_page $page) {
        $data = $page->export_for_template($this);
        return $this->render_from_template('local_conocer_cert/admin_dashboard', $data);
    }
    
    /**
     * Renderiza la vista de detalles de candidato
     *
     * @param \local_conocer_cert\output\candidate_details_page $page
     * @return string HTML
     */
    public function render_candidate_details_page(\local_conocer_cert\output\candidate_details_page $page) {
        $data = $page->export_for_template($this);
        return $this->render_from_template('local_conocer_cert/candidate_details', $data);
    }
    
    /**
     * Renderiza la vista de detalles de la empresa
     *
     * @param \local_conocer_cert\output\company_details_page $page
     * @return string HTML
     */
    public function render_company_details_page(\local_conocer_cert\output\company_details_page $page) {
        $data = $page->export_for_template($this);
        return $this->render_from_template('local_conocer_cert/company_details', $data);
    }
    
    /**
     * Renderiza la vista de detalles de competencia
     *
     * @param \local_conocer_cert\output\competency_details_page $page
     * @return string HTML
     */
    public function render_competency_details_page(\local_conocer_cert\output\competency_details_page $page) {
        $data = $page->export_for_template($this);
        return $this->render_from_template('local_conocer_cert/competency_details', $data);
    }
    
    /**
     * Renderiza la página de evaluación de candidato
     *
     * @param \local_conocer_cert\output\candidate_evaluation_page $page
     * @return string HTML
     */
    public function render_candidate_evaluation_page(\local_conocer_cert\output\candidate_evaluation_page $page) {
        $data = $page->export_for_template($this);
        return $this->render_from_template('local_conocer_cert/candidate_evaluation', $data);
    }
    
    /**
     * Renderiza el formulario de solicitud de certificación
     *
     * @param \local_conocer_cert\output\certification_request_page $page
     * @return string HTML
     */
    public function render_certification_request_page(\local_conocer_cert\output\certification_request_page $page) {
        $data = $page->export_for_template($this);
        return $this->render_from_template('local_conocer_cert/certification_request', $data);
    }
    
    /**
     * Renderiza la tabla de certificaciones de un candidato
     *
     * @param \local_conocer_cert\output\candidate_certifications_table $table
     * @return string HTML
     */
    public function render_candidate_certifications_table(\local_conocer_cert\output\candidate_certifications_table $table) {
        $data = $table->export_for_template($this);
        return $this->render_from_template('local_conocer_cert/candidate_certifications_table', $data);
    }
    
    /**
     * Renderiza la tabla de empresas avales
     *
     * @param \local_conocer_cert\output\companies_table $table
     * @return string HTML
     */
    public function render_companies_table(\local_conocer_cert\output\companies_table $table) {
        $data = $table->export_for_template($this);
        return $this->render_from_template('local_conocer_cert/companies_table', $data);
    }
    
    /**
     * Renderiza la tabla de competencias
     *
     * @param \local_conocer_cert\output\competencies_table $table
     * @return string HTML
     */
    public function render_competencies_table(\local_conocer_cert\output\competencies_table $table) {
        $data = $table->export_for_template($this);
        return $this->render_from_template('local_conocer_cert/competencies_table', $data);
    }
    
    /**
     * Renderiza la tabla de evaluadores
     *
     * @param \local_conocer_cert\output\evaluators_table $table
     * @return string HTML
     */
    public function render_evaluators_table(\local_conocer_cert\output\evaluators_table $table) {
        $data = $table->export_for_template($this);
        return $this->render_from_template('local_conocer_cert/evaluators_table', $data);
    }
    
    /**
     * Renderiza una tarjeta de certificación
     *
     * @param \local_conocer_cert\output\certification_card $card
     * @return string HTML
     */
    public function render_certification_card(\local_conocer_cert\output\certification_card $card) {
        $data = $card->export_for_template($this);
        return $this->render_from_template('local_conocer_cert/certification_card', $data);
    }
    
    /**
     * Renderiza un indicador de estado
     *
     * @param \local_conocer_cert\output\status_indicator $indicator
     * @return string HTML
     */
    public function render_status_indicator(\local_conocer_cert\output\status_indicator $indicator) {
        $data = $indicator->export_for_template($this);
        return $this->render_from_template('local_conocer_cert/status_indicator', $data);
    }
    
    /**
     * Renderiza la barra de progreso de certificación
     *
     * @param \local_conocer_cert\output\certification_progress $progress
     * @return string HTML
     */
    public function render_certification_progress(\local_conocer_cert\output\certification_progress $progress) {
        $data = $progress->export_for_template($this);
        return $this->render_from_template('local_conocer_cert/certification_progress', $data);
    }
    
    /**
     * Renderiza la lista de notificaciones
     *
     * @param \local_conocer_cert\output\notifications_list $list
     * @return string HTML
     */
    public function render_notifications_list(\local_conocer_cert\output\notifications_list $list) {
        $data = $list->export_for_template($this);
        return $this->render_from_template('local_conocer_cert/notifications_list', $data);
    }
    
    /**
     * Renderiza el certificado para imprimir
     *
     * @param \local_conocer_cert\output\print_certificate $certificate
     * @return string HTML
     */
    public function render_print_certificate(\local_conocer_cert\output\print_certificate $certificate) {
        $data = $certificate->export_for_template($this);
        return $this->render_from_template('local_conocer_cert/print_certificate', $data);
    }
    
    /**
     * Renderiza la página de reportes
     *
     * @param \local_conocer_cert\output\reports_page $page
     * @return string HTML
     */
    public function render_reports_page(\local_conocer_cert\output\reports_page $page) {
        $data = $page->export_for_template($this);
        return $this->render_from_template('local_conocer_cert/reports_page', $data);
    }
}
