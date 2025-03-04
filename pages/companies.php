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
 * Manage companies for CONOCER certifications.
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
$estado = optional_param('estado', '', PARAM_ALPHA);
$action = optional_param('action', '', PARAM_ALPHANUMEXT);
$companyid = optional_param('companyid', 0, PARAM_INT);

// Setup page
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/conocer_cert/pages/companies.php'));
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('manage_companies', 'local_conocer_cert'));
$PAGE->set_heading(get_string('manage_companies', 'local_conocer_cert'));
$PAGE->navbar->add(get_string('pluginname', 'local_conocer_cert'), new moodle_url('/local/conocer_cert/index.php'));
$PAGE->navbar->add(get_string('manage_companies', 'local_conocer_cert'));

// Check permissions
require_login();
require_capability('local/conocer_cert:managecompanies', $context);

// Process actions
if ($action && $companyid && confirm_sesskey()) {
    switch ($action) {
        case 'delete':
            if ($DB->record_exists('local_conocer_empresas', ['id' => $companyid])) {
                // Check if company has associated records before deleting
                $canDelete = true;
                
                // Add additional checks for company's associated records if needed
                
                if ($canDelete) {
                    $DB->delete_records('local_conocer_empresas', ['id' => $companyid]);
                    \core\notification::success(get_string('company_deleted', 'local_conocer_cert'));
                } else {
                    \core\notification::error(get_string('error:cannotdeletewithrecords', 'local_conocer_cert'));
                }
            }
            break;
            
        case 'approve':
            if ($DB->record_exists('local_conocer_empresas', ['id' => $companyid])) {
                $DB->set_field('local_conocer_empresas', 'estado', 'aprobado', ['id' => $companyid]);
                
                // Get company information for notification
                $company = $DB->get_record('local_conocer_empresas', ['id' => $companyid]);
                
                // Send notification to the company contact if available
                if (!empty($company->contacto_userid)) {
                    // Notification with approval details
                    \local_conocer_cert\util\notification::send($company->contacto_userid, 'empresa_aprobada', [
                        'nombre_empresa' => $company->nombre,
                        'contacto_nombre' => $company->contacto_nombre,
                        'fecha_aprobacion' => userdate(time()),
                        'contexturl' => new \moodle_url('/local/conocer_cert/pages/dashboard.php'),
                        'contexturlname' => get_string('company_dashboard', 'local_conocer_cert')
                    ]);
                }
                
                \core\notification::success(get_string('company_approved', 'local_conocer_cert'));
            }
            break;
            
        case 'reject':
            if ($DB->record_exists('local_conocer_empresas', ['id' => $companyid])) {
                $DB->set_field('local_conocer_empresas', 'estado', 'rechazado', ['id' => $companyid]);
                
                // Get company information for notification
                $company = $DB->get_record('local_conocer_empresas', ['id' => $companyid]);
                
                // Send notification to the company contact if available
                if (!empty($company->contacto_userid)) {
                    // Notification with rejection details
                    \local_conocer_cert\util\notification::send($company->contacto_userid, 'empresa_rechazada', [
                        'nombre_empresa' => $company->nombre,
                        'contacto_nombre' => $company->contacto_nombre,
                        'fecha_rechazo' => userdate(time()),
                        'contexturl' => new \moodle_url('/local/conocer_cert/pages/dashboard.php'),
                        'contexturlname' => get_string('company_dashboard', 'local_conocer_cert')
                    ]);
                }
                
                \core\notification::success(get_string('company_rejected', 'local_conocer_cert'));
            }
            break;
            
        case 'suspend':
            if ($DB->record_exists('local_conocer_empresas', ['id' => $companyid])) {
                $DB->set_field('local_conocer_empresas', 'estado', 'suspendido', ['id' => $companyid]);
                \core\notification::success(get_string('company_suspended', 'local_conocer_cert'));
            }
            break;
            
        case 'activate':
            if ($DB->record_exists('local_conocer_empresas', ['id' => $companyid])) {
                $DB->set_field('local_conocer_empresas', 'estado', 'activo', ['id' => $companyid]);
                \core\notification::success(get_string('company_activated', 'local_conocer_cert'));
            }
            break;
    }
    
    // Redirect to remove action from URL
    redirect($PAGE->url);
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

// Available statuses for filter
$statuses = [
    '' => get_string('all_statuses', 'local_conocer_cert'),
    'pendiente' => get_string('estado_pendiente', 'local_conocer_cert'),
    'aprobado' => get_string('estado_aprobado', 'local_conocer_cert'),
    'rechazado' => get_string('estado_rechazado', 'local_conocer_cert'),
    'activo' => get_string('estado_activo', 'local_conocer_cert'),
    'suspendido' => get_string('estado_suspendido', 'local_conocer_cert')
];

// Build the SQL query for companies
$params = [];
$sql_select = "SELECT e.*, u.firstname, u.lastname, u.email";
$sql_count = "SELECT COUNT(e.id)";
$sql_from = " FROM {local_conocer_empresas} e
              LEFT JOIN {user} u ON e.contacto_userid = u.id";
$sql_where = " WHERE 1=1";

// Apply filters
if ($search) {
    $sql_where .= " AND (
        " . $DB->sql_like('e.nombre', ':nombre', false) . " OR
        " . $DB->sql_like('e.rfc', ':rfc', false) . " OR
        " . $DB->sql_like('e.contacto_nombre', ':contacto_nombre', false) . " OR
        " . $DB->sql_like('e.contacto_email', ':contacto_email', false) . "
    )";
    $params['nombre'] = '%' . $search . '%';
    $params['rfc'] = '%' . $search . '%';
    $params['contacto_nombre'] = '%' . $search . '%';
    $params['contacto_email'] = '%' . $search . '%';
}

