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
 * Admin page for managing certification candidates.
 *
 * @package    local_conocer_cert
 * @copyright  2025 Sebastian Gonzalez Zepeda sgonzalez@infraestructuragis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/local/conocer_cert/locallib.php');

// Get parameters.
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 20, PARAM_INT);
$sort = optional_param('sort', 'fecha_solicitud', PARAM_ALPHA);
$dir = optional_param('dir', 'DESC', PARAM_ALPHA);
$search = optional_param('search', '', PARAM_RAW);
$filter = optional_param('filter', '', PARAM_ALPHA);
$competenciaid = optional_param('competenciaid', 0, PARAM_INT);
$userid = optional_param('userid', 0, PARAM_INT);
$estado = optional_param('estado', '', PARAM_ALPHA);
$action = optional_param('action', '', PARAM_ALPHA);
$candidateid = optional_param('candidateid', 0, PARAM_INT);

// Setup page.
$url = new moodle_url('/local/conocer_cert/admin/candidates.php');
$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');
$PAGE->set_context(context_system::instance());

// Check permissions.
require_login();
require_capability('local/conocer_cert:managecandidates', $PAGE->context);

// Setup page title and heading.
$title = get_string('candidates_management', 'local_conocer_cert');
$PAGE->set_title($title);
$PAGE->set_heading($title);

// Process actions if any.
if ($action && $candidateid && confirm_sesskey()) {
    $candidate = $DB->get_record('local_conocer_candidatos', ['id' => $candidateid], '*', MUST_EXIST);
    
    switch ($action) {
        case 'approve':
            // Approve candidate documents and move to next stage
            $candidate->estado = 'aprobado';
            $DB->update_record('local_conocer_candidatos', $candidate);
            
            // Check if there's already a process record
            $process = $DB->get_record('local_conocer_procesos', ['candidato_id' => $candidateid]);
            
            if (!$process) {
                // Create new process record
                $process = new stdClass();
                $process->candidato_id = $candidateid;
                $process->etapa = 'evaluacion';
                $process->fecha_inicio = time();
                $process->timemodified = time();
                $DB->insert_record('local_conocer_procesos', $process);
            } else {
                // Update existing process
                $process->etapa = 'evaluacion';
                $process->timemodified = time();
                $DB->update_record('local_conocer_procesos', $process);
            }
            
            // Send notification to candidate
            \local_conocer_cert\util\notification::send($candidate->userid, 'documentos_aprobados', [
                'competencia' => get_competency_name($candidate->competencia_id),
                'nivel' => $candidate->nivel,
                'contexturl' => new moodle_url('/local/conocer_cert/candidate/view_request.php', ['id' => $candidateid]),
                'contexturlname' => get_string('view_request', 'local_conocer_cert')
            ]);
            
            \core\notification::success(get_string('candidate_approved', 'local_conocer_cert'));
            break;
            
        case 'reject':
            // Reject candidate application
            $candidate->estado = 'rechazado';
            $DB->update_record('local_conocer_candidatos', $candidate);
            
            // Send notification to candidate
            \local_conocer_cert\util\notification::send($candidate->userid, 'documentos_rechazados', [
                'competencia' => get_competency_name($candidate->competencia_id),
                'nivel' => $candidate->nivel,
                'contexturl' => new moodle_url('/local/conocer_cert/candidate/view_request.php', ['id' => $candidateid]),
                'contexturlname' => get_string('view_request', 'local_conocer_cert')
            ]);
            
            \core\notification::success(get_string('candidate_rejected', 'local_conocer_cert'));
            break;
            
        case 'delete':
            // Delete candidate
            $DB->delete_records('local_conocer_candidatos', ['id' => $candidateid]);
            
            // Delete related records
            $DB->delete_records('local_conocer_documentos', ['candidato_id' => $candidateid]);
            
            // Get process IDs for this candidate
            $processids = $DB->get_fieldset_select('local_conocer_procesos', 'id', 'candidato_id = :candidatoid', 
                ['candidatoid' => $candidateid]);
            
            if (!empty($processids)) {
                list($insql, $inparams) = $DB->get_in_or_equal($processids);
                $DB->delete_records_select('local_conocer_evaluaciones', "proceso_id $insql", $inparams);
                $DB->delete_records_select('local_conocer_certificados', "proceso_id $insql", $inparams);
            }
            
            $DB->delete_records('local_conocer_procesos', ['candidato_id' => $candidateid]);
            
            \core\notification::success(get_string('candidate_deleted', 'local_conocer_cert'));
            break;
            
        case 'assign_evaluator':
            // Redirect to evaluator assignment page
            redirect(new moodle_url('/local/conocer_cert/admin/assign_evaluator.php', ['id' => $candidateid]));
            break;
    }
    
    // Redirect to refresh the page
    redirect($PAGE->url);
}

