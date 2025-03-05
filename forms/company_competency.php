<?php
// Archivo: local/conocer_cert/classes/forms/company_competency_form.php
// Formulario para asignación de competencias a empresas

namespace local_conocer_cert\forms;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Formulario para asignar competencias a una empresa
 */
class company_competency_form extends \moodleform {
    
    function definition() {
        global $DB;
        
        $mform = $this->_form;
        $empresa = $this->_customdata['empresa'] ?? null;
        $competencias_actuales = $this->_customdata['competencias_actuales'] ?? [];
        
        // Sección de información de la empresa
        $mform->addElement('header', 'companyinfo', get_string('companyinfo', 'local_conocer_cert'));
        
        // Mostrar nombre de la empresa (solo lectura)
        $mform->addElement('static', 'nombre_empresa', get_string('companyname', 'local_conocer_cert'), $empresa->nombre);
        
        // Mostrar RFC de la empresa (solo lectura)
        $mform->addElement('static', 'rfc_empresa', get_string('rfc', 'local_conocer_cert'), $empresa->rfc);
        
        // Sección de competencias disponibles
        $mform->addElement('header', 'competenciasheader', get_string('availablecompetencies', 'local_conocer_cert'));
        
        // Obtener todas las competencias activas
        $competencias = $DB->get_records_menu('local_conocer_competencias', array('activo' => 1), 'nombre', 'id, nombre');
        
        // Añadir selector de competencias múltiple
        $mform->addElement('select', 'competencias', get_string('selectcompetencies', 'local_conocer_cert'), 
            $competencias, array('multiple' => 'multiple', 'size' => 10));
        $mform->addRule('competencias', get_string('required'), 'required');
        $mform->addHelpButton('competencias', 'selectcompetencies', 'local_conocer_cert');
        
        // Si hay competencias actuales, seleccionarlas por defecto
        if (!empty($competencias_actuales)) {
            $mform->setDefault('competencias', $competencias_actuales);
        }
        
        // Justificación de la asignación
        $mform->addElement('textarea', 'justificacion', get_string('justification', 'local_conocer_cert'), 
            array('rows' => 5, 'cols' => 50));
        $mform->setType('justificacion', PARAM_TEXT);
        $mform->addRule('justificacion', get_string('required'), 'required');
        
        // Nivel de cualificación como aval
        $niveles_aval = [
            'basico' => get_string('basic_endorsement', 'local_conocer_cert'),
            'intermedio' => get_string('intermediate_endorsement', 'local_conocer_cert'),
            'avanzado' => get_string('advanced_endorsement', 'local_conocer_cert'),
            'experto' => get_string('expert_endorsement', 'local_conocer_cert')
        ];
        $mform->addElement('select', 'nivel_aval', get_string('endorsement_level', 'local_conocer_cert'), $niveles_aval);
        $mform->setDefault('nivel_aval', 'basico');
        
        // Fecha de inicio de validez
        $mform->addElement('date_selector', 'fecha_inicio', get_string('startdate', 'local_conocer_cert'));
        $mform->setDefault('fecha_inicio', time());
        
        // Fecha de fin de validez (opcional)
        $mform->addElement('date_selector', 'fecha_fin', get_string('enddate', 'local_conocer_cert'), ['optional' => true]);
        
        // Agregar campo oculto para el ID de la empresa
        $mform->addElement('hidden', 'empresa_id', $empresa->id);
        $mform->setType('empresa_id', PARAM_INT);
        
        // Sección para documentos adicionales
        $mform->addElement('header', 'documents', get_string('supportingdocuments', 'local_conocer_cert'));
        
        // Documento de acreditación
        $mform->addElement('filepicker', 'doc_acreditacion', get_string('accreditation_document', 'local_conocer_cert'));
        $mform->addHelpButton('doc_acreditacion', 'accreditation_document', 'local_conocer_cert');
        
        // Evidencias de experiencia en la competencia
        $mform->addElement('filepicker', 'doc_evidencias', get_string('competency_evidence', 'local_conocer_cert'));
        
        // Otros documentos de soporte
        $mform->addElement('filepicker', 'doc_soporte', get_string('supporting_documents', 'local_conocer_cert'));
        
        // Comentarios adicionales
        $mform->addElement('textarea', 'comentarios', get_string('additionalcomments', 'local_conocer_cert'), 
            array('rows' => 3, 'cols' => 50));
        $mform->setType('comentarios', PARAM_TEXT);
        
        // Botones de acción
        $this->add_action_buttons();
    }
    
    function validation($data, $files) {
        $errors = parent::validation($data, $files);
        
        // Validar que se haya seleccionado al menos una competencia
        if (empty($data['competencias']) || !is_array($data['competencias']) || count($data['competencias']) == 0) {
            $errors['competencias'] = get_string('selectatleastonecompetency', 'local_conocer_cert');
        }
        
        // Validar que la fecha de fin sea posterior a la de inicio si está presente
        if (!empty($data['fecha_fin']) && $data['fecha_fin'] < $data['fecha_inicio']) {
            $errors['fecha_fin'] = get_string('endbeforestart', 'local_conocer_cert');
        }
        
        return $errors;
    }
    
    /**
     * Procesa los datos del formulario antes de guardar
     * 
     * @param array $data Datos del formulario
     * @return array Datos procesados
     */
    public function process_data($data) {
        // Convertir competencias a formato para guardar
        if (!empty($data['competencias']) && is_array($data['competencias'])) {
            $data['competencias_json'] = json_encode($data['competencias']);
        } else {
            $data['competencias_json'] = '[]';
        }
        
        return $data;
    }
}
