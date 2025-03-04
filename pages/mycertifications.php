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
 * My Certifications page for displaying a user's certifications and certification progress.
 *
 * @package    local_conocer_cert
 * @copyright  2025 Sebastian Gonzalez Zepeda sgonzalez@infraestructuragis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/local/conocer_cert/locallib.php');

// Parameters
$userid = optional_param('userid', 0, PARAM_INT); // 0 means the current user
$status = optional_param('status', 'all', PARAM_ALPHA); // Filter by status: all, active, completed
$competencyid = optional_param('competencyid', 0, PARAM_INT); // Filter by competency

// Set up the page
$url = new moodle_url('/local/conocer_cert/pages/mycertifications.php');
if ($userid) {
    $url->param('userid', $userid);
}
if ($status !== 'all') {
    $url->param('status', $status);
}
if ($competencyid) {
    $url->param('competencyid', $competencyid);
}

$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');

// Require login
require_login();

// If viewing someone else's certifications, check permissions
$viewingself = true;
$targetuserid = $USER->id;

if ($userid && $userid != $USER->id) {
    $viewingself = false;
    $targetuserid = $userid;
    
    // Check permissions
    require_capability('local/conocer_cert:viewothercertifications', context_system::instance());
    
    // Get user info
    $targetuser = core_user::get_user($userid);
    if (!$targetuser) {
        throw new moodle_exception('invaliduser');
    }
}

// Set page title and heading
if ($viewingself) {
    $title = get_string('mycertifications', 'local_conocer_cert');
} else {
    $title = get_string('user_certifications', 'local_conocer_cert', fullname($targetuser));
}

$PAGE->set_title($title);
$PAGE->set_heading($title);

// Add navigation items
$PAGE->navbar->add(get_string('dashboard', 'local_conocer_cert'), 
    new moodle_url('/local/conocer_cert/pages/dashboard.php'));
$PAGE->navbar->add($title, $url);

// Get renderer
$output = $PAGE->get_renderer('local_conocer_cert');

// Prepare filter form
$options = [
    'userid' => $targetuserid,
    'status' => $status,
    'competencyid' => $competencyid
];

$filter_form = new \local_conocer_cert\forms\certification_filter_form($url->out(false), $options);

// Process filter form
if ($filter_data = $filter_form->get_data()) {
    $redirect_url = new moodle_url('/local/conocer_cert/pages/mycertifications.php', [
        'userid' => $targetuserid,
        'status' => $filter_data->status,
        'competencyid' => $filter_data->competencyid
    ]);
    redirect($redirect_url);
}

// Get certification data
$certifications = get_user_certifications($targetuserid, $status, $competencyid);

// Start output
echo $OUTPUT->header();

// Display filter form
$filter_form->display();

// Display user certifications
$is_admin = has_capability('local/conocer_cert:managecandidates', context_system::instance());

// If admin viewing someone else's certifications, show a link back to user management
if ($is_admin && !$viewingself) {
    echo html_writer::start_div('admin-actions mb-4');
    echo html_writer::link(
        new moodle_url('/local/conocer_cert/pages/candidates.php'),
        html_writer::tag('i', '', ['class' => 'fa fa-arrow-left mr-1']) . get_string('back_to_candidates', 'local_conocer_cert'),
        ['class' => 'btn btn-secondary btn-sm']
    );
    echo html_writer::end_div();
}

// Display active processes (in progress certifications)
$active_processes = get_user_active_processes($targetuserid, $competencyid);

if (!empty($active_processes)) {
    echo html_writer::tag('h3', get_string('active_processes', 'local_conocer_cert'), ['class' => 'mb-3']);
    
    echo html_writer::start_div('table-responsive');
    echo html_writer::start_tag('table', ['class' => 'table table-striped table-hover']);
    
    echo html_writer::start_tag('thead', ['class' => 'thead-dark']);
    echo html_writer::start_tag('tr');
    echo html_writer::tag('th', get_string('competency', 'local_conocer_cert'));
    echo html_writer::tag('th', get_string('code', 'local_conocer_cert'));
    echo html_writer::tag('th', get_string('level', 'local_conocer_cert'));
    echo html_writer::tag('th', get_string('stage', 'local_conocer_cert'));
    echo html_writer::tag('th', get_string('start_date', 'local_conocer_cert'));
    echo html_writer::tag('th', get_string('days_active', 'local_conocer_cert'));
    echo html_writer::tag('th', get_string('progress', 'local_conocer_cert'));
    echo html_writer::tag('th', get_string('actions', 'local_conocer_cert'));
    echo html_writer::end_tag('tr');
    echo html_writer::end_tag('thead');
    
    echo html_writer::start_tag('tbody');
    
    foreach ($active_processes as $process) {
        echo html_writer::start_tag('tr');
        
        echo html_writer::tag('td', $process->competencia_nombre);
        echo html_writer::tag('td', html_writer::tag('span', $process->competencia_codigo, ['class' => 'badge badge-secondary']));
        echo html_writer::tag('td', get_string('level' . $process->nivel, 'local_conocer_cert'));
        echo html_writer::tag('td', get_string('etapa_' . $process->etapa, 'local_conocer_cert'));
        echo html_writer::tag('td', userdate($process->fecha_inicio));
        
        // Calculate days active
        $days_active = ceil((time() - $process->fecha_inicio) / (24 * 60 * 60));
        echo html_writer::tag('td', $days_active);
        
        // Calculate progress percentage
        $progress = get_certification_progress_percentage($process->etapa);
        echo html_writer::start_tag('td');
        echo html_writer::start_div('progress');
        echo html_writer::div('', 'progress-bar bg-info', [
            'role' => 'progressbar',
            'style' => 'width: ' . $progress . '%',
            'aria-valuenow' => $progress,
            'aria-valuemin' => '0',
            'aria-valuemax' => '100'
        ]);
        echo html_writer::end_div();
        echo html_writer::tag('small', $progress . '%', ['class' => 'text-muted']);
        echo html_writer::end_tag('td');
        
        // Actions
        echo html_writer::start_tag('td');
        echo html_writer::link(
            new moodle_url('/local/conocer_cert/candidate/view_process.php', ['id' => $process->id]),
            html_writer::tag('i', '', ['class' => 'fa fa-eye']),
            ['class' => 'btn btn-sm btn-outline-primary', 'title' => get_string('view_details', 'local_conocer_cert')]
        );
        echo html_writer::end_tag('td');
        
        echo html_writer::end_tag('tr');
    }
    
    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');
    echo html_writer::end_div();
    
    echo html_writer::div('', 'mb-5');
}

