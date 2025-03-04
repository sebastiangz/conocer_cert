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
 * Manage evaluators for CONOCER certifications.
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
$estatus = optional_param('estatus', '', PARAM_ALPHANUMEXT);
$action = optional_param('action', '', PARAM_ALPHANUMEXT);
$evaluadorid = optional_param('evaluadorid', 0, PARAM_INT);
$id = optional_param('id', 0, PARAM_INT); // ID for view/edit/etc. actions
$candidateid = optional_param('candidateid', 0, PARAM_INT); // ID for assign action

// Setup page
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/conocer_cert/pages/evaluators.php'));
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('manage_evaluators', 'local_conocer_cert'));
$PAGE->set_heading(get_string('manage_evaluators', 'local_conocer_cert'));
$PAGE->navbar->add(get_string('pluginname', 'local_conocer_cert'), new moodle_url('/local/conocer_cert/index.php'));
$PAGE->navbar->add(get_string('manage_evaluators', 'local_conocer_cert'));

// Check permissions
require_login();
require_capability('local/conocer_cert:manageevaluators', $context);

// Process actions
if ($action) {
    // Use evaluator ID from either id or evaluadorid param
    $targetid = ($id > 0) ? $id : $evaluadorid;
    
    // Actions that need an evaluator ID and session key check
    if (($action == 'delete' || $action == 'suspend' || $action == 'activate') && $targetid && confirm_sesskey()) {
        switch ($action) {
            case 'delete':
                if ($DB->record_exists('local_conocer_evaluadores', ['id' => $targetid])) {
                    // Check if evaluator has assigned candidates
                    $evaluator = $DB->get_record('local_conocer_evaluadores', ['id' => $targetid]);
                    if (!$evaluator) {
                        break;
                    }
                    
                    $hasAssignments = $DB->record_exists('local_conocer_procesos', ['evaluador_id' => $evaluator->userid]);
                    
                    if ($hasAssignments) {
                        // Cannot delete - has assigned candidates
                        \core\notification::error(get_string('error:cannotdeleteevaluator', 'local_conocer_cert'));
                    } else {
                        // Safe to delete, but first clean up role assignment if needed
                        $evaluator_role_id = \local_conocer_cert\evaluator\manager::get_evaluator_role_id();
                        if ($evaluator_role_id) {
                            role_unassign($evaluator_role_id, $evaluator->userid, $context->id);
                        }
                        
                        $DB->delete_records('local_conocer_evaluadores', ['id' => $targetid]);
                        \core\notification::success(get_string('evaluator_deleted', 'local_conocer_cert'));
                    }
                }
                break;
                
            case 'suspend':
                if ($DB->record_exists('local_conocer_evaluadores', ['id' => $targetid])) {
                    $DB->set_field('local_conocer_evaluadores', 'estatus', 'suspendido', ['id' => $targetid]);
                    \core\notification::success(get_string('evaluator_suspended', 'local_conocer_cert'));
                }
                break;
                
            case 'activate':
                if ($DB->record_exists('local_conocer_evaluadores', ['id' => $targetid])) {
                    $DB->set_field('local_conocer_evaluadores', 'estatus', 'activo', ['id' => $targetid]);
                    \core\notification::success(get_string('evaluator_activated', 'local_conocer_cert'));
                }
                break;
        }
        
        // Redirect to remove action from URL
        redirect($PAGE->url);
    }
    
    // Handle assign evaluator action
    if ($action == 'assign' && $candidateid && confirm_sesskey()) {
        if (isset($_POST['evaluator']) && is_numeric($_POST['evaluator']) && $_POST['evaluator'] > 0) {
            $evaluatoruserid = (int)$_POST['evaluator'];
            $comments = optional_param('comments', '', PARAM_TEXT);
            
            $result = \local_conocer_cert\evaluator\manager::assign_evaluator_to_candidate($candidateid, $evaluatoruserid, $comments);
            
            if ($result) {
                \core\notification::success(get_string('evaluator_assigned', 'local_conocer_cert'));
                
                // Redirect to candidate view
                redirect(new moodle_url('/local/conocer_cert/pages/candidates.php', 
                    ['action' => 'view', 'id' => $candidateid]));
            } else {
                \core\notification::error(get_string('error:evaluator_assignment_failed', 'local_conocer_cert'));
            }
        } else {
            // Display assignment form
            $candidate = $DB->get_record('local_conocer_candidatos', ['id' => $candidateid]);
            if (!$candidate) {
                \core\notification::error(get_string('error:candidatenotfound', 'local_conocer_cert'));
                redirect($PAGE->url);
            }
            
            // Get available evaluators for this competencia
            $evaluators = \local_conocer_cert\evaluator\manager::get_available_evaluators($candidate->competencia_id);
            
            // Custom rendering for the assignment form
            echo $OUTPUT->header();
            echo $OUTPUT->heading(get_string('assign_evaluator', 'local_conocer_cert'));
            
            // Get user info and competency
            $candidateuser = $DB->get_record('user', ['id' => $candidate->userid]);
            $competencia = $DB->get_record('local_conocer_competencias', ['id' => $candidate->competencia_id]);
            
            echo html_writer::tag('p', get_string('assign_evaluator_to', 'local_conocer_cert', [
                'candidate' => fullname($candidateuser),
                'competencia' => $competencia->nombre,
                'nivel' => $candidate->nivel
            ]));
            
            if (empty($evaluators)) {
                echo $OUTPUT->notification(get_string('no_available_evaluators', 'local_conocer_cert'), 'warning');
            } else {
                echo html_writer::start_tag('form', ['method' => 'post', 'action' => $PAGE->url]);
                echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'assign']);
                echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'candidateid', 'value' => $candidateid]);
                echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
                
                // Evaluator selection
                echo html_writer::start_div('form-group');
                echo html_writer::tag('label', get_string('select_evaluator', 'local_conocer_cert'), ['for' => 'evaluator']);
                echo html_writer::start_tag('select', ['id' => 'evaluator', 'name' => 'evaluator', 'class' => 'form-control']);
                echo html_writer::tag('option', get_string('select'), ['value' => '']);
                
                foreach ($evaluators as $evaluator) {
                    $option_text = fullname($evaluator) . ' (' . $evaluator->email . ')';
                    echo html_writer::tag('option', $option_text, ['value' => $evaluator->userid]);
                }
                
                echo html_writer::end_tag('select');
                echo html_writer::end_div();
                
                // Comments field
                echo html_writer::start_div('form-group');
                echo html_writer::tag('label', get_string('comments', 'local_conocer_cert'), ['for' => 'comments']);
                echo html_writer::tag('textarea', '', [
                    'id' => 'comments',
                    'name' => 'comments',
                    'class' => 'form-control',
                    'rows' => 3
                ]);
                echo html_writer::end_div();
                
                // Submit button
                echo html_writer::start_div('form-group');
                echo html_writer::tag('button', get_string('assign', 'local_conocer_cert'), [
                    'type' => 'submit',
                    'class' => 'btn btn-primary'
                ]);
                echo html_writer::link(
                    new moodle_url('/local/conocer_cert/pages/candidates.php'),
                    get_string('cancel'),
                    ['class' => 'btn btn-secondary ml-2']
                );
                echo html_writer::end_div();
                
                echo html_writer::end_tag('form');
            }
            
            echo $OUTPUT->footer();
            die();
        }
    }
    
    // Handle view/edit actions
    if ($action == 'view' || $action == 'edit') {
        if ($targetid) {
            // Include the appropriate action file
            switch ($action) {
                case 'view':
                    // Template for viewing evaluator details
                    require_once($CFG->dirroot . '/local/conocer_cert/pages/evaluator_view.php');
                    die(); // Exit after handling the view
                    break;
                
                case 'edit':
                    // Template for editing evaluator
                    require_once($CFG->dirroot . '/local/conocer_cert/pages/evaluator_edit.php');
                    die(); // Exit after handling the edit
                    break;
            }
        }
    }
    
    // Handle add action
    if ($action == 'add') {
        // Template for adding a new evaluator
        require_once($CFG->dirroot . '/local/conocer_cert/pages/evaluator_add.php');
        die(); // Exit after handling the add
    }
}

