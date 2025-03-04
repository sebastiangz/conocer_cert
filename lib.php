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
 * @package   local_conocer_cert
 * @copyright 2025 Sebastian Gonzalez Zepeda sgonzalez@infraestructuragis.com
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Adds navigation nodes to the admin tree.
 *
 * @param navigation_node $navigation The navigation node to extend
 * @param context $context The context of the course
 * @return void
 */
function local_conocer_cert_extend_navigation(global_navigation $navigation) {
    global $USER, $DB, $CFG;
    
    // Get the current context and check capabilities
    $context = context_system::instance();
    
    // Get plugin configuration for URLs
    $baseurl = new moodle_url('/local/conocer_cert');
    
    // Add main node for the plugin
    $main = $navigation->add(
        get_string('pluginname', 'local_conocer_cert'),
        $baseurl,
        navigation_node::TYPE_CONTAINER
    );
    
    // Check if user is a candidate (has at least one certification request)
    $iscandidato = $DB->record_exists('local_conocer_candidatos', ['userid' => $USER->id]);
    
    // Check if user is an evaluator
    $isevaluator = $DB->record_exists('local_conocer_evaluadores', ['userid' => $USER->id, 'estatus' => 'activo']);
    
    // Check if user is a company contact
    $iscompany = $DB->record_exists('local_conocer_empresas', ['contacto_userid' => $USER->id]);
    
    // Check admin capabilities
    $isadmin = has_capability('local/conocer_cert:managecandidates', $context);
    
    // Add candidate nodes
    if ($iscandidato || has_capability('local/conocer_cert:requestcertification', $context)) {
        $candidateurl = new moodle_url('/local/conocer_cert/pages/dashboard.php', ['type' => 'candidate']);
        $candidate = $main->add(
            get_string('candidate_dashboard', 'local_conocer_cert'),
            $candidateurl
        );
        
        // Add child nodes for candidate
        $candidate->add(
            get_string('request_certification', 'local_conocer_cert'),
            new moodle_url('/local/conocer_cert/pages/mycertifications.php', ['action' => 'new'])
        );
        
        $candidate->add(
            get_string('mycertifications', 'local_conocer_cert'),
            new moodle_url('/local/conocer_cert/pages/mycertifications.php')
        );
        
        // Only show document management if already a candidate
        if ($iscandidato) {
            $candidate->add(
                get_string('my_documents', 'local_conocer_cert'),
                new moodle_url('/local/conocer_cert/pages/mycertifications.php', ['action' => 'documents'])
            );
        }
    }
    
    // Add evaluator nodes
    if ($isevaluator) {
        $evaluatorurl = new moodle_url('/local/conocer_cert/pages/dashboard.php', ['type' => 'evaluator']);
        $evaluator = $main->add(
            get_string('evaluator_dashboard', 'local_conocer_cert'),
            $evaluatorurl
        );
        
        $evaluator->add(
            get_string('assigned_candidates', 'local_conocer_cert'),
            new moodle_url('/local/conocer_cert/pages/evaluators.php', ['action' => 'assigned'])
        );
        
        $evaluator->add(
            get_string('completed_evaluations', 'local_conocer_cert'),
            new moodle_url('/local/conocer_cert/pages/evaluators.php', ['action' => 'completed'])
        );
        
        $evaluator->add(
            get_string('evaluator_profile', 'local_conocer_cert'),
            new moodle_url('/local/conocer_cert/pages/evaluators.php', ['action' => 'profile'])
        );
    }
    
    // Add company nodes
    if ($iscompany) {
        $companyurl = new moodle_url('/local/conocer_cert/pages/dashboard.php', ['type' => 'company']);
        $company = $main->add(
            get_string('company_dashboard', 'local_conocer_cert'),
            $companyurl
        );
        
        $company->add(
            get_string('company_profile', 'local_conocer_cert'),
            new moodle_url('/local/conocer_cert/pages/companies.php', ['action' => 'profile'])
        );
        
        $company->add(
            get_string('company_competencies', 'local_conocer_cert'),
            new moodle_url('/local/conocer_cert/pages/companies.php', ['action' => 'competencies'])
        );
        
        $company->add(
            get_string('company_candidates', 'local_conocer_cert'),
            new moodle_url('/local/conocer_cert/pages/companies.php', ['action' => 'candidates'])
        );
    }
    
    // Add admin nodes
    if ($isadmin) {
        $adminurl = new moodle_url('/local/conocer_cert/pages/dashboard.php', ['type' => 'admin']);
        $admin = $main->add(
            get_string('admin_dashboard', 'local_conocer_cert'),
            $adminurl
        );
        
        $admin->add(
            get_string('admin_candidates', 'local_conocer_cert'),
            new moodle_url('/local/conocer_cert/pages/candidates.php')
        );
        
        $admin->add(
            get_string('admin_companies', 'local_conocer_cert'),
            new moodle_url('/local/conocer_cert/pages/companies.php')
        );
        
        $admin->add(
            get_string('admin_evaluators', 'local_conocer_cert'),
            new moodle_url('/local/conocer_cert/pages/evaluators.php')
        );
        
        $admin->add(
            get_string('admin_competencies', 'local_conocer_cert'),
            new moodle_url('/local/conocer_cert/pages/competencies.php')
        );
        
        $admin->add(
            get_string('admin_reports', 'local_conocer_cert'),
            new moodle_url('/local/conocer_cert/pages/reports.php')
        );
    }
}

