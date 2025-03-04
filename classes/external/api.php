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
 * External API functions for CONOCER certification plugin.
 *
 * @package    local_conocer_cert
 * @copyright  2025 Sebastian Gonzalez Zepeda sgonzalez@infraestructuragis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_conocer_cert\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use context_system;

/**
 * API class with external services for the CONOCER certification plugin.
 */
class api extends external_api {

    /**
     * Returns description of get_competencies parameters.
     *
     * @return external_function_parameters
     */
    public static function get_competencies_parameters() {
        return new external_function_parameters([
            'active' => new external_value(PARAM_BOOL, 'Only active competencies', false, true),
            'sector' => new external_value(PARAM_TEXT, 'Filter by sector', false),
        ]);
    }

    /**
     * Get available competencies.
     *
     * @param bool $active Only active competencies
     * @param string $sector Filter by sector
     * @return array List of competencies
     */
    public static function get_competencies($active = true, $sector = '') {
        global $DB;
        
        // Parameter validation
        $params = self::validate_parameters(self::get_competencies_parameters(), [
            'active' => $active,
            'sector' => $sector
        ]);
        
        // Context validation
        $context = context_system::instance();
        self::validate_context($context);
        
        // Capability check
        require_capability('local/conocer_cert:viewcompetencies', $context);
        
        // Build query conditions
        $conditions = [];
        $sqlparams = [];
        
        if ($params['active']) {
            $conditions[] = 'activo = :activo';
            $sqlparams['activo'] = 1;
        }
        
        if (!empty($params['sector'])) {
            $conditions[] = 'sector = :sector';
            $sqlparams['sector'] = $params['sector'];
        }
        
        $where = '';
        if (!empty($conditions)) {
            $where = 'WHERE ' . implode(' AND ', $conditions);
        }
        
        $sql = "SELECT id, codigo, nombre, niveles_disponibles, sector, descripcion
                FROM {local_conocer_competencias}
                $where
                ORDER BY nombre";
        
        $competencies = $DB->get_records_sql($sql, $sqlparams);
        
        $result = [];
        foreach ($competencies as $competency) {
            $result[] = [
                'id' => $competency->id,
                'code' => $competency->codigo,
                'name' => $competency->nombre,
                'description' => $competency->descripcion,
                'sector' => $competency->sector,
                'available_levels' => $competency->niveles_disponibles
            ];
        }
        
        return $result;
    }

    /**
     * Returns description of get_competencies returns.
     *
     * @return external_multiple_structure
     */
    public static function get_competencies_returns() {
        return new external_multiple_structure(
            new external_single_structure([
                'id' => new external_value(PARAM_INT, 'Competency ID'),
                'code' => new external_value(PARAM_TEXT, 'Competency code'),
                'name' => new external_value(PARAM_TEXT, 'Competency name'),
                'description' => new external_value(PARAM_RAW, 'Competency description', VALUE_OPTIONAL),
                'sector' => new external_value(PARAM_TEXT, 'Competency sector', VALUE_OPTIONAL),
                'available_levels' => new external_value(PARAM_TEXT, 'Available levels (comma separated)')
            ])
        );
    }

