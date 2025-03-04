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
 * Document review form for the CONOCER certification system.
 *
 * @package    local_conocer_cert
 * @copyright  2025 Sebastian Gonzalez Zepeda sgonzalez@infraestructuragis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_conocer_cert\forms;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for reviewing candidate documents.
 */
class document_review_form extends \moodleform {
    
    /**
     * Form definition.
     */
    public function definition() {
        global $DB;
        
        $mform = $this->_form;
        $document = $this->_customdata['document'] ?? null;
        $candidate = $this->_customdata['candidate'] ?? null;
        
        if (!$document || !$candidate) {
            return;
        }
        
        // Get document type name
        $document_types = local_conocer_cert_get_document_types();
        $doc_type_name = isset($document_types[$document->tipo]) ? $document_types[$document->tipo]['name'] : $document->tipo;
        
        // Document information
        $mform->addElement('header', 'documentinfo', get_string('document_information', 'local_conocer_cert'));
        
        // Display document details
        $mform->addElement('static', 'document_name', get_string('document_name', 'local_conocer_cert'), $document->nombre_archivo);
        $mform->addElement('static', 'document_type', get_string('document_type', 'local_conocer_cert'), $doc_type_name);
        $mform->addElement('static', 'upload_date', get_string('upload_date', 'local_conocer_cert'), userdate($document->fecha_subida));
        
        if (!empty($document->comentarios)) {
            $mform->addElement('static', 'document_comments', get_string('document_comments', 'local_conocer_cert'), $document->comentarios);
        }
        
        // Current status
        $status_text = get_string('doc_status_' . $document->estado, 'local_conocer_cert');
        $mform->addElement('static', 'current_status', get_string('current_status', 'local_conocer_cert'), $status_text);
        
        // Document preview link
        $view_url = new \moodle_url('/local/conocer_cert/document.php', ['id' => $document->id, 'action' => 'view']);
        $mform->addElement('html', '<div class="form-group row">');
        $mform->addElement('html', '<div class="col-md-3 col-form-label d-flex justify-content-md-end">' . 
            get_string('document_preview', 'local_conocer_cert') . '</div>');
        $mform->addElement('html', '<div class="col-md-9"><a href="' . $view_url . '" class="btn btn-info" target="_blank">' . 
            get_string('view_document', 'local_conocer_cert') . '</a></div>');
        $mform->addElement('html', '</div>');
        
        // Review section
        $mform->addElement('header', 'reviewheader', get_string('document_review', 'local_conocer_cert'));
        
        // Document status
        $status_options = [
            'pendiente' => get_string('doc_status_pendiente', 'local_conocer_cert'),
            'aprobado' => get_string('doc_status_aprobado', 'local_conocer_cert'),
            'rechazado' => get_string('doc_status_rechazado', 'local_conocer_cert')
        ];
        $mform->addElement('select', 'estado', get_string('document_status', 'local_conocer_cert'), $status_options);
        $mform->setDefault('estado', $document->estado);
        
        // Review comments
        $mform->addElement('textarea', 'comentarios_revision', get_string('review_comments', 'local_conocer_cert'), 
            ['rows' => 4, 'cols' => 50]);
        $mform->setType('comentarios_revision', PARAM_TEXT);
        
        // Send notification to candidate
        $mform->addElement('advcheckbox', 'notificar_candidato', get_string('notify_candidate', 'local_conocer_cert'));
        $mform->setDefault('notificar_candidato', 1);
        
        // Hidden fields
        $mform->addElement('hidden', 'document_id', $document->id);
        $mform->setType('document_id', PARAM_INT);
        
        $mform->addElement('hidden', 'candidate_id', $candidate->id);
        $mform->setType('candidate_id', PARAM_INT);
        
        // Buttons
        $this->add_action_buttons(true, get_string('save_review', 'local_conocer_cert'));
    }
    
