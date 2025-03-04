<?php
// Archivo: local/conocer_cert/classes/forms/empresa_form.php
// Formulario para registro de empresas como avales

namespace local_conocer_cert\forms;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Formulario para registro de empresas como avales
 */
class empresa_form extends \moodleform {
    
    function definition() {
        global $DB;
        
        $mform = $this->_form;
        $empresa = $this->_customdata['empresa'] ?? null;
        
        // Información de la empresa
        $mform->addElement('header', 'companyinfo', get_string('companyinfo', 'local_conocer_cert'));
        
        // Nombre de la empresa
        $mform->addElement('text', 'nombre', get_string('companyname', 'local_conocer_cert'), array('size' => 50));
        $mform->setType('nombre', PARAM_TEXT);
        $mform->addRule('nombre', get_string('required'), 'required');
        
        // RFC
        $mform->addElement('text', 'rfc', get_string('rfc', 'local_conocer_cert'));
        $mform->setType('rfc', PARAM_ALPHANUMEXT);
        $mform->addRule('rfc', get_string('required'), 'required');
        $mform->addHelpButton('rfc', 'rfcformat', 'local_conocer_cert');
        
        // Dirección
        $mform->addElement('textarea', 'direccion', get_string('address', 'local_conocer_cert'), 
            array('rows' => 3, 'cols' => 50));
        $mform->setType('direccion', PARAM_TEXT);
        $mform->addRule('direccion', get_string('required'), 'required');
        
        // Sector/Industria
        $sectores = array(
            'agropecuario' => get_string('sector_agro', 'local_conocer_cert'),
            'industrial' => get_string('sector_industrial', 'local_conocer_cert'),
            'servicios' => get_string('sector_services', 'local_conocer_cert'),
            'educacion' => get_string('sector_education', 'local_conocer_cert'),
            'tecnologia' => get_string('sector_technology', 'local_conocer_cert'),
            'otro' => get_string('sector_other', 'local_conocer_cert')
        );
        $mform->addElement('select', 'sector', get_string('sector', 'local_conocer_cert'), $sectores);
        $mform->addRule('sector', get_string('required'), 'required');
        
        // Número de empleados
        $mform->addElement('text', 'num_empleados', get_string('employeecount', 'local_conocer_cert'));
        $mform->setType('num_empleados', PARAM_INT);
        $mform->addRule('num_empleados', get_string('required'), 'required');
        
        // Información de contacto
        $mform->addElement('header', 'contactinfo', get_string('contactinfo', 'local_conocer_cert'));
        
        // Nombre del contacto
        $mform->addElement('text', 'contacto_nombre', get_string('contactname', 'local_conocer_cert'), array('size' => 50));
        $mform->setType('contacto_nombre', PARAM_TEXT);
        $mform->addRule('contacto_nombre', get_string('required'), 'required');
        
        // Puesto del contacto
        $mform->addElement('text', 'contacto_puesto', get_string('contactposition', 'local_conocer_cert'));
        $mform->setType('contacto_puesto', PARAM_TEXT);
        $mform->addRule('contacto_puesto', get_string('required'), 'required');
        
        // Email del contacto
        $mform->addElement('text', 'contacto_email', get_string('email'), array('size' => 50));
        $mform->setType('contacto_email', PARAM_EMAIL);
        $mform->addRule('contacto_email', get_string('required'), 'required');
        
        // Teléfono del contacto
        $mform->addElement('text', 'contacto_telefono', get_string('phone', 'local_conocer_cert'));
        $mform->setType('contacto_telefono', PARAM_ALPHANUMEXT);
        $mform->addRule('contacto_telefono', get_string('required'), 'required');
        
        // Documentos requeridos
        $mform->addElement('header', 'documents', get_string('requireddocuments', 'local_conocer_cert'));
        
        // Acta constitutiva
        $mform->addElement('filepicker', 'acta_constitutiva', get_string('articlesofincorporation', 'local_conocer_cert'));
        $mform->addRule('acta_constitutiva', get_string('required'), 'required');
        
        // RFC documento
        $mform->addElement('filepicker', 'rfc_doc', get_string('rfcdocument', 'local_conocer_cert'));
        $mform->addRule('rfc_doc', get_string('required'), 'required');
        
        // Poder notarial del representante
        $mform->addElement('filepicker', 'poder_notarial', get_string('notarialpower', 'local_conocer_cert'));
        $mform->addRule('poder_notarial', get_string('required'), 'required');
        
        // Comprobante de domicilio fiscal
        $mform->addElement('filepicker', 'comprobante_fiscal', get_string('fiscaladdressproof', 'local_conocer_cert'));
        $mform->addRule('comprobante_fiscal', get_string('required'), 'required');
        
        // ID del representante legal
        $mform->addElement('filepicker', 'id_representante', get_string('legalrepid', 'local_conocer_cert'));
        $mform->addRule('id_representante', get_string('required'), 'required');
        
        // Competencias de interés
        $mform->addElement('header', 'competenciasheader', get_string('competenciesofinterest', 'local_conocer_cert'));
        
        // Lista de competencias disponibles
        $competencias = $DB->get_records_menu('local_conocer_competencias', array('activo' => 1), 'nombre', 'id, nombre');
        $mform->addElement('select', 'competencias', get_string('competencies', 'local_conocer_cert'), 
            $competencias, array('multiple' => 'multiple'));
        $mform->addRule('competencias', get_string('required'), 'required');
        
        // Justificación del interés
        $mform->addElement('textarea', 'justificacion', get_string('justification', 'local_conocer_cert'), 
            array('rows' => 5, 'cols' => 50));
        $mform->setType('justificacion', PARAM_TEXT);
        $mform->addRule('justificacion', get_string('required'), 'required');
        
        // Términos y condiciones
        $mform->addElement('checkbox', 'terminos', get_string('acceptterms', 'local_conocer_cert'));
        $mform->addRule('terminos', get_string('required'), 'required');
        
        // Completar formulario con datos existentes si los hay
        if ($empresa) {
            $this->set_data($empresa);
        }
        
        // Botones de acción
        $this->add_action_buttons();
    }
    