    /**
     * Returns description of get_candidate_certifications parameters.
     *
     * @return external_function_parameters
     */
    public static function get_candidate_certifications_parameters() {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'User ID (0 for current user)', false, 0),
        ]);
    }

    /**
     * Get certifications for a candidate.
     *
     * @param int $userid User ID (0 for current user)
     * @return array List of certifications
     */
    public static function get_candidate_certifications($userid = 0) {
        global $DB, $USER;
        
        // Parameter validation
        $params = self::validate_parameters(self::get_candidate_certifications_parameters(), [
            'userid' => $userid,
        ]);
        
        // Context validation
        $context = context_system::instance();
        self::validate_context($context);
        
        // If userid is 0, use current user
        if (empty($params['userid'])) {
            $params['userid'] = $USER->id;
        }
        
        // Capability check - either own certifications or admin access
        if ($params['userid'] != $USER->id) {
            require_capability('local/conocer_cert:managecandidates', $context);
        }
        
        // Get candidate certifications
        $sql = "SELECT p.id, p.candidato_id, p.etapa, p.resultado, p.fecha_inicio, p.fecha_fin,
                       c.competencia_id, c.nivel, c.estado, 
                       comp.codigo as competencia_codigo, comp.nombre as competencia_nombre,
                       cert.id as certificado_id, cert.numero_folio, cert.fecha_emision, 
                       cert.fecha_vencimiento, cert.estatus as certificado_estatus
                FROM {local_conocer_candidatos} c
                LEFT JOIN {local_conocer_procesos} p ON c.id = p.candidato_id
                LEFT JOIN {local_conocer_competencias} comp ON c.competencia_id = comp.id
                LEFT JOIN {local_conocer_certificados} cert ON p.id = cert.proceso_id
                WHERE c.userid = :userid
                ORDER BY p.fecha_inicio DESC";
        
        $records = $DB->get_records_sql($sql, ['userid' => $params['userid']]);
        
        $result = [];
        foreach ($records as $record) {
            $certification = [
                'process_id' => $record->id,
                'candidate_id' => $record->candidato_id,
                'competency' => [
                    'id' => $record->competencia_id,
                    'code' => $record->competencia_codigo,
                    'name' => $record->competencia_nombre
                ],
                'level' => $record->nivel,
                'status' => $record->estado,
                'stage' => $record->etapa,
                'start_date' => $record->fecha_inicio,
                'result' => $record->resultado
            ];
            
            // Add end date if available
            if (!empty($record->fecha_fin)) {
                $certification['end_date'] = $record->fecha_fin;
            }
            
            // Add certificate data if available
            if (!empty($record->certificado_id)) {
                $certification['certificate'] = [
                    'id' => $record->certificado_id,
                    'folio' => $record->numero_folio,
                    'issue_date' => $record->fecha_emision,
                    'expiry_date' => $record->fecha_vencimiento,
                    'status' => $record->certificado_estatus
                ];
            }
            
            $result[] = $certification;
        }
        
        return $result;
    }

    /**
     * Returns description of get_candidate_certifications returns.
     *
     * @return external_multiple_structure
     */
    public static function get_candidate_certifications_returns() {
        return new external_multiple_structure(
            new external_single_structure([
                'process_id' => new external_value(PARAM_INT, 'Process ID'),
                'candidate_id' => new external_value(PARAM_INT, 'Candidate ID'),
                'competency' => new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Competency ID'),
                    'code' => new external_value(PARAM_TEXT, 'Competency code'),
                    'name' => new external_value(PARAM_TEXT, 'Competency name')
                ]),
                'level' => new external_value(PARAM_INT, 'Competency level'),
                'status' => new external_value(PARAM_TEXT, 'Candidate status'),
                'stage' => new external_value(PARAM_TEXT, 'Process stage'),
                'start_date' => new external_value(PARAM_INT, 'Process start date (timestamp)'),
                'end_date' => new external_value(PARAM_INT, 'Process end date (timestamp)', VALUE_OPTIONAL),
                'result' => new external_value(PARAM_TEXT, 'Process result', VALUE_OPTIONAL),
                'certificate' => new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Certificate ID'),
                    'folio' => new external_value(PARAM_TEXT, 'Certificate folio number'),
                    'issue_date' => new external_value(PARAM_INT, 'Certificate issue date (timestamp)'),
                    'expiry_date' => new external_value(PARAM_INT, 'Certificate expiry date (timestamp)', VALUE_OPTIONAL),
                    'status' => new external_value(PARAM_TEXT, 'Certificate status')
                ], 'Certificate data', VALUE_OPTIONAL)
            ])
        );
    }

    /**
     * Returns description of verify_certificate parameters.
     *
     * @return external_function_parameters
     */
    public static function verify_certificate_parameters() {
        return new external_function_parameters([
            'folio' => new external_value(PARAM_TEXT, 'Certificate folio number'),
            'hash' => new external_value(PARAM_TEXT, 'Certificate verification hash', false, ''),
        ]);
    }

    /**
     * Verify certificate authenticity.
     *
     * @param string $folio Certificate folio number
     * @param string $hash Certificate verification hash
     * @return array Certificate verification result
     */
    public static function verify_certificate($folio, $hash = '') {
        global $DB;
        
        // Parameter validation
        $params = self::validate_parameters(self::verify_certificate_parameters(), [
            'folio' => $folio,
            'hash' => $hash
        ]);
        
        // Get certificate by folio
        $conditions = ['numero_folio' => $params['folio']];
        
        // Add hash to conditions if provided
        if (!empty($params['hash'])) {
            $conditions['hash_verificacion'] = $params['hash'];
        }
        
        $certificate = $DB->get_record('local_conocer_certificados', $conditions);
        
        if (!$certificate) {
            return [
                'valid' => false,
                'message' => get_string('certificate_not_found', 'local_conocer_cert')
            ];
        }
        
        // Get process and candidate data
        $sql = "SELECT cert.id, cert.proceso_id, cert.numero_folio, cert.fecha_emision, 
                       cert.fecha_vencimiento, cert.estatus,
                       p.candidato_id, c.userid, c.competencia_id, c.nivel,
                       u.firstname, u.lastname, u.email,
                       comp.codigo as competencia_codigo, comp.nombre as competencia_nombre
                FROM {local_conocer_certificados} cert
                JOIN {local_conocer_procesos} p ON cert.proceso_id = p.id
                JOIN {local_conocer_candidatos} c ON p.candidato_id = c.id
                JOIN {user} u ON c.userid = u.id
                JOIN {local_conocer_competencias} comp ON c.competencia_id = comp.id
                WHERE cert.id = :certificateid";
        
        $record = $DB->get_record_sql($sql, ['certificateid' => $certificate->id]);
        
        if (!$record) {
            return [
                'valid' => false,
                'message' => get_string('certificate_data_error', 'local_conocer_cert')
            ];
        }
        
        // Check if certificate is active
        $isActive = ($record->estatus == 'activo');
        $isExpired = (!empty($record->fecha_vencimiento) && $record->fecha_vencimiento < time());
        
        $result = [
            'valid' => $isActive && !$isExpired,
            'folio' => $record->numero_folio,
            'issue_date' => $record->fecha_emision,
            'holder' => $record->firstname . ' ' . $record->lastname,
            'competency' => [
                'code' => $record->competencia_codigo,
                'name' => $record->competencia_nombre
            ],
            'level' => $record->nivel
        ];
        
        if ($isExpired) {
            $result['message'] = get_string('certificate_expired', 'local_conocer_cert');
            $result['expiry_date'] = $record->fecha_vencimiento;
        } else if (!$isActive) {
            $result['message'] = get_string('certificate_inactive', 'local_conocer_cert');
        } else {
            $result['message'] = get_string('certificate_valid', 'local_conocer_cert');
            
            if (!empty($record->fecha_vencimiento)) {
                $result['expiry_date'] = $record->fecha_vencimiento;
            }
        }
        
        return $result;
    }

    /**
     * Returns description of verify_certificate returns.
     *
     * @return external_single_structure
     */
    public static function verify_certificate_returns() {
        return new external_single_structure([
            'valid' => new external_value(PARAM_BOOL, 'Is certificate valid'),
            'message' => new external_value(PARAM_TEXT, 'Verification message'),
            'folio' => new external_value(PARAM_TEXT, 'Certificate folio number', VALUE_OPTIONAL),
            'issue_date' => new external_value(PARAM_INT, 'Certificate issue date (timestamp)', VALUE_OPTIONAL),
            'expiry_date' => new external_value(PARAM_INT, 'Certificate expiry date (timestamp)', VALUE_OPTIONAL),
            'holder' => new external_value(PARAM_TEXT, 'Certificate holder name', VALUE_OPTIONAL),
            'competency' => new external_single_structure([
                'code' => new external_value(PARAM_TEXT, 'Competency code'),
                'name' => new external_value(PARAM_TEXT, 'Competency name')
            ], 'Competency data', VALUE_OPTIONAL),
            'level' => new external_value(PARAM_INT, 'Competency level', VALUE_OPTIONAL)
        ]);
    }

    /**
     * Returns description of submit_certification_request parameters.
     *
     * @return external_function_parameters
     */
    public static function submit_certification_request_parameters() {
        return new external_function_parameters([
            'competency_id' => new external_value(PARAM_INT, 'Competency ID'),
            'level' => new external_value(PARAM_INT, 'Competency level'),
            'curp' => new external_value(PARAM_TEXT, 'CURP number'),
            'phone' => new external_value(PARAM_TEXT, 'Contact phone'),
            'address' => new external_value(PARAM_TEXT, 'Address', false, ''),
            'experience' => new external_value(PARAM_TEXT, 'Related experience'),
            'userid' => new external_value(PARAM_INT, 'User ID (0 for current user)', false, 0),
        ]);
    }

    /**
     * Submit a certification request.
     *
     * @param int $competency_id Competency ID
     * @param int $level Competency level
     * @param string $curp CURP number
     * @param string $phone Contact phone
     * @param string $address Address
     * @param string $experience Related experience
     * @param int $userid User ID (0 for current user)
     * @return array Submission result
     */
    public static function submit_certification_request($competency_id, $level, $curp, $phone, $experience, $address = '', $userid = 0) {
        global $DB, $USER;
        
        // Parameter validation
        $params = self::validate_parameters(self::submit_certification_request_parameters(), [
            'competency_id' => $competency_id,
            'level' => $level,
            'curp' => $curp,
            'phone' => $phone,
            'address' => $address,
            'experience' => $experience,
            'userid' => $userid,
        ]);
        
        // Context validation
        $context = context_system::instance();
        self::validate_context($context);
        
        // If userid is 0, use current user
        if (empty($params['userid'])) {
            $params['userid'] = $USER->id;
        }
        
        // Capability check - either for self or admin access
        if ($params['userid'] != $USER->id) {
            require_capability('local/conocer_cert:managecandidates', $context);
        }
        
        // Verify the competency exists and is active
        $competency = $DB->get_record('local_conocer_competencias', [
            'id' => $params['competency_id'],
            'activo' => 1
        ]);
        
        if (!$competency) {
            throw new \moodle_exception('competencynotfound', 'local_conocer_cert');
        }
        
        // Verify the level is available for this competency
        $availableLevels = explode(',', $competency->niveles_disponibles);
        if (!in_array($params['level'], $availableLevels)) {
            throw new \moodle_exception('levelnotavailable', 'local_conocer_cert');
        }
        
        // Validate CURP format
        if (strlen($params['curp']) != 18 || !preg_match('/^[A-Z]{4}[0-9]{6}[HM][A-Z]{5}[0-9A-Z]{2}$/', $params['curp'])) {
            throw new \moodle_exception('invalidcurp', 'local_conocer_cert');
        }
        
        // Validate phone format
        if (!preg_match('/^[0-9]{10}$/', $params['phone'])) {
            throw new \moodle_exception('invalidphone', 'local_conocer_cert');
        }
        
        // Check if there's already a request for this user and competency
        $existingRequest = $DB->get_record('local_conocer_candidatos', [
            'userid' => $params['userid'],
            'competencia_id' => $params['competency_id'],
            'nivel' => $params['level']
        ]);
        
        if ($existingRequest) {
            return [
                'success' => false,
                'message' => get_string('request_already_exists', 'local_conocer_cert'),
                'candidate_id' => $existingRequest->id
            ];
        }
        
        // Create new request
        $request = new \stdClass();
        $request->userid = $params['userid'];
        $request->competencia_id = $params['competency_id'];
        $request->nivel = $params['level'];
        $request->estado = 'pendiente';
        $request->curp = $params['curp'];
        $request->telefono = $params['phone'];
        $request->direccion = $params['address'];
        $request->experiencia = $params['experience'];
        $request->fecha_solicitud = time();
        $request->fecha_modificacion = time();
        
        $candidateId = $DB->insert_record('local_conocer_candidatos', $request);
        
        if (!$candidateId) {
            return [
                'success' => false,
                'message' => get_string('request_creation_failed', 'local_conocer_cert')
            ];
        }
        
        // Create initial process
        $process = new \stdClass();
        $process->candidato_id = $candidateId;
        $process->etapa = 'solicitud';
        $process->fecha_inicio = time();
        $process->timemodified = time();
        
        $processId = $DB->insert_record('local_conocer_procesos', $process);
        
        // Send notification to user
        $user = $DB->get_record('user', ['id' => $params['userid']]);
        
        \local_conocer_cert\util\notification::send($user->id, 'candidato_registrado', [
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'competencia' => $competency->nombre,
            'nivel' => $params['level'],
            'contexturl' => new \moodle_url('/local/conocer_cert/candidate/view_request.php', ['id' => $candidateId]),
            'contexturlname' => get_string('view_request', 'local_conocer_cert')
        ]);
        
        return [
            'success' => true,
            'message' => get_string('request_created', 'local_conocer_cert'),
            'candidate_id' => $candidateId,
            'process_id' => $processId
        ];
    }

    /**
     * Returns description of submit_certification_request returns.
     *
     * @return external_single_structure
     */
    public static function submit_certification_request_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Success status'),
            'message' => new external_value(PARAM_TEXT, 'Status message'),
            'candidate_id' => new external_value(PARAM_INT, 'Candidate ID', VALUE_OPTIONAL),
            'process_id' => new external_value(PARAM_INT, 'Process ID', VALUE_OPTIONAL)
        ]);
    }

    /**
     * Returns description of get_evaluator_assignments parameters.
     *
     * @return external_function_parameters
     */
    public static function get_evaluator_assignments_parameters() {
        return new external_function_parameters([
            'status' => new external_value(PARAM_TEXT, 'Filter by status (pending, completed, all)', false, 'all'),
        ]);
    }

    /**
     * Get assignments for the current evaluator.
     *
     * @param string $status Filter by status
     * @return array List of assignments
     */
    public static function get_evaluator_assignments($status = 'all') {
        global $DB, $USER;
        
        // Parameter validation
        $params = self::validate_parameters(self::get_evaluator_assignments_parameters(), [
            'status' => $status,
        ]);
        
        // Context validation
        $context = context_system::instance();
        self::validate_context($context);
        
        // Capability check
        require_capability('local/conocer_cert:evaluatecandidates', $context);
        
        // Verify user is an evaluator
        $isEvaluator = \local_conocer_cert\evaluator\manager::is_evaluator($USER->id);
        if (!$isEvaluator) {
            throw new \moodle_exception('usernotanevaluator', 'local_conocer_cert');
        }
        
        // Build query conditions
        $conditions = ["p.evaluador_id = :evaluatorid"];
        $sqlparams = ['evaluatorid' => $USER->id];
        
        // Filter by status
        if ($params['status'] == 'pending') {
            $conditions[] = "p.etapa = 'evaluacion' AND (p.fecha_evaluacion IS NULL OR p.fecha_evaluacion = 0)";
        } else if ($params['status'] == 'completed') {
            $conditions[] = "p.etapa IN ('aprobado', 'rechazado')";
        }
        
        $where = implode(' AND ', $conditions);
        
        // Get assignments
        $sql = "SELECT p.id, p.candidato_id, p.etapa, p.resultado, p.fecha_inicio, p.fecha_evaluacion, p.fecha_fin,
                       c.userid as candidate_userid, c.competencia_id, c.nivel, 
                       u.firstname, u.lastname, u.email,
                       comp.codigo as competencia_codigo, comp.nombre as competencia_nombre
                FROM {local_conocer_procesos} p
                JOIN {local_conocer_candidatos} c ON p.candidato_id = c.id
                JOIN {user} u ON c.userid = u.id
                JOIN {local_conocer_competencias} comp ON c.competencia_id = comp.id
                WHERE $where
                ORDER BY p.fecha_inicio DESC";
        
        $records = $DB->get_records_sql($sql, $sqlparams);
        
        $result = [];
        foreach ($records as $record) {
            $assignment = [
                'process_id' => $record->id,
                'candidate_id' => $record->candidato_id,
                'candidate' => [
                    'id' => $record->candidate_userid,
                    'name' => $record->firstname . ' ' . $record->lastname,
                    'email' => $record->email
                ],
                'competency' => [
                    'id' => $record->competencia_id,
                    'code' => $record->competencia_codigo,
                    'name' => $record->competencia_nombre
                ],
                'level' => $record->nivel,
                'stage' => $record->etapa,
                'start_date' => $record->fecha_inicio
            ];
            
            // Add evaluation date if available
            if (!empty($record->fecha_evaluacion)) {
                $assignment['evaluation_date'] = $record->fecha_evaluacion;
            }
            
            // Add result if available
            if (!empty($record->resultado)) {
                $assignment['result'] = $record->resultado;
            }
            
            // Add end date if available
            if (!empty($record->fecha_fin)) {
                $assignment['end_date'] = $record->fecha_fin;
            }
            
            // Determine status
            if ($record->etapa == 'evaluacion' && empty($record->fecha_evaluacion)) {
                $assignment['status'] = 'pending';
            } else if ($record->etapa == 'evaluacion') {
                $assignment['status'] = 'in_progress';
            } else {
                $assignment['status'] = 'completed';
            }
            
            $result[] = $assignment;
        }
        
        return $result;
    }

    /**
     * Returns description of get_evaluator_assignments returns.
     *
     * @return external_multiple_structure
     */
    public static function get_evaluator_assignments_returns() {
        return new external_multiple_structure(
            new external_single_structure([
                'process_id' => new external_value(PARAM_INT, 'Process ID'),
                'candidate_id' => new external_value(PARAM_INT, 'Candidate ID'),
                'candidate' => new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'User ID'),
                    'name' => new external_value(PARAM_TEXT, 'Full name'),
                    'email' => new external_value(PARAM_TEXT, 'Email')
                ]),
                'competency' => new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Competency ID'),
                    'code' => new external_value(PARAM_TEXT, 'Competency code'),
                    'name' => new external_value(PARAM_TEXT, 'Competency name')
                ]),
                'level' => new external_value(PARAM_INT, 'Competency level'),
                'stage' => new external_value(PARAM_TEXT, 'Process stage'),
                'status' => new external_value(PARAM_TEXT, 'Assignment status'),
                'start_date' => new external_value(PARAM_INT, 'Assignment start date (timestamp)'),
                'evaluation_date' => new external_value(PARAM_INT, 'Evaluation date (timestamp)', VALUE_OPTIONAL),
                'end_date' => new external_value(PARAM_INT, 'End date (timestamp)', VALUE_OPTIONAL),
                'result' => new external_value(PARAM_TEXT, 'Evaluation result', VALUE_OPTIONAL)
            ])
        );
    }
}