// Get list of competencies for filter
$competencias = $DB->get_records_menu('local_conocer_competencias', ['activo' => 1], 'nombre', 'id, nombre');

// Available statuses for filter
$statuses = [
    '' => get_string('all_statuses', 'local_conocer_cert'),
    'activo' => get_string('status_active', 'local_conocer_cert'),
    'pendiente' => get_string('status_pending', 'local_conocer_cert'),
    'suspendido' => get_string('status_suspended', 'local_conocer_cert'),
    'inactivo' => get_string('status_inactive', 'local_conocer_cert')
];

// Build the SQL query for evaluators
$params = [];
$sql_select = "SELECT e.*, u.firstname, u.lastname, u.email,
               (SELECT COUNT(p.id) FROM {local_conocer_procesos} p WHERE p.evaluador_id = e.userid AND p.etapa = 'evaluacion') AS active_count,
               (SELECT COUNT(p.id) FROM {local_conocer_procesos} p WHERE p.evaluador_id = e.userid) AS total_evaluations";
$sql_count = "SELECT COUNT(e.id)";
$sql_from = " FROM {local_conocer_evaluadores} e
              JOIN {user} u ON e.userid = u.id";
$sql_where = " WHERE 1=1";

// Apply filters
if ($search) {
    $sql_where .= " AND (
        " . $DB->sql_like('u.firstname', ':firstname', false) . " OR
        " . $DB->sql_like('u.lastname', ':lastname', false) . " OR
        " . $DB->sql_like('u.email', ':email', false) . " OR
        " . $DB->sql_like('e.curp', ':curp', false) . "
    )";
    $params['firstname'] = '%' . $search . '%';
    $params['lastname'] = '%' . $search . '%';
    $params['email'] = '%' . $search . '%';
    $params['curp'] = '%' . $search . '%';
}

