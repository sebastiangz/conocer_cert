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
 * Manage candidates for CONOCER certifications.
 *
 * @package    local_conocer_cert
 * @copyright  2025 Sebastian Gonzalez Zepeda sgonzalez@infraestructuragis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/local/conocer_cert/locallib.php');

// Parameters
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 20, PARAM_INT);
$sort = optional_param('sort', 'lastname', PARAM_ALPHANUMEXT);
$dir = optional_param('dir', 'ASC', PARAM_ALPHA);
$search = optional_param('search', '', PARAM_RAW);
$competenciaid = optional_param('competenciaid', 0, PARAM_INT);
$estado = optional_param('estado', '', PARAM_ALPHA);
$action = optional_param('action', '', PARAM_ALPHANUMEXT);
$candidateid = optional_param('candidateid', 0, PARAM_INT);
$id = optional_param('id', 0, PARAM_INT); // ID for view/edit/etc. actions

// Setup page
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/conocer_cert/pages/candidates.php'));
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('manage_candidates', 'local_conocer_cert'));
$PAGE->set_heading(get_string('manage_candidates', 'local_conocer_cert'));
$PAGE->navbar->add(get_string('pluginname', 'local_conocer_cert'), new moodle_url('/local/conocer_cert/index.php'));
$PAGE->navbar->add(get_string('manage_candidates', 'local_conocer_cert'));

// Check permissions
require_login();
require_capability('local/conocer_cert:managecandidates', $context);

// Process actions
if ($action) {
    // Use candidate ID from either id or candidateid param
    $targetid = ($id > 0) ? $id : $candidateid;
    
    // Actions that need a candidate ID and session key check
    if (($action == 'delete' || $action == 'suspend' || $action == 'activate') && $targetid && confirm_sesskey()) {
        switch ($action) {
            case 'delete':
                if ($DB->record_exists('local_conocer_candidatos', ['id' => $targetid])) {
                    // Check if candidate has processes or documents
                    $hasProcesses = $DB->record_exists('local_conocer_procesos', ['candidato_id' => $targetid]);
                    $hasDocuments = $DB->record_exists('local_conocer_documentos', ['candidato_id' => $targetid]);
                    
                    if ($hasProcesses || $hasDocuments) {
                        // Cannot delete - has associated records
                        \core\notification::error(get_string('error:cannotdeletewithrecords', 'local_conocer_cert'));
                    } else {
                        // Safe to delete
                        $DB->delete_records('local_conocer_candidatos', ['id' => $targetid]);
                        \core\notification::success(get_string('candidate_deleted', 'local_conocer_cert'));
                    }
                }
                break;
                
            case 'suspend':
                if ($DB->record_exists('local_conocer_candidatos', ['id' => $targetid])) {
                    $DB->set_field('local_conocer_candidatos', 'estado', 'suspendido', ['id' => $targetid]);
                    \core\notification::success(get_string('candidate_suspended', 'local_conocer_cert'));
                }
                break;
                
            case 'activate':
                if ($DB->record_exists('local_conocer_candidatos', ['id' => $targetid])) {
                    $DB->set_field('local_conocer_candidatos', 'estado', 'activo', ['id' => $targetid]);
                    \core\notification::success(get_string('candidate_activated', 'local_conocer_cert'));
                }
                break;
        }
        
        // Redirect to remove action from URL
        redirect($PAGE->url);
    }
    
    // Handle view/edit/document actions with separate templates or includes
    if ($action == 'view' || $action == 'edit' || $action == 'documents' || $action == 'process') {
        if ($targetid) {
            // Include the appropriate action file or generate content directly
            switch ($action) {
                case 'view':
                    // Template for viewing candidate details
                    require_once($CFG->dirroot . '/local/conocer_cert/pages/candidate_view.php');
                    die(); // Exit after handling the view
                    break;
                
                case 'edit':
                    // Template for editing candidate
                    require_once($CFG->dirroot . '/local/conocer_cert/pages/candidate_edit.php');
                    die(); // Exit after handling the edit
                    break;
                
                case 'documents':
                    // Template for managing candidate documents
                    require_once($CFG->dirroot . '/local/conocer_cert/pages/candidate_documents.php');
                    die(); // Exit after handling documents
                    break;
                
                case 'process':
                    // Template for viewing candidate process
                    require_once($CFG->dirroot . '/local/conocer_cert/pages/process_view.php');
                    die(); // Exit after handling process view
                    break;
            }
        }
    }
    
    // Handle add action
    if ($action == 'add') {
        // Template for adding a new candidate
        require_once($CFG->dirroot . '/local/conocer_cert/pages/candidate_add.php');
        die(); // Exit after handling the add
    }
}

