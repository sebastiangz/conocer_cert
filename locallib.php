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
 * @package    local_conocer_cert
 * @copyright  2025 Sebastian Gonzalez Zepeda sgonzalez@infraestructuragis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/conocer_cert/lib.php');

/**
 * Get all certification processes for a candidate
 *
 * @param int $candidateid Candidate ID
 * @return array Array of certification processes
 */
function local_conocer_cert_get_candidate_processes($candidateid) {
    global $DB;
    
    // Get all processes for this candidate
    return $DB->get_records('local_conocer_procesos', ['candidato_id' => $candidateid], 'fecha_inicio DESC');
}

/**
 * Get all documents for a candidate
 *
 * @param int $candidateid Candidate ID
 * @return array Array of documents
 */
function local_conocer_cert_get_candidate_documents($candidateid) {
    global $DB;
    
    // Get all documents for this candidate
    return $DB->get_records('local_conocer_documentos', ['candidato_id' => $candidateid]);
}

/**
 * Get pending documents for a candidate
 *
 * @param int $candidateid Candidate ID
 * @return array Array of document types that are still pending
 */
function local_conocer_cert_get_pending_documents($candidateid) {
    global $DB;
    
    // Get candidate's competency to determine required documents
    $candidate = $DB->get_record('local_conocer_candidatos', ['id' => $candidateid]);
    if (!$candidate) {
        return [];
    }
    
    // Get competency details
    $competency = $DB->get_record('local_conocer_competencias', ['id' => $candidate->competencia_id]);
    if (!$competency || empty($competency->documentos_requeridos)) {
        // Default required documents if not specified
        $requiredDocs = ['id_oficial', 'curp_doc', 'comprobante_domicilio', 'evidencia_laboral', 'fotografia'];
    } else {
        $requiredDocs = explode(',', $competency->documentos_requeridos);
    }
    
    // Get already uploaded documents
    $existingDocs = $DB->get_fieldset_select('local_conocer_documentos', 'tipo', 'candidato_id = :candidateid', 
        ['candidateid' => $candidateid]);
    
    // Return the difference (required docs that haven't been uploaded)
    return array_diff($requiredDocs, $existingDocs);
}

/**
 * Check if a candidate has all required documents uploaded
 *
 * @param int $candidateid Candidate ID
 * @return bool True if all required documents are uploaded
 */
function local_conocer_cert_has_all_documents($candidateid) {
    $pendingDocs = local_conocer_cert_get_pending_documents($candidateid);
    return empty($pendingDocs);
}

/**
 * Generate a unique folio number for a certificate
 *
 * @param string $prefix Prefix for the folio number (default: 'CERT')
 * @return string Unique folio number
 */
function local_conocer_cert_generate_folio($prefix = 'CERT') {
    global $DB;
    
    $year = date('Y');
    $sequence = $DB->count_records('local_conocer_certificados') + 1;
    
    return $prefix . '-' . $year . '-' . str_pad($sequence, 6, '0', STR_PAD_LEFT);
}

/**
 * Generate a unique verification hash for a certificate
 *
 * @param int $certificateid Certificate ID
 * @param int $userid User ID
 * @return string Verification hash
 */
function local_conocer_cert_generate_verification_hash($certificateid, $userid) {
    global $CFG;
    
    $data = $certificateid . '|' . $userid . '|' . time();
    $salt = isset($CFG->passwordsaltmain) ? $CFG->passwordsaltmain : '';
    
    return substr(md5($data . $salt), 0, 16);
}

/**
 * Create a new certificate for a completed process
 *
 * @param int $processid Process ID
 * @param int $issuerid ID of the user issuing the certificate
 * @param int $validityYears Number of years the certificate is valid (0 for no expiry)
 * @return int|false ID of the new certificate or false on failure
 */
