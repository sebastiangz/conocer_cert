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
 * Index page for CONOCER certification plugin.
 *
 * @package    local_conocer_cert
 * @copyright  2025 Sebastian Gonzalez Zepeda sgonzalez@infraestructuragis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/local/conocer_cert/lib.php');
require_once($CFG->dirroot . '/local/conocer_cert/locallib.php');

// Check login and permissions
require_login();
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/conocer_cert/index.php'));
$PAGE->set_title(get_string('pluginname', 'local_conocer_cert'));
$PAGE->set_heading(get_string('pluginname', 'local_conocer_cert'));
$PAGE->set_pagelayout('standard');

// Check user type and redirect to appropriate dashboard
if (has_capability('local/conocer_cert:managecandidates', $context)) {
    // Admin dashboard
    redirect(local_conocer_cert_get_admin_dashboard_url());
} else if (local_conocer_cert_is_evaluator()) {
    // Evaluator dashboard
    redirect(local_conocer_cert_get_evaluator_dashboard_url());
} else if (local_conocer_cert_is_company_contact()) {
    // Company dashboard
    redirect(local_conocer_cert_get_company_dashboard_url());
} else if (local_conocer_cert_is_candidate()) {
    // Candidate dashboard
    redirect(local_conocer_cert_get_candidate_dashboard_url());
} else {
    // Default page for users without a specific role

    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('pluginname', 'local_conocer_cert'));

    // Display welcome message and options
    echo '<div class="welcome-message">';
    echo '<p>' . get_string('welcome_message', 'local_conocer_cert') . '</p>';
    echo '</div>';

    // Show role selection cards
    echo '<div class="role-selection-container">';
    
    // Candidate card
    echo '<div class="card">';
    echo '<div class="card-body">';
    echo '<h3 class="card-title">' . get_string('candidate', 'local_conocer_cert') . '</h3>';
    echo '<p class="card-text">' . get_string('candidate_description', 'local_conocer_cert') . '</p>';
    echo '<a href="' . new moodle_url('/local/conocer_cert/candidate/new_request.php') . '" class="btn btn-primary">';
    echo get_string('request_certification', 'local_conocer_cert') . '</a>';
    echo '</div>';
    echo '</div>';
    
    // Company card
    echo '<div class="card">';
    echo '<div class="card-body">';
    echo '<h3 class="card-title">' . get_string('company', 'local_conocer_cert') . '</h3>';
    echo '<p class="card-text">' . get_string('company_description', 'local_conocer_cert') . '</p>';
    echo '<a href="' . new moodle_url('/local/conocer_cert/company/register.php') . '" class="btn btn-primary">';
    echo get_string('register_company', 'local_conocer_cert') . '</a>';
    echo '</div>';
    echo '</div>';
    
    // Evaluator application card
    echo '<div class="card">';
    echo '<div class="card-body">';
    echo '<h3 class="card-title">' . get_string('evaluator', 'local_conocer_cert') . '</h3>';
    echo '<p class="card-text">' . get_string('evaluator_description', 'local_conocer_cert') . '</p>';
    echo '<a href="' . new moodle_url('/local/conocer_cert/evaluator/apply.php') . '" class="btn btn-primary">';
    echo get_string('apply_as_evaluator', 'local_conocer_cert') . '</a>';
    echo '</div>';
    echo '</div>';
    
    echo '</div>'; // End role-selection-container

    echo $OUTPUT->footer();
}