// Get list of competencies for filter
$competencias = $DB->get_records_menu('local_conocer_competencias', ['activo' => 1], 'nombre', 'id, nombre');

// Build the SQL query for candidates
$params = [];
$sql_select = "SELECT c.*, u.firstname, u.lastname, u.email, comp.nombre as competencia_nombre, comp.codigo as competencia_codigo";
$sql_count = "SELECT COUNT(c.id)";
$sql_from = " FROM {local_conocer_candidatos} c
              JOIN {user} u ON c.userid = u.id
              JOIN {local_conocer_competencias} comp ON c.competencia_id = comp.id";
$sql_where = " WHERE 1=1";

// Apply filters
if ($search) {
    $sql_where .= " AND (
        " . $DB->sql_like('u.firstname', ':firstname', false) . " OR
        " . $DB->sql_like('u.lastname', ':lastname', false) . " OR
        " . $DB->sql_like('u.email', ':email', false) . " OR
        " . $DB->sql_like('c.curp', ':curp', false) . " OR
        " . $DB->sql_like('comp.codigo', ':codigo', false) . "
    )";
    $params['firstname'] = '%' . $search . '%';
    $params['lastname'] = '%' . $search . '%';
    $params['email'] = '%' . $search . '%';
    $params['curp'] = '%' . $search . '%';
    $params['codigo'] = '%' . $search . '%';
}

if ($competenciaid) {
    $sql_where .= " AND c.competencia_id = :competenciaid";
    $params['competenciaid'] = $competenciaid;
}

if ($estado) {
    $sql_where .= " AND c.estado = :estado";
    $params['estado'] = $estado;
}

// Get total count
$totalcandidates = $DB->count_records_sql($sql_count . $sql_from . $sql_where, $params);

// Apply sort order
$sql_order = " ORDER BY $sort $dir";

// Get paginated data
$candidates = $DB->get_records_sql($sql_select . $sql_from . $sql_where . $sql_order, $params, $page * $perpage, $perpage);

