<?php
// Archivo: local/conocer_cert/classes/forms/candidato_form.php
// Formulario para candidatos a certificación

namespace local_conocer_cert\forms;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Formulario para solicitud de certificación por un candidato
 */
class candidato_form extends \moodleform {
    
    function definition() {
        global $DB, $USER;
        
        $mform = $this->_form;
        $candidato = $this->_customdata['candidato'] ?? null;
        
        // Sección de información personal
        $mform->addElement('header', 'personalinfo', get_string('personalinfo', 'local_conocer_cert'));
        
        // Nombre completo (tomado del perfil de usuario)
        $mform->addElement('static', 'fullname', get_string('fullname', 'local_conocer_cert'), 
            fullname($USER));
        
        // Correo electrónico (tomado del perfil de usuario)
        $mform->addElement('static', 'email', get_string('email'), $USER->email);
        
        // CURP (campo adicional para México)
        $mform->addElement('text', 'curp', get_string('curp', 'local_conocer_cert'));
        $mform->setType('curp', PARAM_ALPHANUMEXT);
        $mform->addRule('curp', get_string('required'), 'required');
        $mform->addHelpButton('curp', 'curpformat', 'local_conocer_cert');
        
        // Teléfono
        $mform->addElement('text', 'telefono', get_string('phone', 'local_conocer_cert'));
        $mform->setType('telefono', PARAM_ALPHANUMEXT);
        $mform->addRule('telefono', get_string('required'), 'required');
        
        // Dirección
        $mform->addElement('textarea', 'direccion', get_string('address', 'local_conocer_cert'), 
            array('rows' => 3, 'cols' => 50));
        $mform->setType('direccion', PARAM_TEXT);
        $mform->addRule('direccion', get_string('required'), 'required');
        
        // Sección de certificación
        $mform->addElement('header', 'certinfo', get_string('certificationinfo', 'local_conocer_cert'));
        
        // Competencia a certificar
        $competencias = $DB->get_records_menu('local_conocer_competencias', array('activo' => 1), 'nombre', 'id, nombre');
        $mform->addElement('select', 'competencia_id', get_string('competency', 'local_conocer_cert'), $competencias);
        $mform->addRule('competencia_id', get_string('required'), 'required');
        
        // Nivel de competencia
        $niveles = array(
            1 => get_string('level1', 'local_conocer_cert'),
            2 => get_string('level2', 'local_conocer_cert'),
            3 => get_string('level3', 'local_conocer_cert'),
            4 => get_string('level4', 'local_conocer_cert'),
            5 => get_string('level5', 'local_conocer_cert')
        );
        $mform->addElement('select', 'nivel', get_string('level', 'local_conocer_cert'), $niveles);
        $mform->addRule('nivel', get_string('required'), 'required');
        
        // Experiencia relacionada con la competencia
        $mform->addElement('textarea', 'experiencia', get_string('experience', 'local_conocer_cert'), 
            array('rows' => 5, 'cols' => 50));
        $mform->setType('experiencia', PARAM_TEXT);
        $mform->addRule('experiencia', get_string('required'), 'required');
        
        // ¿Cómo se enteró del programa?
        $fuentes = array(
            'internet' => get_string('source_internet', 'local_conocer_cert'),
            'amigo' => get_string('source_friend', 'local_conocer_cert'),
            'trabajo' => get_string('source_work', 'local_conocer_cert'),
            'escuela' => get_string('source_school', 'local_conocer_cert'),
            'radio' => get_string('source_radio', 'local_conocer_cert'),
            'television' => get_string('source_tv', 'local_conocer_cert'),
            'redes' => get_string('source_social', 'local_conocer_cert'),
            'otro' => get_string('source_other', 'local_conocer_cert')
        );
        $mform->addElement('select', 'fuente_informacion', get_string('howdidyouhear', 'local_conocer_cert'), $fuentes);
        
        // Situación laboral actual
        $situaciones = array(
            'empleado' => get_string('employment_employed', 'local_conocer_cert'),
            'desempleado' => get_string('employment_unemployed', 'local_conocer_cert'),
            'autonomo' => get_string('employment_selfemployed', 'local_conocer_cert'),
            'estudiante' => get_string('employment_student', 'local_conocer_cert'),
            'jubilado' => get_string('employment_retired', 'local_conocer_cert'),
            'otro' => get_string('employment_other', 'local_conocer_cert')
        );
        $mform->addElement('select', 'situacion_laboral', get_string('currentemployment', 'local_conocer_cert'), $situaciones);
        
        // Documentos requeridos
        $mform->addElement('header', 'documents', get_string('requireddocuments', 'local_conocer_cert'));
        
        // ID Oficial
        $mform->addElement('filepicker', 'id_oficial', get_string('officialid', 'local_conocer_cert'));
        $mform->addRule('id_oficial', get_string('required'), 'required');
        
        // CURP documento
        $mform->addElement('filepicker', 'curp_doc', get_string('curpdocument', 'local_conocer_cert'));
        $mform->addRule('curp_doc', get_string('required'), 'required');
        
        // Comprobante de domicilio
        $mform->addElement('filepicker', 'comprobante_domicilio', get_string('addressproof', 'local_conocer_cert'));
        $mform->addRule('comprobante_domicilio', get_string('required'), 'required');
        
        // Evidencias de experiencia laboral
        $mform->addElement('filepicker', 'evidencia_laboral', get_string('workevidence', 'local_conocer_cert'));
        $mform->addRule('evidencia_laboral', get_string('required'), 'required');
        
        // Fotografía
        $mform->addElement('filepicker', 'fotografia', get_string('photo', 'local_conocer_cert'));
        $mform->addRule('fotografia', get_string('required'), 'required');
        
        // Documentos adicionales (opcional)
        $mform->addElement('filepicker', 'docs_adicionales', get_string('additionaldocs', 'local_conocer_cert'));
        
        // Sección de preferencias de evaluación
        $mform->addElement('header', 'evalprefs', get_string('evaluationpreferences', 'local_conocer_cert'));
        
        // Modalidad preferida
        $modalidades = array(
            'presencial' => get_string('evalmode_inperson', 'local_conocer_cert'),
            'virtual' => get_string('evalmode_virtual', 'local_conocer_cert'),
            'mixta' => get_string('evalmode_mixed', 'local_conocer_cert'),
            'cualquiera' => get_string('evalmode_any', 'local_conocer_cert')
        );
        $mform->addElement('select', 'modalidad_preferida', get_string('preferredevalmode', 'local_conocer_cert'), $modalidades);
        $mform->setDefault('modalidad_preferida', 'cualquiera');
        
        // Disponibilidad
        $disponibilidades = array(
            'diassemana' => get_string('availability_weekdays', 'local_conocer_cert'),
            'finessemana' => get_string('availability_weekends', 'local_conocer_cert'),
            'mananas' => get_string('availability_mornings', 'local_conocer_cert'),
            'tardes' => get_string('availability_afternoons', 'local_conocer_cert'),
            'completa' => get_string('availability_anytime', 'local_conocer_cert')
        );
        $mform->addElement('select', 'disponibilidad', get_string('availability', 'local_conocer_cert'), $disponibilidades);
        $mform->setDefault('disponibilidad', 'completa');
        
        // Comentarios adicionales (opcional)
        $mform->addElement('textarea', 'comentarios', get_string('additionalcomments', 'local_conocer_cert'), 
            array('rows' => 3, 'cols' => 50));
        $mform->setType('comentarios', PARAM_TEXT);
        
        // Aceptación de términos
        $mform->addElement('checkbox', 'terminos', get_string('acceptterms', 'local_conocer_cert'));
        $mform->addRule('terminos', get_string('required'), 'required');
        
        // Política de privacidad
        $mform->addElement('checkbox', 'privacidad', get_string('acceptprivacypolicy', 'local_conocer_cert'));
        $mform->addRule('privacidad', get_string('required'), 'required');
        
        // Completar formulario con datos existentes si los hay
        if ($candidato) {
            $this->set_data($candidato);
        }
        
        // Botones de acción
        $this->add_action_buttons();
    }
    
