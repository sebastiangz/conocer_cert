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
 * Internal library of functions for local_conocer_cert
 *
 * All functions specified in this file are to be considered internal and should not
 * be called directly from outside the plugin.
 *
 * @package   local_conocer_cert
 * @copyright 2025 Sebastian Gonzalez Zepeda sgonzalez@infraestructuragis.com
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/conocer_cert/lib.php');

/**
 * Get candidate dashboard data for the specified user
 *
 * @param int $userid User ID
 * @return \local_conocer_cert\dashboard\candidate_dashboard Dashboard object
 */
function local_conocer_cert_get_candidate_dashboard($userid) {
    // Create and return candidate dashboard
    $dashboard = new \local_conocer_cert\dashboard\candidate_dashboard(
        \core_user::get_user($userid)
    );
    
    return $dashboard;
}

/**
 * Get evaluator dashboard data for the specified user
 *
 * @param int $userid User ID
 * @return \local_conocer_cert\dashboard\evaluator_dashboard Dashboard object
 */
function local_conocer_cert_get_evaluator_dashboard($userid) {
    // Create and return evaluator dashboard
    $dashboard = new \local_conocer_cert\dashboard\evaluator_dashboard(
        \core_user::get_user($userid)
    );
    
    return $dashboard;
}

/**
 * Get company dashboard data for the specified user
 *
 * @param int $userid User ID
 * @return \local_conocer_cert\dashboard\company_dashboard Dashboard object
 */
function local_conocer_cert_get_company_dashboard($userid) {
    // Create and return company dashboard
    $dashboard = new \local_conocer_cert\dashboard\company_dashboard(
        \core_user::get_user($userid)
    );
    
    return $dashboard;
}

/**
 * Get admin dashboard data for the specified user
 *
 * @param int $userid User ID
 * @return \local_conocer_cert\dashboard\admin_dashboard Dashboard object
 */
function local_conocer_cert_get_admin_dashboard($userid) {
    // Create and return admin dashboard
    $dashboard = new \local_conocer_cert\dashboard\admin_dashboard(
        \core_user::get_user($userid)
    );
    
    return $dashboard;
}

/**
 * Get candidate data for the specified ID
 *
 * @param int $candidateid Candidate ID
 * @return object|false Candidate data or false if not found
 */
function local_conocer_cert_get_candidate($candidateid) {
    global $DB;
    
    // Get candidate record
    $candidate = $DB->get_record('local_conocer_candidatos', ['id' => $candidateid]);
    
    if (!$candidate) {
        return false;
    }
    
    // Get additional data
    $user = $DB->get_record('user', ['id' => $candidate->userid]);
    $competencia = $DB->get_record('local_conocer_competencias', ['id' => $candidate->competencia_id]);
    
    // Add user data
    $candidate->user_fullname = fullname($user);
    $candidate->user_email = $user->email;
    
    // Add competency data
    $candidate->competencia_nombre = $competencia ? $competencia->nombre : '';
    $candidate->competencia_codigo = $competencia ? $competencia->codigo : '';
    
    return $candidate;
}

/**
 * Get candidate documents for the specified candidate ID
 *
 * @param int $candidateid Candidate ID
 * @return array Documents array
 */
function local_conocer_cert_get_candidate_documents($candidateid) {
    global $DB;
    
    // Get document records
    $documents = $DB->get_records('local_conocer_documentos', ['candidato_id' => $candidateid]);
    
    return $documents;
}

/**
 * Get certification process for a candidate
 *
 * @param int $candidateid Candidate ID
 * @return object|false Process data or false if not found
 */
function local_conocer_cert_get_candidate_process($candidateid) {
    global $DB;
    
    // Get process record
    $process = $DB->get_record('local_conocer_procesos', ['candidato_id' => $candidateid]);
    
    if (!$process) {
        return false;
    }
    
    // Get evaluator data if assigned
    if (!empty($process->evaluador_id)) {
        $evaluator = $DB->get_record('user', ['id' => $process->evaluador_id]);
        $process->evaluador_nombre = fullname($evaluator);
    }
    
    return $process;
}

/**
 * Get certificate for a process
 *
 * @param int $processid Process ID
 * @return object|false Certificate data or false if not found
 */