function local_conocer_cert_create_certificate($processid, $issuerid, $validityYears = 5) {
    global $DB;
    
    // Get process details
    $process = $DB->get_record('local_conocer_procesos', ['id' => $processid]);
    if (!$process || $process->resultado != 'aprobado') {
        return false;
    }
    
    // Get candidate details
    $candidate = $DB->get_record('local_conocer_candidatos', ['id' => $process->candidato_id]);
    if (!$candidate) {
        return false;
    }
    
    // Create certificate record
    $certificate = new stdClass();
    $certificate->proceso_id = $processid;
    $certificate->numero_folio = local_conocer_cert_generate_folio();
    $certificate->fecha_emision = time();
    
    // Calculate expiry date if applicable
    if ($validityYears > 0) {
        $certificate->fecha_vencimiento = strtotime("+{$validityYears} years", $certificate->fecha_emision);
    } else {
        $certificate->fecha_vencimiento = null;
    }
    
    $certificate->emitido_por = $issuerid;
    $certificate->estatus = 'activo';
    
    // Insert record
    $certificateid = $DB->insert_record('local_conocer_certificados', $certificate);
    
    if ($certificateid) {
        // Generate and store verification hash
        $verificationHash = local_conocer_cert_generate_verification_hash($certificateid, $candidate->userid);
        $DB->set_field('local_conocer_certificados', 'hash_verificacion', $verificationHash, ['id' => $certificateid]);
        
        // Trigger certificate creation event
        $context = context_system::instance();
        $event = \local_conocer_cert\event\certificate_created::create([
            'objectid' => $certificateid,
            'context' => $context,
            'relateduserid' => $candidate->userid,
            'other' => [
                'processid' => $processid,
                'folio' => $certificate->numero_folio
            ]
        ]);
        $event->trigger();
        
        // Send notification to candidate
        \local_conocer_cert\util\notification::send($candidate->userid, 'certificado_disponible', [
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'folio' => $certificate->numero_folio,
            'fecha_emision' => userdate($certificate->fecha_emision),
            'contexturl' => new moodle_url('/local/conocer_cert/candidate/download_certificate.php', ['id' => $certificateid]),
            'contexturlname' => get_string('download_certificate', 'local_conocer_cert')
        ]);
    }
    
    return $certificateid;
}

/**
 * Get active certificates for a user
 *
 * @param int $userid User ID
 * @return array Array of active certificates
 */
function local_conocer_cert_get_user_certificates($userid) {
    global $DB;
    
    $sql = "SELECT cert.*, p.candidato_id, c.competencia_id, c.nivel
            FROM {local_conocer_certificados} cert
            JOIN {local_conocer_procesos} p ON cert.proceso_id = p.id
            JOIN {local_conocer_candidatos} c ON p.candidato_id = c.id
            WHERE c.userid = :userid
            ORDER BY cert.fecha_emision DESC";
    
    return $DB->get_records_sql($sql, ['userid' => $userid]);
}

/**
 * Verify a certificate by its folio number and verification hash
 *
 * @param string $folio Certificate folio number
 * @param string $hash Verification hash
 * @return object|false Certificate data with additional verification info, or false if invalid
 */
function local_conocer_cert_verify_certificate($folio, $hash = '') {
    global $DB;
    
    // Build query conditions
    $params = ['folio' => $folio];
    $hashcondition = '';
    
    if (!empty($hash)) {
        $params['hash'] = $hash;
        $hashcondition = 'AND cert.hash_verificacion = :hash';
    }
    
    $sql = "SELECT cert.*, p.candidato_id, p.resultado, p.fecha_evaluacion,
                   c.userid, c.competencia_id, c.nivel,
                   u.firstname, u.lastname,
                   comp.nombre as competencia_nombre, comp.codigo as competencia_codigo
            FROM {local_conocer_certificados} cert
            JOIN {local_conocer_procesos} p ON cert.proceso_id = p.id
            JOIN {local_conocer_candidatos} c ON p.candidato_id = c.id
            JOIN {user} u ON c.userid = u.id
            JOIN {local_conocer_competencias} comp ON c.competencia_id = comp.id
            WHERE cert.numero_folio = :folio $hashcondition";
    
    $certificate = $DB->get_record_sql($sql, $params);
    
    if (!$certificate) {
        return false;
    }
    
    // Add verification status
    $certificate->is_valid = ($certificate->estatus == 'activo');
    
    // Check if expired
    if (!empty($certificate->fecha_vencimiento)) {
        $certificate->is_expired = (time() > $certificate->fecha_vencimiento);
        if ($certificate->is_expired) {
            $certificate->is_valid = false;
        }
    } else {
        $certificate->is_expired = false;
    }
    
    return $certificate;
}

