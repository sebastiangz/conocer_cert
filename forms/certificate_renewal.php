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
 * Certificate renewal form for the CONOCER certification system.
 *
 * @package    local_conocer_cert
 * @copyright  2025 Sebastian Gonzalez Zepeda sgonzalez@infraestructuragis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_conocer_cert\forms;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for renewing expired certificates.
 */
class certificate_renewal_form extends \moodleform {
    
    /**
     * Form definition.
     */
    public function definition() {
        global $DB;
        
        $mform = $this->_form;
        $certificate = $this->_customdata['certificate'] ?? null;
        $process = $this->_customdata['process'] ?? null;
        $candidate = $this->_customdata['candidate'] ?? null;
        
        if (!$certificate || !$process || !$candidate) {
            return;
        }
        
        // Get competency details
        $competency = $DB->get_record('local_conocer_competencias', ['id' => $candidate->competencia_id]);
        
        // Certificate information
        $mform->addElement('header', 'certificateinfo', get_string('certificate_information', 'local_conocer_cert'));
        
        // Display certificate details
        $mform->addElement('static', 'certificate_folio', get_string('certificate_folio', 'local_conocer_cert'), $certificate->numero_folio);
        $mform->addElement('static', 'competency_name', get_string('competency', 'local_conocer_cert'), $competency->nombre);
        $mform->addElement('static', 'competency_code', get_string('competency_code', 'local_conocer_cert'), $competency->codigo);
        $mform->addElement('static', 'level', get_string('level', 'local_conocer_cert'), get_string('level' . $candidate->nivel, 'local_conocer_cert'));
        $mform->addElement('static', 'issue_date', get_string('issue_date', 'local_conocer_cert'), userdate($certificate->fecha_emision));
        $mform->addElement('static', 'expiry_date', get_string('expiry_date', 'local_conocer_cert'), userdate($certificate->fecha_vencimiento));
        
        // Certificate preview link
        $view_url = new \moodle_url('/local/conocer_cert/candidate/view_certificate.php', ['id' => $certificate->id]);
        $mform->addElement('html', '<div class="form-group row">');
        $mform->addElement('html', '<div class="col-md-3 col-form-label d-flex justify-content-md-end">' . 
            get_string('certificate_preview', 'local_conocer_cert') . '</div>');
        $mform->addElement('html', '<div class="col-md-9"><a href="' . $view_url . '" class="btn btn-info" target="_blank">' . 
            get_string('view_certificate', 'local_conocer_cert') . '</a></div>');
        $mform->addElement('html', '</div>');
        
        // Renewal section
        $mform->addElement('header', 'renewalheader', get_string('certificate_renewal', 'local_conocer_cert'));
        
        // Renewal options
        $renewal_options = [
            'simple' => get_string('renewal_simple', 'local_conocer_cert'),
            'reevaluation' => get_string('renewal_reevaluation', 'local_conocer_cert'),
            'full' => get_string('renewal_full', 'local_conocer_cert')
        ];
        $mform->addElement('select', 'renewal_type', get_string('renewal_type', 'local_conocer_cert'), $renewal_options);
        $mform->setDefault('renewal_type', 'simple');
        
        // Add descriptions for each option
        $mform->addElement('html', '<div id="renewal-descriptions">');
        
        $mform->addElement('html', '<div id="desc-simple" class="renewal-desc alert alert-info">');
        $mform->addElement('html', get_string('renewal_simple_desc', 'local_conocer_cert'));
        $mform->addElement('html', '</div>');
        
        $mform->addElement('html', '<div id="desc-reevaluation" class="renewal-desc alert alert-info" style="display:none;">');
        $mform->addElement('html', get_string('renewal_reevaluation_desc', 'local_conocer_cert'));
        $mform->addElement('html', '</div>');
        
        $mform->addElement('html', '<div id="desc-full" class="renewal-desc alert alert-info" style="display:none;">');
        $mform->addElement('html', get_string('renewal_full_desc', 'local_conocer_cert'));
        $mform->addElement('html', '</div>');
        
        $mform->addElement('html', '</div>');
        
        // Current activity related to the competency
        $mform->addElement('textarea', 'current_activity', get_string('current_activity', 'local_conocer_cert'), 
            ['rows' => 4, 'cols' => 50]);
        $mform->setType('current_activity', PARAM_TEXT);
        $mform->addRule('current_activity', get_string('required'), 'required');
        $mform->addHelpButton('current_activity', 'current_activity_help', 'local_conocer_cert');
        
        // Evidence of continued practice
        $mform->addElement('filemanager', 'evidence_filemanager', get_string('evidence_of_practice', 'local_conocer_cert'), 
            null, [
                'maxbytes' => 10485760, // 10MB
                'maxfiles' => 5,
                'accepted_types' => ['.pdf', '.jpg', '.jpeg', '.png', '.doc', '.docx']
            ]);
        $mform->addHelpButton('evidence_filemanager', 'evidence_of_practice_help', 'local_conocer_cert');
        
        // Only required for simple renewal
        $mform->disabledIf('evidence_filemanager', 'renewal_type', 'ne', 'simple');
        
        // Agreement to terms
        $mform->addElement('checkbox', 'agreement', get_string('agreement_renewal', 'local_conocer_cert'));
        $mform->addRule('agreement', get_string('required'), 'required');
        
        // Hidden fields
        $mform->addElement('hidden', 'certificate_id', $certificate->id);
        $mform->setType('certificate_id', PARAM_INT);
        
        $mform->addElement('hidden', 'process_id', $process->id);
        $mform->setType('process_id', PARAM_INT);
        
        $mform->addElement('hidden', 'candidate_id', $candidate->id);
        $mform->setType('candidate_id', PARAM_INT);
        
        // Buttons
        $this->add_action_buttons(true, get_string('submit_renewal', 'local_conocer_cert'));
    }
    
