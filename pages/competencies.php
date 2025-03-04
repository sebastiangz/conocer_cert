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
 * Manage competencies for CONOCER certifications.
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
$sort = optional_param('sort', 'nombre', PARAM_ALPHANUMEXT);
$dir = optional_param('dir', 'ASC', PARAM_ALPHA);
$search = optional_param('search', '', PARAM_RAW);
$sector = optional_param('sector', '', PARAM_ALPHANUMEXT);
$action = optional_param('action', '', PARAM_ALPHANUMEXT);
$competenciaid = optional_param('competenciaid', 0, PARAM_INT);
$id = optional_param('id', 0, PARAM_INT); // ID for view/edit/etc. actions

// Setup page
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/conocer_cert/pages/competencies.php'));
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('manage_competencies', 'local_conocer_cert'));
$PAGE->set_heading(get_string('manage_competencies', 'local_conocer_cert'));
$PAGE->navbar->add(get_string('pluginname', 'local_conocer_cert'), new moodle_url('/local/conocer_cert/index.php'));
$PAGE->navbar->add(get_string('manage_competencies', 'local_conocer_cert'));

// Check permissions
require_login();
require_capability('local/conocer_cert:managecompetencies', $context);

// Process actions
if ($action) {
    // Use competency ID from either id or competenciaid param
    $targetid = ($id > 0) ? $id : $competenciaid;
    
    // Actions that need a competency ID and session key check
    if (($action == 'delete' || $action == 'activate' || $action == 'deactivate') && $targetid && confirm_sesskey()) {
        switch ($action) {
            case 'delete':
                if ($DB->record_exists('local_conocer_competencias', ['id' => $targetid])) {
                    // Check if competency has candidates or processes
                    $hasCandidates = $DB->record_exists('local_conocer_candidatos', ['competencia_id' => $targetid]);
                    
                    if ($hasCandidates) {
                        // Cannot delete - has associated records
                        \core\notification::error(get_string('error:cannotdeletecompetency', 'local_conocer_cert'));
                    } else {
                        // Safe to delete
                        $DB->delete_records('local_conocer_competencias', ['id' => $targetid]);
                        \core\notification::success(get_string('competency_deleted', 'local_conocer_cert'));
                    }
                }
                break;
                
            case 'activate':
                if ($DB->record_exists('local_conocer_competencias', ['id' => $targetid])) {
                    $DB->set_field('local_conocer_competencias', 'activo', 1, ['id' => $targetid]);
                    \core\notification::success(get_string('competency_activated', 'local_conocer_cert'));
                }
                break;
                
            case 'deactivate':
                if ($DB->record_exists('local_conocer_competencias', ['id' => $targetid])) {
                    $DB->set_field('local_conocer_competencias', 'activo', 0, ['id' => $targetid]);
                    \core\notification::success(get_string('competency_deactivated', 'local_conocer_cert'));
                }
                break;
        }
        
        // Redirect to remove action from URL
        redirect($PAGE->url);
    }
    
    // Handle view/edit actions
    if ($action == 'view' || $action == 'edit') {
        if ($targetid) {
            // Include the appropriate action file
            switch ($action) {
                case 'view':
                    // Template for viewing competency details
                    require_once($CFG->dirroot . '/local/conocer_cert/pages/competency_view.php');
                    die(); // Exit after handling the view
                    break;
                
                case 'edit':
                    // Template for editing competency
                    require_once($CFG->dirroot . '/local/conocer_cert/pages/competency_edit.php');
                    die(); // Exit after handling the edit
                    break;
            }
        }
    }
    
    // Handle add action
    if ($action == 'add') {
        // Template for adding a new competency
        require_once($CFG->dirroot . '/local/conocer_cert/pages/competency_add.php');
        die(); // Exit after handling the add
    }
}

// Available sectors for filter
$sectors = [
    '' => get_string('all_sectors', 'local_conocer_cert'),
    'agropecuario' => get_string('sector_agro', 'local_conocer_cert'),
    'industrial' => get_string('sector_industrial', 'local_conocer_cert'),
    'comercio' => get_string('sector_commerce', 'local_conocer_cert'),
    'servicios' => get_string('sector_services', 'local_conocer_cert'),
    'educacion' => get_string('sector_education', 'local_conocer_cert'),
    'tecnologia' => get_string('sector_technology', 'local_conocer_cert'),
    'salud' => get_string('sector_health', 'local_conocer_cert'),
    'otro' => get_string('sector_other', 'local_conocer_cert')
];