// Process candidate data for display
foreach ($candidates as $candidate) {
    // Get process information
    $process = $DB->get_record('local_conocer_procesos', ['candidato_id' => $candidate->id], '*', IGNORE_MULTIPLE);
    
    if ($process) {
        $candidate->process_stage = $process->etapa;
        $candidate->process_result = $process->resultado;
        
        // If evaluator is assigned, get their name
        if (!empty($process->evaluador_id)) {
            $evaluator = $DB->get_record('user', ['id' => $process->evaluador_id]);
            $candidate->evaluator_name = $evaluator ? fullname($evaluator) : '';
        }
    }
    
    // Check if candidate has documents
    $candidate->document_count = $DB->count_records('local_conocer_documentos', ['candidato_id' => $candidate->id]);
    
    // Create action URLs
    $candidate->view_url = new moodle_url('/local/conocer_cert/pages/candidates.php', ['action' => 'view', 'id' => $candidate->id]);
    $candidate->edit_url = new moodle_url('/local/conocer_cert/pages/candidates.php', ['action' => 'edit', 'id' => $candidate->id]);
    $candidate->documents_url = new moodle_url('/local/conocer_cert/pages/candidates.php', ['action' => 'documents', 'id' => $candidate->id]);
    
    if ($process) {
        $candidate->process_url = new moodle_url('/local/conocer_cert/pages/candidates.php', ['action' => 'process', 'id' => $process->id]);
        
        // Assign evaluator URL
        if (empty($process->evaluador_id) && $process->etapa == 'evaluacion') {
            $candidate->assign_evaluator_url = new moodle_url('/local/conocer_cert/pages/evaluators.php', ['action' => 'assign', 'id' => $candidate->id]);
        }
    }
    
    // Delete/Suspend/Activate URLs
    $baseUrl = new moodle_url('/local/conocer_cert/pages/candidates.php', [
        'candidateid' => $candidate->id,
        'sesskey' => sesskey()
    ]);
    
    $candidate->delete_url = new moodle_url($baseUrl, ['action' => 'delete']);
    
    if ($candidate->estado == 'activo' || $candidate->estado == 'pendiente') {
        $candidate->suspend_url = new moodle_url($baseUrl, ['action' => 'suspend']);
    } else {
        $candidate->activate_url = new moodle_url($baseUrl, ['action' => 'activate']);
    }
}

// Setup search and filter form data
$filters = [
    'search' => $search,
    'competenciaid' => $competenciaid,
    'estado' => $estado,
    'competencias' => $competencias,
    'estados' => [
        '' => get_string('all_statuses', 'local_conocer_cert'),
        'pendiente' => get_string('estado_pendiente', 'local_conocer_cert'),
        'activo' => get_string('estado_activo', 'local_conocer_cert'),
        'suspendido' => get_string('estado_suspendido', 'local_conocer_cert'),
        'completado' => get_string('estado_completado', 'local_conocer_cert')
    ]
];

// Setup paging
$baseurl = new moodle_url('/local/conocer_cert/pages/candidates.php', [
    'sort' => $sort,
    'dir' => $dir,
    'search' => $search,
    'competenciaid' => $competenciaid,
    'estado' => $estado
]);
$paging = new paging_bar($totalcandidates, $page, $perpage, $baseurl);

// Setup page buttons
$buttons = [
    'add' => new single_button(
        new moodle_url('/local/conocer_cert/pages/candidates.php', ['action' => 'add']),
        get_string('add_candidate', 'local_conocer_cert'),
        'get'
    )
];

// Output page
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('manage_candidates', 'local_conocer_cert'));

// Display buttons
echo $OUTPUT->container_start('buttons');
echo $OUTPUT->render($buttons['add']);
echo $OUTPUT->container_end();

// Display search and filter form
?>
<form id="candidatesfilterform" method="get" action="<?php echo $PAGE->url; ?>" class="mb-4">
    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                <label for="search"><?php echo get_string('search'); ?></label>
                <input type="text" id="search" name="search" class="form-control" value="<?php echo s($search); ?>" 
                       placeholder="<?php echo get_string('search_candidates_placeholder', 'local_conocer_cert'); ?>">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label for="competenciaid"><?php echo get_string('competency', 'local_conocer_cert'); ?></label>
                <select id="competenciaid" name="competenciaid" class="form-control">
                    <option value="0"><?php echo get_string('all_competencies', 'local_conocer_cert'); ?></option>
                    <?php foreach ($competencias as $id => $nombre): ?>
                        <option value="<?php echo $id; ?>" <?php echo ($competenciaid == $id) ? 'selected' : ''; ?>>
                            <?php echo format_string($nombre); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label for="estado"><?php echo get_string('status', 'local_conocer_cert'); ?></label>
                <select id="estado" name="estado" class="form-control">
                    <?php foreach ($filters['estados'] as $value => $label): ?>
                        <option value="<?php echo $value; ?>" <?php echo ($estado === $value) ? 'selected' : ''; ?>>
                            <?php echo $label; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <div class="form-group">
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fa fa-search"></i> <?php echo get_string('search'); ?>
                </button>
            </div>
        </div>
    </div>