if ($competenciaid) {
    $sql_where .= " AND e.competencias LIKE :competenciaid";
    $params['competenciaid'] = '%"' . $competenciaid . '"%'; // Assuming JSON format for competencias field
}

if ($estatus) {
    $sql_where .= " AND e.estatus = :estatus";
    $params['estatus'] = $estatus;
}

// Get total count
$totalevaluators = $DB->count_records_sql($sql_count . $sql_from . $sql_where, $params);

// Apply sort order
$sql_order = " ORDER BY $sort $dir";

// Get paginated data
$evaluators = $DB->get_records_sql($sql_select . $sql_from . $sql_where . $sql_order, $params, $page * $perpage, $perpage);

// Process evaluator data for display
foreach ($evaluators as $evaluator) {
    // Format competencias for display
    if (!empty($evaluator->competencias)) {
        // Decode from JSON
        $competenciasIds = json_decode($evaluator->competencias);
        
        if (is_array($competenciasIds) && !empty($competenciasIds)) {
            // Get competency names and codes
            list($sql, $sqlparams) = $DB->get_in_or_equal($competenciasIds);
            $competenciaRecords = $DB->get_records_select('local_conocer_competencias', "id $sql", $sqlparams, '', 'id, nombre, codigo');
            
            $evaluator->competencias_list = [];
            foreach ($competenciaRecords as $competencia) {
                $evaluator->competencias_list[] = [
                    'id' => $competencia->id,
                    'nombre' => $competencia->nombre,
                    'codigo' => $competencia->codigo
                ];
            }
            
            $evaluator->competencias_count = count($evaluator->competencias_list);
        } else {
            $evaluator->competencias_count = 0;
        }
    } else {
        $evaluator->competencias_count = 0;
    }
    
    // Create action URLs
    $evaluator->view_url = new moodle_url('/local/conocer_cert/pages/evaluators.php', ['action' => 'view', 'id' => $evaluator->id]);
    $evaluator->edit_url = new moodle_url('/local/conocer_cert/pages/evaluators.php', ['action' => 'edit', 'id' => $evaluator->id]);
    
    // Delete/Suspend/Activate URLs
    $baseUrl = new moodle_url('/local/conocer_cert/pages/evaluators.php', [
        'evaluadorid' => $evaluator->id,
        'sesskey' => sesskey()
    ]);
    
    $evaluator->delete_url = new moodle_url($baseUrl, ['action' => 'delete']);
    
    // Status-specific actions
    if ($evaluator->estatus == 'activo') {
        $evaluator->suspend_url = new moodle_url($baseUrl, ['action' => 'suspend']);
    } else if ($evaluator->estatus == 'suspendido' || $evaluator->estatus == 'inactivo') {
        $evaluator->activate_url = new moodle_url($baseUrl, ['action' => 'activate']);
    }
}

// Setup paging
$baseurl = new moodle_url('/local/conocer_cert/pages/evaluators.php', [
    'sort' => $sort,
    'dir' => $dir,
    'search' => $search,
    'competenciaid' => $competenciaid,
    'estatus' => $estatus
]);
$paging = new paging_bar($totalevaluators, $page, $perpage, $baseurl);

