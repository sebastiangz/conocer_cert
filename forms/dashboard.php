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
 * Dashboard preferences form for the CONOCER certification system.
 *
 * @package    local_conocer_cert
 * @copyright  2025 Sebastian Gonzalez Zepeda sgonzalez@infraestructuragis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_conocer_cert\forms;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for setting dashboard preferences.
 */
class dashboard_preferences_form extends \moodleform {
    
    /**
     * Form definition.
     */
    public function definition() {
        global $USER;
        
        $mform = $this->_form;
        $dashboardtype = $this->_customdata['dashboard_type'] ?? 'candidate';
        
        // Heading
        $mform->addElement('header', 'prefheader', get_string('dashboard_preferences', 'local_conocer_cert'));
        
        // Hidden field for dashboard type
        $mform->addElement('hidden', 'dashboard_type', $dashboardtype);
        $mform->setType('dashboard_type', PARAM_ALPHA);
        
        // Common preferences for all dashboard types
        // Display mode
        $display_options = [
            'cards' => get_string('display_cards', 'local_conocer_cert'),
            'tables' => get_string('display_tables', 'local_conocer_cert'),
            'mixed' => get_string('display_mixed', 'local_conocer_cert')
        ];
        $mform->addElement('select', 'display_mode', get_string('display_mode', 'local_conocer_cert'), $display_options);
        $mform->setDefault('display_mode', get_user_preferences('local_conocer_cert_display_mode', 'mixed'));
        
        // Show notifications
        $mform->addElement('advcheckbox', 'show_notifications', get_string('show_notifications', 'local_conocer_cert'));
        $mform->setDefault('show_notifications', get_user_preferences('local_conocer_cert_show_notifications', 1));
        
        // Auto refresh
        $mform->addElement('advcheckbox', 'auto_refresh', get_string('auto_refresh', 'local_conocer_cert'));
        $mform->setDefault('auto_refresh', get_user_preferences('local_conocer_cert_auto_refresh', 1));
        
        // Items per page
        $items_per_page = [
            5 => 5,
            10 => 10,
            15 => 15,
            20 => 20,
            25 => 25,
            50 => 50
        ];
        $mform->addElement('select', 'items_per_page', get_string('items_per_page', 'local_conocer_cert'), $items_per_page);
        $mform->setDefault('items_per_page', get_user_preferences('local_conocer_cert_items_per_page', 10));
        
        // Sort options (depends on dashboard type)
        $sort_options = [];
        switch ($dashboardtype) {
            case 'candidate':
                $sort_options = [
                    'fecha_desc' => get_string('sort_date_newest', 'local_conocer_cert'),
                    'fecha_asc' => get_string('sort_date_oldest', 'local_conocer_cert'),
                    'competencia' => get_string('sort_competency', 'local_conocer_cert'),
                    'nivel' => get_string('sort_level', 'local_conocer_cert'),
                    'estado' => get_string('sort_status', 'local_conocer_cert')
                ];
                break;
                
            case 'evaluator':
                $sort_options = [
                    'fecha_asignacion_desc' => get_string('sort_assignment_date_newest', 'local_conocer_cert'),
                    'fecha_asignacion_asc' => get_string('sort_assignment_date_oldest', 'local_conocer_cert'),
                    'candidato' => get_string('sort_candidate_name', 'local_conocer_cert'),
                    'competencia' => get_string('sort_competency', 'local_conocer_cert'),
                    'prioridad' => get_string('sort_priority', 'local_conocer_cert')
                ];
                break;
                
            case 'company':
                $sort_options = [
                    'fecha_desc' => get_string('sort_date_newest', 'local_conocer_cert'),
                    'fecha_asc' => get_string('sort_date_oldest', 'local_conocer_cert'),
                    'candidato' => get_string('sort_candidate_name', 'local_conocer_cert'),
                    'competencia' => get_string('sort_competency', 'local_conocer_cert'),
                    'estado' => get_string('sort_status', 'local_conocer_cert')
                ];
                break;
                
            case 'admin':
                $sort_options = [
                    'fecha_desc' => get_string('sort_date_newest', 'local_conocer_cert'),
                    'fecha_asc' => get_string('sort_date_oldest', 'local_conocer_cert'),
                    'candidato' => get_string('sort_candidate_name', 'local_conocer_cert'),
                    'competencia' => get_string('sort_competency', 'local_conocer_cert'),
                    'empresa' => get_string('sort_company', 'local_conocer_cert'),
                    'estado' => get_string('sort_status', 'local_conocer_cert'),
                    'prioridad' => get_string('sort_priority', 'local_conocer_cert')
                ];
                break;
        }
        
        $mform->addElement('select', 'sort_by', get_string('sort_by', 'local_conocer_cert'), $sort_options);
        $mform->setDefault('sort_by', get_user_preferences('local_conocer_cert_' . $dashboardtype . '_sort_by', 'fecha_desc'));
        
        // Dashboard-specific preferences
        if ($dashboardtype == 'candidate') {
            // Show completed certifications
            $mform->addElement('advcheckbox', 'show_completed', get_string('show_completed_certifications', 'local_conocer_cert'));
            $mform->setDefault('show_completed', get_user_preferences('local_conocer_cert_show_completed', 1));
            
            // Email notifications for status changes
            $mform->addElement('advcheckbox', 'email_status_changes', get_string('email_status_changes', 'local_conocer_cert'));
            $mform->setDefault('email_status_changes', get_user_preferences('local_conocer_cert_email_status_changes', 1));
        }
        
        if ($dashboardtype == 'evaluator') {
            // Highlight urgent items
            $mform->addElement('advcheckbox', 'highlight_urgent', get_string('highlight_urgent_items', 'local_conocer_cert'));
            $mform->setDefault('highlight_urgent', get_user_preferences('local_conocer_cert_highlight_urgent', 1));
            
            // Urgent threshold (days)
            $urgent_thresholds = [
                3 => 3,
                5 => 5,
                7 => 7,
                10 => 10
            ];
            $mform->addElement('select', 'urgent_threshold', get_string('urgent_threshold_days', 'local_conocer_cert'), $urgent_thresholds);
            $mform->setDefault('urgent_threshold', get_user_preferences('local_conocer_cert_urgent_threshold', 5));
            $mform->disabledIf('urgent_threshold', 'highlight_urgent', 'notchecked');
            
            // Email notifications for new assignments
            $mform->addElement('advcheckbox', 'email_new_assignments', get_string('email_new_assignments', 'local_conocer_cert'));
            $mform->setDefault('email_new_assignments', get_user_preferences('local_conocer_cert_email_new_assignments', 1));
        }
        
        if ($dashboardtype == 'admin') {
            // Show statistics
            $mform->addElement('advcheckbox', 'show_statistics', get_string('show_statistics', 'local_conocer_cert'));
            $mform->setDefault('show_statistics', get_user_preferences('local_conocer_cert_show_statistics', 1));
            
            // Default section
            $sections = [
                'pending_assignments' => get_string('section_pending_assignments', 'local_conocer_cert'),
                'pending_documents' => get_string('section_pending_documents', 'local_conocer_cert'),
                'pending_companies' => get_string('section_pending_companies', 'local_conocer_cert'),
                'recent_certifications' => get_string('section_recent_certifications', 'local_conocer_cert')
            ];
            $mform->addElement('select', 'default_section', get_string('default_section', 'local_conocer_cert'), $sections);
            $mform->setDefault('default_section', get_user_preferences('local_conocer_cert_default_section', 'pending_assignments'));
        }
        
        // Buttons
        $this->add_action_buttons(true, get_string('save_preferences', 'local_conocer_cert'));
    }
    