if ($sector) {
    $sql_where .= " AND e.sector = :sector";
    $params['sector'] = $sector;
}

if ($estado) {
    $sql_where .= " AND e.estado = :estado";
    $params['estado'] = $estado;
}

// Get total count
$totalcompanies = $DB->count_records_sql($sql_count . $sql_from . $sql_where, $params);

// Apply sort order
$sql_order = " ORDER BY $sort $dir";

// Get paginated data
$companies = $DB->get_records_sql($sql_select . $sql_from . $sql_where . $sql_order, $params, $page * $perpage, $perpage);

// Process company data for display
foreach ($companies as $company) {
    // Format competencias (stored as JSON or comma-separated list)
    if (!empty($company->competencias)) {
        // Check if it's stored as JSON
        $competencias = json_decode($company->competencias);
        if (!$competencias) {
            // If not JSON, try comma-separated list
            $competencias = explode(',', $company->competencias);
        }
        
        if (!empty($competencias)) {
            // Get competency names and codes
            list($sql, $sqlparams) = $DB->get_in_or_equal($competencias);
            $competenciaRecords = $DB->get_records_select('local_conocer_competencias', "id $sql", $sqlparams, '', 'id, nombre, codigo');
            
            $company->competencias_list = [];
            foreach ($competenciaRecords as $competencia) {
                $company->competencias_list[] = [
                    'id' => $competencia->id,
                    'nombre' => $competencia->nombre,
                    'codigo' => $competencia->codigo
                ];
            }
            
            $company->competencias_count = count($company->competencias_list);
        } else {
            $company->competencias_count = 0;
        }
    } else {
        $company->competencias_count = 0;
    }
    
    // Create action URLs
    $company->view_url = new moodle_url('/local/conocer_cert/pages/view_company.php', ['id' => $company->id]);
    $company->edit_url = new moodle_url('/local/conocer_cert/pages/edit_company.php', ['id' => $company->id]);
    $company->documents_url = new moodle_url('/local/conocer_cert/pages/company_documents.php', ['id' => $company->id]);
    
    // Delete/Approve/Reject/Suspend/Activate URLs
    $baseUrl = new moodle_url('/local/conocer_cert/pages/companies.php', [
        'companyid' => $company->id,
        'sesskey' => sesskey()
    ]);
    
    $company->delete_url = new moodle_url($baseUrl, ['action' => 'delete']);
    
    // Status-specific actions
    if ($company->estado == 'pendiente') {
        $company->approve_url = new moodle_url($baseUrl, ['action' => 'approve']);
        $company->reject_url = new moodle_url($baseUrl, ['action' => 'reject']);
    } else if ($company->estado == 'aprobado' || $company->estado == 'activo') {
        $company->suspend_url = new moodle_url($baseUrl, ['action' => 'suspend']);
    } else if ($company->estado == 'suspendido') {
        $company->activate_url = new moodle_url($baseUrl, ['action' => 'activate']);
    }
    
    // Format contact name
    if (!empty($company->contacto_userid) && !empty($company->firstname) && !empty($company->lastname)) {
        $company->contact_fullname = $company->firstname . ' ' . $company->lastname;
    } else {
        $company->contact_fullname = $company->contacto_nombre;
    }
}

