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
 * Certification evaluation form for the CONOCER certification system.
 *
 * @package    local_conocer_cert
 * @copyright  2025 Sebastian Gonzalez Zepeda sgonzalez@infraestructuragis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_conocer_cert\forms;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for evaluating certification candidates.
 */
class certification_evaluation_form extends \moodleform {
    
    /**
     * Form definition.
     */
    public function definition() {
        global $DB;
        
        $mform = $this->_form;
        $process = $this->_customdata['process'] ?? null;
        $candidate = $this->_customdata['candidate'] ?? null;
        $competency = $this->_customdata['competency'] ?? null;
        $criteria = $this->_customdata['criteria'] ?? [];
        
        if (!$process || !$candidate || !$competency) {
            return;
        }
        
        // Evaluation information
        $mform->addElement('header', 'evaluationinfo', get_string('evaluation_information', 'local_conocer_cert'));
        
        // Evaluation criteria
        if (!empty($criteria)) {
            $mform->addElement('html', '<div class="criteria-container">');
            $mform->addElement('html', '<h4>' . get_string('evaluation_criteria', 'local_conocer_cert') . '</h4>');
            
            // Add criteria elements
            foreach ($criteria as $index => $criterion) {
                $mform->addElement('html', '<div class="criterion-item">');
                $mform->addElement('html', '<h5>' . ($index + 1) . '. ' . $criterion->nombre . '</h5>');
                $mform->addElement('html', '<p class="criterion-description">' . $criterion->descripcion . '</p>');
                
                // Rating scale for this criterion
                $mform->addElement('select', 'criterion_' . $criterion->id, get_string('criterion_evaluation', 'local_conocer_cert'), [
                    '' => get_string('select_evaluation', 'local_conocer_cert'),
                    '0' => get_string('not_demonstrated', 'local_conocer_cert'),
                    '1' => get_string('partially_demonstrated', 'local_conocer_cert'),
                    '2' => get_string('demonstrated', 'local_conocer_cert'),
                    '3' => get_string('fully_demonstrated', 'local_conocer_cert')
                ]);
                
                // Comments for this criterion
                $mform->addElement('textarea', 'criterion_comment_' . $criterion->id, 
                    get_string('criterion_comments', 'local_conocer_cert'), ['rows' => 2, 'cols' => 50]);
                $mform->setType('criterion_comment_' . $criterion->id, PARAM_TEXT);
                
                $mform->addElement('html', '</div>');
            }
            
            $mform->addElement('html', '</div>');
        }
        
        // Overall grade
        $mform->addElement('select', 'calificacion', get_string('overall_grade', 'local_conocer_cert'), [
            '' => get_string('select_grade', 'local_conocer_cert'),
            '0' => '0',
            '1' => '1',
            '2' => '2',
            '3' => '3',
            '4' => '4',
            '5' => '5',
            '6' => '6',
            '7' => '7',
            '8' => '8',
            '9' => '9',
            '10' => '10'
        ]);
        $mform->addRule('calificacion', get_string('required'), 'required');
        
        // Final result
        $mform->addElement('select', 'resultado', get_string('final_result', 'local_conocer_cert'), [
            '' => get_string('select_result', 'local_conocer_cert'),
            'aprobado' => get_string('resultado_aprobado', 'local_conocer_cert'),
            'rechazado' => get_string('resultado_rechazado', 'local_conocer_cert')
        ]);
        $mform->addRule('resultado', get_string('required'), 'required');
        
        // General comments
        $mform->addElement('textarea', 'comentarios', get_string('general_comments', 'local_conocer_cert'), 
            ['rows' => 4, 'cols' => 50]);
        $mform->setType('comentarios', PARAM_TEXT);
        
        // Recommendations
        $mform->addElement('textarea', 'recomendaciones', get_string('recommendations', 'local_conocer_cert'), 
            ['rows' => 4, 'cols' => 50]);
        $mform->setType('recomendaciones', PARAM_TEXT);
        
        // Hidden fields
        $mform->addElement('hidden', 'process_id', $process->id);
        $mform->setType('process_id', PARAM_INT);
        
        $mform->addElement('hidden', 'candidate_id', $candidate->id);
        $mform->setType('candidate_id', PARAM_INT);
        
        // Notification options
        $mform->addElement('advcheckbox', 'notificar_candidato', get_string('notify_candidate', 'local_conocer_cert'));
        $mform->setDefault('notificar_candidato', 1);
        
        // Buttons
        $this->add_action_buttons(true, get_string('submit_evaluation', 'local_conocer_cert'));
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
        
        // Validate that the result matches the grade
        if (!empty($data['calificacion']) && !empty($data['resultado'])) {
            if ($data['resultado'] == 'aprobado' && $data['calificacion'] < 7) {
                $errors['resultado'] = get_string('error_grade_result_mismatch', 'local_conocer_cert');
            } else if ($data['resultado'] == 'rechazado' && $data['calificacion'] >= 7) {
                $errors['resultado'] = get_string('error_grade_result_mismatch', 'local_conocer_cert');
            }
        }
        
        return $errors;
    }
    
