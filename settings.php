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
 * Plugin settings page.
 *
 * @package    local_conocer_cert
 * @copyright  2025 Sebastian Gonzalez Zepeda sgonzalez@infraestructuragis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) { // Needs this condition or there is an error on login page.
    $settings = new admin_settingpage('local_conocer_cert', get_string('pluginname', 'local_conocer_cert'));
    $ADMIN->add('localplugins', $settings);
    
    // Configuración general
    $settings->add(new admin_setting_heading('local_conocer_cert/generalsettings',
        get_string('generalsettings', 'local_conocer_cert'),
        ''));
    
    // Nombre de la institución certificadora
    $settings->add(new admin_setting_configtext('local_conocer_cert/certificationauthority',
        get_string('certificationauthority', 'local_conocer_cert'),
        get_string('certificationauthoritydesc', 'local_conocer_cert'),
        get_string('pluginname', 'local_conocer_cert'), PARAM_TEXT));
    
    // Duración por defecto de los certificados (en años)
    $settings->add(new admin_setting_configselect('local_conocer_cert/certificateduration',
        get_string('certificateduration', 'local_conocer_cert'),
        get_string('certificatedurationdesc', 'local_conocer_cert'),
        3, [1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5, 10 => 10]));
    
    // Configuración de notificaciones
    $settings->add(new admin_setting_heading('local_conocer_cert/notificationsettings',
        get_string('notificationsettings', 'local_conocer_cert'),
        ''));
    
    // Habilitar notificaciones por email
    $settings->add(new admin_setting_configcheckbox('local_conocer_cert/enableemailnotifications',
        get_string('enableemailnotifications', 'local_conocer_cert'),
        get_string('enableemailnotificationsdesc', 'local_conocer_cert'),
        1));
    
    // Dirección de correo del remitente
    $settings->add(new admin_setting_configtext('local_conocer_cert/notificationsfromaddress',
        get_string('notificationsfromaddress', 'local_conocer_cert'),
        get_string('notificationsfromaddressdesc', 'local_conocer_cert'),
        '', PARAM_EMAIL));
    
    // Nombre del remitente
    $settings->add(new admin_setting_configtext('local_conocer_cert/notificationsfromname',
        get_string('notificationsfromname', 'local_conocer_cert'),
        get_string('notificationsfromnamedesc', 'local_conocer_cert'),
        get_string('pluginname', 'local_conocer_cert'), PARAM_TEXT));
    
    // Configuración de seguridad
    $settings->add(new admin_setting_heading('local_conocer_cert/securitysettings',
        get_string('securitysettings', 'local_conocer_cert'),
        ''));
    
    // Tamaño máximo de archivos (en MB)
    $maxuploadsizeoptions = [
        1 => '1MB',
        2 => '2MB',
        5 => '5MB',
        10 => '10MB',
        20 => '20MB',
        50 => '50MB'
    ];
    $settings->add(new admin_setting_configselect('local_conocer_cert/maxfilesize',
        get_string('maxfilesize', 'local_conocer_cert'),
        get_string('maxfilesizedesc', 'local_conocer_cert'),
        10, $maxuploadsizeoptions));
    
    // Tipos MIME permitidos
    $defaultmimetypes = 'application/pdf,image/jpeg,image/png';
    $settings->add(new admin_setting_configtextarea('local_conocer_cert/allowedmimetypes',
        get_string('allowedmimetypes', 'local_conocer_cert'),
        get_string('allowedmimetypesdesc', 'local_conocer_cert'),
        $defaultmimetypes, PARAM_TEXT));
    
    // Escaneo de virus (si está disponible)
    if (function_exists('clam_handle_infected_file')) {
        $settings->add(new admin_setting_configcheckbox('local_conocer_cert/scanforvirus',
            get_string('scanforvirus', 'local_conocer_cert'),
            get_string('scanforvirusdesc', 'local_conocer_cert'),
            1));
    }
    
    // Enlace a la página principal del plugin
    $settings->add(new admin_setting_heading('local_conocer_cert/pluginpages',
        get_string('pluginpages', 'local_conocer_cert'),
        html_writer::link(
            new moodle_url('/local/conocer_cert/pages/dashboard.php'),
            get_string('gotoplugin', 'local_conocer_cert'),
            ['class' => 'btn btn-primary', 'target' => '_blank']
        )));
}