// Setup page buttons
$buttons = [
    'add' => new single_button(
        new moodle_url('/local/conocer_cert/pages/evaluators.php', ['action' => 'add']),
        get_string('add_evaluator', 'local_conocer_cert'),
        'get'
    )
];

// Output page
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('manage_evaluators', 'local_conocer_cert'));

// Display buttons
echo $OUTPUT->container_start('buttons');
echo $OUTPUT->render($buttons['add']);
echo $OUTPUT->container_end();

// Display search and filter form
?>
<form id="evaluatorsfilterform" method="get" action="<?php echo $PAGE->url; ?>" class="mb-4">
    <div class="row">
        <div class="col-md-3">
            <div class="form-group">
                <label for="search"><?php echo get_string('search'); ?></label>
                <input type="text" id="search" name="search" class="form-control" value="<?php echo s($search); ?>" 
                       placeholder="<?php echo get_string('search_evaluators_placeholder', 'local_conocer_cert'); ?>">
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
                <label for="estatus"><?php echo get_string('status', 'local_conocer_cert'); ?></label>
                <select id="estatus" name="estatus" class="form-control">
                    <?php foreach ($statuses as $value => $label): ?>
                        <option value="<?php echo $value; ?>" <?php echo ($estatus === $value) ? 'selected' : ''; ?>>
                            <?php echo $label; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="col-md-3 d-flex align-items-end">
            <div class="form-group">
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-search"></i> <?php echo get_string('search'); ?>
                </button>
                <a href="<?php echo $PAGE->url; ?>" class="btn btn-secondary ml-2">
                    <i class="fa fa-redo"></i> <?php echo get_string('reset'); ?>
                </a>
            </div>
        </div>
    </div>
</form>

<?php
// Display results count and paging
if ($totalevaluators > 0) {
    echo html_writer::tag('p', get_string('displaying_evaluators', 'local_conocer_cert', [
        'count' => count($evaluators),
        'total' => $totalevaluators
    ]));
}