// Build the SQL query for competencies
$params = [];
$sql_select = "SELECT c.*, (SELECT COUNT(ca.id) FROM {local_conocer_candidatos} ca WHERE ca.competencia_id = c.id) AS candidate_count";
$sql_count = "SELECT COUNT(c.id)";
$sql_from = " FROM {local_conocer_competencias} c";
$sql_where = " WHERE 1=1";

// Apply filters
if ($search) {
    $sql_where .= " AND (
        " . $DB->sql_like('c.nombre', ':nombre', false) . " OR
        " . $DB->sql_like('c.codigo', ':codigo', false) . " OR
        " . $DB->sql_like('c.descripcion', ':descripcion', false) . "
    )";
    $params['nombre'] = '%' . $search . '%';
    $params['codigo'] = '%' . $search . '%';
    $params['descripcion'] = '%' . $search . '%';
}

if ($sector) {
    $sql_where .= " AND c.sector = :sector";
    $params['sector'] = $sector;
}

// Get total count
$totalcompetencies = $DB->count_records_sql($sql_count . $sql_from . $sql_where, $params);

// Apply sort order
$sql_order = " ORDER BY $sort $dir";

// Get paginated data
$competencies = $DB->get_records_sql($sql_select . $sql_from . $sql_where . $sql_order, $params, $page * $perpage, $perpage);

// Process competency data for display
foreach ($competencies as $competency) {
    // Format niveles_disponibles for display
    if (!empty($competency->niveles_disponibles)) {
        $niveles = explode(',', $competency->niveles_disponibles);
        $competency->niveles_list = [];
        
        foreach ($niveles as $nivel) {
            $competency->niveles_list[] = [
                'nivel' => $nivel,
                'texto' => get_string('level' . $nivel, 'local_conocer_cert')
            ];
        }
        
        // Join for simple text display
        $competency->niveles_texto = implode(', ', array_map(function($item) {
            return $item['texto'];
        }, $competency->niveles_list));
    } else {
        $competency->niveles_texto = get_string('no_levels', 'local_conocer_cert');
    }
    
    // Create action URLs
    $competency->view_url = new moodle_url('/local/conocer_cert/pages/competencies.php', ['action' => 'view', 'id' => $competency->id]);
    $competency->edit_url = new moodle_url('/local/conocer_cert/pages/competencies.php', ['action' => 'edit', 'id' => $competency->id]);
    
    // Delete/Activate/Deactivate URLs
    $baseUrl = new moodle_url('/local/conocer_cert/pages/competencies.php', [
        'competenciaid' => $competency->id,
        'sesskey' => sesskey()
    ]);
    
    $competency->delete_url = new moodle_url($baseUrl, ['action' => 'delete']);
    
    if ($competency->activo) {
        $competency->deactivate_url = new moodle_url($baseUrl, ['action' => 'deactivate']);
    } else {
        $competency->activate_url = new moodle_url($baseUrl, ['action' => 'activate']);
    }
}

// Setup paging
$baseurl = new moodle_url('/local/conocer_cert/pages/competencies.php', [
    'sort' => $sort,
    'dir' => $dir,
    'search' => $search,
    'sector' => $sector
]);
$paging = new paging_bar($totalcompetencies, $page, $perpage, $baseurl);

// Setup page buttons
$buttons = [
    'add' => new single_button(
        new moodle_url('/local/conocer_cert/pages/competencies.php', ['action' => 'add']),
        get_string('add_competency', 'local_conocer_cert'),
        'get'
    )
];

// Output page
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('manage_competencies', 'local_conocer_cert'));

// Display buttons
echo $OUTPUT->container_start('buttons');
echo $OUTPUT->render($buttons['add']);
echo $OUTPUT->container_end();

// Display search and filter form
?>
<form id="competenciesfilterform" method="get" action="<?php echo $PAGE->url; ?>" class="mb-4">
    <div class="row">
        <div class="col-md-5">
            <div class="form-group">
                <label for="search"><?php echo get_string('search'); ?></label>
                <input type="text" id="search" name="search" class="form-control" value="<?php echo s($search); ?>" 
                       placeholder="<?php echo get_string('search_competencies_placeholder', 'local_conocer_cert'); ?>">
            </div>
        </div>
        <div class="col-md-5">
            <div class="form-group">
                <label for="sector"><?php echo get_string('sector', 'local_conocer_cert'); ?></label>
                <select id="sector" name="sector" class="form-control">
                    <?php foreach ($sectors as $value => $label): ?>
                        <option value="<?php echo $value; ?>" <?php echo ($sector === $value) ? 'selected' : ''; ?>>
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
if ($totalcompetencies > 0) {
    echo html_writer::tag('p', get_string('displaying_competencies', 'local_conocer_cert', [
        'count' => count($competencies),
        'total' => $totalcompetencies
    ]));
}