    /**
     * Save the preferences when the form is submitted.
     *
     * @return bool
     */
    public function save_preferences() {
        $data = $this->get_data();
        if (!$data) {
            return false;
        }
        
        // Save common preferences
        set_user_preference('local_conocer_cert_display_mode', $data->display_mode);
        set_user_preference('local_conocer_cert_show_notifications', $data->show_notifications);
        set_user_preference('local_conocer_cert_auto_refresh', $data->auto_refresh);
        set_user_preference('local_conocer_cert_items_per_page', $data->items_per_page);
        set_user_preference('local_conocer_cert_' . $data->dashboard_type . '_sort_by', $data->sort_by);
        
        // Save dashboard-specific preferences
        if ($data->dashboard_type == 'candidate') {
            set_user_preference('local_conocer_cert_show_completed', $data->show_completed);
            set_user_preference('local_conocer_cert_email_status_changes', $data->email_status_changes);
        }
        
        if ($data->dashboard_type == 'evaluator') {
            set_user_preference('local_conocer_cert_highlight_urgent', $data->highlight_urgent);
            set_user_preference('local_conocer_cert_urgent_threshold', $data->urgent_threshold);
            set_user_preference('local_conocer_cert_email_new_assignments', $data->email_new_assignments);
        }
        
        if ($data->dashboard_type == 'admin') {
            set_user_preference('local_conocer_cert_show_statistics', $data->show_statistics);
            set_user_preference('local_conocer_cert_default_section', $data->default_section);
        }
        
        return true;
    }
}
