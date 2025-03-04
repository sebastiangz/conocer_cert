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
 * Reports filter form for the CONOCER certification system.
 *
 * @package    local_conocer_cert
 * @copyright  2025 Sebastian Gonzalez Zepeda sgonzalez@infraestructuragis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_conocer_cert\forms;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for filtering reports.
 */
class reports_filter_form extends \moodleform {
    
    /**
     * Form definition.
     */
    public function definition() {
        global $DB;
        
        $mform = $this->_form;
        
        // Heading
        $mform->addElement('header', 'filterheader', get_string('filter_reports', 'local_conocer_cert'));
        
        // Report type
        $report_types = [
            'certifications' => get_string('report_certifications', 'local_conocer_cert'),
            'companies' => get_string('report_companies', 'local_conocer_cert'),
            'evaluators' => get_string('report_evaluators', 'local_conocer_cert'),
            'competencies' => get_string('report_competencies', 'local_conocer_cert'),
            'evaluations' => get_string('report_evaluations', 'local_conocer_cert'),
            'statistics' => get_string('report_statistics', 'local_conocer_cert')
        ];
        $mform->addElement('select', 'report_type', get_string('report_type', 'local_conocer_cert'), $report_types);
        $mform->setDefault('report_type', 'certifications');
        
        // Filter by date range
        $mform->addElement('date_selector', 'fecha_desde', get_string('date_from', 'local_conocer_cert'), ['optional' => true]);
        $mform->addElement('date_selector', 'fecha_hasta', get_string('date_to', 'local_conocer_cert'), ['optional' => true]);
        
        // Filter by competency
        $competencies = $DB->get_records_menu('local_conocer_competencias', ['activo' => 1], 'nombre', 'id, nombre');
        $competencies = ['' => get_string('all_competencies', 'local_conocer_cert')] + $competencies;
        $mform->addElement('select', 'competencia_id', get_string('competency', 'local_conocer_cert'), $competencies);
        
        // Filter by evaluator (only for evaluations report)
        $evaluators = [];
        $evaluators[''] = get_string('all_evaluators', 'local_conocer_cert');
        
        $sql = "SELECT e.id, u.id as userid, " . $DB->sql_concat('u.firstname', "' '", 'u.lastname') . " AS fullname
                FROM {local_conocer_evaluadores} e
                JOIN {user} u ON e.userid = u.id
                WHERE e.estatus = 'activo'
                ORDER BY u.firstname, u.lastname";
        $records = $DB->get_records_sql($sql);
        
        foreach ($records as $record) {
            $evaluators[$record->userid] = $record->fullname;
        }
        
        $mform->addElement('select', 'evaluador_id', get_string('evaluator', 'local_conocer_cert'), $evaluators);
        $mform->disabledIf('evaluador_id', 'report_type', 'neq', 'evaluations');
        
        // Filter by sector (for companies report)
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
        $mform->disabledIf('sector', 'report_type', 'neq', 'companies');
        
        // Group by
        $group_options = [
            'month' => get_string('group_by_month', 'local_conocer_cert'),
            'competency' => get_string('group_by_competency', 'local_conocer_cert'),
            'level' => get_string('group_by_level', 'local_conocer_cert'),
            'status' => get_string('group_by_status', 'local_conocer_cert'),
            'evaluator' => get_string('group_by_evaluator', 'local_conocer_cert'),
        ];
        $mform->addElement('select', 'group_by', get_string('group_by', 'local_conocer_cert'), $group_options);
        
        // Format
        $format_options = [
            'html' => get_string('format_html', 'local_conocer_cert'),
            'excel' => get_string('format_excel', 'local_conocer_cert'),
            'pdf' => get_string('format_pdf', 'local_conocer_cert'),
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
 * Reports filter form for the CONOCER certification system.
 *
 * @package    local_conocer_cert
 * @copyright  2025 Sebastian Gonzalez Zepeda sgonzalez@infraestructuragis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_conocer_cert\forms;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for filtering reports.
 */
class reports_filter_form extends \moodleform {
    
    /**
     * Form definition.
     */
    public function definition() {
        global $DB;
        
        $mform = $this->_form;
        
        // Heading
        $mform->addElement('header', 'filterheader', get_string('filter_reports', 'local_conocer_cert'));
        
        // Report type
        $report_types = [
            'certifications' => get_string('report_certifications', 'local_conocer_cert'),
            'companies' => get_string('report_companies', 'local_conocer_cert'),
            'evaluators' => get_string('report_evaluators', 'local_conocer_cert'),
            'competencies' => get_string('report_competencies', 'local_conocer_cert'),
            'evaluations' => get_string('report_evaluations', 'local_conocer_cert'),
            'statistics' => get_string('report_statistics', 'local_conocer_cert')
        ];
        $mform->addElement('select', 'report_type', get_string('report_type', 'local_conocer_cert'), $report_types);
        $mform->setDefault('report_type', 'certifications');
        
        // Filter by date range
        $mform->addElement('date_selector', 'fecha_desde', get_string('date_from', 'local_conocer_cert'), ['optional' => true]);
        $mform->addElement('date_selector', 'fecha_hasta', get_string('date_to', 'local_conocer_cert'), ['optional' => true]);
        
        // Filter by competency
        $competencies = $DB->get_records_menu('local_conocer_competencias', ['activo' => 1], 'nombre', 'id, nombre');
        $competencies = ['' => get_string('all_competencies', 'local_conocer_cert')] + $competencies;
        $mform->addElement('select', 'competencia_id', get_string('competency', 'local_conocer_cert'), $competencies);
        
        // Filter by evaluator (only for evaluations report)
        $evaluators = [];
        $evaluators[''] = get_string('all_evaluators', 'local_conocer_cert');
        
        $sql = "SELECT e.id, u.id as userid, " . $DB->sql_concat('u.firstname', "' '", 'u.lastname') . " AS fullname
                FROM {local_conocer_evaluadores} e
                JOIN {user} u ON e.userid = u.id
                WHERE e.estatus = 'activo'
                ORDER BY u.firstname, u.lastname";
        $records = $DB->get_records_sql($sql);
        
        foreach ($records as $record) {
            $evaluators[$record->userid] = $record->fullname;
        }
        
        $mform->addElement('select', 'evaluador_id', get_string('evaluator', 'local_conocer_cert'), $evaluators);
        $mform->disabledIf('evaluador_id', 'report_type', 'neq', 'evaluations');
        
        // Filter by sector (for companies report)
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
        $mform->disabledIf('sector', 'report_type', 'neq', 'companies');
        
        // Group by
        $group_options = [
            'month' => get_string('group_by_month', 'local_conocer_cert'),
            'competency' => get_string('group_by_competency', 'local_conocer_cert'),
            'level' => get_string('group_by_level', 'local_conocer_cert'),
            'status' => get_string('group_by_status', 'local_conocer_cert'),
            'evaluator' => get_string('group_by_evaluator', 'local_conocer_cert'),
        ];
        $mform->addElement('select', 'group_by', get_string('group_by', 'local_conocer_cert'), $group_options);
        
        // Format
        $format_options = [
            'html' => get_string('format_html', 'local_conocer_cert'),
            'excel' => get_string('format_excel', 'local_conocer_cert'),
            'pdf' => get_string('format_pdf', 'local_conocer_cert'),
            'csv' => get_string('format_csv', 'local_conocer_cert')
        ];
        $mform->addElement('select', 'format', get_string('report_format', 'local_conocer_cert'), $format_options);
        $mform->setDefault('format', 'html');
        
        // Include charts
        $mform->addElement('advcheckbox', 'include_charts', get_string('include_charts', 'local_conocer_cert'));
        $mform->setDefault('include_charts', 1);
        $mform->disabledIf('include_charts', 'format', 'eq', 'csv');
        
        // Buttons
        $this->add_action_buttons(true, get_string('generate_report', 'local_conocer_cert'));
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
}