// Display competencies table
if (!empty($competencies)) {
    // Setup table headers with sorting
    $columns = [
        'codigo' => get_string('competencycode', 'local_conocer_cert'),
        'nombre' => get_string('competencyname', 'local_conocer_cert'),
        'sector' => get_string('sector', 'local_conocer_cert'),
        'niveles' => get_string('competencylevels', 'local_conocer_cert'),
        'activo' => get_string('status', 'local_conocer_cert'),
        'candidates' => get_string('candidates', 'local_conocer_cert'),
        'actions' => get_string('actions')
    ];
    
    $sortableColumns = ['codigo', 'nombre', 'sector', 'activo'];
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
    
    foreach ($competencies as $competency) {
        echo '<tr>';
        
        // Competency Code
        echo '<td>';
        echo '<strong>' . $competency->codigo . '</strong>';
        echo '</td>';
        
        // Competency Name
        echo '<td>';
        echo html_writer::link($competency->view_url, format_string($competency->nombre));
        echo '</td>';
        
        // Sector
        echo '<td>' . get_string('sector_' . $competency->sector, 'local_conocer_cert') . '</td>';
        
        // Levels
        echo '<td>';
        if (!empty($competency->niveles_list)) {
            foreach ($competency->niveles_list as $nivel) {
                echo '<span class="badge badge-info mr-1">' . $nivel['texto'] . '</span>';
            }
        } else {
            echo '<span class="badge badge-secondary">' . get_string('no_levels', 'local_conocer_cert') . '</span>';
        }
        echo '</td>';
        
        // Status (Active/Inactive)
        echo '<td>';
        if ($competency->activo) {
            echo '<span class="badge badge-success">' . get_string('active', 'local_conocer_cert') . '</span>';
        } else {
            echo '<span class="badge badge-secondary">' . get_string('inactive', 'local_conocer_cert') . '</span>';
        }
        echo '</td>';
        
        // Candidate count
        echo '<td>';
        if ($competency->candidate_count > 0) {
            echo '<span class="badge badge-primary">' . $competency->candidate_count . '</span>';
        } else {
            echo '<span class="badge badge-light">0</span>';
        }
        echo '</td>';
        
        // Actions
        echo '<td>';
        echo '<div class="btn-group" role="group">';
        
        // View button
        echo html_writer::link(
            $competency->view_url,
            '<i class="fa fa-eye"></i>',
            ['class' => 'btn btn-sm btn-outline-primary', 'title' => get_string('view')]
        );
        
        // Edit button
        echo html_writer::link(
            $competency->edit_url,
            '<i class="fa fa-edit"></i>',
            ['class' => 'btn btn-sm btn-outline-secondary', 'title' => get_string('edit')]
        );
        
        echo '</div>';
        
        // Status/Delete buttons
        echo '<div class="btn-group mt-1" role="group">';
        
        // Activate/Deactivate buttons
        if (isset($competency->deactivate_url)) {
            echo html_writer::link(
                $competency->deactivate_url,
                '<i class="fa fa-times-circle"></i>',
                [
                    'class' => 'btn btn-sm btn-outline-warning', 
                    'title' => get_string('deactivate', 'local_conocer_cert'),
                    'onclick' => 'return confirm("' . get_string('confirm_deactivate_competency', 'local_conocer_cert') . '");'
                ]
            );
        }
        
        if (isset($competency->activate_url)) {
            echo html_writer::link(
                $competency->activate_url,
                '<i class="fa fa-check-circle"></i>',
                [
                    'class' => 'btn btn-sm btn-outline-success', 
                    'title' => get_string('activate', 'local_conocer_cert'),
                    'onclick' => 'return confirm("' . get_string('confirm_activate_competency', 'local_conocer_cert') . '");'
                ]
            );
        }
        
        // Delete button (only if no candidates)
        if ($competency->candidate_count == 0) {
            echo html_writer::link(
                $competency->delete_url,
                '<i class="fa fa-trash"></i>',
                [
                    'class' => 'btn btn-sm btn-outline-danger', 
                    'title' => get_string('delete'),
                    'onclick' => 'return confirm("' . get_string('confirm_delete_competency', 'local_conocer_cert') . '");'
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
    echo $OUTPUT->paging_bar($totalcompetencies, $page, $perpage, $baseurl);
} else {
    echo $OUTPUT->notification(get_string('no_competencies_found', 'local_conocer_cert'), 'info');
}

echo $OUTPUT->footer();
