<?php
// Archivo: local/conocer_cert/classes/forms/competencia_form.php
// Formulario para gestión de competencias CONOCER

namespace local_conocer_cert\forms;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Formulario para gestionar competencias CONOCER
 */
class competencia_form extends \moodleform {
    
    function definition() {
        global $DB;
        
        $mform = $this->_form;
        $competencia = $this->_customdata['competencia'] ?? null;
        
        // Información general de la competencia
        $mform->addElement('header', 'generalinfo', get_string('competencyinfo', 'local_conocer_cert'));
        
        // Código de la competencia
        $mform->addElement('text', 'codigo', get_string('competencycode', 'local_conocer_cert'), ['size' => 20]);
        $mform->setType('codigo', PARAM_ALPHANUMEXT);
        $mform->addRule('codigo', get_string('required'), 'required');
        $mform->addHelpButton('codigo', 'competencycodeformat', 'local_conocer_cert');
        
        // Nombre de la competencia
        $mform->addElement('text', 'nombre', get_string('competencyname', 'local_conocer_cert'), ['size' => 60]);
        $mform->setType('nombre', PARAM_TEXT);
        $mform->addRule('nombre', get_string('required'), 'required');
        
        // Descripción
        $mform->addElement('textarea', 'descripcion', get_string('description', 'local_conocer_cert'), 
            ['rows' => 5, 'cols' => 60]);
        $mform->setType('descripcion', PARAM_TEXT);
        $mform->addRule('descripcion', get_string('required'), 'required');
        
        // Sector/Área
        $sectores = [
            'agropecuario' => get_string('sector_agro', 'local_conocer_cert'),
            'industrial' => get_string('sector_industrial', 'local_conocer_cert'),
            'comercio' => get_string('sector_commerce', 'local_conocer_cert'),
            'servicios' => get_string('sector_services', 'local_conocer_cert'),
            'educacion' => get_string('sector_education', 'local_conocer_cert'),
            'tecnologia' => get_string('sector_technology', 'local_conocer_cert'),
            'salud' => get_string('sector_health', 'local_conocer_cert'),
            'otro' => get_string('sector_other', 'local_conocer_cert')
        ];
        $mform->addElement('select', 'sector', get_string('sector', 'local_conocer_cert'), $sectores);
        $mform->addRule('sector', get_string('required'), 'required');
        
        // Niveles de competencia
        $mform->addElement('header', 'levels', get_string('competencylevels', 'local_conocer_cert'));
        
        // Checkbox para cada nivel disponible
        $niveles = [
            1 => get_string('level1', 'local_conocer_cert'),
            2 => get_string('level2', 'local_conocer_cert'),
            3 => get_string('level3', 'local_conocer_cert'),
            4 => get_string('level4', 'local_conocer_cert'),
            5 => get_string('level5', 'local_conocer_cert')
        ];
        
        $niveles_group = [];
        foreach ($niveles as $nivel => $label) {
            $niveles_group[] = $mform->createElement('checkbox', 'nivel'.$nivel, '', $label);
        }
        $mform->addGroup($niveles_group, 'niveles_disponibles', get_string('availablelevels', 'local_conocer_cert'), ['<br>'], false);
        
        // Descripción de cada nivel
        foreach ($niveles as $nivel => $label) {
            $mform->addElement('textarea', 'descripcion_nivel'.$nivel, $label . ' - ' . get_string('description', 'local_conocer_cert'), 
                ['rows' => 3, 'cols' => 60]);
            $mform->setType('descripcion_nivel'.$nivel, PARAM_TEXT);
            $mform->disabledIf('descripcion_nivel'.$nivel, 'nivel'.$nivel, 'notchecked');
        }
        
        // Requisitos y evaluación
        $mform->addElement('header', 'requirements', get_string('requirementseval', 'local_conocer_cert'));
        
        // Requisitos previos
        $mform->addElement('textarea', 'requisitos', get_string('prerequisites', 'local_conocer_cert'), 
            ['rows' => 4, 'cols' => 60]);
        $mform->setType('requisitos', PARAM_TEXT);
        
        // Tipo de evaluación
        $evalTypes = [
            'practica' => get_string('evaltype_practical', 'local_conocer_cert'),
            'teorica' => get_string('evaltype_theoretical', 'local_conocer_cert'),
            'mixta' => get_string('evaltype_mixed', 'local_conocer_cert')
        ];
        $mform->addElement('select', 'tipo_evaluacion', get_string('evaluationtype', 'local_conocer_cert'), $evalTypes);
        
        // Duración estimada del proceso (en días)
        $mform->addElement('text', 'duracion_estimada', get_string('estimatedduration', 'local_conocer_cert'), ['size' => 5]);
        $mform->setType('duracion_estimada', PARAM_INT);
        $mform->addRule('duracion_estimada', get_string('required'), 'required');
        $mform->setDefault('duracion_estimada', 30);
        
        // Costo
        $mform->addElement('text', 'costo', get_string('cost', 'local_conocer_cert'), ['size' => 10]);
        $mform->setType('costo', PARAM_FLOAT);
        $mform->addRule('costo', get_string('required'), 'required');
        $mform->setDefault('costo', 0.00);
        
        // Estado
        $mform->addElement('header', 'status', get_string('competencystatus', 'local_conocer_cert'));
        
        // Activo o inactivo
        $mform->addElement('advcheckbox', 'activo', get_string('active', 'local_conocer_cert'), null, null, [0, 1]);
        $mform->setDefault('activo', 1);
        
        // Fecha de inicio
        $mform->addElement('date_selector', 'fecha_inicio', get_string('startdate', 'local_conocer_cert'));
        $mform->setDefault('fecha_inicio', time());
        
        // Fecha de fin (opcional)
        $mform->addElement('date_selector', 'fecha_fin', get_string('enddate', 'local_conocer_cert'), ['optional' => true]);
        
        // Documentos requeridos
        $mform->addElement('header', 'documents', get_string('requireddocuments', 'local_conocer_cert'));
        
        // Lista de documentos requeridos
        $documentosOptions = [
            'id_oficial' => get_string('doc_id_oficial', 'local_conocer_cert'),
            'curp_doc' => get_string('doc_curp_doc', 'local_conocer_cert'),
            'comprobante_domicilio' => get_string('doc_comprobante_domicilio', 'local_conocer_cert'),
            'evidencia_laboral' => get_string('doc_evidencia_laboral', 'local_conocer_cert'),
            'fotografia' => get_string('doc_fotografia', 'local_conocer_cert'),
            'certificado_estudios' => get_string('doc_certificado_estudios', 'local_conocer_cert'),
            'curriculum' => get_string('doc_curriculum', 'local_conocer_cert'),
            'carta_recomendacion' => get_string('doc_carta_recomendacion', 'local_conocer_cert')
        ];
        
        $mform->addElement('select', 'documentos_requeridos', get_string('requiredcandidatedocs', 'local_conocer_cert'), 
            $documentosOptions, ['multiple' => 'multiple']);
        
        // Cargar datos existentes si se está editando
        if ($competencia) {
            $this->set_data($competencia);
            
            // Procesar los niveles disponibles
            if (!empty($competencia->niveles_disponibles)) {
                $niveles_array = explode(',', $competencia->niveles_disponibles);
                foreach ($niveles_array as $nivel) {
                    $mform->setDefault('nivel'.$nivel, 1);
                }
            }
            
            // Procesar los documentos requeridos
            if (!empty($competencia->documentos_requeridos)) {
                $mform->setDefault('documentos_requeridos', explode(',', $competencia->documentos_requeridos));
            }
        } else {
            // Por defecto, todos los niveles disponibles
            for ($i = 1; $i <= 5; $i++) {
                $mform->setDefault('nivel'.$i, 1);
            }
            
            // Por defecto, todos los documentos básicos
            $mform->setDefault('documentos_requeridos', ['id_oficial', 'curp_doc', 'comprobante_domicilio', 'evidencia_laboral', 'fotografia']);
        }
        
        // Botones de acción
        $this->add_action_buttons();
    }
    
