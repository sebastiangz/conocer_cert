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
 * Document upload form for the CONOCER certification system.
 *
 * @package    local_conocer_cert
 * @copyright  2025 Sebastian Gonzalez Zepeda sgonzalez@infraestructuragis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_conocer_cert\forms;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/local/conocer_cert/locallib.php');

/**
 * Form for uploading documents for certification.
 */
class document_upload_form extends \moodleform {
    
    /** @var array Document types information */
    protected $document_types = [];
    
    /** @var array Required document types */
    protected $required_documents = [];
    
    /** @var array Already uploaded documents */
    protected $uploaded_documents = [];
    
    /**
     * Form definition.
     */
    public function definition() {
        global $DB;
        
        $mform = $this->_form;
        $candidate = $this->_customdata['candidate'] ?? null;
        $context = $this->_customdata['context'] ?? \context_system::instance();
        
        if (!$candidate) {
            return;
        }
        
        // Get competency to determine required documents
        $competency = $DB->get_record('local_conocer_competencias', ['id' => $candidate->competencia_id]);
        if ($competency && !empty($competency->documentos_requeridos)) {
            $this->required_documents = explode(',', $competency->documentos_requeridos);
        } else {
            // Default required documents if not specified
            $this->required_documents = ['id_oficial', 'curp_doc', 'comprobante_domicilio', 'evidencia_laboral', 'fotografia'];
        }
        
        // Get already uploaded documents
        $this->uploaded_documents = $DB->get_records('local_conocer_documentos', ['candidato_id' => $candidate->id], '', 'tipo, id, nombre_archivo, estado');
        
        // Document upload section
        $mform->addElement('header', 'documentheader', get_string('document_upload', 'local_conocer_cert'));
        
        // Hidden field for candidate ID
        $mform->addElement('hidden', 'candidate_id', $candidate->id);
        $mform->setType('candidate_id', PARAM_INT);
        
        // Define document types
        $this->document_types = local_conocer_cert_get_document_types();
        
        // Add document upload fields
        foreach ($this->document_types as $type => $info) {
            // Check if this document type is required
            $is_required = in_array($type, $this->required_documents);
            
            // Check if already uploaded
            $already_uploaded = isset($this->uploaded_documents[$type]);
            
            // Create group for each document type
            $mform->addElement('html', '<div class="document-upload-item" id="document-item-' . $type . '">');
            
            // Document title with required indicator if needed
            $doc_title = $info['name'];
            if ($is_required) {
                $doc_title .= ' ' . html_writer::tag('span', get_string('required'), ['class' => 'badge badge-danger']);
            }
            
            $mform->addElement('html', '<h5>' . $doc_title . '</h5>');
            
            // Document description
            if (!empty($info['description'])) {
                $mform->addElement('html', '<p class="small text-muted">' . $info['description'] . '</p>');
            }
            
            // Show existing document if uploaded
            if ($already_uploaded) {
                $doc = $this->uploaded_documents[$type];
                $status_text = get_string('doc_status_' . $doc->estado, 'local_conocer_cert');
                $status_class = '';
                
                switch ($doc->estado) {
                    case 'aprobado':
                        $status_class = 'success';
                        break;
                    case 'rechazado':
                        $status_class = 'danger';
                        break;
                    default:
                        $status_class = 'warning';
                }
                
                $mform->addElement('html', '<div class="document-status alert alert-' . $status_class . '">');
                $mform->addElement('html', '<p><strong>' . get_string('uploaded_document', 'local_conocer_cert') . 
                    ':</strong> ' . $doc->nombre_archivo . '</p>');
                $mform->addElement('html', '<p><strong>' . get_string('status', 'local_conocer_cert') . 
                    ':</strong> ' . $status_text . '</p>');
                
                // Add view link
                $view_url = new \moodle_url('/local/conocer_cert/document.php', [
                    'id' => $doc->id,
                    'action' => 'view'
                ]);
                
                $mform->addElement('html', '<a href="' . $view_url . '" class="btn btn-sm btn-info" target="_blank">' . 
                    get_string('view_document', 'local_conocer_cert') . '</a>');
                
                // Add replace option only if document is not approved
                if ($doc->estado != 'aprobado') {
                    $mform->addElement('advcheckbox', 'replace_' . $type, get_string('replace_document', 'local_conocer_cert'));
                    $mform->addElement('filepicker', 'document_' . $type, get_string('upload_replacement', 'local_conocer_cert'), 
                        null, $this->get_filemanager_options($type));
                    $mform->disabledIf('document_' . $type, 'replace_' . $type, 'notchecked');
                }
                
                $mform->addElement('html', '</div>');
            } else {
                // Add file upload field
                $mform->addElement('filepicker', 'document_' . $type, get_string('select_file', 'local_conocer_cert'), 
                    null, $this->get_filemanager_options($type));
                
                if ($is_required) {
                    $mform->addRule('document_' . $type, get_string('required'), 'required');
                }
            }
            
            $mform->addElement('html', '</div>');
        }
        
        // Add comments field
        $mform->addElement('textarea', 'comments', get_string('upload_comments', 'local_conocer_cert'), 
            ['rows' => 3, 'cols' => 50]);
        $mform->setType('comments', PARAM_TEXT);
        
        // Buttons
        $this->add_action_buttons(true, get_string('upload_documents', 'local_conocer_cert'));
    }
    