function local_conocer_cert_get_certificate($processid) {
    global $DB;
    
    // Get certificate record
    $certificate = $DB->get_record('local_conocer_certificados', ['proceso_id' => $processid]);
    
    return $certificate;
}

/**
 * Generate a unique certificate folio number
 *
 * @param int $userid User ID
 * @param int $competenciaid Competency ID
 * @return string Unique folio number
 */
function local_conocer_cert_generate_folio() {
    global $DB;
    
    // Get the next certificate ID
    $nextid = $DB->count_records('local_conocer_certificados') + 1;
    
    // Generate a folio in format CERT-YYYY-NNNNN
    $folio = 'CERT-' . date('Y') . '-' . str_pad($nextid, 5, '0', STR_PAD_LEFT);
    
    // Check if folio already exists (very unlikely but just in case)
    if ($DB->record_exists('local_conocer_certificados', ['numero_folio' => $folio])) {
        // Add a random suffix
        $folio .= '-' . substr(uniqid(), -4);
    }
    
    return $folio;
}

/**
 * Generate a verification hash for a certificate
 *
 * @param int $certificateid Certificate ID
 * @param string $folio Certificate folio number
 * @return string Verification hash
 */
function local_conocer_cert_generate_verification_hash($certificateid, $folio) {
    // Generate a verification hash using certificate ID and folio
    $data = $certificateid . '-' . $folio . '-' . time();
    $hash = substr(md5($data), 0, 12);
    
    return $hash;
}

/**
 * Create a new certificate for a completed process
 *
 * @param int $processid Process ID
 * @param int $userid User ID of the issuer
 * @param int $expiry_period Expiry period in days (0 for no expiry)
 * @return int|false Certificate ID or false on failure
 */
function local_conocer_cert_create_certificate($processid, $userid, $expiry_period = 0) {
    global $DB;
    
    // Check if process exists and is completed successfully
    $process = $DB->get_record('local_conocer_procesos', [
        'id' => $processid,
        'resultado' => 'aprobado'
    ]);
    
    if (!$process) {
        return false;
    }
    
    // Check if certificate already exists
    if ($DB->record_exists('local_conocer_certificados', ['proceso_id' => $processid])) {
        return false;
    }
    
    // Generate folio number
    $folio = local_conocer_cert_generate_folio();
    
    // Prepare certificate data
    $certificate = new \stdClass();
    $certificate->proceso_id = $processid;
    $certificate->numero_folio = $folio;
    $certificate->fecha_emision = time();
    $certificate->estatus = 'activo';
    $certificate->emitido_por = $userid;
    
    // Set expiration date if applicable
    if ($expiry_period > 0) {
        $certificate->fecha_vencimiento = time() + ($expiry_period * 24 * 60 * 60);
    }
    
    // Insert certificate record
    $certificateid = $DB->insert_record('local_conocer_certificados', $certificate);
    
    if ($certificateid) {
        // Generate and store verification hash
        $hash = local_conocer_cert_generate_verification_hash($certificateid, $folio);
        $DB->set_field('local_conocer_certificados', 'hash_verificacion', $hash, ['id' => $certificateid]);
        
        // Trigger certificate created event
        $candidate = $DB->get_record('local_conocer_candidatos', ['id' => $process->candidato_id]);
        $context = \context_system::instance();
        
        $params = [
            'objectid' => $certificateid,
            'context' => $context,
            'relateduserid' => $candidate->userid
        ];
        
        $event = \local_conocer_cert\event\certificate_created::create($params);
        $event->trigger();
        
        // Send notification to candidate
        \local_conocer_cert\util\notification::send($candidate->userid, 'certificado_disponible', [
            'competencia' => $DB->get_field('local_conocer_competencias', 'nombre', ['id' => $candidate->competencia_id]),
            'nivel' => $candidate->nivel,
            'folio' => $folio
        ]);
    }
    
    return $certificateid;
}

/**
 * Verify a certificate by folio and hash
 *
 * @param string $folio Certificate folio number
 * @param string $hash Certificate verification hash
 * @return object|false Certificate verification result or false if invalid
 */