// Display evaluators table
if (!empty($evaluators)) {
    // Setup table headers with sorting
    $columns = [
        'name' => get_string('fullname'),
        'email' => get_string('email'),
        'competencias' => get_string('competencies', 'local_conocer_cert'),
        'workload' => get_string('workload', 'local_conocer_cert'),
        'estatus' => get_string('status', 'local_conocer_cert'),
        'experience' => get_string('experience', 'local_conocer_cert'),
        'actions' => get_string('actions')
    ];
    
    $sortableColumns = ['lastname', 'email', 'estatus'];
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
    
    foreach ($evaluators as $evaluator) {
        echo '<tr>';
        
        // Name
        echo '<td>';
        echo html_writer::link($evaluator->view_url, fullname($evaluator));
        echo '</td>';
        
        // Email
        echo '<td>' . $evaluator->email . '</td>';
        
        // Competencias
        echo '<td>';
        if ($evaluator->competencias_count > 0) {
            echo '<span class="badge badge-info">' . $evaluator->competencias_count . ' ' . get_string('competencies', 'local_conocer_cert') . '</span>';
            
            echo '<div class="small mt-1">';
            $competenciasShown = 0;
            foreach ($evaluator->competencias_list as $competencia) {
                if ($competenciasShown < 2) { // Only show first 2
                    echo '<span class="badge badge-secondary">' . $competencia['codigo'] . '</span> ';
                    $competenciasShown++;
                }
            }
            
            if ($evaluator->competencias_count > 2) {
                echo ' <span class="badge badge-light">+' . ($evaluator->competencias_count - 2) . ' ' . get_string('more', 'local_conocer_cert') . '</span>';
            }
            echo '</div>';
        } else {
            echo '<span class="badge badge-secondary">' . get_string('no_competencies', 'local_conocer_cert') . '</span>';
        }
        echo '</td>';
        
        // Workload
        echo '<td>';
        $workloadPercent = 0;
        if (isset($evaluator->max_candidatos) && $evaluator->max_candidatos > 0) {
            $workloadPercent = min(100, round(($evaluator->active_count / $evaluator->max_candidatos) * 100));
        }
        
        echo '<div class="progress">';
        $progressClass = 'bg-success';
        if ($workloadPercent > 75) {
            $progressClass = 'bg-danger';
        } else if ($workloadPercent > 50) {
            $progressClass = 'bg-warning';
        }
        echo '<div class="progress-bar ' . $progressClass . '" role="progressbar" style="width: ' . $workloadPercent . '%;" ';
        echo 'aria-valuenow="' . $workloadPercent . '" aria-valuemin="0" aria-valuemax="100">' . $workloadPercent . '%</div>';
        echo '</div>';
        
        echo '<div class="small mt-1">';
        echo $evaluator->active_count . ' ' . get_string('active', 'local_conocer_cert');
        if (isset($evaluator->max_candidatos)) {
            echo ' / ' . $evaluator->max_candidatos . ' ' . get_string('max', 'local_conocer_cert');
        }
        
        if ($evaluator->total_evaluations > 0) {
            echo '<br>' . $evaluator->total_evaluations . ' ' . get_string('total_evaluations', 'local_conocer_cert');
        }
        echo '</div>';
        
        echo '</td>';
        
        // Estatus
        echo '<td>';
        switch ($evaluator->estatus) {
            case 'activo':
                echo '<span class="badge badge-success">' . get_string('status_active', 'local_conocer_cert') . '</span>';
                break;
            case 'pendiente':
                echo '<span class="badge badge-warning">' . get_string('status_pending', 'local_conocer_cert') . '</span>';
                break;
            case 'suspendido':
                echo '<span class="badge badge-danger">' . get_string('status_suspended', 'local_conocer_cert') . '</span>';
                break;
            case 'inactivo':
                echo '<span class="badge badge-secondary">' . get_string('status_inactive', 'local_conocer_cert') . '</span>';
                break;
            default:
                echo $evaluator->estatus;
        }
        echo '</td>';
        
        // Experience
        echo '<td>';
        if (!empty($evaluator->experiencia_anios)) {
            echo $evaluator->experiencia_anios . ' ' . get_string('years', 'local_conocer_cert');
            
            if (!empty($evaluator->grado_academico)) {
                echo '<br><small>' . get_string($evaluator->grado_academico, 'local_conocer_cert') . '</small>';
            }
        } else {
            echo '<span class="text-muted">-</span>';
        }
        echo '</td>';
        
        // Actions
        echo '<td>';
        echo '<div class="btn-group" role="group">';
        
        // View button
        echo html_writer::link(
            $evaluator->view_url,
            '<i class="fa fa-eye"></i>',
            ['class' => 'btn btn-sm btn-outline-primary', 'title' => get_string('view')]
        );
        
        // Edit button
        echo html_writer::link(
            $evaluator->edit_url,
            '<i class="fa fa-edit"></i>',
            ['class' => 'btn btn-sm btn-outline-secondary', 'title' => get_string('edit')]
        );
        
        echo '</div>';
        
        // Status/Delete buttons
        echo '<div class="btn-group mt-1" role="group">';
        
        // Suspend/Activate buttons
        if (isset($evaluator->suspend_url)) {
            echo html_writer::link(
                $evaluator->suspend_url,
                '<i class="fa fa-pause"></i>',
                [
                    'class' => 'btn btn-sm btn-outline-warning', 
                    'title' => get_string('suspend', 'local_conocer_cert'),
                    'onclick' => 'return confirm("' . get_string('confirm_suspend_evaluator', 'local_conocer_cert') . '");'
                ]
            );
        }
        
        if (isset($evaluator->activate_url)) {
            echo html_writer::link(
                $evaluator->activate_url,
                '<i class="fa fa-play"></i>',
                [
                    'class' => 'btn btn-sm btn-outline-success', 
                    'title' => get_string('activate', 'local_conocer_cert'),
                    'onclick' => 'return confirm("' . get_string('confirm_activate_evaluator', 'local_conocer_cert') . '");'
                ]
            );
        }
        
        // Delete button (only if no active evaluations)
        if ($evaluator->active_count == 0) {
            echo html_writer::link(
                $evaluator->delete_url,
                '<i class="fa fa-trash"></i>',
                [
                    'class' => 'btn btn-sm btn-outline-danger', 
                    'title' => get_string('delete'),
                    'onclick' => 'return confirm("' . get_string('confirm_delete_evaluator', 'local_conocer_cert') . '");'
                ]
            );
        }
        
        echo '</div>';
        echo '</td>';
        
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
    
    // Display pagination
    echo $OUTPUT->paging_bar($totalevaluators, $page, $perpage, $baseurl);
} else {
    echo $OUTPUT->notification(get_string('no_evaluators_found', 'local_conocer_cert'), 'info');
}

echo $OUTPUT->footer();
