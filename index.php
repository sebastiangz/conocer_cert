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
 * Main entry point for the CONOCER certification plugin
 * 
 * This page redirects users to their appropriate dashboard based on their role in the system.
 *
 * @package   local_conocer_cert
 * @copyright 2025 Sebastian Gonzalez Zepeda sgonzalez@infraestructuragis.com
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/local/conocer_cert/locallib.php');

// Page setup
$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/conocer_cert/index.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('pluginname', 'local_conocer_cert'));
$PAGE->set_heading(get_string('pluginname', 'local_conocer_cert'));

// Require login
require_login();

// Determine which dashboard to redirect to based on user's role
$context = context_system::instance();
$isadmin = has_capability('local/conocer_cert:managecandidates', $context);
$useriscandidate = $DB->record_exists('local_conocer_candidatos', ['userid' => $USER->id]);
$userisevaluator = $DB->record_exists('local_conocer_evaluadores', ['userid' => $USER->id, 'estatus' => 'activo']);
$useriscompany = $DB->record_exists('local_conocer_empresas', ['contacto_userid' => $USER->id]);

// Priority: Admin > Evaluator > Company > Candidate
if ($isadmin) {
    redirect(new moodle_url('/local/conocer_cert/pages/dashboard.php', ['type' => 'admin']));
} else if ($userisevaluator) {
    redirect(new moodle_url('/local/conocer_cert/pages/dashboard.php', ['type' => 'evaluator']));
} else if ($useriscompany) {
    redirect(new moodle_url('/local/conocer_cert/pages/dashboard.php', ['type' => 'company']));
} else if ($useriscandidate) {
    redirect(new moodle_url('/local/conocer_cert/pages/dashboard.php', ['type' => 'candidate']));
} else {
    // User has no specific role yet, show welcome page with options
    echo $OUTPUT->header();
    
    // Display welcome message
    echo $OUTPUT->heading(get_string('welcome_to_conocer', 'local_conocer_cert'));
    echo html_writer::div(get_string('welcome_description', 'local_conocer_cert'), 'mb-4');
    
    $options = [];
    
    // Show options based on capabilities
    if (has_capability('local/conocer_cert:requestcertification', $context)) {
        $options[] = [
            'title' => get_string('become_candidate', 'local_conocer_cert'),
            'description' => get_string('become_candidate_desc', 'local_conocer_cert'),
            'url' => new moodle_url('/local/conocer_cert/pages/mycertifications.php', ['action' => 'new']),
            'icon' => 'certificate'
        ];
    }
    
    if (has_capability('local/conocer_cert:registercompany', $context)) {
        $options[] = [
            'title' => get_string('register_company', 'local_conocer_cert'),
            'description' => get_string('register_company_desc', 'local_conocer_cert'),
            'url' => new moodle_url('/local/conocer_cert/pages/companies.php', ['action' => 'register']),
            'icon' => 'building'
        ];
    }
    
    if (has_capability('local/conocer_cert:viewcompetencies', $context)) {
        $options[] = [
            'title' => get_string('browse_competencies', 'local_conocer_cert'),
            'description' => get_string('browse_competencies_desc', 'local_conocer_cert'),
            'url' => new moodle_url('/local/conocer_cert/pages/competencies.php', ['action' => 'browse']),
            'icon' => 'list'
        ];
    }
    
    // Display options
    $optionshtml = html_writer::start_div('container');
    $optionshtml .= html_writer::start_div('row');
    
    foreach ($options as $option) {
        $optionshtml .= html_writer::start_div('col-md-4 mb-4');
        $optionshtml .= html_writer::start_div('card h-100');
        $optionshtml .= html_writer::start_div('card-body');
        
        // Card header with icon and title
        $optionshtml .= html_writer::start_div('card-title h5');
        $optionshtml .= html_writer::tag('i', '', ['class' => 'fa fa-' . $option['icon'] . ' mr-2']);
        $optionshtml .= $option['title'];
        $optionshtml .= html_writer::end_div();
        
        // Card content
        $optionshtml .= html_writer::div($option['description'], 'card-text mb-4');
        
        // Card action button
        $optionshtml .= html_writer::start_div('text-center');
        $optionshtml .= html_writer::link(
            $option['url'],
            get_string('get_started', 'local_conocer_cert'),
            ['class' => 'btn btn-primary']
        );
        $optionshtml .= html_writer::end_div();
        
        $optionshtml .= html_writer::end_div(); // card-body
        $optionshtml .= html_writer::end_div(); // card
        $optionshtml .= html_writer::end_div(); // col
    }
    
    $optionshtml .= html_writer::end_div(); // row
    $optionshtml .= html_writer::end_div(); // container
    
    echo $optionshtml;
    
    // If no options are available, show information message
    if (empty($options)) {
        echo $OUTPUT->notification(
            get_string('no_options_available', 'local_conocer_cert'),
            'info'
        );
    }
    
    echo $OUTPUT->footer();
}