/**
 * Get evaluator workload statistics
 *
 * @param int $evaluatorid Evaluator ID
 * @return array Workload statistics
 */
function local_conocer_cert_get_evaluator_workload($evaluatorid) {
    global $DB;
    
    // Get evaluator user ID
    $evaluator = $DB->get_record('local_conocer_evaluadores', ['id' => $evaluatorid]);
    if (!$evaluator) {
        return [];
    }
    
    $stats = [];
    
    // Total assigned
    $stats['total_asignados'] = $DB->count_records('local_conocer_procesos', ['evaluador_id' => $evaluator->userid]);
    
    // Pending evaluations
    $stats['pendientes'] = $DB->count_records_select(
        'local_conocer_procesos',
        "evaluador_id = :evaluatorid AND etapa = 'evaluacion' AND (fecha_evaluacion IS NULL OR fecha_evaluacion = 0)",
        ['evaluatorid' => $evaluator->userid]
    );
    
    // In progress
    $stats['en_progreso'] = $DB->count_records_select(
        'local_conocer_procesos',
        "evaluador_id = :evaluatorid AND etapa = 'evaluacion' AND fecha_evaluacion IS NOT NULL AND fecha_fin IS NULL",
        ['evaluatorid' => $evaluator->userid]
    );
    
    // Completed
    $stats['completados'] = $DB->count_records_select(
        'local_conocer_procesos',
        "evaluador_id = :evaluatorid AND etapa IN ('aprobado', 'rechazado')",
        ['evaluatorid' => $evaluator->userid]
    );
    
    // Last 7 days
    $oneWeekAgo = time() - (7 * 24 * 60 * 60);
    $stats['ultimos_7_dias'] = $DB->count_records_select(
        'local_conocer_procesos',
        "evaluador_id = :evaluatorid AND fecha_evaluacion > :timelimit",
        ['evaluatorid' => $evaluator->userid, 'timelimit' => $oneWeekAgo]
    );
    
    return $stats;
}

/**
 * Format filesize in human readable format
 *
 * @param int $bytes File size in bytes
 * @return string Formatted file size
 */
function local_conocer_cert_format_filesize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } else if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } else if ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

/**
 * Get active processes for a candidate
 *
 * @param int $candidateid Candidate ID
 * @return array Active certification processes
 */
function local_conocer_cert_get_active_processes($candidateid) {
    global $DB;
    
    return $DB->get_records_select(
        'local_conocer_procesos',
        "candidato_id = :candidateid AND etapa IN ('solicitud', 'evaluacion', 'pendiente_revision')",
        ['candidateid' => $candidateid],
        'fecha_inicio DESC'
    );
}

/**
 * Get completed processes for a candidate
 *
 * @param int $candidateid Candidate ID
 * @return array Completed certification processes
 */
function local_conocer_cert_get_completed_processes($candidateid) {
    global $DB;
    
    return $DB->get_records_select(
        'local_conocer_procesos',
        "candidato_id = :candidateid AND etapa IN ('aprobado', 'rechazado')",
        ['candidateid' => $candidateid],
        'fecha_fin DESC'
    );
}

/**
 * Check if a competency is valid for certification
 *
 * @param int $competencyid Competency ID
 * @param int $level Competency level
 * @return bool True if the competency and level are valid
 */
function local_conocer_cert_is_valid_competency($competencyid, $level) {
    global $DB;
    
    // Get competency details
    $competency = $DB->get_record('local_conocer_competencias', ['id' => $competencyid, 'activo' => 1]);
    if (!$competency) {
        return false;
    }
    
    // Check if level is available for this competency
    $availableLevels = explode(',', $competency->niveles_disponibles);
    return in_array($level, $availableLevels);
}

/**
 * Create a new certification process for a candidate
 *
 * @param int $candidateid Candidate ID
 * @param string $etapa Initial stage (default: 'solicitud')
 * @return int|false Process ID or false on failure
 */
function local_conocer_cert_create_process($candidateid, $etapa = 'solicitud') {
    global $DB;
    
    // Verify candidate exists
    if (!$DB->record_exists('local_conocer_candidatos', ['id' => $candidateid])) {
        return false;
    }
    
    // Create process record
    $process = new stdClass();
    $process->candidato_id = $candidateid;
    $process->etapa = $etapa;
    $process->fecha_inicio = time();
    $process->timemodified = time();
    
    return $DB->insert_record('local_conocer_procesos', $process);
}

