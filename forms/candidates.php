<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Candidates filter form for the CONOCER certification system.
 *
 * @package    local_conocer_cert
 * @copyright  2025 Sebastian Gonzalez Zepeda sgonzalez@infraestructuragis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_conocer_cert\forms;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for filtering candidates.
 */
class candidates_filter_form extends \moodleform {
    
    /**
     * Form definition.
     */
    public function definition() {
        global $DB;
        
        $mform = $this->_form;
        
        // Heading
        $mform->addElement('header', 'filterheader', get_string('filter_candidates', 'local_conocer_cert'));
        
        // Search by name or email
        $mform->addElement('text', 'search', get_string('search_by_name_email', 'local_conocer_cert'), ['size' => 30]);
        $mform->setType('search', PARAM_TEXT);
        
        // Filter by competency
        $competencies = $DB->get_records_menu('local_conocer_competencias', ['activo' => 1], 'nombre', 'id, nombre');
        $competencies = ['' => get_string('all_competencies', 'local_conocer_cert')] + $competencies;
        $mform->addElement('select', 'competencia_id', get_string('competency', 'local_conocer_cert'), $competencies);
        
        // Filter by level
        $levels = [
            '' => get_string('all_levels', 'local_conocer_cert'),
            '1' => get_string('level1', 'local_conocer_cert'),
            '2' => get_string('level2', 'local_conocer_cert'),
            '3' => get_string('level3', 'local_conocer_cert'),
            '4' => get_string('level4', 'local_conocer_cert'),
            '5' => get_string('level5', 'local_conocer_cert')
        ];
        $mform->addElement('select', 'nivel', get_string('level', 'local_conocer_cert'), $levels);
        
        // Filter by status
        $statuses = [
            '' => get_string('all_statuses', 'local_conocer_cert'),
            'pendiente' => get_string('estado_pendiente', 'local_conocer_cert'),
            'documentacion' => get_string('estado_documentacion', 'local_conocer_cert'),
            'evaluacion' => get_string('estado_evaluacion', 'local_conocer_cert'),
            'aprobado' => get_string('estado_aprobado', 'local_conocer_cert'),
            'rechazado' => get_string('estado_rechazado', 'local_conocer_cert')
        ];
        $mform->addElement('select', 'estado', get_string('status', 'local_conocer_cert'), $statuses);
        
        // Date range
        $mform->addElement('date_selector', 'fecha_desde', get_string('date_from', 'local_conocer_cert'), ['optional' => true]);
        $mform->addElement('date_selector', 'fecha_hasta', get_string('date_to', 'local_conocer_cert'), ['optional' => true]);
        
        // Only with pending documents
        $mform->addElement('advcheckbox', 'pendientes_doc', get_string('only_pending_documents', 'local_conocer_cert'));
        
        // Only without evaluator
        $mform->addElement('advcheckbox', 'sin_evaluador', get_string('only_without_evaluator', 'local_conocer_cert'));
        
        // Buttons
        $this->add_action_buttons(true, get_string('filter', 'local_conocer_cert'));
    }
    
    /**
     * Validation of the form.
     *
     * @param array $data Data from the form
     * @param array $files Files from the form
     * @return array Errors
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        
        // Validate date range
        if (!empty($data['fecha_desde']) && !empty($data['fecha_hasta'])) {
            if ($data['fecha_desde'] > $data['fecha_hasta']) {
                $errors['fecha_hasta'] = get_string('error_date_range', 'local_conocer_cert');
            }
        }
        
        return $errors;
    }
    
    /**
     * Return submitted data if properly submitted or returns NULL if validation fails.
     *
     * @return object|null
     */
    public function get_data() {
        $data = parent::get_data();
        if ($data) {
            // Format search term for SQL LIKE
            if (!empty($data->search)) {
                $data->search = '%' . $data->search . '%';
            }
        }
        return $data;
    }
}