// Display certification cards
$certifications_table = new \local_conocer_cert\output\candidate_certifications_table($certifications, $targetuserid);
echo $output->render_candidate_certifications_table($certifications_table);

// If no certifications and no active processes, show message and button to request certification
if (empty($certifications) && empty($active_processes)) {
    echo html_writer::start_div('alert alert-info');
    echo html_writer::tag('p', get_string('no_certifications_message', 'local_conocer_cert'));
    echo html_writer::end_div();
    
    if ($viewingself) {
        echo html_writer::start_div('text-center mt-4');
        echo html_writer::link(
            new moodle_url('/local/conocer_cert/candidate/new_request.php'),
            html_writer::tag('i', '', ['class' => 'fa fa-plus-circle mr-1']) . get_string('request_certification', 'local_conocer_cert'),
            ['class' => 'btn btn-primary']
        );
        echo html_writer::end_div();
    }
}

// Finish the page
echo $OUTPUT->footer();

/**
 * Get a user's certifications
 *
 * @param int $userid User ID
 * @param string $status Filter by status (all, active, completed)
 * @param int $competencyid Filter by competency ID
 * @return array User's certifications
 */
function get_user_certifications($userid, $status = 'all', $competencyid = 0) {
    global $DB;
    
    // Build SQL conditions
    $conditions = ["c.userid = :userid", "p.resultado IS NOT NULL"];
    $params = ['userid' => $userid];
    
    if ($competencyid) {
        $conditions[] = "c.competencia_id = :competenciaid";
        $params['competenciaid'] = $competencyid;
    }
    
    // Apply status filter
    if ($status === 'active') {
        $conditions[] = "(cert.estatus = 'activo' OR cert.estatus IS NULL)";
    } else if ($status === 'completed') {
        $conditions[] = "cert.estatus = 'vencido'";
    }
    
    $where = implode(' AND ', $conditions);
    
    // Get certification data
    $sql = "SELECT p.id, p.candidato_id, p.resultado, p.fecha_inicio, p.fecha_fin,
                   c.competencia_id, c.nivel, c.userid,
                   comp.nombre as competencia_nombre, comp.codigo as competencia_codigo,
                   cert.id as certificado_id, cert.numero_folio, cert.fecha_emision, 
                   cert.fecha_vencimiento, cert.estatus as certificado_estatus
            FROM {local_conocer_procesos} p
            JOIN {local_conocer_candidatos} c ON p.candidato_id = c.id
            JOIN {local_conocer_competencias} comp ON c.competencia_id = comp.id
            LEFT JOIN {local_conocer_certificados} cert ON p.id = cert.proceso_id
            WHERE $where
            ORDER BY p.fecha_fin DESC";
    
    return $DB->get_records_sql($sql, $params);
}

/**
 * Get a user's active certification processes
 *
 * @param int $userid User ID
 * @param int $competencyid Filter by competency ID
 * @return array User's active processes
 */
function get_user_active_processes($userid, $competencyid = 0) {
    global $DB;
    
    // Build SQL conditions
    $conditions = ["c.userid = :userid", "p.etapa IN ('solicitud', 'evaluacion', 'pendiente_revision')"];
    $params = ['userid' => $userid];
    
    if ($competencyid) {
        $conditions[] = "c.competencia_id = :competenciaid";
        $params['competenciaid'] = $competencyid;
    }
    
    $where = implode(' AND ', $conditions);
    
    // Get active processes
    $sql = "SELECT p.id, p.candidato_id, p.etapa, p.evaluador_id, p.fecha_inicio,
                   c.competencia_id, c.nivel,
                   comp.nombre as competencia_nombre, comp.codigo as competencia_codigo
            FROM {local_conocer_procesos} p
            JOIN {local_conocer_candidatos} c ON p.candidato_id = c.id
            JOIN {local_conocer_competencias} comp ON c.competencia_id = comp.id
            WHERE $where
            ORDER BY p.fecha_inicio DESC";
    
    return $DB->get_records_sql($sql, $params);
}

/**
 * Get certification progress percentage based on stage
 *
 * @param string $stage Current certification stage
 * @return int Progress percentage
 */
function get_certification_progress_percentage($stage) {
    $stages = [
        'solicitud' => 25,
        'documentacion' => 50,
        'evaluacion' => 75,
        'pendiente_revision' => 90,
        'aprobado' => 100,
        'rechazado' => 100
    ];
    
    return isset($stages[$stage]) ? $stages[$stage] : 0;
}