/**
 * Get assigned candidates for an evaluator
 *
 * @param int $evaluatoruserid Evaluator user ID
 * @param string $filter Filter by status ('pendientes', 'completados', or 'all')
 * @return array Candidate information records
 */
function local_conocer_cert_get_assigned_candidates($evaluatoruserid, $filter = 'all') {
    global $DB;
    
    $conditions = [];
    $params = ['evaluatorid' => $evaluatoruserid];
    
    $baseCondition = "p.evaluador_id = :evaluatorid";
    $conditions[] = $baseCondition;
    
    if ($filter == 'pendientes') {
        $conditions[] = "p.etapa = 'evaluacion'";
    } else if ($filter == 'completados') {
        $conditions[] = "p.etapa IN ('aprobado', 'rechazado')";
    }
    
    $where = implode(' AND ', $conditions);
    
    $sql = "SELECT p.id, p.candidato_id, p.etapa, p.resultado, p.fecha_inicio, p.fecha_evaluacion, p.fecha_fin,
                   c.userid, c.competencia_id, c.nivel,
                   u.firstname, u.lastname, u.email,
                   comp.nombre as competencia_nombre, comp.codigo as competencia_codigo
            FROM {local_conocer_procesos} p
            JOIN {local_conocer_candidatos} c ON p.candidato_id = c.id
            JOIN {user} u ON c.userid = u.id
            JOIN {local_conocer_competencias} comp ON c.competencia_id = comp.id
            WHERE $where
            ORDER BY p.fecha_inicio DESC";
    
    return $DB->get_records_sql($sql, $params);
}

/**
 * Assign an evaluator to a candidate's process
 *
 * @param int $processid Process ID
 * @param int $evaluatoruserid Evaluator user ID
 * @return bool Success status
 */
function local_conocer_cert_assign_evaluator($processid, $evaluatoruserid) {
    global $DB;
    
    // Verify process exists and is in a valid stage
    $process = $DB->get_record_select(
        'local_conocer_procesos', 
        "id = :processid AND etapa IN ('solicitud', 'evaluacion')",
        ['processid' => $processid]
    );
    
    if (!$process) {
        return false;
    }
    
    // Verify evaluator exists and is active
    $evaluator = $DB->get_record_select(
        'local_conocer_evaluadores',
        "userid = :userid AND estatus = 'activo'",
        ['userid' => $evaluatoruserid]
    );
    
    if (!$evaluator) {
        return false;
    }
    
    // Update process with evaluator and change stage to evaluation
    $process->evaluador_id = $evaluatoruserid;
    $process->etapa = 'evaluacion';
    $process->timemodified = time();
    
    $result = $DB->update_record('local_conocer_procesos', $process);
    
    if ($result) {
        // Get candidate details
        $candidate = $DB->get_record('local_conocer_candidatos', ['id' => $process->candidato_id]);
        
        // Notify evaluator
        $evaluatoruser = $DB->get_record('user', ['id' => $evaluatoruserid]);
        $candidateuser = $DB->get_record('user', ['id' => $candidate->userid]);
        $competencia = $DB->get_record('local_conocer_competencias', ['id' => $candidate->competencia_id]);
        
        \local_conocer_cert\util\notification::send($evaluatoruserid, 'evaluador_nueva_asignacion', [
            'firstname' => $evaluatoruser->firstname,
            'lastname' => $evaluatoruser->lastname,
            'candidate_name' => fullname($candidateuser),
            'competencia' => $competencia->nombre,
            'nivel' => $candidate->nivel,
            'proceso_id' => $process->id,
            'contexturl' => new moodle_url('/local/conocer_cert/evaluator/evaluate.php', ['id' => $process->candidato_id]),
            'contexturlname' => get_string('evaluate_candidate', 'local_conocer_cert')
        ]);
        
        // Notify candidate
        \local_conocer_cert\util\notification::send($candidate->userid, 'evaluador_asignado', [
            'firstname' => $candidateuser->firstname,
            'lastname' => $candidateuser->lastname,
            'evaluador_nombre' => fullname($evaluatoruser),
            'competencia' => $competencia->nombre,
            'nivel' => $candidate->nivel,
            'contexturl' => new moodle_url('/local/conocer_cert/candidate/view_process.php', ['id' => $process->id]),
            'contexturlname' => get_string('view_process', 'local_conocer_cert')
        ]);
    }
    
    return $result;
}

