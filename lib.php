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
 * Library of functions for local_conocer_cert
 *
 * @package    local_conocer_cert
 * @copyright  2025 Sebastian Gonzalez Zepeda sgonzalez@infraestructuragis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Checks if a user is a candidate in the CONOCER certification system
 *
 * @param int $userid User ID to check (defaults to current user)
 * @return bool True if the user is a candidate
 */
function local_conocer_cert_is_candidate($userid = null) {
    global $USER, $DB;
    
    if ($userid === null) {
        $userid = $USER->id;
    }
    
    // Check if the user has any candidate records
    return $DB->record_exists('local_conocer_candidatos', ['userid' => $userid]);
}

/**
 * Checks if a user is an evaluator in the CONOCER certification system
 *
 * @param int $userid User ID to check (defaults to current user)
 * @return bool True if the user is an evaluator
 */
function local_conocer_cert_is_evaluator($userid = null) {
    global $USER, $DB;
    
    if ($userid === null) {
        $userid = $USER->id;
    }
    
    // Check if the user is an active evaluator
    return $DB->record_exists('local_conocer_evaluadores', ['userid' => $userid, 'estatus' => 'activo']);
}

/**
 * Checks if a user is a company contact in the CONOCER certification system
 *
 * @param int $userid User ID to check (defaults to current user)
 * @return bool True if the user is a company contact
 */
function local_conocer_cert_is_company_contact($userid = null) {
    global $USER, $DB;
    
    if ($userid === null) {
        $userid = $USER->id;
    }
    
    // Check if the user is a contact for any company
    return $DB->record_exists('local_conocer_empresas', ['contacto_userid' => $userid]);
}

/**
 * Get a user's candidate record in the CONOCER certification system
 *
 * @param int $userid User ID (defaults to current user)
 * @return object|false The candidate record or false if not found
 */
function local_conocer_cert_get_candidate($userid = null) {
    global $USER, $DB;
    
    if ($userid === null) {
        $userid = $USER->id;
    }
    
    // Get the most recent candidate record for this user
    return $DB->get_record('local_conocer_candidatos', ['userid' => $userid], '*', IGNORE_MULTIPLE);
}

/**
 * Get a user's evaluator record in the CONOCER certification system
 *
 * @param int $userid User ID (defaults to current user)
 * @return object|false The evaluator record or false if not found
 */
function local_conocer_cert_get_evaluator($userid = null) {
    global $USER, $DB;
    
    if ($userid === null) {
        $userid = $USER->id;
    }
    
    return $DB->get_record('local_conocer_evaluadores', ['userid' => $userid]);
}

/**
 * Get a user's company record in the CONOCER certification system
 *
 * @param int $userid User ID (defaults to current user)
 * @return object|false The company record where the user is the contact, or false if not found
 */
function local_conocer_cert_get_company($userid = null) {
    global $USER, $DB;
    
    if ($userid === null) {
        $userid = $USER->id;
    }
    
    return $DB->get_record('local_conocer_empresas', ['contacto_userid' => $userid]);
}

/**
 * Extend Moodle navigation
 *
 * @param global_navigation $navigation Navigation object
 */