// Setup paging
$baseurl = new moodle_url('/local/conocer_cert/pages/companies.php', [
    'sort' => $sort,
    'dir' => $dir,
    'search' => $search,
    'sector' => $sector,
    'estado' => $estado
]);
$paging = new paging_bar($totalcompanies, $page, $perpage, $baseurl);

// Setup page buttons
$buttons = [
    'add' => new single_button(
        new moodle_url('/local/conocer_cert/pages/add_company.php'),
        get_string('add_company', 'local_conocer_cert'),
        'get'
    )
];

// Output page
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('manage_companies', 'local_conocer_cert'));

// Display buttons
echo $OUTPUT->container_start('buttons');
echo $OUTPUT->render($buttons['add']);
echo $OUTPUT->container_end();

// Display search and filter form
?>
<form id="companiesfilterform" method="get" action="<?php echo $PAGE->url; ?>" class="mb-4">
    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                <label for="search"><?php echo get_string('search'); ?></label>
                <input type="text" id="search" name="search" class="form-control" value="<?php echo s($search); ?>" 
                       placeholder="<?php echo get_string('search_companies_placeholder', 'local_conocer_cert'); ?>">
            </div>
        </div>
        <div class="col-md-3">
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
        <div class="col-md-3">
            <div class="form-group">
                <label for="estado"><?php echo get_string('status', 'local_conocer_cert'); ?></label>
                <select id="estado" name="estado" class="form-control">
                    <?php foreach ($statuses as $value => $label): ?>
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
if ($totalcompanies > 0) {
    echo html_writer::tag('p', get_string('displaying_companies', 'local_conocer_cert', [
        'count' => count($companies),
        'total' => $totalcompanies
    ]));
}