/**
 * Get the status of a candidate's documents
 *
 * @param int $candidateid Candidate ID
 * @return array Document status information
 */
function local_conocer_cert_get_document_status($candidateid) {
    global $DB;
    
    // Get the list of uploaded documents
    $documents = $DB->get_records('local_conocer_documentos', ['candidato_id' => $candidateid]);
    
    // Get pending documents
    $pendingDocs = local_conocer_cert_get_pending_documents($candidateid);
    
    // Count by status
    $counts = [
        'total' => count($documents),
        'pendientes' => count($pendingDocs),
        'aprobados' => 0,
        'rechazados' => 0,
        'en_revision' => 0
    ];
    
    foreach ($documents as $doc) {
        if ($doc->estado == 'aprobado') {
            $counts['aprobados']++;
        } else if ($doc->estado == 'rechazado') {
            $counts['rechazados']++;
        } else {
            $counts['en_revision']++;
        }
    }
    
    return [
        'documents' => $documents,
        'pending' => $pendingDocs,
        'counts' => $counts,
        'all_uploaded' => empty($pendingDocs),
        'all_approved' => ($counts['aprobados'] == $counts['total'] && $counts['total'] > 0)
    ];
}

/**
 * Complete a certification process with results
 *
 * @param int $processid Process ID
 * @param string $resultado Result (aprobado/rechazado)
 * @param array $additionalData Additional data to include
 * @return bool Success status
 */
function local_conocer_cert_complete_process($processid, $resultado, $additionalData = []) {
    global $DB, $USER;
    
    // Verify process exists and is in evaluation stage
    $process = $DB->get_record_select(
        'local_conocer_procesos', 
        "id = :processid AND etapa = 'evaluacion'",
        ['processid' => $processid]
    );
    
    if (!$process) {
        return false;
    }
    
    // Update process with result and complete
    $process->resultado = $resultado;
    $process->etapa = $resultado; // 'aprobado' or 'rechazado'
    $process->fecha_fin = time();
    $process->timemodified = time();
    
    // Add additional data if provided
    foreach ($additionalData as $key => $value) {
        if (property_exists($process, $key)) {
            $process->$key = $value;
        }
    }
    
    $result = $DB->update_record('local_conocer_procesos', $process);
    
    if ($result) {
        // Get candidate details
        $candidate = $DB->get_record('local_conocer_candidatos', ['id' => $process->candidato_id]);
        $candidateuser = $DB->get_record('user', ['id' => $candidate->userid]);
        $competencia = $DB->get_record('local_conocer_competencias', ['id' => $candidate->competencia_id]);
        
        // Create certificate if approved
        if ($resultado == 'aprobado') {
            local_conocer_cert_create_certificate($processid, $USER->id);
        }
        
        // Notify candidate of completion
        \local_conocer_cert\util\notification::notify_certification_completed($processid);
        
        // Trigger certification completion event
        $context = context_system::instance();
        $event = \local_conocer_cert\event\certification_completed::create_from_process($process, $context, $candidate->userid);
        $event->trigger();
    }
    
    return $result;
}

/**
 * Get evaluators that can be assigned to a specific competency
 *
 * @param int $competencyid Competency ID
 * @return array Array of eligible evaluators
 */
function local_conocer_cert_get_eligible_evaluators($competencyid) {
    global $DB;
    
    $sql = "SELECT e.*, u.firstname, u.lastname, u.email
            FROM {local_conocer_evaluadores} e
            JOIN {user} u ON e.userid = u.id
            WHERE e.estatus = 'activo'
            AND (e.competencias LIKE :comp1 OR e.competencias LIKE :comp2 OR e.competencias LIKE :comp3)";
    
    $params = [
        'comp1' => '%"' . $competencyid . '"%',
        'comp2' => '%[' . $competencyid . ',%',
        'comp3' => '%,' . $competencyid . ',%'
    ];
    
    return $DB->get_records_sql($sql, $params);
}