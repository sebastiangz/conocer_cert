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
 * Notification preferences form for the CONOCER certification system.
 *
 * @package    local_conocer_cert
 * @copyright  2025 Sebastian Gonzalez Zepeda sgonzalez@infraestructuragis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_conocer_cert\forms;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for configuring notification preferences.
 */
class notification_preferences_form extends \moodleform {
    
    /**
     * Form definition.
     */
    public function definition() {
        global $USER;
        
        $mform = $this->_form;
        $usertype = $this->_customdata['user_type'] ?? 'candidate';
        
        // Heading
        $mform->addElement('header', 'prefheader', get_string('notification_preferences', 'local_conocer_cert'));
        
        // Hidden field for user type
        $mform->addElement('hidden', 'user_type', $usertype);
        $mform->setType('user_type', PARAM_ALPHA);
        
        // Global notification settings
        $mform->addElement('advcheckbox', 'enable_notifications', get_string('enable_all_notifications', 'local_conocer_cert'));
        $mform->setDefault('enable_notifications', get_user_preferences('local_conocer_cert_notifications_enabled', 1));
        
        $mform->addElement('advcheckbox', 'enable_email_notifications', get_string('enable_email_notifications', 'local_conocer_cert'));
        $mform->setDefault('enable_email_notifications', get_user_preferences('local_conocer_cert_email_notifications_enabled', 1));
        $mform->disabledIf('enable_email_notifications', 'enable_notifications', 'notchecked');
        
        $mform->addElement('advcheckbox', 'enable_system_notifications', get_string('enable_system_notifications', 'local_conocer_cert'));
        $mform->setDefault('enable_system_notifications', get_user_preferences('local_conocer_cert_system_notifications_enabled', 1));
        $mform->disabledIf('enable_system_notifications', 'enable_notifications', 'notchecked');
        
        // Specific notification types based on user type
        switch ($usertype) {
            case 'candidate':
                $notification_types = [
                    'candidato_registrado' => get_string('notif_candidato_registrado', 'local_conocer_cert'),
                    'documentos_aprobados' => get_string('notif_documentos_aprobados', 'local_conocer_cert'),
                    'documentos_rechazados' => get_string('notif_documentos_rechazados', 'local_conocer_cert'),
                    'evaluador_asignado' => get_string('notif_evaluador_asignado', 'local_conocer_cert'),
                    'proceso_completado' => get_string('notif_proceso_completado', 'local_conocer_cert'),
                    'certificado_disponible' => get_string('notif_certificado_disponible', 'local_conocer_cert'),
                    'certificado_por_vencer' => get_string('notif_certificado_por_vencer', 'local_conocer_cert'),
                    'recordatorio_documentos' => get_string('notif_recordatorio_documentos', 'local_conocer_cert')
                ];
                break;
                
            case 'evaluator':
                $notification_types = [
                    'evaluador_nueva_asignacion' => get_string('notif_evaluador_nueva_asignacion', 'local_conocer_cert'),
                    'recordatorio_evaluador' => get_string('notif_recordatorio_evaluador', 'local_conocer_cert'),
                    'plazo_evaluacion_vencimiento' => get_string('notif_plazo_evaluacion_vencimiento', 'local_conocer_cert')
                ];
                break;
                
            case 'company':
                $notification_types = [
                    'empresa_registrada' => get_string('notif_empresa_registrada', 'local_conocer_cert'),
                    'empresa_aprobada' => get_string('notif_empresa_aprobada', 'local_conocer_cert'),
                    'empresa_rechazada' => get_string('notif_empresa_rechazada', 'local_conocer_cert'),
                    'empresa_competencia_asignada' => get_string('notif_empresa_competencia_asignada', 'local_conocer_cert')
                ];
                break;
                
            case 'admin':
                $notification_types = [
                    'candidato_registrado' => get_string('notif_candidato_registrado', 'local_conocer_cert'),
                    'empresa_registrada' => get_string('notif_empresa_registrada', 'local_conocer_cert'),
                    'proceso_completado' => get_string('notif_proceso_completado', 'local_conocer_cert'),
                    'informe_certificados_vencidos' => get_string('notif_informe_certificados_vencidos', 'local_conocer_cert'),
                    'alerta_sistema' => get_string('notif_alerta_sistema', 'local_conocer_cert')
                ];
                break;
                
            default:
                $notification_types = [];
        }
        
        // Create sections for notification types
        if (!empty($notification_types)) {
            $mform->addElement('header', 'notificationtypes', get_string('notification_types', 'local_conocer_cert'));
            
            $mform->addElement('html', '<div class="notification-preferences">');
            
            foreach ($notification_types as $type => $description) {
                $mform->addElement('html', '<div class="notification-preference-item">');
                
                // Description
                $mform->addElement('html', '<div class="notification-description">' . $description . '</div>');
                
                // Email checkbox
                $email_pref = 'email_' . $type;
                $mform->addElement('checkbox', $email_pref, get_string('email', 'local_conocer_cert'));
                $mform->setDefault($email_pref, get_user_preferences('local_conocer_cert_notif_' . $type . '_email', 1));
                $mform->disabledIf($email_pref, 'enable_email_notifications', 'notchecked');
                $mform->disabledIf($email_pref, 'enable_notifications', 'notchecked');
                
                // System checkbox
                $system_pref = 'system_' . $type;
                $mform->addElement('checkbox', $system_pref, get_string('system', 'local_conocer_cert'));
                $mform->setDefault($system_pref, get_user_preferences('local_conocer_cert_notif_' . $type . '_system', 1));
                $mform->disabledIf($system_pref, 'enable_system_notifications', 'notchecked');
                $mform->disabledIf($system_pref, 'enable_notifications', 'notchecked');
                
                $mform->addElement('html', '</div>');
            }
            
            $mform->addElement('html', '</div>');
        }
        
        // Buttons
        $this->add_action_buttons(true, get_string('save_preferences', 'local_conocer_cert'));
    }
    