    function validation($data, $files) {
        global $DB;
        
        $errors = parent::validation($data, $files);
        
        // Validar código único
        if (!empty($data['codigo'])) {
            $params = ['codigo' => $data['codigo']];
            if (!empty($data['id'])) {
                $params['id'] = ['<>', $data['id']];
            }
            
            if ($DB->record_exists('local_conocer_competencias', $params)) {
                $errors['codigo'] = get_string('competencycodeexists', 'local_conocer_cert');
            }
        }
        
        // Validar formato del código (estándar CONOCER)
        if (!empty($data['codigo']) && !preg_match('/^EC[0-9]{4}$/', $data['codigo'])) {
            $errors['codigo'] = get_string('invalidcompetencycode', 'local_conocer_cert');
        }
        
        // Verificar que al menos un nivel esté seleccionado
        $hasLevel = false;
        for ($i = 1; $i <= 5; $i++) {
            if (!empty($data['nivel'.$i])) {
                $hasLevel = true;
                break;
            }
        }
        
        if (!$hasLevel) {
            $errors['niveles_disponibles'] = get_string('atleastonelevel', 'local_conocer_cert');
        }
        
        // Validar fecha de fin si está presente
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
        // Procesar niveles disponibles
        $niveles = [];
        for ($i = 1; $i <= 5; $i++) {
            if (!empty($data['nivel'.$i])) {
                $niveles[] = $i;
            }
        }
        $data['niveles_disponibles'] = implode(',', $niveles);
        
        // Procesar documentos requeridos
        if (!empty($data['documentos_requeridos']) && is_array($data['documentos_requeridos'])) {
            $data['documentos_requeridos'] = implode(',', $data['documentos_requeridos']);
        }
        
        // Limpiar campos temporales
        for ($i = 1; $i <= 5; $i++) {
            unset($data['nivel'.$i]);
        }
        
        return $data;
    }
}
