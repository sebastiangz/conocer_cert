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
 * Evaluators filter form for the CONOCER certification system.
 *
 * @package    local_conocer_cert
 * @copyright  2025 Sebastian Gonzalez Zepeda sgonzalez@infraestructuragis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_conocer_cert\forms;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for filtering evaluators.
 */
class evaluators_filter_form extends \moodleform {
    
    /**
     * Form definition.
     */
    public function definition() {
        global $DB;
        
        $mform = $this->_form;
        
        // Heading
        $mform->addElement('header', 'filterheader', get_string('filter_evaluators', 'local_conocer_cert'));
        
        // Search by name or email
        $mform->addElement('text', 'search', get_string('search_by_name_email', 'local_conocer_cert'), ['size' => 30]);
        $mform->setType('search', PARAM_TEXT);
        
        // Filter by status
        $statuses = [
            '' => get_string('all_statuses', 'local_conocer_cert'),
            'activo' => get_string('status_active', 'local_conocer_cert'),
            'inactivo' => get_string('status_inactive', 'local_conocer_cert'),
            'pendiente' => get_string('status_pending', 'local_conocer_cert'),
            'suspendido' => get_string('status_suspended', 'local_conocer_cert')
        ];
        $mform->addElement('select', 'estatus', get_string('status', 'local_conocer_cert'), $statuses);
        
        // Filter by competency
        $competencies = $DB->get_records_menu('local_conocer_competencias', ['activo' => 1], 'nombre', 'id, nombre');
        $competencies = ['' => get_string('all_competencies', 'local_conocer_cert')] + $competencies;
        $mform->addElement('select', 'competencia_id', get_string('competency', 'local_conocer_cert'), $competencies);
        
        // Years of experience
        $experiencia = [
            '' => get_string('all_experience_levels', 'local_conocer_cert'),
            '1-2' => get_string('experience_1_2_years', 'local_conocer_cert'),
            '3-5' => get_string('experience_3_5_years', 'local_conocer_cert'),
            '6-10' => get_string('experience_6_10_years', 'local_conocer_cert'),
            '10+' => get_string('experience_10_plus_years', 'local_conocer_cert')
        ];
        $mform->addElement('select', 'experiencia', get_string('experience', 'local_conocer_cert'), $experiencia);
        
        // Availability
        $disponibilidad = [
            '' => get_string('all_availability_types', 'local_conocer_cert'),
            'completa' => get_string('availability_full', 'local_conocer_cert'),
            'parcial' => get_string('availability_partial', 'local_conocer_cert'),
            'fines_semana' => get_string('availability_weekends', 'local_conocer_cert'),
            'limitada' => get_string('availability_limited', 'local_conocer_cert')
        ];
        $mform->addElement('select', 'disponibilidad', get_string('availability', 'local_conocer_cert'), $disponibilidad);
        
        // Only with available capacity
        $mform->addElement('advcheckbox', 'with_capacity', get_string('only_with_available_capacity', 'local_conocer_cert'));
        
        // Buttons
        $this->add_action_buttons(true, get_string('filter', 'local_conocer_cert'));
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
            
            // Process experience range
            if (!empty($data->experiencia)) {
                switch ($data->experiencia) {
                    case '1-2':
                        $data->experiencia_min = 1;
                        $data->experiencia_max = 2;
                        break;
                    case '3-5':
                        $data->experiencia_min = 3;
                        $data->experiencia_max = 5;
                        break;
                    case '6-10':
                        $data->experiencia_min = 6;
                        $data->experiencia_max = 10;
                        break;
                    case '10+':
                        $data->experiencia_min = 10;
                        $data->experiencia_max = null;
                        break;
                }
            }
        }
        return $data;
    }
}