    /**
     * Process form submission to initiate certificate renewal.
     *
     * @return bool|int Success status or new process ID
     */
    public function process_renewal() {
        global $DB, $USER;
        
        $data = $this->get_data();
        if (!$data) {
            return false;
        }
        
        try {
            // Begin transaction
            $transaction = $DB->start_delegated_transaction();
            
            // Get original records
            $certificate = $DB->get_record('local_conocer_certificados', ['id' => $data->certificate_id]);
            $process = $DB->get_record('local_conocer_procesos', ['id' => $data->process_id]);
            $candidate = $DB->get_record('local_conocer_candidatos', ['id' => $data->candidate_id]);
            
            if (!$certificate || !$process || !$candidate) {
                throw new \moodle_exception('recordnotfound', 'local_conocer_cert');
            }
            
            // Create a new process based on the renewal type
            $new_process = new \stdClass();
            $new_process->candidato_id = $candidate->id;
            $new_process->etapa = 'solicitud'; // Initial stage for all renewal types
            $new_process->fecha_inicio = time();
            $new_process->renovacion = 1; // Mark as a renewal
            $new_process->proceso_original_id = $process->id; // Link to original process
            $new_process->notas = get_string('renewal_initiated', 'local_conocer_cert', $data->renewal_type);
            
            // Set different stages based on renewal type
            switch ($data->renewal_type) {
                case 'simple':
                    // For simple renewal, we skip to evaluation directly
                    $new_process->etapa = 'evaluacion';
                    break;
                    
                case 'reevaluation':
                    // For reevaluation, we need an evaluator assigned
                    $new_process->etapa = 'evaluacion';
                    break;
                    
                case 'full':
                    // For full renewal, we start from document submission
                    $new_process->etapa = 'solicitud';
                    break;
            }
            
            // Insert new process
            $new_process_id = $DB->insert_record('local_conocer_procesos', $new_process);
            
            if (!$new_process_id) {
                throw new \moodle_exception('insertfailed', 'local_conocer_cert');
            }
            
            // Handle evidence files for simple renewal
            if ($data->renewal_type == 'simple' && !empty($data->evidence_filemanager)) {
                $this->process_evidence_files($data->evidence_filemanager, $candidate->id, $new_process_id);
            }
            
            // Store renewal information
            $renewal = new \stdClass();
            $renewal->proceso_id = $new_process_id;
            $renewal->certificado_original_id = $certificate->id;
            $renewal->tipo_renovacion = $data->renewal_type;
            $renewal->actividad_actual = $data->current_activity;
            $renewal->fecha_solicitud = time();
            $renewal->estado = 'pendiente';
            
            $renewal_id = $DB->insert_record('local_conocer_renovaciones', $renewal);
            
            if (!$renewal_id) {
                throw new \moodle_exception('insertfailed', 'local_conocer_cert');
            }
            
            // Update candidate status
            $DB->set_field('local_conocer_candidatos', 'estado', 'renovacion', ['id' => $candidate->id]);
            
            // Commit transaction
            $transaction->allow_commit();
            
            // Send notification to administrators
            $this->notify_admin_renewal($candidate->id, $new_process_id, $data->renewal_type);
            
            // If simple renewal, also notify evaluators
            if ($data->renewal_type == 'simple') {
                $this->notify_evaluators_renewal($candidate->id, $new_process_id);
            }
            
            return $new_process_id;
            
        } catch (\Exception $e) {
            if (isset($transaction)) {
                $transaction->rollback($e);
            }
            return false;
        }
    }
    
