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
 * Competencies filter form for the CONOCER certification system.
 *
 * @package    local_conocer_cert
 * @copyright  2025 Sebastian Gonzalez Zepeda sgonzalez@infraestructuragis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_conocer_cert\forms;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for filtering competencies.
 */
class competencies_filter_form extends \moodleform {
    
    /**
     * Form definition.
     */
    public function definition() {
        $mform = $this->_form;
        
        // Heading
        $mform->addElement('header', 'filterheader', get_string('filter_competencies', 'local_conocer_cert'));
        
        // Search by name or code
        $mform->addElement('text', 'search', get_string('search_by_name_code', 'local_conocer_cert'), ['size' => 30]);
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
        
        // Filter by level
        $levels = [];
        $levels[''] = get_string('all_levels', 'local_conocer_cert');
        for ($i = 1; $i <= 5; $i++) {
            $levels[$i] = get_string('level' . $i, 'local_conocer_cert');
        }
        $mform->addElement('select', 'nivel', get_string('level', 'local_conocer_cert'), $levels);
        
        // Filter by evaluation type
        $evalTypes = [
            '' => get_string('all_eval_types', 'local_conocer_cert'),
            'practica' => get_string('evaltype_practical', 'local_conocer_cert'),
            'teorica' => get_string('evaltype_theoretical', 'local_conocer_cert'),
            'mixta' => get_string('evaltype_mixed', 'local_conocer_cert')
        ];
        $mform->addElement('select', 'tipo_evaluacion', get_string('evaluationtype', 'local_conocer_cert'), $evalTypes);
        
        // Only active competencies
        $mform->addElement('advcheckbox', 'activas', get_string('only_active_competencies', 'local_conocer_cert'), '', [], [0, 1]);
        $mform->setDefault('activas', 1);
        
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
        }
        return $data;
    }
}