function local_conocer_cert_verify_certificate($folio, $hash = '') {
    global $DB;
    
    // Prepare conditions
    $conditions = ['numero_folio' => $folio];
    
    if (!empty($hash)) {
        $conditions['hash_verificacion'] = $hash;
    }
    
    // Get certificate
    $certificate = $DB->get_record('local_conocer_certificados', $conditions);
    
    if (!$certificate) {
        return false;
    }
    
    // Get process and candidate data
    $process = $DB->get_record('local_conocer_procesos', ['id' => $certificate->proceso_id]);
    
    if (!$process) {
        return false;
    }
    
    $candidate = $DB->get_record('local_conocer_candidatos', ['id' => $process->candidato_id]);
    
    if (!$candidate) {
        return false;
    }
    
    $user = $DB->get_record('user', ['id' => $candidate->userid]);
    $competencia = $DB->get_record('local_conocer_competencias', ['id' => $candidate->competencia_id]);
    
    // Prepare verification result
    $result = new \stdClass();
    $result->valid = ($certificate->estatus === 'activo');
    $result->folio = $certificate->numero_folio;
    $result->issue_date = $certificate->fecha_emision;
    $result->holder_name = fullname($user);
    $result->competency = $competencia ? $competencia->nombre : '';
    $result->competency_code = $competencia ? $competencia->codigo : '';
    $result->level = $candidate->nivel;
    
    // Check expiration
    if (!empty($certificate->fecha_vencimiento)) {
        $result->expiry_date = $certificate->fecha_vencimiento;
        $result->expired = (time() > $certificate->fecha_vencimiento);
        
        if ($result->expired) {
            $result->valid = false;
            $result->status_message = get_string('certificate_expired', 'local_conocer_cert');
        }
    }
    
    if ($certificate->estatus !== 'activo') {
        $result->status_message = get_string('certificate_inactive', 'local_conocer_cert');
    } else if ($result->valid) {
        $result->status_message = get_string('certificate_valid', 'local_conocer_cert');
    }
    
    return $result;
}

/**
 * Get available evaluators for a competency
 *
 * @param int $competenciaid Competency ID
 * @return array Array of available evaluators
 */
function local_conocer_cert_get_available_evaluators($competenciaid) {
    global $DB;
    
    // SQL to find evaluators with the specified competency
    $sql = "SELECT e.*, u.firstname, u.lastname, u.email
            FROM {local_conocer_evaluadores} e
            JOIN {user} u ON e.userid = u.id
            WHERE e.estatus = 'activo'
              AND (e.competencias LIKE ? OR e.competencias LIKE ? OR e.competencias LIKE ?)";
    
    $params = [
        '%"' . $competenciaid . '"%',
        '%[' . $competenciaid . ',%',
        '%,' . $competenciaid . ',%'
    ];
    
    $evaluators = $DB->get_records_sql($sql, $params);
    
    // Filter evaluators by workload (limit to those who haven't reached their maximum)
    foreach ($evaluators as $key => $evaluator) {
        // Count current assignments
        $current_assignments = $DB->count_records('local_conocer_procesos', [
            'evaluador_id' => $evaluator->userid,
            'etapa' => 'evaluacion'
        ]);
        
        // Check if maximum exceeded
        if (!empty($evaluator->max_candidatos) && $current_assignments >= $evaluator->max_candidatos) {
            unset($evaluators[$key]);
        }
    }
    
    return $evaluators;
}

/**
 * Assign an evaluator to a candidate
 *
 * @param int $candidateid Candidate ID
 * @param int $evaluatorid Evaluator ID
 * @param string $comments Assignment comments
 * @return bool Success status
 */
function local_conocer_cert_assign_evaluator($candidateid, $evaluatorid, $comments = '') {
    return \local_conocer_cert\evaluator\manager::assign_evaluator_to_candidate(
        $candidateid, $evaluatorid, $comments
    );
}

/**
 * Submit an evaluation for a candidate
 *
 * @param int $processid Process ID
 * @param object $data Evaluation data
 * @return bool Success status
 */
function local_conocer_cert_submit_evaluation($processid, $data) {
    return \local_conocer_cert\evaluator\manager::submit_evaluation($processid, $data);
}

/**
 * Register a new certification candidate
 *
 * @param object $data Candidate data
 * @return int|false Candidate ID or false on failure
 */