    /**
     * Get file manager options for document type
     *
     * @param string $type Document type
     * @return array File manager options
     */
    protected function get_filemanager_options($type) {
        $filemanager_options = [];
        
        // Set allowed file types based on document type
        switch ($type) {
            case 'fotografia':
                $filemanager_options = [
                    'maxbytes' => \local_conocer_cert\util\file_validator::MAX_PHOTO_SIZE,
                    'accepted_types' => \local_conocer_cert\util\file_validator::ALLOWED_PHOTO_MIMES
                ];
                break;
                
            case 'id_oficial':
            case 'curp_doc':
            case 'comprobante_domicilio':
            case 'evidencia_laboral':
                $filemanager_options = [
                    'maxbytes' => \local_conocer_cert\util\file_validator::MAX_FILE_SIZE,
                    'accepted_types' => \local_conocer_cert\util\file_validator::ALLOWED_ID_MIMES
                ];
                break;
                
            case 'acta_constitutiva':
            case 'rfc_doc':
            case 'poder_notarial':
            case 'comprobante_fiscal':
                $filemanager_options = [
                    'maxbytes' => \local_conocer_cert\util\file_validator::MAX_FILE_SIZE,
                    'accepted_types' => \local_conocer_cert\util\file_validator::ALLOWED_OFFICIAL_MIMES
                ];
                break;
                
            default:
                $filemanager_options = [
                    'maxbytes' => \local_conocer_cert\util\file_validator::MAX_FILE_SIZE,
                    'accepted_types' => \local_conocer_cert\util\file_validator::ALLOWED_ID_MIMES
                ];
        }
        
        return $filemanager_options;
    }
    