    /**
     * Process evidence files for simple renewal.
     *
     * @param int $itemid Draft file area item ID
     * @param int $candidate_id Candidate ID
     * @param int $process_id Process ID
     */
    private function process_evidence_files($itemid, $candidate_id, $process_id) {
        global $USER;
        
        $fs = get_file_storage();
        $context = \context_system::instance();
        
        // Copy files from draft area to plugin file area
        file_save_draft_area_files(
            $itemid,
            $context->id,
            'local_conocer_cert',
            'renovacion_evidencia',
            $process_id
        );
    }
    
    /**
     * Notify administrators about the renewal request.
     *
     * @param int $candidate_id Candidate ID
     * @param int $process_id Process ID
     * @param string $renewal_type Type of renewal
     * @return bool Success status
     */
    private function notify_admin_renewal($candidate_id, $process_id, $renewal_type) {
        global $DB, $USER;
        
        // Get candidate and competency details
        $candidate = $DB->get_record('local_conocer_candidatos', ['id' => $candidate_id]);
        $competencia = $DB->get_record('local_conocer_competencias', ['id' => $candidate->competencia_id]);
        $user = $DB->get_record('user', ['id' => $candidate->userid]);
        
        // Get admin users
        $context = \context_system::instance();
        $admins = get_users_by_capability($context, 'local/conocer_cert:managecandidates');
        
        $success = true;
        
        // Send notification to each admin
        foreach ($admins as $admin) {
            $result = \local_conocer_cert\util\notification::send($admin->id, 'certificado_renovacion_solicitud', [
                'firstname' => $admin->firstname,
                'lastname' => $admin->lastname,
                'candidate_name' => fullname($user),
                'competencia' => $competencia->nombre,
                'nivel' => $candidate->nivel,
                'renewal_type' => get_string('renewal_' . $renewal_type, 'local_conocer_cert'),
                'contexturl' => new \moodle_url('/local/conocer_cert/admin/view_renewal.php', ['id' => $process_id]),
                'contexturlname' => get_string('view_renewal', 'local_conocer_cert')
            ], $USER->id);
            
            if (!$result) {
                $success = false;
            }
        }
        
        return $success;
    }
    
    /**
     * Notify evaluators about the simple renewal request.
     *
     * @param int $candidate_id Candidate ID
     * @param int $process_id Process ID
     * @return bool Success status
     */
    private function notify_evaluators_renewal($candidate_id, $process_id) {
        global $DB, $USER;
        
        // Get candidate and competency details
        $candidate = $DB->get_record('local_conocer_candidatos', ['id' => $candidate_id]);
        $competencia = $DB->get_record('local_conocer_competencias', ['id' => $candidate->competencia_id]);
        $user = $DB->get_record('user', ['id' => $candidate->userid]);
        
        // Get evaluators for this competency
        $evaluators = \local_conocer_cert\evaluator\manager::get_available_evaluators($competencia->id);
        
        $success = true;
        
        // Send notification to each evaluator
        foreach ($evaluators as $evaluator) {
            $result = \local_conocer_cert\util\notification::send($evaluator->userid, 'certificado_renovacion_evaluador', [
                'firstname' => $evaluator->firstname,
                'lastname' => $evaluator->lastname,
                'candidate_name' => fullname($user),
                'competencia' => $competencia->nombre,
                'nivel' => $candidate->nivel,
                'contexturl' => new \moodle_url('/local/conocer_cert/evaluator/review_renewal.php', ['id' => $process_id]),
                'contexturlname' => get_string('review_renewal', 'local_conocer_cert')
            ], $USER->id);
            
            if (!$result) {
                $success = false;
            }
        }
        
        return $success;
    }
}