function local_conocer_cert_register_candidate($data) {
    global $DB, $USER;
    
    // Extract competency ID and validate it exists
    $competencia_id = $data->competencia_id;
    $competencia = $DB->get_record('local_conocer_competencias', [
        'id' => $competencia_id,
        'activo' => 1
    ]);
    
    if (!$competencia) {
        return false;
    }
    
    // Check if the same competency/level combination already exists for this user
    $exists = $DB->record_exists('local_conocer_candidatos', [
        'userid' => $USER->id,
        'competencia_id' => $competencia_id,
        'nivel' => $data->nivel
    ]);
    
    if ($exists) {
        return false;
    }
    
    // Prepare candidate data
    $candidate = new \stdClass();
    $candidate->userid = $USER->id;
    $candidate->competencia_id = $competencia_id;
    $candidate->nivel = $data->nivel;
    $candidate->estado = 'pendiente';
    $candidate->curp = isset($data->curp) ? $data->curp : '';
    $candidate->telefono = isset($data->telefono) ? $data->telefono : '';
    $candidate->direccion = isset($data->direccion) ? $data->direccion : '';
    $candidate->experiencia = isset($data->experiencia) ? $data->experiencia : '';
    $candidate->fecha_solicitud = time();
    $candidate->fecha_modificacion = time();
    
    // Insert candidate record
    $candidateid = $DB->insert_record('local_conocer_candidatos', $candidate);
    
    if ($candidateid) {
        // Create initial process record
        $process = new \stdClass();
        $process->candidato_id = $candidateid;
        $process->etapa = 'solicitud';
        $process->fecha_inicio = time();
        $process->timemodified = time();
        
        $processid = $DB->insert_record('local_conocer_procesos', $process);
        
        // Trigger candidate created event
        $context = \context_system::instance();
        $candidate->id = $candidateid;
        
        $event = \local_conocer_cert\event\candidate_created::create_from_candidate($candidate, $context);
        $event->trigger();
        
        // Send notification
        \local_conocer_cert\util\notification::send($USER->id, 'candidato_registrado', [
            'competencia' => $competencia->nombre,
            'nivel' => $data->nivel
        ]);
    }
    
    return $candidateid;
}

/**
 * Register a new company
 *
 * @param object $data Company data
 * @return int|false Company ID or false on failure
 */
function local_conocer_cert_register_company($data) {
    global $DB, $USER;
    
    // Check if RFC already exists
    $exists = $DB->record_exists('local_conocer_empresas', ['rfc' => $data->rfc]);
    
    if ($exists) {
        return false;
    }
    
    // Prepare company data
    $company = new \stdClass();
    $company->nombre = $data->nombre;
    $company->rfc = $data->rfc;
    $company->direccion = $data->direccion;
    $company->sector = $data->sector;
    $company->contacto_nombre = $data->contacto_nombre;
    $company->contacto_email = $data->contacto_email;
    $company->contacto_telefono = $data->contacto_telefono;
    $company->contacto_puesto = $data->contacto_puesto;
    $company->contacto_userid = $USER->id;
    $company->estado = 'pendiente';
    $company->justificacion = $data->justificacion;
    $company->fecha_solicitud = time();
    $company->fecha_modificacion = time();
    
    // Process competencias as JSON
    if (!empty($data->competencias) && is_array($data->competencias)) {
        $company->competencias = json_encode($data->competencias);
    } else {
        $company->competencias = '[]';
    }
    
    // Insert company record
    $companyid = $DB->insert_record('local_conocer_empresas', $company);
    
    if ($companyid) {
        // Trigger company registered event
        $context = \context_system::instance();
        $company->id = $companyid;
        
        $event = \local_conocer_cert\event\company_registered::create_from_company($company, $context);
        $event->trigger();
        
        // Send notification
        \local_conocer_cert\util\notification::send($USER->id, 'empresa_registrada', [
            'nombre' => $company->nombre
        ]);
    }
    
    return $companyid;
}

/**
 * Get certification statistics
 *
 * @param array $filters Optional filters
 * @return object Statistics object
 */