    /**
     * Save the notification preferences when the form is submitted.
     *
     * @return bool
     */
    public function save_preferences() {
        global $USER;
        
        $data = $this->get_data();
        if (!$data) {
            return false;
        }
        
        // Save global preferences
        set_user_preference('local_conocer_cert_notifications_enabled', $data->enable_notifications);
        set_user_preference('local_conocer_cert_email_notifications_enabled', $data->enable_email_notifications);
        set_user_preference('local_conocer_cert_system_notifications_enabled', $data->enable_system_notifications);
        
        // Get notification types based on user type
        switch ($data->user_type) {
            case 'candidate':
                $notification_types = [
                    'candidato_registrado',
                    'documentos_aprobados',
                    'documentos_rechazados',
                    'evaluador_asignado',
                    'proceso_completado',
                    'certificado_disponible',
                    'certificado_por_vencer',
                    'recordatorio_documentos'
                ];
                break;
                
            case 'evaluator':
                $notification_types = [
                    'evaluador_nueva_asignacion',
                    'recordatorio_evaluador',
                    'plazo_evaluacion_vencimiento'
                ];
                break;
                
            case 'company':
                $notification_types = [
                    'empresa_registrada',
                    'empresa_aprobada',
                    'empresa_rechazada',
                    'empresa_competencia_asignada'
                ];
                break;
                
            case 'admin':
                $notification_types = [
                    'candidato_registrado',
                    'empresa_registrada',
                    'proceso_completado',
                    'informe_certificados_vencidos',
                    'alerta_sistema'
                ];
                break;
                
            default:
                $notification_types = [];
        }
        
        // Save specific notification preferences
        foreach ($notification_types as $type) {
            $email_pref = 'email_' . $type;
            if (isset($data->$email_pref)) {
                set_user_preference('local_conocer_cert_notif_' . $type . '_email', $data->$email_pref);
            }
            
            $system_pref = 'system_' . $type;
            if (isset($data->$system_pref)) {
                set_user_preference('local_conocer_cert_notif_' . $type . '_system', $data->$system_pref);
            }
        }
        
        return true;
    }
}