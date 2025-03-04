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
 * Plugin administration pages for CONOCER certification plugin
 *
 * @package   local_conocer_cert
 * @copyright 2025 Sebastian Gonzalez Zepeda sgonzalez@infraestructuragis.com
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    // Create settings page with categories
    $settings = new admin_settingpage('local_conocer_cert', get_string('pluginname', 'local_conocer_cert'));
    $ADMIN->add('localplugins', $settings);
    
    // General settings
    $settings->add(new admin_setting_heading(
        'local_conocer_cert/general_settings',
        get_string('general_settings', 'local_conocer_cert'),
        ''
    ));
    
    // Institution name
    $settings->add(new admin_setting_configtext(
        'local_conocer_cert/institution_name',
        get_string('institution_name', 'local_conocer_cert'),
        get_string('institution_name_desc', 'local_conocer_cert'),
        get_string('pluginname', 'local_conocer_cert'),
        PARAM_TEXT
    ));
    
    // Institution logo
    $settings->add(new admin_setting_configstoredfile(
        'local_conocer_cert/institution_logo',
        get_string('institution_logo', 'local_conocer_cert'),
        get_string('institution_logo_desc', 'local_conocer_cert'),
        'institution_logo',
        0,
        ['maxfiles' => 1, 'accepted_types' => ['image']]
    ));
    
    // Certification expiry settings
    $settings->add(new admin_setting_heading(
        'local_conocer_cert/expiry_settings',
        get_string('expiry_settings', 'local_conocer_cert'),
        ''
    ));
    
    // Default certificate expiry period (in days)
    $settings->add(new admin_setting_configtext(
        'local_conocer_cert/certificate_expiry_days',
        get_string('certificate_expiry_days', 'local_conocer_cert'),
        get_string('certificate_expiry_days_desc', 'local_conocer_cert'),
        '1825', // Default: 5 years (1825 days)
        PARAM_INT
    ));
    
    // Enable certificate expiry
    $settings->add(new admin_setting_configcheckbox(
        'local_conocer_cert/enable_certificate_expiry',
        get_string('enable_certificate_expiry', 'local_conocer_cert'),
        get_string('enable_certificate_expiry_desc', 'local_conocer_cert'),
        1
    ));
    
    // Document settings
    $settings->add(new admin_setting_heading(
        'local_conocer_cert/document_settings',
        get_string('document_settings', 'local_conocer_cert'),
        ''
    ));
    
    // Maximum file size for documents (in bytes)
    $settings->add(new admin_setting_configtext(
        'local_conocer_cert/max_file_size',
        get_string('max_file_size', 'local_conocer_cert'),
        get_string('max_file_size_desc', 'local_conocer_cert'),
        '10485760', // Default: 10MB
        PARAM_INT
    ));
    
    // Maximum file size for photos (in bytes)
    $settings->add(new admin_setting_configtext(
        'local_conocer_cert/max_photo_size',
        get_string('max_photo_size', 'local_conocer_cert'),
        get_string('max_photo_size_desc', 'local_conocer_cert'),
        '2097152', // Default: 2MB
        PARAM_INT
    ));
    
    // Allowed document types
    $doctypes = [
        'application/pdf' => 'PDF',
        'image/jpeg' => 'JPEG',
        'image/png' => 'PNG'
    ];
    
    $settings->add(new admin_setting_configmultiselect(
        'local_conocer_cert/allowed_document_types',
        get_string('allowed_document_types', 'local_conocer_cert'),
        get_string('allowed_document_types_desc', 'local_conocer_cert'),
        array_keys($doctypes), // Default: all
        $doctypes
    ));
    
    // Notification settings
    $settings->add(new admin_setting_heading(
        'local_conocer_cert/notification_settings',
        get_string('notification_settings', 'local_conocer_cert'),
        ''
    ));
    
    // Email notifications
    $settings->add(new admin_setting_configcheckbox(
        'local_conocer_cert/enable_email_notifications',
        get_string('enable_email_notifications', 'local_conocer_cert'),
        get_string('enable_email_notifications_desc', 'local_conocer_cert'),
        1
    ));
    
    // Document reminder days
    $settings->add(new admin_setting_configtext(
        'local_conocer_cert/document_reminder_days',
        get_string('document_reminder_days', 'local_conocer_cert'),
        get_string('document_reminder_days_desc', 'local_conocer_cert'),
        '7', // Default: 7 days
        PARAM_INT
    ));
    
    // Evaluation reminder days
    $settings->add(new admin_setting_configtext(
        'local_conocer_cert/evaluation_reminder_days',
        get_string('evaluation_reminder_days', 'local_conocer_cert'),
        get_string('evaluation_reminder_days_desc', 'local_conocer_cert'),
        '3', // Default: 3 days
        PARAM_INT
    ));
    
    // Certificate expiry notification days
    $settings->add(new admin_setting_configtext(
        'local_conocer_cert/certificate_expiry_notification_days',
        get_string('certificate_expiry_notification_days', 'local_conocer_cert'),
        get_string('certificate_expiry_notification_days_desc', 'local_conocer_cert'),
        '30', // Default: 30 days
        PARAM_INT
    ));
    
    // Add link to view competency list
    $ADMIN->add('localplugins', new admin_externalpage(
        'local_conocer_cert_competencies',
        get_string('admin_competencies', 'local_conocer_cert'),
        new moodle_url('/local/conocer_cert/pages/competencies.php')
    ));
    
    // Add link to view evaluator list
    $ADMIN->add('localplugins', new admin_externalpage(
        'local_conocer_cert_evaluators',
        get_string('admin_evaluators', 'local_conocer_cert'),
        new moodle_url('/local/conocer_cert/pages/evaluators.php')
    ));
    
    // Add link to view reports
    $ADMIN->add('localplugins', new admin_externalpage(
        'local_conocer_cert_reports',
        get_string('admin_reports', 'local_conocer_cert'),
        new moodle_url('/local/conocer_cert/pages/reports.php')
    ));
}
