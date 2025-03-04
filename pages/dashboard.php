<?php
// Archivo: local/conocer_cert/pages/dashboard.php
// Página principal del dashboard de certificaciones CONOCER

require_once('../../../config.php');
require_once($CFG->dirroot . '/local/conocer_cert/locallib.php');

// Parámetros
$type = optional_param('type', '', PARAM_ALPHA);

// Verificar login
require_login();

// Establecer contexto
$context = context_system::instance();
$PAGE->set_context($context);

// Establecer URL
$url = new moodle_url('/local/conocer_cert/pages/dashboard.php', array('type' => $type));
$PAGE->set_url($url);

// Establecer título y encabezado
$title = get_string('dashboard', 'local_conocer_cert');
$PAGE->set_title($title);
$PAGE->set_heading($title);

// Establecer navegación
$PAGE->set_pagelayout('standard');
$PAGE->navbar->add(get_string('dashboard', 'local_conocer_cert'));

// Determinar qué dashboard mostrar basado en el tipo o rol del usuario
$dashboardType = '';

if (!empty($type)) {
    // Usar el tipo especificado en la URL
    $dashboardType = $type;
} else {
    // Determinar el tipo de dashboard basado en roles
    if (has_capability('local/conocer_cert:managecandidates', $context)) {
        $dashboardType = 'admin';
    } else if (has_capability('local/conocer_cert:evaluatecandidates', $context)) {
        $dashboardType = 'evaluator';
    } else {
        // Verificar si es contacto de empresa
        $isCompanyContact = $DB->record_exists('local_conocer_empresas', ['contacto_userid' => $USER->id]);
        if ($isCompanyContact) {
            $dashboardType = 'company';
        } else {
            // Por defecto, mostrar dashboard de candidato
            $dashboardType = 'candidate';
        }
    }
}

// Cargar el dashboard apropiado
switch ($dashboardType) {
    case 'admin':
        require_once($CFG->dirroot . '/local/conocer_cert/classes/dashboard/admin_dashboard.php');
        require_once($CFG->dirroot . '/local/conocer_cert/classes/output/admin_dashboard_page.php');
        
        $dashboard = new \local_conocer_cert\dashboard\admin_dashboard();
        $page = new \local_conocer_cert\output\admin_dashboard_page(
            $dashboard, 
            get_string('admin_dashboard', 'local_conocer_cert')
        );
        break;
    
    case 'evaluator':
        require_once($CFG->dirroot . '/local/conocer_cert/classes/dashboard/evaluator_dashboard.php');
        require_once($CFG->dirroot . '/local/conocer_cert/classes/output/evaluator_dashboard_page.php');
        
        $dashboard = new \local_conocer_cert\dashboard\evaluator_dashboard();
        $page = new \local_conocer_cert\output\evaluator_dashboard_page(
            $dashboard, 
            get_string('evaluator_dashboard', 'local_conocer_cert')
        );
        break;
    
    case 'company':
        require_once($CFG->dirroot . '/local/conocer_cert/classes/dashboard/company_dashboard.php');
        require_once($CFG->dirroot . '/local/conocer_cert/classes/output/company_dashboard_page.php');
        
        $dashboard = new \local_conocer_cert\dashboard\company_dashboard();
        $page = new \local_conocer_cert\output\company_dashboard_page(
            $dashboard, 
            get_string('company_dashboard', 'local_conocer_cert')
        );
        break;
    
    case 'candidate':
    default:
        require_once($CFG->dirroot . '/local/conocer_cert/classes/dashboard/candidate_dashboard.php');
        require_once($CFG->dirroot . '/local/conocer_cert/classes/output/candidate_dashboard_page.php');
        
        $dashboard = new \local_conocer_cert\dashboard\candidate_dashboard();
        $page = new \local_conocer_cert\output\candidate_dashboard_page(
            $dashboard, 
            get_string('candidate_dashboard', 'local_conocer_cert')
        );
        break;
}

// Inicializar JavaScript para el dashboard
$PAGE->requires->js_call_amd('local_conocer_cert/dashboard_controller', 'init', [$dashboardType]);

// Renderizar la página
$output = $PAGE->get_renderer('local_conocer_cert');
echo $OUTPUT->header();
echo $output->render($page);
echo $OUTPUT->footer();