    function validation($data, $files) {
        global $DB;
        $errors = parent::validation($data, $files);
        
        // Validar RFC (formato mexicano)
        if (!empty($data['rfc'])) {
            // Validar formato de RFC para personas morales
            if (!preg_match('/^[A-Z&Ñ]{3}[0-9]{6}[A-Z0-9]{3}$/', $data['rfc'])) {
                $errors['rfc'] = get_string('invalidrfc', 'local_conocer_cert');
            }
            
            // Verificar si ya existe ese RFC
            if (!isset($this->_customdata['empresa']) || 
                $this->_customdata['empresa']->rfc != $data['rfc']) {
                if ($DB->record_exists('local_conocer_empresas', array('rfc' => $data['rfc']))) {
                    $errors['rfc'] = get_string('rfcexists', 'local_conocer_cert');
                }
            }
        }
        
        // Validar email
        if (!empty($data['contacto_email']) && !validate_email($data['contacto_email'])) {
            $errors['contacto_email'] = get_string('invalidemail');
        }
        
        // Validar teléfono
        if (!empty($data['contacto_telefono']) && !preg_match('/^[0-9]{10}$/', $data['contacto_telefono'])) {
            $errors['contacto_telefono'] = get_string('invalidphone', 'local_conocer_cert');
        }
        
        // Validar número de empleados
        if (!empty($data['num_empleados']) && (!is_numeric($data['num_empleados']) || $data['num_empleados'] < 1)) {
            $errors['num_empleados'] = get_string('invalidemployeecount', 'local_conocer_cert');
        }
        
        // Validar competencias seleccionadas
        if (empty($data['competencias']) || !is_array($data['competencias']) || count($data['competencias']) == 0) {
            $errors['competencias'] = get_string('selectatleastonecompetency', 'local_conocer_cert');
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
            $data['competencias'] = implode(',', $data['competencias']);
        }
        
        return $data;
    }
}