function local_conocer_cert_get_statistics($filters = []) {
    global $DB;
    
    $stats = new \stdClass();
    
    // Build WHERE clause based on filters
    $where = '';
    $params = [];
    
    if (!empty($filters['competencia_id'])) {
        $where = 'WHERE c.competencia_id = :competencia_id';
        $params['competencia_id'] = $filters['competencia_id'];
    }
    
    if (!empty($filters['fecha_inicio']) && !empty($filters['fecha_fin'])) {
        $where = $where ? $where . ' AND ' : 'WHERE ';
        $where .= 'c.fecha_solicitud BETWEEN :fecha_inicio AND :fecha_fin';
        $params['fecha_inicio'] = $filters['fecha_inicio'];
        $params['fecha_fin'] = $filters['fecha_fin'];
    }
    
    // Total candidates
    $sql = "SELECT COUNT(*) FROM {local_conocer_candidatos} c $where";
    $stats->total_candidates = $DB->count_records_sql($sql, $params);
    
    // Approved certifications
    $sql = "SELECT COUNT(*) 
            FROM {local_conocer_candidatos} c
            JOIN {local_conocer_procesos} p ON c.id = p.candidato_id
            $where
            AND p.resultado = 'aprobado'";
    $stats->approved = $DB->count_records_sql($sql, $params);
    
    // Rejected certifications
    $sql = "SELECT COUNT(*) 
            FROM {local_conocer_candidatos} c
            JOIN {local_conocer_procesos} p ON c.id = p.candidato_id
            $where
            AND p.resultado = 'rechazado'";
    $stats->rejected = $DB->count_records_sql($sql, $params);
    
    // In progress
    $sql = "SELECT COUNT(*) 
            FROM {local_conocer_candidatos} c
            JOIN {local_conocer_procesos} p ON c.id = p.candidato_id
            $where
            AND p.etapa IN ('solicitud', 'evaluacion', 'pendiente_revision')";
    $stats->in_progress = $DB->count_records_sql($sql, $params);
    
    // Calculate approval rate
    $total_completed = $stats->approved + $stats->rejected;
    $stats->approval_rate = $total_completed > 0 ? round(($stats->approved / $total_completed) * 100, 2) : 0;
    
    // Average completion time (in days)
    $sql = "SELECT AVG(p.fecha_fin - p.fecha_inicio) / 86400 as avg_days
            FROM {local_conocer_candidatos} c
            JOIN {local_conocer_procesos} p ON c.id = p.candidato_id
            $where
            AND p.fecha_fin IS NOT NULL";
    $avg_days = $DB->get_field_sql($sql, $params);
    $stats->avg_completion_days = round($avg_days, 1);
    
    return $stats;
}

/**
 * Send scheduled notifications
 * 
 * This function is called by scheduled tasks to send reminders and notifications
 *
 * @return bool Success status
 */
function local_conocer_cert_send_scheduled_notifications() {
    return \local_conocer_cert\util\notification::send_scheduled_notifications();
}

/**
 * Process certificate expirations
 * 
 * This function is called by scheduled tasks to mark expired certificates
 *
 * @return bool Success status
 */
function local_conocer_cert_process_certificate_expirations() {
    global $DB;
    
    $now = time();
    $certificates = $DB->get_records_select('local_conocer_certificados', 
        "fecha_vencimiento < :now AND estatus = 'activo'", 
        ['now' => $now]
    );
    
    foreach ($certificates as $cert) {
        // Update certificate status
        $DB->set_field('local_conocer_certificados', 'estatus', 'vencido', ['id' => $cert->id]);
        
        // Get process and candidate info
        $process = $DB->get_record('local_conocer_procesos', ['id' => $cert->proceso_id]);
        
        if ($process) {
            $candidate = $DB->get_record('local_conocer_candidatos', ['id' => $process->candidato_id]);
            
            if ($candidate) {
                // Trigger certificate expired event
                $context = \context_system::instance();
                $event = \local_conocer_cert\event\certificate_expired::create_from_certificate($cert, $context, $candidate->userid);
                $event->trigger();
                
                // Send notification to certificate holder
                \local_conocer_cert\util\notification::send($candidate->userid, 'certificado_vencido', [
                    'folio' => $cert->numero_folio,
                    'fecha_vencimiento' => userdate($cert->fecha_vencimiento)
                ]);
            }
        }
    }
    
    return true;
}