// Display companies table
if (!empty($companies)) {
    // Setup table headers with sorting
    $columns = [
        'nombre' => get_string('companyname', 'local_conocer_cert'),
        'rfc' => get_string('rfc', 'local_conocer_cert'),
        'sector' => get_string('sector', 'local_conocer_cert'),
        'contacto' => get_string('contact', 'local_conocer_cert'),
        'competencias' => get_string('competencies', 'local_conocer_cert'),
        'estado' => get_string('status', 'local_conocer_cert'),
        'actions' => get_string('actions')
    ];
    
    $sortableColumns = ['nombre', 'rfc', 'sector', 'estado'];
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
    
    foreach ($companies as $company) {
        echo '<tr>';
        
        // Company Name
        echo '<td>';
        echo html_writer::link($company->view_url, format_string($company->nombre));
        echo '</td>';
        
        // RFC
        echo '<td>' . $company->rfc . '</td>';
        
        // Sector
        echo '<td>' . get_string('sector_' . $company->sector, 'local_conocer_cert') . '</td>';
        
        // Contact
        echo '<td>';
        echo $company->contact_fullname;
        if (!empty($company->contacto_email)) {
            echo '<br><small>' . $company->contacto_email . '</small>';
        }
        if (!empty($company->contacto_telefono)) {
            echo '<br><small>' . $company->contacto_telefono . '</small>';
        }
        echo '</td>';
        
        // Competencias
        echo '<td>';
        if ($company->competencias_count > 0) {
            echo '<span class="badge badge-info">' . $company->competencias_count . ' ' . get_string('competencies', 'local_conocer_cert') . '</span>';
            
            echo '<div class="small mt-1">';
            $competenciasShown = 0;
            foreach ($company->competencias_list as $competencia) {
                if ($competenciasShown < 2) { // Only show first 2
                    echo '<span class="badge badge-secondary">' . $competencia['codigo'] . '</span> ';
                    $competenciasShown++;
                }
            }
            
            if ($company->competencias_count > 2) {
                echo ' <span class="badge badge-light">+' . ($company->competencias_count - 2) . ' ' . get_string('more', 'local_conocer_cert') . '</span>';
            }
            echo '</div>';
        } else {
            echo '<span class="badge badge-secondary">' . get_string('no_competencies', 'local_conocer_cert') . '</span>';
        }
        echo '</td>';
        
        // Estado
        echo '<td>';
        switch ($company->estado) {
            case 'pendiente':
                echo '<span class="badge badge-warning">' . get_string('estado_pendiente', 'local_conocer_cert') . '</span>';
                break;
            case 'aprobado':
            case 'activo':
                echo '<span class="badge badge-success">' . get_string('estado_aprobado', 'local_conocer_cert') . '</span>';
                break;
            case 'rechazado':
                echo '<span class="badge badge-danger">' . get_string('estado_rechazado', 'local_conocer_cert') . '</span>';
                break;
            case 'suspendido':
                echo '<span class="badge badge-secondary">' . get_string('estado_suspendido', 'local_conocer_cert') . '</span>';
                break;
            default:
                echo $company->estado;
        }
        
        // Show fecha_solicitud
        if (!empty($company->fecha_solicitud)) {
            echo '<br><small>' . get_string('requested_on', 'local_conocer_cert') . ': ' . userdate($company->fecha_solicitud) . '</small>';
        }
        echo '</td>';
        
        // Actions
        echo '<td>';
        echo '<div class="btn-group" role="group">';
        
        // View button
        echo html_writer::link(
            $company->view_url,
            '<i class="fa fa-eye"></i>',
            ['class' => 'btn btn-sm btn-outline-primary', 'title' => get_string('view')]
        );
        
        // Edit button
        echo html_writer::link(
            $company->edit_url,
            '<i class="fa fa-edit"></i>',
            ['class' => 'btn btn-sm btn-outline-secondary', 'title' => get_string('edit')]
        );
        
        // Documents button
        echo html_writer::link(
            $company->documents_url,
            '<i class="fa fa-file-alt"></i>',
            ['class' => 'btn btn-sm btn-outline-info', 'title' => get_string('documents', 'local_conocer_cert')]
        );
        
        echo '</div>';
        
        // Status change buttons
        echo '<div class="btn-group mt-1" role="group">';
        
        // Approval/Rejection buttons for pending companies
        if (isset($company->approve_url)) {
            echo html_writer::link(
                $company->approve_url,
                '<i class="fa fa-check"></i>',
                [
                    'class' => 'btn btn-sm btn-outline-success', 
                    'title' => get_string('approve', 'local_conocer_cert'),
                    'onclick' => 'return confirm("' . get_string('confirm_approve_company', 'local_conocer_cert') . '");'
                ]
            );
            
            echo html_writer::link(
                $company->reject_url,
                '<i class="fa fa-times"></i>',
                [
                    'class' => 'btn btn-sm btn-outline-danger', 
                    'title' => get_string('reject', 'local_conocer_cert'),
                    'onclick' => 'return confirm("' . get_string('confirm_reject_company', 'local_conocer_cert') . '");'
                ]
            );
        }
        
        // Suspend/Activate buttons
        if (isset($company->suspend_url)) {
            echo html_writer::link(
                $company->suspend_url,
                '<i class="fa fa-pause"></i>',
                [
                    'class' => 'btn btn-sm btn-outline-warning', 
                    'title' => get_string('suspend', 'local_conocer_cert'),
                    'onclick' => 'return confirm("' . get_string('confirm_suspend_company', 'local_conocer_cert') . '");'
                ]
            );
        }
        
        if (isset($company->activate_url)) {
            echo html_writer::link(
                $company->activate_url,
                '<i class="fa fa-play"></i>',
                [
                    'class' => 'btn btn-sm btn-outline-success', 
                    'title' => get_string('activate', 'local_conocer_cert'),
                    'onclick' => 'return confirm("' . get_string('confirm_activate_company', 'local_conocer_cert') . '");'
                ]
            );
        }
        
        // Delete button
        echo html_writer::link(
            $company->delete_url,
            '<i class="fa fa-trash"></i>',
            [
                'class' => 'btn btn-sm btn-outline-danger', 
                'title' => get_string('delete'),
                'onclick' => 'return confirm("' . get_string('confirm_delete_company', 'local_conocer_cert') . '");'
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
    echo $OUTPUT->paging_bar($totalcompanies, $page, $perpage, $baseurl);
} else {
    echo $OUTPUT->notification(get_string('no_companies_found', 'local_conocer_cert'), 'info');
}

echo $OUTPUT->footer();