</form>

<?php
// Display results count and paging
if ($totalcandidates > 0) {
    echo html_writer::tag('p', get_string('displaying_candidates', 'local_conocer_cert', [
        'count' => count($candidates),
        'total' => $totalcandidates
    ]));
}

// Display candidates table
if (!empty($candidates)) {
    // Setup table headers with sorting
    $columns = [
        'fullname' => get_string('fullname'),
        'email' => get_string('email'),
        'competencia' => get_string('competency', 'local_conocer_cert'),
        'nivel' => get_string('level', 'local_conocer_cert'),
        'estado' => get_string('status', 'local_conocer_cert'),
        'etapa' => get_string('stage', 'local_conocer_cert'),
        'actions' => get_string('actions')
    ];
    
    $sortableColumns = ['lastname', 'email', 'nivel', 'estado'];
    $currentSort = $sort;
    $currentDir = $dir;

    echo '<div class="table-responsive">';
    echo '<table class="table table-striped table-hover">';
    echo '<thead>';
    echo '<tr>';
    foreach ($columns as $column => $heading) {
        echo '<th>';
        // Make some columns sortable
        if (in_array($column, $sortableColumns)) {
            $sortUrl = new moodle_url($PAGE->url, [
                'sort' => $column,
                'dir' => $currentSort === $column ? ($currentDir === 'ASC' ? 'DESC' : 'ASC') : 'ASC'
            ]);
            echo html_writer::link($sortUrl, $heading);
            if ($currentSort === $column) {
                echo ' <i class="fa fa-sort-' . strtolower($currentDir) . '"></i>';
            }
        } else {
            echo $heading;
        }
        echo '</th>';
    }
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    foreach ($candidates as $candidate) {
        echo '<tr>';
        
        // Full Name
        echo '<td>';
        echo html_writer::link($candidate->view_url, fullname($candidate));
        echo '</td>';
        
        // Email
        echo '<td>' . $candidate->email . '</td>';
        
        // Competencia
        echo '<td>';
        if (!empty($candidate->competencia_codigo)) {
            echo '<span class="badge badge-secondary">' . $candidate->competencia_codigo . '</span> ';
        }
        echo $candidate->competencia_nombre;
        echo '</td>';
        
        // Nivel
        echo '<td>' . $candidate->nivel . '</td>';
        
        // Estado
        echo '<td>';
        switch ($candidate->estado) {
            case 'activo':
                echo '<span class="badge badge-success">' . get_string('estado_activo', 'local_conocer_cert') . '</span>';
                break;
            case 'pendiente':
                echo '<span class="badge badge-warning">' . get_string('estado_pendiente', 'local_conocer_cert') . '</span>';
                break;
            case 'suspendido':
                echo '<span class="badge badge-danger">' . get_string('estado_suspendido', 'local_conocer_cert') . '</span>';
                break;
            case 'completado':
                echo '<span class="badge badge-info">' . get_string('estado_completado', 'local_conocer_cert') . '</span>';
                break;
            default:
                echo $candidate->estado;
        }
        echo '</td>';
        
        // Etapa del proceso
        echo '<td>';
        if (isset($candidate->process_stage)) {
            $stageString = get_string('etapa_' . $candidate->process_stage, 'local_conocer_cert');
            switch ($candidate->process_stage) {
                case 'solicitud':
                    echo '<span class="badge badge-primary">' . $stageString . '</span>';
                    break;
                case 'evaluacion':
                    echo '<span class="badge badge-warning">' . $stageString . '</span>';
                    if (!empty($candidate->evaluator_name)) {
                        echo '<br><small>' . get_string('evaluator', 'local_conocer_cert') . ': ' . $candidate->evaluator_name . '</small>';
                    } else {
                        echo '<br><small class="text-danger">' . get_string('no_evaluator_assigned', 'local_conocer_cert') . '</small>';
                    }
                    break;
                case 'aprobado':
                    echo '<span class="badge badge-success">' . $stageString . '</span>';
                    break;
                case 'rechazado':
                    echo '<span class="badge badge-danger">' . $stageString . '</span>';
                    break;
                default:
                    echo $stageString;
            }
            
            // Show result if available
            if (!empty($candidate->process_result)) {
                $resultString = get_string('resultado_' . $candidate->process_result, 'local_conocer_cert');
                echo '<br><small>' . get_string('result', 'local_conocer_cert') . ': ' . $resultString . '</small>';
            }
        } else {
            echo '<span class="badge badge-secondary">' . get_string('no_process', 'local_conocer_cert') . '</span>';
        }
        echo '</td>';
        
        // Actions
        echo '<td>';
        echo '<div class="btn-group" role="group">';
        
        // View button
        echo html_writer::link(
            $candidate->view_url,
            '<i class="fa fa-eye"></i>',
            ['class' => 'btn btn-sm btn-outline-primary', 'title' => get_string('view')]
        );
        
        // Edit button
        echo html_writer::link(
            $candidate->edit_url,
            '<i class="fa fa-edit"></i>',
            ['class' => 'btn btn-sm btn-outline-secondary', 'title' => get_string('edit')]
        );
        
        // Documents button
        echo html_writer::link(
            $candidate->documents_url,
            '<i class="fa fa-file-alt"></i>',
            ['class' => 'btn btn-sm btn-outline-info', 'title' => get_string('documents', 'local_conocer_cert')]
        );
        
        // Process button, if exists
        if (isset($candidate->process_url)) {
            echo html_writer::link(
                $candidate->process_url,
                '<i class="fa fa-tasks"></i>',
                ['class' => 'btn btn-sm btn-outline-primary', 'title' => get_string('process', 'local_conocer_cert')]
            );
        }
        
        // Assign evaluator button, if needed
        if (isset($candidate->assign_evaluator_url)) {
            echo html_writer::link(
                $candidate->assign_evaluator_url,
                '<i class="fa fa-user-plus"></i>',
                ['class' => 'btn btn-sm btn-outline-warning', 'title' => get_string('assign_evaluator', 'local_conocer_cert')]
            );
        }
        
        // Delete/Suspend/Activate buttons
        echo '</div>';
        echo '<div class="btn-group mt-1" role="group">';
        
        if (isset($candidate->suspend_url)) {
            echo html_writer::link(
                $candidate->suspend_url,
                '<i class="fa fa-pause"></i>',
                [
                    'class' => 'btn btn-sm btn-outline-warning', 
                    'title' => get_string('suspend', 'local_conocer_cert'),
                    'onclick' => 'return confirm("' . get_string('confirm_suspend_candidate', 'local_conocer_cert') . '");'
                ]
            );
        }
        
        if (isset($candidate->activate_url)) {
            echo html_writer::link(
                $candidate->activate_url,
                '<i class="fa fa-play"></i>',
                [
                    'class' => 'btn btn-sm btn-outline-success', 
                    'title' => get_string('activate', 'local_conocer_cert'),
                    'onclick' => 'return confirm("' . get_string('confirm_activate_candidate', 'local_conocer_cert') . '");'
                ]
            );
        }
        
        echo html_writer::link(
            $candidate->delete_url,
            '<i class="fa fa-trash"></i>',
            [
                'class' => 'btn btn-sm btn-outline-danger', 
                'title' => get_string('delete'),
                'onclick' => 'return confirm("' . get_string('confirm_delete_candidate', 'local_conocer_cert') . '");'
            ]
        );
        
        echo '</div>';
        echo '</td>';
        
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
    
    // Display pagination
    echo $OUTPUT->paging_bar($totalcandidates, $page, $perpage, $baseurl);
} else {
    echo $OUTPUT->notification(get_string('no_candidates_found', 'local_conocer_cert'), 'info');
}

echo $OUTPUT->footer();