/**
 * Adds options to the user profile menu
 *
 * @param \core_user\output\myprofile\tree $tree The navigation tree to extend
 * @param stdClass $user The user object
 * @param boolean $iscurrentuser Is the user viewing their own profile
 * @param stdClass $course The current course
 * @return void
 */
function local_conocer_cert_myprofile_navigation(core_user\output\myprofile\tree $tree, $user, $iscurrentuser, $course) {
    global $DB, $USER;
    
    if (!$iscurrentuser && !has_capability('local/conocer_cert:managecandidates', context_system::instance())) {
        return;
    }
    
    // Check if user is a candidate
    $iscandidato = $DB->record_exists('local_conocer_candidatos', ['userid' => $user->id]);
    
    // Check if user is an evaluator
    $isevaluator = $DB->record_exists('local_conocer_evaluadores', ['userid' => $user->id, 'estatus' => 'activo']);
    
    // Check if user is a company contact
    $iscompany = $DB->record_exists('local_conocer_empresas', ['contacto_userid' => $user->id]);
    
    // Create node category
    $category = new core_user\output\myprofile\category('conocer_cert', 
        get_string('pluginname', 'local_conocer_cert'), 'contact');
    $tree->add_category($category);
    
    // Add node for certifications
    if ($iscandidato) {
        $url = new moodle_url('/local/conocer_cert/pages/mycertifications.php', ['userid' => $user->id]);
        $node = new core_user\output\myprofile\node('conocer_cert', 'certifications',
            get_string('mycertifications', 'local_conocer_cert'), null, $url);
        $tree->add_node($node);
    }
    
    // Add node for evaluator profile
    if ($isevaluator) {
        $url = new moodle_url('/local/conocer_cert/pages/evaluators.php', 
            ['action' => 'profile', 'userid' => $user->id]);
        $node = new core_user\output\myprofile\node('conocer_cert', 'evaluator', 
            get_string('evaluator_profile', 'local_conocer_cert'), null, $url);
        $tree->add_node($node);
    }
    
    // Add node for company profile
    if ($iscompany) {
        $url = new moodle_url('/local/conocer_cert/pages/companies.php', 
            ['action' => 'profile', 'userid' => $user->id]);
        $node = new core_user\output\myprofile\node('conocer_cert', 'company', 
            get_string('company_profile', 'local_conocer_cert'), null, $url);
        $tree->add_node($node);
    }
}

/**
 * Serves the plugin files.
 *
 * @param stdClass $course The course object
 * @param stdClass $cm The course module object
 * @param context $context The context
 * @param string $filearea The name of the file area
 * @param array $args Extra arguments
 * @param bool $forcedownload Whether the file should be downloaded
 * @param array $options Additional options affecting the file serving
 * @return bool False if the file not found, just send the file otherwise
 */