    /**
     * Process form submission to submit evaluation.
     *
     * @return bool Success status
     */
    public function process_evaluation() {
        global $DB;
        
        $data = $this->get_data();
        if (!$data) {
            return false;
        }
        
        try {
            // Begin transaction
            $transaction = $DB->start_delegated_transaction();
            
            // Update process record
            $process = new \stdClass();
            $process->id = $data->process_id;
            $process->resultado = $data->resultado;
            $process->fecha_evaluacion = time();
            
            if ($data->resultado == 'aprobado') {
                $process->etapa = 'aprobado';
                $process->fecha_fin = time();
            } else {
                $process->etapa = 'rechazado';
                $process->fecha_fin = time();
            }
            
            $DB->update_record('local_conocer_procesos', $process);
            
            // Create evaluation record
            $evaluation = new \stdClass();
            $evaluation->proceso_id = $data->process_id;
            $evaluation->evaluador_id = $DB->get_field('local_conocer_procesos', 'evaluador_id', ['id' => $data->process_id]);
            $evaluation->calificacion = $data->calificacion;
            $evaluation->comentarios = $data->comentarios;
            $evaluation->recomendaciones = $data->recomendaciones;
            $evaluation->fecha_evaluacion = time();
            
            $evaluationid = $DB->insert_record('local_conocer_evaluaciones', $evaluation);
            
            // Process criteria evaluations
            $criteria = $this->_customdata['criteria'] ?? [];
            foreach ($criteria as $criterion) {
                $criterion_field = 'criterion_' . $criterion->id;
                $comment_field = 'criterion_comment_' . $criterion->id;
                
                if (isset($data->$criterion_field)) {
                    $criterion_eval = new \stdClass();
                    $criterion_eval->evaluacion_id = $evaluationid;
                    $criterion_eval->criterio_id = $criterion->id;
                    $criterion_eval->calificacion = $data->$criterion_field;
                    $criterion_eval->comentarios = isset($data->$comment_field) ? $data->$comment_field : '';
                    
                    $DB->insert_record('local_conocer_criterios_evaluacion', $criterion_eval);
                }
            }
            
            // Create certificate if approved
            if ($data->resultado == 'aprobado') {
                $this->create_certificate($data->process_id);
            }
            
            // Commit transaction
            $transaction->allow_commit();
            
            // Send notification if requested
            if (!empty($data->notificar_candidato)) {
                $this->notify_candidate($data->candidate_id, $data->process_id, $data->resultado);
            }
            
            // Trigger event
            $candidate = $DB->get_record('local_conocer_candidatos', ['id' => $data->candidate_id]);
            $context = \context_system::instance();
            $event = \local_conocer_cert\event\certification_completed::create_from_process(
                $DB->get_record('local_conocer_procesos', ['id' => $data->process_id]),
                $context,
                $candidate->userid
            );
            $event->trigger();
            
            return true;
            
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Create certificate for approved candidate.
     *
     * @param int $process_id Process ID
     * @return int Certificate ID or false on failure
     */
    private function create_certificate($process_id) {
        global $DB, $USER;
        
        // Get process details
        $process = $DB->get_record('local_conocer_procesos', ['id' => $process_id]);
        if (!$process || $process->resultado != 'aprobado') {
            return false;
        }
        
        // Get candidate details
        $candidate = $DB->get_record('local_conocer_candidatos', ['id' => $process->candidato_id]);
        if (!$candidate) {
            return false;
        }
        
        // Get competency details
        $competency = $DB->get_record('local_conocer_competencias', ['id' => $candidate->competencia_id]);
        if (!$competency) {
            return false;
        }
        
        // Generate certificate number
        $folio = $this->generate_certificate_number($candidate->competencia_id, $candidate->nivel);
        
        // Calculate expiration date (if applicable)
        $expiry_date = null;
        $duration = $DB->get_field('local_conocer_cert_config', 'value', ['name' => 'certificate_validity_years']);
        if ($duration) {
            $expiry_date = strtotime("+$duration years");
        }
        
        // Create certificate record
        $certificate = new \stdClass();
        $certificate->proceso_id = $process_id;
        $certificate->numero_folio = $folio;
        $certificate->fecha_emision = time();
        $certificate->fecha_vencimiento = $expiry_date;
        $certificate->emitido_por = $USER->id;
        $certificate->estatus = 'activo';
        $certificate->hash_verificacion = md5($folio . time() . $candidate->userid);
        
        $certificate_id = $DB->insert_record('local_conocer_certificados', $certificate);
        
        // Update process to include certificate ID
        $DB->set_field('local_conocer_procesos', 'certificado_id', $certificate_id, ['id' => $process_id]);
        
        return $certificate_id;
    }
    
    /**
     * Generate a unique certificate number.
     *
     * @param int $competency_id Competency ID
     * @param int $level Level
     * @return string Certificate number
     */
    private function generate_certificate_number($competency_id, $level) {
        global $DB;
        
        // Get competency code
        $code = $DB->get_field('local_conocer_competencias', 'codigo', ['id' => $competency_id]);
        
        // Get the current year
        $year = date('Y');
        
        // Get the sequential number
        $sequence_number = $DB->count_records('local_conocer_certificados') + 1;
        
        // Format: CODE-LEVEL-YEAR-SEQUENCE
        $folio = sprintf('%s-%d-%d-%06d', $code, $level, $year, $sequence_number);
        
        return $folio;
    }
    
    /**
     * Notify candidate about the evaluation result.
     *
     * @param int $candidate_id Candidate ID
     * @param int $process_id Process ID
     * @param string $result Result of the evaluation
     * @return bool Success status
     */
    private function notify_candidate($candidate_id, $process_id, $result) {
        global $DB, $USER;
        
        // Get candidate details
        $candidate = $DB->get_record('local_conocer_candidatos', ['id' => $candidate_id]);
        $candidato_user = $DB->get_record('user', ['id' => $candidate->userid]);
        $competencia = $DB->get_record('local_conocer_competencias', ['id' => $candidate->competencia_id]);
        
        // Send notification
        return \local_conocer_cert\util\notification::send($candidate->userid, 'proceso_completado', [
            'firstname' => $candidato_user->firstname,
            'lastname' => $candidato_user->lastname,
            'competencia' => $competencia->nombre,
            'nivel' => $candidate->nivel,
            'resultado' => get_string('resultado_' . $result, 'local_conocer_cert'),
            'evaluador_nombre' => fullname($USER),
            'contexturl' => new \moodle_url('/local/conocer_cert/candidate/view_certification.php', ['id' => $process_id]),
            'contexturlname' => get_string('view_certification', 'local_conocer_cert')
        ], $USER->id);
    }
}
