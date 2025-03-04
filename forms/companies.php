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
 * Companies filter form for the CONOCER certification system.
 *
 * @package    local_conocer_cert
 * @copyright  2025 Sebastian Gonzalez Zepeda sgonzalez@infraestructuragis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_conocer_cert\forms;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for filtering companies.
 */
class companies_filter_form extends \moodleform {
    
    /**
     * Form definition.
     */
    public function definition() {
        global $DB;
        
        $mform = $this->_form;
        
        // Heading
        $mform->addElement('header', 'filterheader', get_string('filter_companies', 'local_conocer_cert'));
        
        // Search by name or RFC
        $mform->addElement('text', 'search', get_string('search_by_name_rfc', 'local_conocer_cert'), ['size' => 30]);
        $mform->setType('search', PARAM_TEXT);
        
        // Filter by sector
        $sectors = [
            '' => get_string('all_sectors', 'local_conocer_cert'),
            'agropecuario' => get_string('sector_agro', 'local_conocer_cert'),
            'industrial' => get_string('sector_industrial', 'local_conocer_cert'),
            'servicios' => get_string('sector_services', 'local_conocer_cert'),
            'educacion' => get_string('sector_education', 'local_conocer_cert'),
            'tecnologia' => get_string('sector_technology', 'local_conocer_cert'),
            'otro' => get_string('sector_other', 'local_conocer_cert')
        ];
        $mform->addElement('select', 'sector', get_string('sector', 'local_conocer_cert'), $sectors);
        
        // Filter by status
        $statuses = [
            '' => get_string('all_statuses', 'local_conocer_cert'),
            'pendiente' => get_string('estado_pendiente', 'local_conocer_cert'),
            'activo' => get_string('estado_activo', 'local_conocer_cert'),
            'rechazado' => get_string('estado_rechazado', 'local_conocer_cert'),
            'suspendido' => get_string('estado_suspendido', 'local_conocer_cert')
        ];
        $mform->addElement('select', 'estado', get_string('status', 'local_conocer_cert'), $statuses);
        
        // Filter by competency
        $competencies = $DB->get_records_menu('local_conocer_competencias', ['activo' => 1], 'nombre', 'id, nombre');
        $competencies = ['' => get_string('all_competencies', 'local_conocer_cert')] + $competencies;
        $mform->addElement('select', 'competencia_id', get_string('competency', 'local_conocer_cert'), $competencies);
        
        // Date range
        $mform->addElement('date_selector', 'fecha_desde', get_string('date_from', 'local_conocer_cert'), ['optional' => true]);
        $mform->addElement('date_selector', 'fecha_hasta', get_string('date_to', 'local_conocer_cert'), ['optional' => true]);
        
        // Only with pending approval
        $mform->addElement('advcheckbox', 'pendientes_aprobacion', get_string('only_pending_approval', 'local_conocer_cert'));
        
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