function local_conocer_cert_extend_navigation(global_navigation $navigation) {
    global $CFG, $USER;
    
    // Add the main CONOCER navigation node
    if (isloggedin() && !isguestuser()) {
        $conocernode = $navigation->add(
            get_string('pluginname', 'local_conocer_cert'),
            new moodle_url('/local/conocer_cert/index.php'),
            navigation_node::TYPE_CUSTOM,
            null,
            'conocercert',
            new pix_icon('icon', get_string('pluginname', 'local_conocer_cert'), 'local_conocer_cert')
        );
        
        // Based on user role add specific navigation nodes
        if (local_conocer_cert_is_candidate()) {
            $conocernode->add(
                get_string('candidate_dashboard', 'local_conocer_cert'),
                new moodle_url('/local/conocer_cert/candidate/dashboard.php'),
                navigation_node::TYPE_CUSTOM
            );
            
            $conocernode->add(
                get_string('new_request', 'local_conocer_cert'),
                new moodle_url('/local/conocer_cert/candidate/new_request.php'),
                navigation_node::TYPE_CUSTOM
            );
            
            $conocernode->add(
                get_string('my_certifications', 'local_conocer_cert'),
                new moodle_url('/local/conocer_cert/candidate/my_certifications.php'),
                navigation_node::TYPE_CUSTOM
            );
        }
        
        if (local_conocer_cert_is_evaluator()) {
            $conocernode->add(
                get_string('evaluator_dashboard', 'local_conocer_cert'),
                new moodle_url('/local/conocer_cert/evaluator/dashboard.php'),
                navigation_node::TYPE_CUSTOM
            );
            
            $conocernode->add(
                get_string('pending_evaluations', 'local_conocer_cert'),
                new moodle_url('/local/conocer_cert/evaluator/pending.php'),
                navigation_node::TYPE_CUSTOM
            );
        }
        
        if (local_conocer_cert_is_company_contact()) {
            $conocernode->add(
                get_string('company_dashboard', 'local_conocer_cert'),
                new moodle_url('/local/conocer_cert/company/dashboard.php'),
                navigation_node::TYPE_CUSTOM
            );
        }
        
        // Add admin links if user has appropriate capabilities
        if (has_capability('local/conocer_cert:managecandidates', context_system::instance())) {
            $adminnode = $conocernode->add(
                get_string('administration', 'local_conocer_cert'),
                new moodle_url('/local/conocer_cert/pages/index.php'),
                navigation_node::TYPE_CUSTOM
            );
            
            $adminnode->add(
                get_string('candidates', 'local_conocer_cert'),
                new moodle_url('/local/conocer_cert/pages/candidates.php'),
                navigation_node::TYPE_CUSTOM
            );
            
            $adminnode->add(
                get_string('companies', 'local_conocer_cert'),
                new moodle_url('/local/conocer_cert/pages/companies.php'),
                navigation_node::TYPE_CUSTOM
            );
            
            $adminnode->add(
                get_string('evaluators', 'local_conocer_cert'),
                new moodle_url('/local/conocer_cert/pages/evaluators.php'),
                navigation_node::TYPE_CUSTOM
            );
            
            $adminnode->add(
                get_string('competencies', 'local_conocer_cert'),
                new moodle_url('/local/conocer_cert/pages/competencies.php'),
                navigation_node::TYPE_CUSTOM
            );
            
            $adminnode->add(
                get_string('reports', 'local_conocer_cert'),
                new moodle_url('/local/conocer_cert/pages/reports.php'),
                navigation_node::TYPE_CUSTOM
            );
        }
    }
}

/**
 * Add items to the user menu.
 *
 * @param navigation_node $navigation The navigation node to extend
 * @param stdClass $user The user object
 * @param context_user $context The context of the user
 * @param stdClass $course The course to object for the tool
 * @param context_course $coursecontext The context of the course
 */
function local_conocer_cert_extend_navigation_user($navigation, $user, $context, $course, $coursecontext) {
    global $USER;
    
    if ($user->id == $USER->id) {
        // Only add links for the current user
        
        // Add candidate dashboard if user is a candidate
        if (local_conocer_cert_is_candidate()) {
            $url = new moodle_url('/local/conocer_cert/candidate/dashboard.php');
            $navigation->add(
                get_string('candidate_dashboard', 'local_conocer_cert'),
                $url,
                navigation_node::TYPE_SETTING
            );
        }
        
        // Add evaluator dashboard if user is an evaluator
        if (local_conocer_cert_is_evaluator()) {
            $url = new moodle_url('/local/conocer_cert/evaluator/dashboard.php');
            $navigation->add(
                get_string('evaluator_dashboard', 'local_conocer_cert'),
                $url,
                navigation_node::TYPE_SETTING
            );
        }
        
        // Add company dashboard if user is a company contact
        if (local_conocer_cert_is_company_contact()) {
            $url = new moodle_url('/local/conocer_cert/company/dashboard.php');
            $navigation->add(
                get_string('company_dashboard', 'local_conocer_cert'),
                $url,
                navigation_node::TYPE_SETTING
            );
        }
    }
}

/**
 * Returns the URL for the candidate dashboard
 * 
 * @return moodle_url URL for the candidate dashboard
 */
function local_conocer_cert_get_candidate_dashboard_url() {
    return new moodle_url('/local/conocer_cert/candidate/dashboard.php');
}

/**
 * Returns the URL for the evaluator dashboard
 * 
 * @return moodle_url URL for the evaluator dashboard
 */
function local_conocer_cert_get_evaluator_dashboard_url() {
    return new moodle_url('/local/conocer_cert/evaluator/dashboard.php');
}

/**
 * Returns the URL for the company dashboard
 * 
 * @return moodle_url URL for the company dashboard
 */
function local_conocer_cert_get_company_dashboard_url() {
    return new moodle_url('/local/conocer_cert/company/dashboard.php');
}

/**
 * Returns the URL for the admin dashboard
 * 
 * @return moodle_url URL for the admin dashboard
 */
function local_conocer_cert_get_admin_dashboard_url() {
    return new moodle_url('/local/conocer_cert/pages/index.php');
}