    /**
     * Process form submission to save document review.
     *
     * @return bool Success status
     */
    public function process_review() {
        global $DB, $USER;
        
        $data = $this->get_data();
        if (!$data) {
            return false;
        }
        
        try {
            // Begin transaction
            $transaction = $DB->start_delegated_transaction();
            
            // Update document record
            $document = new \stdClass();
            $document->id = $data->document_id;
            $document->estado = $data->estado;
            $document->comentarios_revision = $data->comentarios_revision;
            $document->revisado_por = $USER->id;
            $document->fecha_revision = time();
            
            $result = $DB->update_record('local_conocer_documentos', $document);
            
            if (!$result) {
                throw new \moodle_exception('updatefailed', 'local_conocer_cert');
            }
            
            // Check if we need to update candidate status
            if ($data->estado == 'rechazado') {
                // If a document is rejected, set candidate status to pendiente
                $DB->set_field('local_conocer_candidatos', 'estado', 'pendiente', ['id' => $data->candidate_id]);
                
                // Find process in documentacion stage and set it back to solicitud
                $sql = "SELECT id FROM {local_conocer_procesos} 
                        WHERE candidato_id = :candidateid AND etapa = 'documentacion'";
                $process_id = $DB->get_field_sql($sql, ['candidateid' => $data->candidate_id]);
                
                if ($process_id) {
                    $DB->set_field('local_conocer_procesos', 'etapa', 'solicitud', ['id' => $process_id]);
                }
            } else if ($data->estado == 'aprobado') {
                // Check if all required documents are approved
                $this->check_all_documents_approved($data->candidate_id);
            }
            
            // Commit transaction
            $transaction->allow_commit();
            
            // Send notification if requested
            if (!empty($data->notificar_candidato)) {
                $this->notify_candidate($data->document_id, $data->candidate_id, $data->estado, $data->comentarios_revision);
            }
            
            return true;
            
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Check if all required documents are approved and update candidate status if needed.
     *
     * @param int $candidate_id Candidate ID
     */
    private function check_all_documents_approved($candidate_id) {
        global $DB;
        
        // Get candidate details
        $candidate = $DB->get_record('local_conocer_candidatos', ['id' => $candidate_id]);
        if (!$candidate) {
            return;
        }
        
        // Get competency to determine required documents
        $competency = $DB->get_record('local_conocer_competencias', ['id' => $candidate->competencia_id]);
        
        // Get required document types
        $required_documents = [];
        if ($competency && !empty($competency->documentos_requeridos)) {
            $required_documents = explode(',', $competency->documentos_requeridos);
        } else {
            // Default required documents if not specified
            $required_documents = ['id_oficial', 'curp_doc', 'comprobante_domicilio', 'evidencia_laboral', 'fotografia'];
        }
        
        // Check if all required documents are approved
        $all_approved = true;
        foreach ($required_documents as $doc_type) {
            $doc = $DB->get_record('local_conocer_documentos', [
                'candidato_id' => $candidate_id,
                'tipo' => $doc_type
            ]);
            
            if (!$doc || $doc->estado != 'aprobado') {
                $all_approved = false;
                break;
            }
        }
        
        if ($all_approved) {
            // All required documents are approved, update candidate status
            $DB->set_field('local_conocer_candidatos', 'estado', 'evaluacion', ['id' => $candidate_id]);
            
            // Find process in documentacion stage and update to evaluacion
            $sql = "SELECT id FROM {local_conocer_procesos} 
                    WHERE candidato_id = :candidateid AND etapa = 'documentacion'";
            $process_id = $DB->get_field_sql($sql, ['candidateid' => $candidate_id]);
            
            if ($process_id) {
                $DB->set_field('local_conocer_procesos', 'etapa', 'evaluacion', ['id' => $process_id]);
                
                // Send notification to indicate that all documents are approved
                $user = $DB->get_record('user', ['id' => $candidate->userid]);
                
                \local_conocer_cert\util\notification::send($user->id, 'documentos_aprobados', [
                    'firstname' => $user->firstname,
                    'lastname' => $user->lastname,
                    'competencia' => $competency->nombre,
                    'nivel' => $candidate->nivel,
                    'contexturl' => new \moodle_url('/local/conocer_cert/candidate/view_process.php', ['id' => $process_id]),
                    'contexturlname' => get_string('view_process', 'local_conocer_cert')
                ]);
            }
        }
    }
    
    /**
     * Notify candidate about the document review.
     *
     * @param int $document_id Document ID
     * @param int $candidate_id Candidate ID
     * @param string $status Document status
     * @param string $comments Review comments
     * @return bool Success status
     */
    private function notify_candidate($document_id, $candidate_id, $status, $comments) {
        global $DB, $USER;
        
        // Get document and candidate details
        $document = $DB->get_record('local_conocer_documentos', ['id' => $document_id]);
        $candidate = $DB->get_record('local_conocer_candidatos', ['id' => $candidate_id]);
        
        if (!$document || !$candidate) {
            return false;
        }
        
        $user = $DB->get_record('user', ['id' => $candidate->userid]);
        $competencia = $DB->get_record('local_conocer_competencias', ['id' => $candidate->competencia_id]);
        
        // Get document type name
        $document_types = local_conocer_cert_get_document_types();
        $doc_type_name = isset($document_types[$document->tipo]) ? $document_types[$document->tipo]['name'] : $document->tipo;
        
        // Determine notification type based on status
        $notification_type = ($status == 'aprobado') ? 'documento_aprobado' : 'documento_rechazado';
        
        // Send notification
        return \local_conocer_cert\util\notification::send($user->id, $notification_type, [
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'documento' => $doc_type_name,
            'nombre_archivo' => $document->nombre_archivo,
            'competencia' => $competencia->nombre,
            'nivel' => $candidate->nivel,
            'comentarios' => $comments,
            'revisor' => fullname($USER),
            'contexturl' => new \moodle_url('/local/conocer_cert/candidate/view_documents.php', ['id' => $candidate_id]),
            'contexturlname' => get_string('view_documents', 'local_conocer_cert')
        ], $USER->id);
    }
}