// Build SQL query.
$params = [];
$conditions = [];

if ($search) {
    $searchsql = $DB->sql_like('CONCAT(u.firstname, \' \', u.lastname)', ':search', false);
    $conditions[] = $searchsql;
    $params['search'] = '%' . $search . '%';
}

if ($competenciaid) {
    $conditions[] = 'c.competencia_id = :competenciaid';
    $params['competenciaid'] = $competenciaid;
}

if ($userid) {
    $conditions[] = 'c.userid = :userid';
    $params['userid'] = $userid;
}

if ($estado) {
    $conditions[] = 'c.estado = :estado';
    $params['estado'] = $estado;
}

$where = '';
if (!empty($conditions)) {
    $where = 'WHERE ' . implode(' AND ', $conditions);
}

// Get total candidates count
$countsql = "SELECT COUNT(c.id)
             FROM {local_conocer_candidatos} c
             JOIN {user} u ON c.userid = u.id
             $where";
$totalcount = $DB->count_records_sql($countsql, $params);

// Set up pagination
$baseurl = new moodle_url('/local/conocer_cert/admin/candidates.php', [
    'sort' => $sort,
    'dir' => $dir,
    'search' => $search,
    'filter' => $filter,
    'competenciaid' => $competenciaid,
    'userid' => $userid,
    'estado' => $estado
]);

// Get candidates
$sql = "SELECT c.*, 
               u.firstname, u.lastname, u.email,
               comp.nombre as competencia_nombre, comp.codigo as competencia_codigo,
               p.id as proceso_id, p.etapa, p.evaluador_id
        FROM {local_conocer_candidatos} c
        JOIN {user} u ON c.userid = u.id
        JOIN {local_conocer_competencias} comp ON c.competencia_id = comp.id
        LEFT JOIN {local_conocer_procesos} p ON c.id = p.candidato_id
        $where
        ORDER BY $sort $dir";

$candidates = $DB->get_records_sql($sql, $params, $page * $perpage, $perpage);

// Get competencies for filter
$competencias = $DB->get_records('local_conocer_competencias', null, 'nombre', 'id, nombre, codigo');

// Add navigation breadcrumbs.
$PAGE->navbar->add(get_string('pluginname', 'local_conocer_cert'), new moodle_url('/local/conocer_cert/index.php'));
$PAGE->navbar->add($title);

// Add scripts and styles.
$PAGE->requires->js_call_amd('local_conocer_cert/dashboard_controller', 'init', ['admin']);

// Output starts here.
echo $OUTPUT->header();
echo $OUTPUT->heading($title);

// Search and filter form.
echo '<div class="candidates-filters mb-4">';
echo html_writer::start_tag('form', ['action' => $PAGE->url, 'method' => 'get', 'class' => 'form-inline']);

// Search field
echo html_writer::start_div('form-group mr-2');
echo html_writer::label(get_string('search'), 'search', false, ['class' => 'sr-only']);
echo html_writer::empty_tag('input', [
    'type' => 'text',
    'id' => 'search',
    'name' => 'search',
    'value' => $search,
    'class' => 'form-control',
    'placeholder' => get_string('search_candidate', 'local_conocer_cert')
]);
echo html_writer::end_div();

// Competency filter
echo html_writer::start_div('form-group mr-2');
echo html_writer::label(get_string('competency', 'local_conocer_cert'), 'competenciaid', false, ['class' => 'sr-only']);
echo html_writer::select(
    array_merge([0 => get_string('all_competencies', 'local_conocer_cert')], 
    array_map(function($comp) {
        return $comp->codigo . ' - ' . $comp->nombre;
    }, $competencias)),
    'competenciaid',
    $competenciaid,
    false,
    ['class' => 'form-control']
);
echo html_writer::end_div();