function local_conocer_cert_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    global $USER, $DB, $CFG;
    
    // Security check: verify context
    if ($context->contextlevel != CONTEXT_SYSTEM) {
        return false;
    }
    
    // Check login and capability
    require_login();
    
    // Security: verify file area is a valid one
    $validfileareas = ['candidate_documents', 'company_documents', 'certificates', 'competency_assets'];
    if (!in_array($filearea, $validfileareas)) {
        return false;
    }
    
    // Extract itemid and filename
    $itemid = array_shift($args);
    $filename = array_shift($args);
    
    if (!$filename) {
        return false;
    }
    
    // Security check: verify the user has appropriate permissions
    if ($filearea === 'candidate_documents') {
        // Check if document belongs to the current user
        $document = $DB->get_record('local_conocer_documentos', ['id' => $itemid]);
        if (!$document) {
            return false;
        }
        
        $candidate = $DB->get_record('local_conocer_candidatos', ['id' => $document->candidato_id]);
        if (!$candidate) {
            return false;
        }
        
        // Allow access if:
        // 1. User is the owner of the document
        // 2. User is an admin
        // 3. User is the assigned evaluator
        $isowner = ($candidate->userid == $USER->id);
        $isadmin = has_capability('local/conocer_cert:managecandidates', $context);
        
        $isevaluator = false;
        if (!$isowner && !$isadmin) {
            // Check if user is assigned as evaluator
            $sql = "SELECT p.* 
                    FROM {local_conocer_procesos} p 
                    WHERE p.candidato_id = :candidatoid 
                    AND p.evaluador_id = :evaluatorid";
            $params = ['candidatoid' => $document->candidato_id, 'evaluatorid' => $USER->id];
            $isevaluator = $DB->record_exists_sql($sql, $params);
        }
        
        if (!$isowner && !$isadmin && !$isevaluator) {
            return false;
        }
    } else if ($filearea === 'company_documents') {
        $document = $DB->get_record('local_conocer_documentos_empresa', ['id' => $itemid]);
        if (!$document) {
            return false;
        }
        
        $company = $DB->get_record('local_conocer_empresas', ['id' => $document->empresa_id]);
        if (!$company) {
            return false;
        }
        
        $isowner = ($company->contacto_userid == $USER->id);
        $isadmin = has_capability('local/conocer_cert:managecompanies', $context);
        
        if (!$isowner && !$isadmin) {
            return false;
        }
    } else if ($filearea === 'certificates') {
        $certificate = $DB->get_record('local_conocer_certificados', ['id' => $itemid]);
        if (!$certificate) {
            return false;
        }
        
        $process = $DB->get_record('local_conocer_procesos', ['id' => $certificate->proceso_id]);
        if (!$process) {
            return false;
        }
        
        $candidate = $DB->get_record('local_conocer_candidatos', ['id' => $process->candidato_id]);
        if (!$candidate) {
            return false;
        }
        
        $isowner = ($candidate->userid == $USER->id);
        $isadmin = has_capability('local/conocer_cert:managecandidates', $context);
        
        // Also check for a valid token for public access
        $token = optional_param('token', '', PARAM_RAW);
        $validtoken = false;
        
        if (!empty($token)) {
            $validtoken = \local_conocer_cert\util\security::verify_document_token($token);
            $validtoken = $validtoken && ($validtoken['documentid'] == $itemid);
        }
        
        if (!$isowner && !$isadmin && !$validtoken) {
            return false;
        }
    } else if ($filearea === 'competency_assets') {
        // Competency assets are accessible to anyone who can view competencies
        if (!has_capability('local/conocer_cert:viewcompetencies', $context)) {
            return false;
        }
    }
    
    // Get file storage and retrieve file
    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'local_conocer_cert', $filearea, $itemid, '/', $filename);
    
    if (!$file) {
        return false;
    }
    
    // Send the file
    send_stored_file($file, 86400, 0, $forcedownload, $options);
}
