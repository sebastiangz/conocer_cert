<?php
// Archivo: local/conocer_cert/classes/forms/evaluator_assignment_form.php
// Formulario para asignación de evaluadores a candidatos

namespace local_conocer_cert\forms;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Formulario para asignar evaluadores a candidatos
 */
class evaluator_assignment_form extends \moodleform {
    
    function definition() {
        global $DB;
        
        $mform = $this->_form;
        $candidato = $this->_customdata['candidato'] ?? null;
        $proceso = $this->_customdata['proceso'] ?? null;
        
        // Sección de información del candidato
        $mform->addElement('header', 'candidateinfo', get_string('candidateinfo', 'local_conocer_cert'));
        
        // Obtener datos del usuario asociado al candidato
        $usuario = $DB->get_record('user', ['id' => $candidato->userid]);
        
        // Mostrar nombre del candidato (solo lectura)
        $mform->addElement('static', 'nombre_candidato', get_string('fullname', 'local_conocer_cert'), fullname($usuario));
        
        // Obtener datos de la competencia
        $competencia = $DB->get_record('local_conocer_competencias', ['id' => $candidato->competencia_id]);
        
        // Mostrar competencia y nivel (solo lectura)
        $mform->addElement('static', 'competencia', get_string('competency', 'local_conocer_cert'), 
            $competencia->nombre . ' (' . $competencia->codigo . ') - ' . 
            get_string('level', 'local_conocer_cert') . ' ' . $candidato->nivel);
        
        // Sección de selección de evaluador
        $mform->addElement('header', 'evaluatorheader', get_string('evaluatorselection', 'local_conocer_cert'));
        
        // Obtener evaluadores disponibles para esta competencia
        $evaluadores = \local_conocer_cert\evaluator\manager::get_available_evaluators($candidato->competencia_id);
        
        $opciones_evaluadores = [];
        foreach ($evaluadores as $evaluador) {
            $opciones_evaluadores[$evaluador->userid] = fullname($evaluador) . 
                ' (' . get_string('experience_years', 'local_conocer_cert', $evaluador->experiencia_anios) . ')';
        }
        
        if (empty($opciones_evaluadores)) {
            $mform->addElement('static', 'no_evaluadores', '', 
                get_string('no_evaluators_available', 'local_conocer_cert'));
        } else {
            // Selector de evaluador
            $mform->addElement('select', 'evaluador_id', get_string('select_evaluator', 'local_conocer_cert'), 
                $opciones_evaluadores);
            $mform->addRule('evaluador_id', get_string('required'), 'required');
            
            // Si ya hay un evaluador asignado, seleccionarlo por defecto
            if ($proceso && $proceso->evaluador_id) {
                $mform->setDefault('evaluador_id', $proceso->evaluador_id);
            }
        }
        
        // Fecha estimada de evaluación
        $mform->addElement('date_selector', 'fecha_estimada', get_string('estimated_evaluation_date', 'local_conocer_cert'));
        $mform->setDefault('fecha_estimada', strtotime('+2 weeks'));
        
        // Prioridad de la evaluación
        $prioridades = [
            'normal' => get_string('normal_priority', 'local_conocer_cert'),
            'alta' => get_string('high_priority', 'local_conocer_cert'),
            'urgente' => get_string('urgent_priority', 'local_conocer_cert')
        ];
        $mform->addElement('select', 'prioridad', get_string('evaluation_priority', 'local_conocer_cert'), $prioridades);
        $mform->setDefault('prioridad', 'normal');
        
        // Comentarios para el evaluador
        $mform->addElement('textarea', 'comentarios', get_string('comments_for_evaluator', 'local_conocer_cert'), 
            array('rows' => 5, 'cols' => 50));
        $mform->setType('comentarios', PARAM_TEXT);
        
        // Agregar campos ocultos para IDs
        $mform->addElement('hidden', 'candidato_id', $candidato->id);
        $mform->setType('candidato_id', PARAM_INT);
        
        if ($proceso) {
            $mform->addElement('hidden', 'proceso_id', $proceso->id);
            $mform->setType('proceso_id', PARAM_INT);
        }
        
        // Opciones adicionales
        $mform->addElement('header', 'options', get_string('additional_options', 'local_conocer_cert'));
        
        // Notificar al evaluador
        $mform->addElement('advcheckbox', 'notificar_evaluador', get_string('notify_evaluator', 'local_conocer_cert'), 
            get_string('notify_evaluator_desc', 'local_conocer_cert'));
        $mform->setDefault('notificar_evaluador', 1);
        
        // Notificar al candidato
        $mform->addElement('advcheckbox', 'notificar_candidato', get_string('notify_candidate', 'local_conocer_cert'), 
            get_string('notify_candidate_desc', 'local_conocer_cert'));
        $mform->setDefault('notificar_candidato', 1);
        
        // Botones de acción
        $this->add_action_buttons();
    }
    
    function validation($data, $files) {
        global $DB;
        
        $errors = parent::validation($data, $files);
        
        // Solo validar el evaluador si hay opciones disponibles
        if (isset($data['evaluador_id'])) {
            // Verificar que el evaluador exista y esté activo
            $evaluador = $DB->get_record_select(
                'local_conocer_evaluadores',
                "userid = :userid AND estatus = 'activo'",
                ['userid' => $data['evaluador_id']]
            );
            
            if (!$evaluador) {
                $errors['evaluador_id'] = get_string('invalid_evaluator', 'local_conocer_cert');
            } else {
                // Verificar carga de trabajo del evaluador
                $carga_actual = $DB->count_records_select(
                    'local_conocer_procesos',
                    "evaluador_id = :evaluatorid AND etapa = 'evaluacion'",
                    ['evaluatorid' => $data['evaluador_id']]
                );
                
                if ($evaluador->max_candidatos && $carga_actual >= $evaluador->max_candidatos) {
                    $errors['evaluador_id'] = get_string('evaluator_workload_exceeded', 'local_conocer_cert');
                }
            }
        }
        
        // Validar que la fecha estimada sea futura
        if (!empty($data['fecha_estimada']) && $data['fecha_estimada'] < time()) {
            $errors['fecha_estimada'] = get_string('date_must_be_future', 'local_conocer_cert');
        }
        
        return $errors;
    }
}
