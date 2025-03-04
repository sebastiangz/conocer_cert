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
 * Form for assigning an evaluator to a candidate.
 *
 * @package    local_conocer_cert
 * @copyright  2025 Sebastian Gonzalez Zepeda sgonzalez@infraestructuragis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_conocer_cert\forms;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for assigning evaluators to candidates.
 */
class assign_evaluator_form extends \moodleform {
    
    /**
     * Form definition.
     */
    public function definition() {
        global $DB;
        
        $mform = $this->_form;
        $candidate = $this->_customdata['candidate'] ?? null;
        
        if (!$candidate) {
            return;
        }
        
        // Get candidate details
        $user = $DB->get_record('user', ['id' => $candidate->userid]);
        $competencia = $DB->get_record('local_conocer_competencias', ['id' => $candidate->competencia_id]);
        
        // Candidate information
        $mform->addElement('header', 'candidateinfo', get_string('candidate_information', 'local_conocer_cert'));
        
        // Add static fields with candidate information
        $mform->addElement('static', 'candidato_nombre', get_string('candidate_name', 'local_conocer_cert'), fullname($user));
        $mform->addElement('static', 'candidato_email', get_string('email'), $user->email);
        $mform->addElement('static', 'candidato_competencia', get_string('competency', 'local_conocer_cert'), $competencia->nombre);
        $mform->addElement('static', 'candidato_codigo', get_string('competency_code', 'local_conocer_cert'), $competencia->codigo);
        $mform->addElement('static', 'candidato_nivel', get_string('level', 'local_conocer_cert'), get_string('level' . $candidate->nivel, 'local_conocer_cert'));
        
        // Hidden field for candidate ID
        $mform->addElement('hidden', 'candidate_id', $candidate->id);
        $mform->setType('candidate_id', PARAM_INT);
        
        // Evaluator assignment
        $mform->addElement('header', 'evaluatorinfo', get_string('evaluator_assignment', 'local_conocer_cert'));
        
        // Get available evaluators for this competency
        $evaluators = self::get_available_evaluators($competencia->id);
        
        if (empty($evaluators)) {
            $mform->addElement('static', 'no_evaluators', '', get_string('no_available_evaluators', 'local_conocer_cert'));
        } else {
            // Evaluator selector
            $mform->addElement('select', 'evaluator_id', get_string('select_evaluator', 'local_conocer_cert'), $evaluators);
            $mform->addRule('evaluator_id', get_string('required'), 'required');
            
            // Add comments field
            $mform->addElement('textarea', 'comentarios', get_string('assignment_comments', 'local_conocer_cert'), 
                ['rows' => 3, 'cols' => 50]);
            $mform->setType('comentarios', PARAM_TEXT);
            
            // Notification options
            $mform->addElement('advcheckbox', 'notificar_evaluador', get_string('notify_evaluator', 'local_conocer_cert'));
            $mform->setDefault('notificar_evaluador', 1);
            
            $mform->addElement('advcheckbox', 'notificar_candidato', get_string('notify_candidate', 'local_conocer_cert'));
            $mform->setDefault('notificar_candidato', 1);
            
            // Buttons
            $this->add_action_buttons(true, get_string('assign_evaluator', 'local_conocer_cert'));
        }
    }
    
    /**
     * Get available evaluators for a competency.
     *
     * @param int $competencia_id Competency ID
     * @return array List of evaluators
     */
    private static function get_available_evaluators($competencia_id) {
        global $DB;
        
        $evaluators = [];
        
        // Query to find evaluators that can evaluate this competency
        $sql = "SELECT e.id, e.userid, " . $DB->sql_concat('u.firstname', "' '", 'u.lastname') . " AS fullname,
                       e.disponibilidad, e.max_candidatos, COUNT(p.id) AS current_assignments
                FROM {local_conocer_evaluadores} e
                JOIN {user} u ON e.userid = u.id
                LEFT JOIN {local_conocer_procesos} p ON e.userid = p.evaluador_id AND p.etapa = 'evaluacion'
                WHERE e.estatus = 'activo'
                AND (e.competencias LIKE :comp1 OR e.competencias LIKE :comp2 OR e.competencias LIKE :comp3)
                GROUP BY e.id, e.userid, fullname, e.disponibilidad, e.max_candidatos
                HAVING COUNT(p.id) < e.max_candidatos
                ORDER BY current_assignments, u.firstname, u.lastname";
        
        $params = [
            'comp1' => '%"' . $competencia_id . '"%',
            'comp2' => '%[' . $competencia_id . ',%',
            'comp3' => '%,' . $competencia_id . ',%'
        ];
        
        $records = $DB->get_records_sql($sql, $params);
        
        // Format evaluators as select options
        foreach ($records as $record) {
            $evaluators[$record->userid] = $record->fullname . ' (' . 
                get_string('availability_' . $record->disponibilidad, 'local_conocer_cert') . ', ' . 
                get_string('current_workload', 'local_conocer_cert', $record->current_assignments . '/' . $record->max_candidatos) . ')';
        }
        
        return $evaluators;
    }
    