// Status filter
$estados = [
    '' => get_string('all_statuses', 'local_conocer_cert'),
    'pendiente' => get_string('estado_pendiente', 'local_conocer_cert'),
    'aprobado' => get_string('estado_aprobado', 'local_conocer_cert'),
    'rechazado' => get_string('estado_rechazado', 'local_conocer_cert')
];
echo html_writer::start_div('form-group mr-2');
echo html_writer::label(get_string('status', 'local_conocer_cert'), 'estado', false, ['class' => 'sr-only']);
echo html_writer::select($estados, 'estado', $estado, false, ['class' => 'form-control']);
echo html_writer::end_div();

// Submit button
echo html_writer::empty_tag('input', [
    'type' => 'submit',
    'value' => get_string('apply_filters', 'local_conocer_cert'),
    'class' => 'btn btn-primary'
]);

// Reset filters link
echo '&nbsp;';
echo html_writer::link(
    new moodle_url('/local/conocer_cert/admin/candidates.php'),
    get_string('reset_filters', 'local_conocer_cert'),
    ['class' => 'btn btn-secondary']
);

echo html_writer::end_tag('form');
echo '</div>';

// Display candidates table
if ($candidates) {
    $table = new html_table();
    $table->id = 'candidates-table';
    $table->attributes['class'] = 'table table-striped table-hover candidates-table';
    
    // Define columns
    $columns = [
        'name' => get_string('candidate', 'local_conocer_cert'),
        'email' => get_string('email'),
        'competence' => get_string('competency', 'local_conocer_cert'),
        'level' => get_string('level', 'local_conocer_cert'),
        'status' => get_string('status', 'local_conocer_cert'),
        'date' => get_string('request_date', 'local_conocer_cert'),
        'actions' => get_string('actions', 'local_conocer_cert')
    ];
    
    $table->head = array_values($columns);

    // Add sorting links to headers
    $sortableColumns = ['name' => 'lastname', 'competence' => 'competencia_nombre', 'level' => 'nivel', 'status' => 'estado', 'date' => 'fecha_solicitud'];
    foreach ($sortableColumns as $column => $dbfield) {
        $sorticon = '';
        if ($sort === $dbfield) {
            $sorticon = $dir === 'ASC' ? ' <i class="fa fa-sort-up"></i>' : ' <i class="fa fa-sort-down"></i>';
            $newdir = $dir === 'ASC' ? 'DESC' : 'ASC';
        } else {
            $newdir = 'ASC';
        }
        
        $sorturl = new moodle_url($PAGE->url, ['sort' => $dbfield, 'dir' => $newdir]);
        $columnIndex = array_search($column, array_keys($columns));
        $table->head[$columnIndex] = html_writer::link($sorturl, $columns[$column]) . $sorticon;
    }

    // Fill table with candidate data
    foreach ($candidates as $candidate) {
        $fullname = $candidate->firstname . ' ' . $candidate->lastname;
        
        // Get evaluator name if assigned
        $evaluatorname = '';
        if (!empty($candidate->evaluador_id)) {
            $evaluator = $DB->get_record('user', ['id' => $candidate->evaluador_id]);
            if ($evaluator) {
                $evaluatorname = fullname($evaluator);
            }
        }
        
        // Build action buttons
        $actions = '';
        
        // View button
        $viewurl = new moodle_url('/local/conocer_cert/admin/view_candidate.php', ['id' => $candidate->id]);
        $actions .= html_writer::link(
            $viewurl,
            '<i class="fa fa-eye"></i>',
            ['class' => 'btn btn-sm btn-info action-btn', 'title' => get_string('view')]
        );
        
        // Status-dependent action buttons
        if ($candidate->estado === 'pendiente') {
            // Approve button
            $approveurl = new moodle_url($PAGE->url, [
                'action' => 'approve',
                'candidateid' => $candidate->id,
                'sesskey' => sesskey()
            ]);
            $actions .= html_writer::link(
                $approveurl,
                '<i class="fa fa-check"></i>',
                ['class' => 'btn btn-sm btn-success action-btn ml-1', 'title' => get_string('approve', 'local_conocer_cert')]
            );
            
            // Reject button
            $rejecturl = new moodle_url($PAGE->url, [
                'action' => 'reject',
                'candidateid' => $candidate->id,
                'sesskey' => sesskey()
            ]);
            $actions .= html_writer::link(
                $rejecturl,
                '<i class="fa fa-times"></i>',
                ['class' => 'btn btn-sm btn-danger action-btn ml-1', 'title' => get_string('reject', 'local_conocer_cert')]
            );
        } else if ($candidate->estado === 'aprobado' && !empty($candidate->proceso_id) && empty($candidate->evaluador_id)) {
            // Assign evaluator button
            $assignurl = new moodle_url($PAGE->url, [
                'action' => 'assign_evaluator',
                'candidateid' => $candidate->id,
                'sesskey' => sesskey()
            ]);
            $actions .= html_writer::link(
                $assignurl,
                '<i class="fa fa-user-plus"></i>',
                ['class' => 'btn btn-sm btn-primary action-btn ml-1', 'title' => get_string('assign_evaluator', 'local_conocer_cert')]
            );
        }
        
        // Delete button (always available)
        $deleteurl = new moodle_url($PAGE->url, [
            'action' => 'delete',
            'candidateid' => $candidate->id,
            'sesskey' => sesskey()
        ]);
        $actions .= html_writer::link(
            $deleteurl,
            '<i class="fa fa-trash"></i>',
            [
                'class' => 'btn btn-sm btn-danger action-btn ml-1', 
                'title' => get_string('delete'),
                'onclick' => 'return confirm("' . get_string('confirm_delete_candidate', 'local_conocer_cert') . '");'
            ]
        );
        
        // Get status text and badge
        $statustext = get_string('estado_' . $candidate->estado, 'local_conocer_cert');
        $statusbadge = '';
        switch ($candidate->estado) {
            case 'pendiente':
                $statusbadge = html_writer::tag('span', $statustext, ['class' => 'badge badge-warning']);
                break;
            case 'aprobado':
                $statusbadge = html_writer::tag('span', $statustext, ['class' => 'badge badge-success']);
                break;
            case 'rechazado':
                $statusbadge = html_writer::tag('span', $statustext, ['class' => 'badge badge-danger']);
                break;
            default:
                $statusbadge = html_writer::tag('span', $statustext, ['class' => 'badge badge-info']);
        }
        
        // Add evaluator info to status if available
        if (!empty($evaluatorname)) {
            $statusbadge .= '<br><small>' . get_string('evaluator', 'local_conocer_cert') . ': ' . $evaluatorname . '</small>';
        }
        
        // Create table row
        $row = [];
        $row[] = html_writer::link($viewurl, $fullname);
        $row[] = $candidate->email;
        $row[] = $candidate->competencia_codigo . ' - ' . $candidate->competencia_nombre;
        $row[] = get_string('level' . $candidate->nivel, 'local_conocer_cert');
        $row[] = $statusbadge;
        $row[] = userdate($candidate->fecha_solicitud);
        $row[] = $actions;
        
        $table->data[] = $row;
    }
    
    echo html_writer::table($table);
    
    // Pagination
    echo $OUTPUT->paging_bar($totalcount, $page, $perpage, $baseurl);
} else {
    echo $OUTPUT->notification(get_string('no_candidates_found', 'local_conocer_cert'), 'info');
}

// Add button to register a new candidate
echo html_writer::start_div('mt-4');
$newurl = new moodle_url('/local/conocer_cert/admin/new_candidate.php');
echo html_writer::link(
    $newurl,
    get_string('add_new_candidate', 'local_conocer_cert'),
    ['class' => 'btn btn-primary']
);
echo html_writer::end_div();

// Helper function to get competency name
function get_competency_name($competencyid) {
    global $DB;
    $competency = $DB->get_record('local_conocer_competencias', ['id' => $competencyid]);
    return $competency ? $competency->nombre : '';
}

echo $OUTPUT->footer();