    /**
     * Validation of the form.
     *
     * @param array $data Data from the form
     * @param array $files Files from the form
     * @return array Errors
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        
        // Check required documents
        foreach ($this->required_documents as $type) {
            // Skip if already uploaded and not replacing
            if (isset($this->uploaded_documents[$type]) && 
                (!isset($data['replace_' . $type]) || !$data['replace_' . $type])) {
                continue;
            }
            
            // Check if document is uploaded
            if (!isset($data['document_' . $type]) || empty($data['document_' . $type])) {
                $errors['document_' . $type] = get_string('required_document_missing', 'local_conocer_cert', 
                    $this->document_types[$type]['name']);
            }
        }
        
        return $errors;
    }
    
    /**
     * Process form submission to upload documents.
     *
     * @return bool Success status
     */
    public function process_uploads() {
        global $DB, $USER;
        
        $data = $this->get_data();
        if (!$data) {
            return false;
        }
        
        $candidate_id = $data->candidate_id;
        $context = \context_system::instance();
        $fs = get_file_storage();
        $success = true;
        
        // Process each document type
        foreach ($this->document_types as $type => $info) {
            // Skip if already uploaded and not replacing
            if (isset($this->uploaded_documents[$type]) && 
                (!isset($data->{'replace_' . $type}) || !$data->{'replace_' . $type})) {
                continue;
            }
            
            // Skip if no file uploaded
            if (!isset($data->{'document_' . $type}) || empty($data->{'document_' . $type})) {
                continue;
            }
            
            try {
                // Begin transaction
                $transaction = $DB->start_delegated_transaction();
                
                // Delete existing document if replacing
                if (isset($this->uploaded_documents[$type])) {
                    $DB->delete_records('local_conocer_documentos', [
                        'id' => $this->uploaded_documents[$type]->id,
                        'candidato_id' => $candidate_id
                    ]);
                    
                    // Delete existing file
                    $fs->delete_area_files($context->id, 'local_conocer_cert', 'candidato_documento', 
                        $this->uploaded_documents[$type]->id);
                }
                
                // Prepare document record
                $document = new \stdClass();
                $document->candidato_id = $candidate_id;
                $document->tipo = $type;
                $document->nombre_archivo = '';  // Will be set after file is saved
                $document->estado = 'pendiente';
                $document->fecha_subida = time();
                $document->subido_por = $USER->id;
                $document->comentarios = $data->comments ?? '';
                
                // Save document record
                $document_id = $DB->insert_record('local_conocer_documentos', $document);
                
                // Save the file
                $file_info = file_get_submitted_draft_itemid('document_' . $type);
                $file_record = [
                    'contextid' => $context->id,
                    'component' => 'local_conocer_cert',
                    'filearea' => 'candidato_documento',
                    'itemid' => $document_id,
                    'filepath' => '/',
                    'userid' => $USER->id
                ];
                
                // Get original file from draft area
                $files = $fs->get_area_files(\context_user::instance($USER->id)->id, 'user', 'draft', $file_info, 'itemid', false);
                
                if (!empty($files)) {
                    $file = reset($files);
                    
                    // Validate file
                    $validate_result = \local_conocer_cert\util\file_validator::validate_file(
                        $file, 
                        \local_conocer_cert\util\file_validator::is_allowed_mimetype($file->get_mimetype(), $type),
                        \local_conocer_cert\util\file_validator::get_max_filesize($type)
                    );
                    
                    if (!$validate_result['valid']) {
                        throw new \moodle_exception('invalidfile', 'local_conocer_cert', '', $validate_result['message']);
                    }
                    
                    // Create file in permanent storage
                    $file_record['filename'] = $file->get_filename();
                    $stored_file = $fs->create_file_from_storedfile($file_record, $file);
                    
                    // Update document record with filename
                    $document->id = $document_id;
                    $document->nombre_archivo = $stored_file->get_filename();
                    $DB->update_record('local_conocer_documentos', $document);
                    
                    // Update candidate status if all required documents are uploaded
                    $this->update_candidate_status($candidate_id);
                    
                    // Commit transaction
                    $transaction->allow_commit();
                } else {
                    throw new \moodle_exception('nofilesubmitted', 'local_conocer_cert');
                }
            } catch (\Exception $e) {
                $success = false;
                if (isset($transaction)) {
                    $transaction->rollback($e);
                }
            }
        }
        
        return $success;
    }
    
    /**
     * Update candidate status if all required documents are uploaded.
     *
     * @param int $candidate_id Candidate ID
     */
    private function update_candidate_status($candidate_id) {
        global $DB;
        
        // Get all uploaded document types for this candidate
        $sql = "SELECT tipo FROM {local_conocer_documentos} WHERE candidato_id = :candidateid";
        $uploaded_types = $DB->get_fieldset_sql($sql, ['candidateid' => $candidate_id]);
        
        // Check if all required documents are uploaded
        $missing_docs = array_diff($this->required_documents, $uploaded_types);
        
        if (empty($missing_docs)) {
            // All required documents are uploaded, update candidate status
            $DB->set_field('local_conocer_candidatos', 'estado', 'documentacion', ['id' => $candidate_id]);
            
            // Check if candidate is still in solicitud stage and update to documentacion if needed
            $sql = "SELECT id FROM {local_conocer_procesos} 
                    WHERE candidato_id = :candidateid AND etapa = 'solicitud'";
            $process_id = $DB->get_field_sql($sql, ['candidateid' => $candidate_id]);
            
            if ($process_id) {
                $DB->set_field('local_conocer_procesos', 'etapa', 'documentacion', ['id' => $process_id]);
            }
            
            // Send notification to candidate
            $candidate = $DB->get_record('local_conocer_candidatos', ['id' => $candidate_id]);
            $competencia = $DB->get_record('local_conocer_competencias', ['id' => $candidate->competencia_id]);
            $user = $DB->get_record('user', ['id' => $candidate->userid]);
            
            \local_conocer_cert\util\notification::send($user->id, 'documentos_recibidos', [
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'competencia' => $competencia->nombre,
                'nivel' => $candidate->nivel,
                'contexturl' => new \moodle_url('/local/conocer_cert/candidate/view_documents.php', ['id' => $candidate_id]),
                'contexturlname' => get_string('view_documents', 'local_conocer_cert')
            ]);
        }
    }
}