    function validation($data, $files) {
        $errors = parent::validation($data, $files);
        
        // Validar CURP (formato mexicano)
        if (!empty($data['curp'])) {
            if (strlen($data['curp']) != 18 || !preg_match('/^[A-Z]{4}[0-9]{6}[HM][A-Z]{5}[0-9A-Z]{2}$/', $data['curp'])) {
                $errors['curp'] = get_string('invalidcurp', 'local_conocer_cert');
            }
        }
        
        // Validar teléfono
        if (!empty($data['telefono']) && !preg_match('/^[0-9]{10}$/', $data['telefono'])) {
            $errors['telefono'] = get_string('invalidphone', 'local_conocer_cert');
        }
        
        // Verificar que la competencia está disponible en el nivel seleccionado
        if (!empty($data['competencia_id']) && !empty($data['nivel'])) {
            global $DB;
            $competencia = $DB->get_record('local_conocer_competencias', ['id' => $data['competencia_id']]);
            
            if ($competencia) {
                $niveles_disponibles = explode(',', $competencia->niveles_disponibles);
                if (!in_array($data['nivel'], $niveles_disponibles)) {
                    $errors['nivel'] = get_string('levelnotavailable', 'local_conocer_cert');
                }
            }
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
        // Añadir campos automáticos
        $data['userid'] = $this->_customdata['userid'] ?? $GLOBALS['USER']->id;
        $data['fecha_solicitud'] = time();
        $data['fecha_modificacion'] = time();
        $data['estado'] = 'pendiente';
        
        // Procesar documentos
        // Nota: El procesamiento real de archivos dependerá de cómo lo implementes en tu controlador
        
        return $data;
    }
}