    /**
     * Process form submission to assign evaluator.
     *
     * @return bool Success status
     */
    public function process_assignment() {
        global $DB;
        
        $data = $this->get_data();
        if (!$data) {
            return false;
        }
        
        try {
            // Use the evaluator manager to assign the evaluator to the candidate
            $result = \local_conocer_cert\evaluator\manager::assign_evaluator_to_candidate(
                $data->candidate_id,
                $data->evaluator_id,
                $data->comentarios
            );
            
            if (!$result) {
                return false;
            }
            
            // Get process record
            $process = $DB->get_record_sql(
                "SELECT p.id FROM {local_conocer_procesos} p 
                 WHERE p.candidato_id = :candidateid AND p.evaluador_id = :evaluatorid",
                ['candidateid' => $data->candidate_id, 'evaluatorid' => $data->evaluator_id]
            );
            
            if (!$process) {
                return false;
            }
            
            // Send notifications if requested
            if (!empty($data->notificar_evaluador)) {
                $this->notify_evaluator($data->evaluator_id, $data->candidate_id, $process->id);
            }
            
            if (!empty($data->notificar_candidato)) {
                $this->notify_candidate($data->candidate_id, $data->evaluator_id, $process->id);
            }
            
            return true;
            
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Notify evaluator about the assignment.
     *
     * @param int $evaluator_id Evaluator ID
     * @param int $candidate_id Candidate ID
     * @param int $process_id Process ID
     * @return bool Success status
     */
    private function notify_evaluator($evaluator_id, $candidate_id, $process_id) {
        global $DB, $USER;
        
        // Get candidate details
        $candidate = $DB->get_record('local_conocer_candidatos', ['id' => $candidate_id]);
        $candidato_user = $DB->get_record('user', ['id' => $candidate->userid]);
        $competencia = $DB->get_record('local_conocer_competencias', ['id' => $candidate->competencia_id]);
        
        // Get evaluator details
        $evaluador_user = $DB->get_record('user', ['id' => $evaluator_id]);
        
        // Send notification
        return \local_conocer_cert\util\notification::send($evaluator_id, 'evaluador_nueva_asignacion', [
            'firstname' => $evaluador_user->firstname,
            'lastname' => $evaluador_user->lastname,
            'candidate_name' => fullname($candidato_user),
            'competencia' => $competencia->nombre,
            'nivel' => $candidate->nivel,
            'proceso_id' => $process_id,
            'administrador' => fullname($USER),
            'contexturl' => new \moodle_url('/local/conocer_cert/evaluator/evaluate.php', ['id' => $candidate_id]),
            'contexturlname' => get_string('evaluate_candidate', 'local_conocer_cert')
        ], $USER->id);
    }
    
    /**
     * Notify candidate about the evaluator assignment.
     *
     * @param int $candidate_id Candidate ID
     * @param int $evaluator_id Evaluator ID
     * @param int $process_id Process ID
     * @return bool Success status
     */
    private function notify_candidate($candidate_id, $evaluator_id, $process_id) {
        global $DB, $USER;
        
        // Get candidate details
        $candidate = $DB->get_record('local_conocer_candidatos', ['id' => $candidate_id]);
        $candidato_user = $DB->get_record('user', ['id' => $candidate->userid]);
        $competencia = $DB->get_record('local_conocer_competencias', ['id' => $candidate->competencia_id]);
        
        // Get evaluator details
        $evaluador_user = $DB->get_record('user', ['id' => $evaluator_id]);
        
        // Send notification
        return \local_conocer_cert\util\notification::send($candidate->userid, 'evaluador_asignado', [
            'firstname' => $candidato_user->firstname,
            'lastname' => $candidato_user->lastname,
            'competencia' => $competencia->nombre,
            'nivel' => $candidate->nivel,
            'evaluador_nombre' => fullname($evaluador_user),
            'proceso_id' => $process_id,
            'contexturl' => new \moodle_url('/local/conocer_cert/candidate/view_process.php', ['id' => $process_id]),
            'contexturlname' => get_string('view_process', 'local_conocer_cert')
        ], $USER->id);
    }
}