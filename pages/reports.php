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
 * Reports page for the CONOCER certification system.
 *
 * @package    local_conocer_cert
 * @copyright  2025 Sebastian Gonzalez Zepeda sgonzalez@infraestructuragis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/local/conocer_cert/locallib.php');

// Parameters
$type = optional_param('type', 'general', PARAM_ALPHA);
$competencyid = optional_param('competencyid', 0, PARAM_INT);
$from = optional_param('from', 0, PARAM_INT); // Timestamp for start date filter
$to = optional_param('to', 0, PARAM_INT);     // Timestamp for end date filter
$format = optional_param('format', '', PARAM_ALPHA); // Export format (csv, excel)
$sectorfilter = optional_param('sector', '', PARAM_ALPHA);
$levelfilter = optional_param('nivel', 0, PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);

// Validate report type
$valid_types = ['general', 'certifications', 'competencies', 'evaluators', 'companies'];
if (!in_array($type, $valid_types)) {
    $type = 'general';
}

// Set up the page
$title = get_string('reports', 'local_conocer_cert');
$url = new moodle_url('/local/conocer_cert/pages/reports.php', array('type' => $type));

if ($competencyid) {
    $url->param('competencyid', $competencyid);
}
if ($from) {
    $url->param('from', $from);
}
if ($to) {
    $url->param('to', $to);
}
if (!empty($sectorfilter)) {
    $url->param('sector', $sectorfilter);
}
if ($levelfilter) {
    $url->param('nivel', $levelfilter);
}

$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_title($title);
$PAGE->set_heading($title);

// Check permissions
require_login();
require_capability('local/conocer_cert:viewreports', context_system::instance());

// Initialize renderer
$output = $PAGE->get_renderer('local_conocer_cert');

// Set up filter form
$filter_form = new \local_conocer_cert\forms\report_filter_form($url->out(false), [
    'type' => $type,
    'competencyid' => $competencyid,
    'from' => $from,
    'to' => $to,
    'sector' => $sectorfilter,
    'nivel' => $levelfilter
]);

// Process filter form submission
if ($filter_data = $filter_form->get_data()) {
    // Redirect to apply filters
    $filter_url = new moodle_url('/local/conocer_cert/pages/reports.php', [
        'type' => $filter_data->type,
        'competencyid' => $filter_data->competencyid,
        'from' => $filter_data->from,
        'to' => $filter_data->to,
        'sector' => $filter_data->sector,
        'nivel' => $filter_data->nivel
    ]);
    redirect($filter_url);
}

// Prepare date filters
$date_filters = [];
if ($from) {
    $date_filters['from'] = $from;
}
if ($to) {
    $date_filters['to'] = $to;
}

// Generate report data based on type
$report_data = [];
switch ($type) {
    case 'certifications':
        $report_data = generate_certifications_report($competencyid, $date_filters, $levelfilter);
        break;
    case 'competencies':
        $report_data = generate_competencies_report($sectorfilter, $date_filters);
        break;
    case 'evaluators':
        $report_data = generate_evaluators_report($competencyid, $date_filters);
        break;
    case 'companies':
        $report_data = generate_companies_report($sectorfilter, $date_filters);
        break;
    default:
        $report_data = generate_general_report($date_filters);
        break;
}

// Export if requested
if (!empty($format)) {
    export_report($report_data, $type, $format);
    exit;
}

// Start output
echo $OUTPUT->header();

// Display tabs for different report types
$tabs = [
    new tabobject('general', new moodle_url('/local/conocer_cert/pages/reports.php', ['type' => 'general']),
        get_string('general_report', 'local_conocer_cert')),
    new tabobject('certifications', new moodle_url('/local/conocer_cert/pages/reports.php', ['type' => 'certifications']),
        get_string('certifications_report', 'local_conocer_cert')),
    new tabobject('competencies', new moodle_url('/local/conocer_cert/pages/reports.php', ['type' => 'competencies']),
        get_string('competencies_report', 'local_conocer_cert')),
    new tabobject('evaluators', new moodle_url('/local/conocer_cert/pages/reports.php', ['type' => 'evaluators']),
        get_string('evaluators_report', 'local_conocer_cert')),
    new tabobject('companies', new moodle_url('/local/conocer_cert/pages/reports.php', ['type' => 'companies']),
        get_string('companies_report', 'local_conocer_cert'))
];
echo $OUTPUT->tabtree($tabs, $type);

// Display filter form
$filter_form->display();

// Display export buttons
echo html_writer::start_div('export-buttons mt-3 mb-3');
echo html_writer::link(
    new moodle_url($url, ['format' => 'csv']),
    html_writer::tag('i', '', ['class' => 'fa fa-file-csv mr-2']) . get_string('exportcsv', 'local_conocer_cert'),
    ['class' => 'btn btn-secondary mr-2']
);
echo html_writer::link(
    new moodle_url($url, ['format' => 'excel']),
    html_writer::tag('i', '', ['class' => 'fa fa-file-excel mr-2']) . get_string('exportexcel', 'local_conocer_cert'),
    ['class' => 'btn btn-secondary']
);
echo html_writer::end_div();

// Create and render the report page
$report_page = new \local_conocer_cert\output\reports_page($report_data, $type, [
    'from' => $from ? userdate($from) : '',
    'to' => $to ? userdate($to) : '',
    'competency' => $competencyid ? get_competency_name($competencyid) : '',
    'sector' => $sectorfilter,
    'level' => $levelfilter
]);

echo $output->render_reports_page($report_page);

// Finish the page
echo $OUTPUT->footer();

/**
 * Generate general statistical report
 *
 * @param array $date_filters Date range filters
 * @return array Report data
 */
function generate_general_report($date_filters = []) {
    global $DB;
    
    // Date condition for SQL queries
    $date_condition = '';
    $params = [];
    
    if (!empty($date_filters['from'])) {
        $date_condition .= ' AND timecreated >= :from_date';
        $params['from_date'] = $date_filters['from'];
    }
    
    if (!empty($date_filters['to'])) {
        $date_condition .= ' AND timecreated <= :to_date';
        $params['to_date'] = $date_filters['to'];
    }
    
    // General statistics
    $general_stats = [
        'total_candidates' => $DB->count_records('local_conocer_candidatos'),
        'total_companies' => $DB->count_records('local_conocer_empresas'),
        'total_competencies' => $DB->count_records('local_conocer_competencias', ['activo' => 1]),
        'total_evaluators' => $DB->count_records('local_conocer_evaluadores', ['estatus' => 'activo']),
        'total_certifications' => $DB->count_records_select(
            'local_conocer_procesos',
            "resultado IS NOT NULL" . $date_condition,
            $params
        ),
        'approved_certifications' => $DB->count_records_select(
            'local_conocer_procesos',
            "resultado = 'aprobado'" . $date_condition,
            $params
        ),
        'rejected_certifications' => $DB->count_records_select(
            'local_conocer_procesos',
            "resultado = 'rechazado'" . $date_condition,
            $params
        ),
        'pending_evaluations' => $DB->count_records_select(
            'local_conocer_procesos',
            "etapa = 'evaluacion' AND (fecha_evaluacion IS NULL OR fecha_evaluacion = 0)"
        ),
        'active_processes' => $DB->count_records_select(
            'local_conocer_procesos',
            "etapa IN ('solicitud', 'evaluacion', 'pendiente_revision')"
        )
    ];
    
    // Monthly data for charts
    $monthly_data = get_monthly_certification_data();
    
    // Recent activity for display in the general report
    $recent_activity = get_recent_activity(10);
    
    return [
        'general_stats' => $general_stats,
        'monthly_data' => $monthly_data,
        'recent_activity' => $recent_activity
    ];
}

/**
 * Generate certifications report
 *
 * @param int $competencyid Filter by competency ID
 * @param array $date_filters Date range filters
 * @param int $levelfilter Filter by level
 * @return array Report data
 */
function generate_certifications_report($competencyid = 0, $date_filters = [], $levelfilter = 0) {
    global $DB;
    
    // Build SQL conditions
    $conditions = ["p.resultado IS NOT NULL"];
    $params = [];
    
    if ($competencyid) {
        $conditions[] = "c.competencia_id = :competenciaid";
        $params['competenciaid'] = $competencyid;
    }
    
    if ($levelfilter) {
        $conditions[] = "c.nivel = :nivel";
        $params['nivel'] = $levelfilter;
    }
    
    if (!empty($date_filters['from'])) {
        $conditions[] = "p.fecha_fin >= :from_date";
        $params['from_date'] = $date_filters['from'];
    }
    
    if (!empty($date_filters['to'])) {
        $conditions[] = "p.fecha_fin <= :to_date";
        $params['to_date'] = $date_filters['to'];
    }
    
    $where = implode(' AND ', $conditions);
    
    // Get certification data
    $sql = "SELECT p.id, p.candidato_id, p.resultado, p.fecha_inicio, p.fecha_fin,
                   c.competencia_id, c.nivel, c.userid,
                   u.firstname, u.lastname,
                   comp.nombre as competencia_nombre, comp.codigo as competencia_codigo
            FROM {local_conocer_procesos} p
            JOIN {local_conocer_candidatos} c ON p.candidato_id = c.id
            JOIN {user} u ON c.userid = u.id
            JOIN {local_conocer_competencias} comp ON c.competencia_id = comp.id
            WHERE $where
            ORDER BY p.fecha_fin DESC";
    
    $certifications = $DB->get_records_sql($sql, $params);
    
    // Prepare data for chart
    $chart_data = [
        'labels' => [],
        'datasets' => [
            [
                'label' => get_string('approved', 'local_conocer_cert'),
                'data' => [],
                'backgroundColor' => 'rgba(75, 192, 192, 0.6)'
            ],
            [
                'label' => get_string('rejected', 'local_conocer_cert'),
                'data' => [],
                'backgroundColor' => 'rgba(255, 99, 132, 0.6)'
            ]
        ]
    ];
    
    // Group data by competency
    $by_competency = [];
    $by_level = [];
    
    foreach ($certifications as $cert) {
        // Group by competency
        if (!isset($by_competency[$cert->competencia_id])) {
            $by_competency[$cert->competencia_id] = [
                'competencia' => $cert->competencia_nombre,
                'codigo' => $cert->competencia_codigo,
                'total' => 0,
                'aprobados' => 0,
                'rechazados' => 0
            ];
            
            // Add to chart labels
            $chart_data['labels'][] = $cert->competencia_codigo;
            $chart_data['datasets'][0]['data'][] = 0; // Approved
            $chart_data['datasets'][1]['data'][] = 0; // Rejected
        }
        
        $index = array_search($cert->competencia_codigo, $chart_data['labels']);
        
        $by_competency[$cert->competencia_id]['total']++;
        
        if ($cert->resultado == 'aprobado') {
            $by_competency[$cert->competencia_id]['aprobados']++;
            $chart_data['datasets'][0]['data'][$index]++;
        } else if ($cert->resultado == 'rechazado') {
            $by_competency[$cert->competencia_id]['rechazados']++;
            $chart_data['datasets'][1]['data'][$index]++;
        }
        
        // Group by level
        $level_key = $cert->competencia_id . '_' . $cert->nivel;
        if (!isset($by_level[$level_key])) {
            $by_level[$level_key] = [
                'competencia' => $cert->competencia_nombre,
                'codigo' => $cert->competencia_codigo,
                'nivel' => $cert->nivel,
                'total' => 0,
                'aprobados' => 0,
                'rechazados' => 0
            ];
        }
        
        $by_level[$level_key]['total']++;
        
        if ($cert->resultado == 'aprobado') {
            $by_level[$level_key]['aprobados']++;
        } else if ($cert->resultado == 'rechazado') {
            $by_level[$level_key]['rechazados']++;
        }
    }
    
    // Calculate success rate for each competency
    foreach ($by_competency as &$comp_data) {
        $comp_data['porcentaje_exito'] = $comp_data['total'] > 0 
            ? round(($comp_data['aprobados'] / $comp_data['total']) * 100, 2) 
            : 0;
    }
    
    // Calculate success rate for each level
    foreach ($by_level as &$level_data) {
        $level_data['porcentaje_exito'] = $level_data['total'] > 0 
            ? round(($level_data['aprobados'] / $level_data['total']) * 100, 2) 
            : 0;
    }
    
    // Overall totals
    $totals = [
        'total' => count($certifications),
        'aprobados' => array_sum(array_column($by_competency, 'aprobados')),
        'rechazados' => array_sum(array_column($by_competency, 'rechazados')),
        'porcentaje_exito' => 0
    ];
    
    if ($totals['total'] > 0) {
        $totals['porcentaje_exito'] = round(($totals['aprobados'] / $totals['total']) * 100, 2);
    }
    
    // Format certifications for table display
    $table_data = [];
    foreach ($certifications as $cert) {
        $table_data[] = [
            'id' => $cert->id,
            'candidate' => $cert->firstname . ' ' . $cert->lastname,
            'userid' => $cert->userid,
            'competencia' => $cert->competencia_nombre,
            'codigo' => $cert->competencia_codigo,
            'nivel' => $cert->nivel,
            'resultado' => $cert->resultado,
            'fecha_inicio' => userdate($cert->fecha_inicio),
            'fecha_fin' => userdate($cert->fecha_fin),
            'duracion_dias' => round(($cert->fecha_fin - $cert->fecha_inicio) / (60 * 60 * 24))
        ];
    }
    
    return [
        'chart_data' => $chart_data,
        'by_competency' => array_values($by_competency),
        'by_level' => array_values($by_level),
        'totals' => $totals,
        'table_data' => $table_data
    ];
}

/**
 * Generate competencies report
 *
 * @param string $sector Filter by sector
 * @param array $date_filters Date range filters
 * @return array Report data
 */
function generate_competencies_report($sector = '', $date_filters = []) {
    global $DB;
    
    // Build SQL conditions
    $conditions = [];
    $params = [];
    
    if (!empty($sector)) {
        $conditions[] = "sector = :sector";
        $params['sector'] = $sector;
    }
    
    $where = '';
    if (!empty($conditions)) {
        $where = 'WHERE ' . implode(' AND ', $conditions);
    }
    
    // Get all competencies
    $sql = "SELECT id, codigo, nombre, descripcion, sector, activo, niveles_disponibles
            FROM {local_conocer_competencias}
            $where
            ORDER BY codigo";
    
    $competencies = $DB->get_records_sql($sql, $params);
    
    // Get certification data for each competency
    $certification_counts = [];
    foreach ($competencies as $comp) {
        $comp_id = $comp->id;
        
        // Build SQL for certifications
        $cert_conditions = ["c.competencia_id = :compid", "p.resultado IS NOT NULL"];
        $cert_params = ['compid' => $comp_id];
        
        if (!empty($date_filters['from'])) {
            $cert_conditions[] = "p.fecha_fin >= :from_date";
            $cert_params['from_date'] = $date_filters['from'];
        }
        
        if (!empty($date_filters['to'])) {
            $cert_conditions[] = "p.fecha_fin <= :to_date";
            $cert_params['to_date'] = $date_filters['to'];
        }
        
        $cert_where = implode(' AND ', $cert_conditions);
        
        // Count total certifications
        $total_sql = "SELECT COUNT(p.id) 
                      FROM {local_conocer_procesos} p
                      JOIN {local_conocer_candidatos} c ON p.candidato_id = c.id
                      WHERE $cert_where";
        
        $total_count = $DB->count_records_sql($total_sql, $cert_params);
        
        // Count approved certifications
        $approved_sql = "SELECT COUNT(p.id) 
                         FROM {local_conocer_procesos} p
                         JOIN {local_conocer_candidatos} c ON p.candidato_id = c.id
                         WHERE $cert_where AND p.resultado = 'aprobado'";
        
        $approved_count = $DB->count_records_sql($approved_sql, $cert_params);
        
        // Count requests by level
        $niveles = explode(',', $comp->niveles_disponibles);
        $by_level = [];
        
        foreach ($niveles as $nivel) {
            $level_sql = "SELECT COUNT(c.id) 
                          FROM {local_conocer_candidatos} c
                          WHERE c.competencia_id = :compid AND c.nivel = :nivel";
            
            $level_count = $DB->count_records_sql($level_sql, [
                'compid' => $comp_id,
                'nivel' => $nivel
            ]);
            
            $by_level[$nivel] = $level_count;
        }
        
        $certification_counts[$comp_id] = [
            'total' => $total_count,
            'aprobados' => $approved_count,
            'by_level' => $by_level
        ];
    }
    
    // Prepare data for chart
    $chart_data = [
        'labels' => [],
        'datasets' => [
            [
                'label' => get_string('certification_requests', 'local_conocer_cert'),
                'data' => [],
                'backgroundColor' => 'rgba(54, 162, 235, 0.6)'
            ],
            [
                'label' => get_string('approved_certifications', 'local_conocer_cert'),
                'data' => [],
                'backgroundColor' => 'rgba(75, 192, 192, 0.6)'
            ]
        ]
    ];
    
    // Find most requested competencies
    $most_requested = [];
    $by_level = [];
    $sector_counts = [];
    
    foreach ($competencies as $comp) {
        $comp_id = $comp->id;
        $cert_data = $certification_counts[$comp_id];
        
        // Add to chart data
        $chart_data['labels'][] = $comp->codigo;
        $chart_data['datasets'][0]['data'][] = $cert_data['total'];
        $chart_data['datasets'][1]['data'][] = $cert_data['aprobados'];
        
        // Add to most requested data
        $most_requested[] = [
            'id' => $comp_id,
            'codigo' => $comp->codigo,
            'nombre' => $comp->nombre,
            'sector' => $comp->sector,
            'total_solicitudes' => $cert_data['total'],
            'aprobadas' => $cert_data['aprobados'],
            'porcentaje_exito' => $cert_data['total'] > 0 
                ? round(($cert_data['aprobados'] / $cert_data['total']) * 100, 2) 
                : 0
        ];
        
        // Aggregate by level
        foreach ($cert_data['by_level'] as $nivel => $count) {
            if (!isset($by_level[$nivel])) {
                $by_level[$nivel] = 0;
            }
            $by_level[$nivel] += $count;
        }
        
        // Aggregate by sector
        if (!empty($comp->sector)) {
            if (!isset($sector_counts[$comp->sector])) {
                $sector_counts[$comp->sector] = [
                    'count' => 0,
                    'sector' => $comp->sector
                ];
            }
            $sector_counts[$comp->sector]['count']++;
        }
    }
    
    // Sort most requested by total requests
    usort($most_requested, function($a, $b) {
        return $b['total_solicitudes'] - $a['total_solicitudes'];
    });
    
    // Format level data for display
    $level_data = [];
    foreach ($by_level as $nivel => $count) {
        $level_data[] = [
            'nivel' => $nivel,
            'count' => $count,
            'porcentaje' => array_sum($by_level) > 0 
                ? round(($count / array_sum($by_level)) * 100, 2) 
                : 0
        ];
    }
    
    // Sort levels by number
    usort($level_data, function($a, $b) {
        return $a['nivel'] - $b['nivel'];
    });
    
    // Format sector data
    $sector_data = [];
    foreach ($sector_counts as $sector_info) {
        $sector_data[] = [
            'sector' => $sector_info['sector'],
            'sector_text' => get_string('sector_' . $sector_info['sector'], 'local_conocer_cert'),
            'count' => $sector_info['count'],
            'porcentaje' => count($competencies) > 0 
                ? round(($sector_info['count'] / count($competencies)) * 100, 2) 
                : 0
        ];
    }
    
    return [
        'competencies' => $competencies,
        'chart_data' => $chart_data,
        'most_requested' => array_slice($most_requested, 0, 10),
        'by_level' => $level_data,
        'by_sector' => $sector_data,
        'total_competencies' => count($competencies),
        'active_competencies' => $DB->count_records('local_conocer_competencias', ['activo' => 1])
    ];
}

/**
 * Generate evaluators report
 *
 * @param int $competencyid Filter by competency ID
 * @param array $date_filters Date range filters
 * @return array Report data
 */
function generate_evaluators_report($competencyid = 0, $date_filters = []) {
    global $DB;
    
    // Get all evaluators
    $sql = "SELECT e.*, u.firstname, u.lastname, u.email
            FROM {local_conocer_evaluadores} e
            JOIN {user} u ON e.userid = u.id
            ORDER BY u.lastname, u.firstname";
    
    $evaluators = $DB->get_records_sql($sql);
    
    // Build SQL conditions for evaluations
    $eval_conditions = ["p.evaluador_id IS NOT NULL"];
    $eval_params = [];
    
    if ($competencyid) {
        $eval_conditions[] = "c.competencia_id = :competenciaid";
        $eval_params['competenciaid'] = $competencyid;
    }
    
    if (!empty($date_filters['from'])) {
        $eval_conditions[] = "p.fecha_evaluacion >= :from_date";
        $eval_params['from_date'] = $date_filters['from'];
    }
    
    if (!empty($date_filters['to'])) {
        $eval_conditions[] = "p.fecha_evaluacion <= :to_date";
        $eval_params['to_date'] = $date_filters['to'];
    }
    
    $eval_where = implode(' AND ', $eval_conditions);
    
    // Get evaluations data
    $eval_sql = "SELECT p.id, p.evaluador_id, p.candidato_id, p.fecha_inicio, p.fecha_evaluacion, 
                        p.resultado, p.etapa,
                        c.competencia_id, c.nivel,
                        comp.nombre as competencia_nombre, comp.codigo as competencia_codigo
                 FROM {local_conocer_procesos} p
                 JOIN {local_conocer_candidatos} c ON p.candidato_id = c.id
                 JOIN {local_conocer_competencias} comp ON c.competencia_id = comp.id
                 WHERE $eval_where
                 ORDER BY p.fecha_evaluacion DESC";
    
    $evaluations = $DB->get_records_sql($eval_sql, $eval_params);
    
    // Group evaluations by evaluator
    $evaluator_data = [];
    
    foreach ($evaluators as $evaluator) {
        $userid = $evaluator->userid;
        
        $evaluator_data[$userid] = [
            'id' => $evaluator->id,
            'userid' => $userid,
            'name' => $evaluator->firstname . ' ' . $evaluator->lastname,
            'email' => $evaluator->email,
            'status' => $evaluator->estatus,
            'total_evaluations' => 0,
            'completed_evaluations' => 0,
            'approved' => 0,
            'rejected' => 0,
            'avg_days' => 0,
            'competencies' => []
        ];
        
        // Decode competencies JSON
        if (!empty($evaluator->competencias)) {
            $comp_ids = json_decode($evaluator->competencias);
            if (is_array($comp_ids)) {
                foreach ($comp_ids as $comp_id) {
                    $comp = $DB->get_record('local_conocer_competencias', ['id' => $comp_id]);
                    if ($comp) {
                        $evaluator_data[$userid]['competencies'][] = [
                            'id' => $comp_id,
                            'codigo' => $comp->codigo,
                            'nombre' => $comp->nombre
                        ];
                    }
                }
            }
        }
    }
    
    // Process evaluations
    $days_sum = [];
    $evals_count = [];
    
    foreach ($evaluations as $eval) {
        $evaluator_id = $eval->evaluador_id;
        
        if (isset($evaluator_data[$evaluator_id])) {
            $evaluator_data[$evaluator_id]['total_evaluations']++;
            
            if (!empty($eval->fecha_evaluacion)) {
                $evaluator_data[$evaluator_id]['completed_evaluations']++;
                
                // Calculate days between assignment and evaluation
                $days = ($eval->fecha_evaluacion - $eval->fecha_inicio) / (60 * 60 * 24);
                
                if (!isset($days_sum[$evaluator_id])) {
                    $days_sum[$evaluator_id] = 0;
                    $evals_count[$evaluator_id] = 0;
                }
                
                $days_sum[$evaluator_id] += $days;
                $evals_count[$evaluator_id]++;
                
                // Count by result
                if ($eval->resultado == 'aprobado') {
                    $evaluator_data[$evaluator_id]['approved']++;
                } else if ($eval->resultado == 'rechazado') {
                    $evaluator_data[$evaluator_id]['rejected']++;
                }
            }
        }
    }
    
    // Calculate average days for each evaluator
    foreach ($evaluator_data as $evaluator_id => &$data) {
        if (isset($evals_count[$evaluator_id]) && $evals_count[$evaluator_id] > 0) {
            $data['avg_days'] = round($days_sum[$evaluator_id] / $evals_count[$evaluator_id], 1);
        }
        
        // Calculate success rate
        $data['success_rate'] = $data['completed_evaluations'] > 0 
            ? round(($data['approved'] / $data['completed_evaluations']) * 100, 2) 
            : 0;
    }
    
    // Sort evaluators by completed evaluations (highest first)
    uasort($evaluator_data, function($a, $b) {
        return $b['completed_evaluations'] - $a['completed_evaluations'];
    });
    
    // Prepare chart data
    $chart_data = [
        'labels' => [],
        'datasets' => [
            [
                'label' => get_string('completed_evaluations', 'local_conocer_cert'),
                'data' => [],
                'backgroundColor' => 'rgba(54, 162, 235, 0.6)'
            ],
            [
                'label' => get_string('approved_evaluations', 'local_conocer_cert'),
                'data' => [],
                'backgroundColor' => 'rgba(75, 192, 192, 0.6)'
            ],
            [
                'label' => get_string('rejected_evaluations', 'local_conocer_cert'),
                'data' => [],
                'backgroundColor' => 'rgba(255, 99, 132, 0.6)'
            ]
        ]
    ];
    
    // Top performing evaluators (up to 10)
    $top_evaluators = array_slice($evaluator_data, 0, 10, true);
    
    foreach ($top_evaluators as $e_data) {
        $chart_data['labels'][] = $e_data['name'];
        $chart_data['datasets'][0]['data'][] = $e_data['completed_evaluations'];
        $chart_data['datasets'][1]['data'][] = $e_data['approved'];
        $chart_data['datasets'][2]['data'][] = $e_data['rejected'];
    }
    
    // Calculate overall performance metrics
    $performance = [
        'total_evaluators' => count($evaluators),
        'active_evaluators' => $DB->count_records('local_conocer_evaluadores', ['estatus' => 'activo']),
        'total_evaluations' => array_sum(array_column($evaluator_data, 'total_evaluations')),
        'completed_evaluations' => array_sum(array_column($evaluator_data, 'completed_evaluations')),
        'avg_days_all' => 0,
        'overall_success_rate' => 0
    ];
    
    $total_approved = array_sum(array_column($evaluator_data, 'approved'));
    
    if ($performance['completed_evaluations'] > 0) {
        $performance['overall_success_rate'] = round(($total_approved / $performance['completed_evaluations']) * 100, 2);
        
        // Calculate average days across all evaluators
        $all_days = 0;
        $all_evals = 0;
        
        foreach ($days_sum as $days) {
            $all_days += $days;
        }
        
        foreach ($evals_count as $count) {
            $all_evals += $count;
        }
        
        if ($all_evals > 0) {
            $performance['avg_days_all'] = round($all_days / $all_evals, 1);
        }
    }
    
    return [
        'evaluators' => array_values($evaluator_data),
        'chart_data' => $chart_data,
        'evaluations' => $evaluations,
        'performance' => $performance,
        'evaluators_count' => count($evaluators)
    ];
}

/**
 * Generate companies report
 *
 * @param string $sector Filter by sector
 * @param array $date_filters Date range filters
 * @return array Report data
 */
function generate_companies_report($sector = '', $date_filters = []) {
    global $DB;
    
    // Build SQL conditions
    $conditions = [];
    $params = [];
    
    if (!empty($sector)) {
        $conditions[] = "sector = :sector";
        $params['sector'] = $sector;
    }
    
    if (!empty($date_filters['from'])) {
        $conditions[] = "fecha_solicitud >= :from_date";
        $params['from_date'] = $date_filters['from'];
    }
    
    if (!empty($date_filters['to'])) {
        $conditions[] = "fecha_solicitud <= :to_date";
        $params['to_date'] = $date_filters['to'];
    }
    
    $where = '';
    if (!empty($conditions)) {
        $where = 'WHERE ' . implode(' AND ', $conditions);
    }
    
    // Get all companies
    $sql = "SELECT id, nombre, rfc, sector, contacto_nombre, contacto_email, estado, 
                   fecha_solicitud, fecha_aprobacion, competencias
            FROM {local_conocer_empresas}
            $where
            ORDER BY fecha_solicitud DESC";
    
    $companies = $DB->get_records_sql($sql, $params);
    
    // Group by sector
    $by_sector = [];
    $by_status = [
        'aprobado' => 0,
        'pendiente' => 0,
        'rechazado' => 0
    ];
    
    foreach ($companies as $company) {
        // Group by sector
        if (!empty($company->sector)) {
            if (!isset($by_sector[$company->sector])) {
                $by_sector[$company->sector] = [
                    'sector' => $company->sector,
                    'sector_text' => get_string('sector_' . $company->sector, 'local_conocer_cert'),
                    'count' => 0
                ];
            }
            
            $by_sector[$company->sector]['count']++;
        }
        
        // Count by status
        if (isset($by_status[$company->estado])) {
            $by_status[$company->estado]++;
        }
    }
    
    // Sort sectors by count
    uasort($by_sector, function($a, $b) {
        return $b['count'] - $a['count'];
    });
    
    // Get competency interests
    $competency_interests = [];
    
    foreach ($companies as $company) {
        if (!empty($company->competencias)) {
            $comp_ids = explode(',', $company->competencias);
            
            foreach ($comp_ids as $comp_id) {
                if (!isset($competency_interests[$comp_id])) {
                    $comp = $DB->get_record('local_conocer_competencias', ['id' => $comp_id]);
                    
                    if ($comp) {
                        $competency_interests[$comp_id] = [
                            'id' => $comp_id,
                            'codigo' => $comp->codigo,
                            'nombre' => $comp->nombre,
                            'count' => 0
                        ];
                    }
                }
                
                if (isset($competency_interests[$comp_id])) {
                    $competency_interests[$comp_id]['count']++;
                }
            }
        }
    }
    
    // Sort competency interests by count
    uasort($competency_interests, function($a, $b) {
        return $b['count'] - $a['count'];
    });
    
    // Prepare chart data - Sectors
    $sectors_chart = [
        'labels' => [],
        'datasets' => [
            [
                'data' => [],
                'backgroundColor' => [
                    'rgba(54, 162, 235, 0.6)',
                    'rgba(255, 99, 132, 0.6)',
                    'rgba(255, 206, 86, 0.6)',
                    'rgba(75, 192, 192, 0.6)',
                    'rgba(153, 102, 255, 0.6)',
                    'rgba(255, 159, 64, 0.6)',
                    'rgba(0, 0, 0, 0.6)'
                ]
            ]
        ]
    ];
    
    foreach ($by_sector as $sector_data) {
        $sectors_chart['labels'][] = get_string('sector_' . $sector_data['sector'], 'local_conocer_cert');
        $sectors_chart['datasets'][0]['data'][] = $sector_data['count'];
    }
    
    // Prepare chart data - Status
    $status_chart = [
        'labels' => [
            get_string('approved', 'local_conocer_cert'),
            get_string('pending', 'local_conocer_cert'),
            get_string('rejected', 'local_conocer_cert')
        ],
        'datasets' => [
            [
                'data' => [
                    $by_status['aprobado'],
                    $by_status['pendiente'],
                    $by_status['rechazado']
                ],
                'backgroundColor' => [
                    'rgba(75, 192, 192, 0.6)',
                    'rgba(255, 206, 86, 0.6)',
                    'rgba(255, 99, 132, 0.6)'
                ]
            ]
        ]
    ];
    
    // Format companies for table display
    $table_data = [];
    foreach ($companies as $company) {
        $table_data[] = [
            'id' => $company->id,
            'nombre' => $company->nombre,
            'rfc' => $company->rfc,
            'sector' => $company->sector,
            'sector_text' => get_string('sector_' . $company->sector, 'local_conocer_cert'),
            'contacto_nombre' => $company->contacto_nombre,
            'contacto_email' => $company->contacto_email,
            'estado' => $company->estado,
            'estado_text' => get_string('estado_' . $company->estado, 'local_conocer_cert'),
            'fecha_solicitud' => userdate($company->fecha_solicitud),
            'fecha_aprobacion' => !empty($company->fecha_aprobacion) ? userdate($company->fecha_aprobacion) : '-'
        ];
    }
    
    return [
        'companies' => $table_data,
        'chart_data' => [
            'sectors' => $sectors_chart,
            'status' => $status_chart
        ],
        'by_sector' => array_values($by_sector),
        'by_status' => $by_status,
        'competency_interests' => array_values($competency_interests),
        'companies_count' => count($companies),
        'approved_count' => $by_status['aprobado']
    ];
}

/**
 * Get monthly certification data for charts
 *
 * @param int $months Number of months to include
 * @return array Monthly data
 */
function get_monthly_certification_data($months = 12) {
    global $DB;
    
    $data = [];
    $current_month = time();
    
    // Get data for each month
    for ($i = 0; $i < $months; $i++) {
        $month_start = strtotime("-$i months", $current_month);
        $month_start = strtotime(date('Y-m-01', $month_start));
        
        $month_end = strtotime(date('Y-m-t', $month_start)) + 86399; // End of month
        
        // Format month label
        $month_label = date('M Y', $month_start);
        
        // Count certifications for this month
        $total_sql = "SELECT COUNT(*) 
                       FROM {local_conocer_procesos}
                       WHERE fecha_fin BETWEEN :start_date AND :end_date
                       AND resultado IS NOT NULL";
        
        $total_count = $DB->count_records_sql($total_sql, [
            'start_date' => $month_start,
            'end_date' => $month_end
        ]);
        
        // Count approved certifications
        $approved_sql = "SELECT COUNT(*) 
                          FROM {local_conocer_procesos}
                          WHERE fecha_fin BETWEEN :start_date AND :end_date
                          AND resultado = 'aprobado'";
        
        $approved_count = $DB->count_records_sql($approved_sql, [
            'start_date' => $month_start,
            'end_date' => $month_end
        ]);
        
        // Count rejected certifications
        $rejected_sql = "SELECT COUNT(*) 
                          FROM {local_conocer_procesos}
                          WHERE fecha_fin BETWEEN :start_date AND :end_date
                          AND resultado = 'rechazado'";
        
        $rejected_count = $DB->count_records_sql($rejected_sql, [
            'start_date' => $month_start,
            'end_date' => $month_end
        ]);
        
        // Count new requests
        $requests_sql = "SELECT COUNT(*) 
                          FROM {local_conocer_candidatos}
                          WHERE fecha_solicitud BETWEEN :start_date AND :end_date";
        
        $requests_count = $DB->count_records_sql($requests_sql, [
            'start_date' => $month_start,
            'end_date' => $month_end
        ]);
        
        // Add to data array (in reverse order)
        $data[$months - $i - 1] = [
            'month' => $month_label,
            'total' => $total_count,
            'approved' => $approved_count,
            'rejected' => $rejected_count,
            'requests' => $requests_count
        ];
    }
    
    // Format for chart.js
    $chart_data = [
        'labels' => array_column($data, 'month'),
        'datasets' => [
            [
                'label' => get_string('certification_requests', 'local_conocer_cert'),
                'data' => array_column($data, 'requests'),
                'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                'borderColor' => 'rgba(54, 162, 235, 1)',
                'borderWidth' => 1,
                'tension' => 0.1
            ],
            [
                'label' => get_string('approved_certifications', 'local_conocer_cert'),
                'data' => array_column($data, 'approved'),
                'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
                'borderColor' => 'rgba(75, 192, 192, 1)',
                'borderWidth' => 1,
                'tension' => 0.1
            ],
            [
                'label' => get_string('rejected_certifications', 'local_conocer_cert'),
                'data' => array_column($data, 'rejected'),
                'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
                'borderColor' => 'rgba(255, 99, 132, 1)',
                'borderWidth' => 1,
                'tension' => 0.1
            ]
        ]
    ];
    
    return $chart_data;
}

/**
 * Get recent activity for display
 *
 * @param int $limit Number of activities to return
 * @return array Recent activities
 */
function get_recent_activity($limit = 10) {
    global $DB;
    
    $activity = [];
    
    // Get recent certifications
    $cert_sql = "SELECT p.id, p.candidato_id, p.resultado, p.fecha_fin,
                       c.userid, c.competencia_id, c.nivel,
                       u.firstname, u.lastname,
                       comp.nombre as competencia_nombre, comp.codigo as competencia_codigo
                FROM {local_conocer_procesos} p
                JOIN {local_conocer_candidatos} c ON p.candidato_id = c.id
                JOIN {user} u ON c.userid = u.id
                JOIN {local_conocer_competencias} comp ON c.competencia_id = comp.id
                WHERE p.resultado IS NOT NULL
                ORDER BY p.fecha_fin DESC
                LIMIT $limit";
    
    $certifications = $DB->get_records_sql($cert_sql);
    
    foreach ($certifications as $cert) {
        $activity[] = [
            'type' => 'certification',
            'id' => $cert->id,
            'date' => $cert->fecha_fin,
            'user_id' => $cert->userid,
            'user_name' => $cert->firstname . ' ' . $cert->lastname,
            'competencia' => $cert->competencia_nombre,
            'codigo' => $cert->competencia_codigo,
            'nivel' => $cert->nivel,
            'resultado' => $cert->resultado,
            'details_url' => new moodle_url('/local/conocer_cert/admin/view_process.php', ['id' => $cert->id])
        ];
    }
    
    // Get recent registrations
    $reg_sql = "SELECT c.id, c.userid, c.competencia_id, c.nivel, c.fecha_solicitud,
                      u.firstname, u.lastname,
                      comp.nombre as competencia_nombre, comp.codigo as competencia_codigo
               FROM {local_conocer_candidatos} c
               JOIN {user} u ON c.userid = u.id
               JOIN {local_conocer_competencias} comp ON c.competencia_id = comp.id
               ORDER BY c.fecha_solicitud DESC
               LIMIT $limit";
    
    $registrations = $DB->get_records_sql($reg_sql);
    
    foreach ($registrations as $reg) {
        $activity[] = [
            'type' => 'registration',
            'id' => $reg->id,
            'date' => $reg->fecha_solicitud,
            'user_id' => $reg->userid,
            'user_name' => $reg->firstname . ' ' . $reg->lastname,
            'competencia' => $reg->competencia_nombre,
            'codigo' => $reg->competencia_codigo,
            'nivel' => $reg->nivel,
            'details_url' => new moodle_url('/local/conocer_cert/admin/view_candidate.php', ['id' => $reg->id])
        ];
    }
    
    // Get recent company registrations
    $company_sql = "SELECT id, nombre, rfc, sector, contacto_nombre, contacto_email, fecha_solicitud
                   FROM {local_conocer_empresas}
                   ORDER BY fecha_solicitud DESC
                   LIMIT $limit";
    
    $companies = $DB->get_records_sql($company_sql);
    
    foreach ($companies as $company) {
        $activity[] = [
            'type' => 'company',
            'id' => $company->id,
            'date' => $company->fecha_solicitud,
            'nombre' => $company->nombre,
            'contacto_nombre' => $company->contacto_nombre,
            'sector' => $company->sector,
            'details_url' => new moodle_url('/local/conocer_cert/admin/view_company.php', ['id' => $company->id])
        ];
    }
    
    // Sort by date (newest first)
    usort($activity, function($a, $b) {
        return $b['date'] - $a['date'];
    });
    
    // Format dates and limit results
    $result = [];
    foreach (array_slice($activity, 0, $limit) as $item) {
        $item['date_formatted'] = userdate($item['date']);
        $result[] = $item;
    }
    
    return $result;
}

/**
 * Get competency name by ID
 *
 * @param int $competencyid Competency ID
 * @return string Competency name
 */
function get_competency_name($competencyid) {
    global $DB;
    
    if (empty($competencyid)) {
        return '';
    }
    
    $competency = $DB->get_record('local_conocer_competencias', ['id' => $competencyid]);
    
    if ($competency) {
        return $competency->nombre;
    }
    
    return '';
}

/**
 * Export report data to file (CSV or Excel)
 *
 * @param array $data Report data
 * @param string $type Report type
 * @param string $format Export format (csv, excel)
 */
function export_report($data, $type, $format) {
    global $CFG;
    
    require_once($CFG->libdir . '/csvlib.class.php');
    
    // Prepare export data
    $export_data = [];
    $filename = 'reporte_' . $type . '_' . date('Y-m-d');
    
    switch ($type) {
        case 'certifications':
            $export_data = prepare_certifications_export($data);
            break;
        case 'competencies':
            $export_data = prepare_competencies_export($data);
            break;
        case 'evaluators':
            $export_data = prepare_evaluators_export($data);
            break;
        case 'companies':
            $export_data = prepare_companies_export($data);
            break;
        default:
            $export_data = prepare_general_export($data);
            break;
    }
    
    if ($format == 'csv') {
        $csvexport = new csv_export_writer();
        $csvexport->set_filename($filename);
        
        // Add headers
        $csvexport->add_data($export_data['headers']);
        
        // Add data rows
        foreach ($export_data['data'] as $row) {
            $csvexport->add_data($row);
        }
        
        $csvexport->download_file();
    } else if ($format == 'excel') {
        // For Excel export, we need the PHPExcel library
        // This needs to be installed separately
        require_once($CFG->dirroot . '/lib/excellib.class.php');
        
        $workbook = new MoodleExcelWorkbook($filename);
        $worksheet = $workbook->add_worksheet($type);
        
        // Add headers
        $col = 0;
        foreach ($export_data['headers'] as $header) {
            $worksheet->write(0, $col, $header);
            $col++;
        }
        
        // Add data rows
        $row = 1;
        foreach ($export_data['data'] as $data_row) {
            $col = 0;
            foreach ($data_row as $cell) {
                $worksheet->write($row, $col, $cell);
                $col++;
            }
            $row++;
        }
        
        $workbook->close();
    }
}

/**
 * Prepare certifications data for export
 *
 * @param array $data Report data
 * @return array Export-ready data
 */
function prepare_certifications_export($data) {
    $export = [
        'headers' => [
            get_string('candidate', 'local_conocer_cert'),
            get_string('competency', 'local_conocer_cert'),
            get_string('code', 'local_conocer_cert'),
            get_string('level', 'local_conocer_cert'),
            get_string('result', 'local_conocer_cert'),
            get_string('startdate', 'local_conocer_cert'),
            get_string('completiondate', 'local_conocer_cert'),
            get_string('duration_days', 'local_conocer_cert')
        ],
        'data' => []
    ];
    
    foreach ($data['table_data'] as $row) {
        $export['data'][] = [
            $row['candidate'],
            $row['competencia'],
            $row['codigo'],
            $row['nivel'],
            get_string('resultado_' . $row['resultado'], 'local_conocer_cert'),
            $row['fecha_inicio'],
            $row['fecha_fin'],
            $row['duracion_dias']
        ];
    }
    
    return $export;
}

/**
 * Prepare competencies data for export
 *
 * @param array $data Report data
 * @return array Export-ready data
 */
function prepare_competencies_export($data) {
    $export = [
        'headers' => [
            get_string('code', 'local_conocer_cert'),
            get_string('competency', 'local_conocer_cert'),
            get_string('sector', 'local_conocer_cert'),
            get_string('available_levels', 'local_conocer_cert'),
            get_string('total_requests', 'local_conocer_cert'),
            get_string('approved_certifications', 'local_conocer_cert'),
            get_string('success_rate', 'local_conocer_cert'),
            get_string('active', 'local_conocer_cert')
        ],
        'data' => []
    ];
    
    foreach ($data['competencies'] as $comp) {
        // Find data for this competency
        $requests = 0;
        $approved = 0;
        $success_rate = 0;
        
        foreach ($data['most_requested'] as $req) {
            if ($req['id'] == $comp->id) {
                $requests = $req['total_solicitudes'];
                $approved = $req['aprobadas'];
                $success_rate = $req['porcentaje_exito'];
                break;
            }
        }
        
        $export['data'][] = [
            $comp->codigo,
            $comp->nombre,
            get_string('sector_' . $comp->sector, 'local_conocer_cert'),
            $comp->niveles_disponibles,
            $requests,
            $approved,
            $success_rate . '%',
            $comp->activo ? get_string('yes') : get_string('no')
        ];
    }
    
    return $export;
}

/**
 * Prepare evaluators data for export
 *
 * @param array $data Report data
 * @return array Export-ready data
 */
function prepare_evaluators_export($data) {
    $export = [
        'headers' => [
            get_string('evaluator', 'local_conocer_cert'),
            get_string('email'),
            get_string('status', 'local_conocer_cert'),
            get_string('total_evaluations', 'local_conocer_cert'),
            get_string('completed_evaluations', 'local_conocer_cert'),
            get_string('approved_evaluations', 'local_conocer_cert'),
            get_string('rejected_evaluations', 'local_conocer_cert'),
            get_string('avg_days', 'local_conocer_cert'),
            get_string('success_rate', 'local_conocer_cert')
        ],
        'data' => []
    ];
    
    foreach ($data['evaluators'] as $evaluator) {
        $export['data'][] = [
            $evaluator['name'],
            $evaluator['email'],
            get_string('evaluator_status_' . $evaluator['status'], 'local_conocer_cert'),
            $evaluator['total_evaluations'],
            $evaluator['completed_evaluations'],
            $evaluator['approved'],
            $evaluator['rejected'],
            $evaluator['avg_days'],
            $evaluator['success_rate'] . '%'
        ];
    }
    
    return $export;
}

/**
 * Prepare companies data for export
 *
 * @param array $data Report data
 * @return array Export-ready data
 */
function prepare_companies_export($data) {
    $export = [
        'headers' => [
            get_string('company', 'local_conocer_cert'),
            get_string('rfc', 'local_conocer_cert'),
            get_string('sector', 'local_conocer_cert'),
            get_string('contact', 'local_conocer_cert'),
            get_string('email'),
            get_string('status', 'local_conocer_cert'),
            get_string('application_date', 'local_conocer_cert'),
            get_string('approval_date', 'local_conocer_cert')
        ],
        'data' => []
    ];
    
    foreach ($data['companies'] as $company) {
        $export['data'][] = [
            $company['nombre'],
            $company['rfc'],
            $company['sector_text'],
            $company['contacto_nombre'],
            $company['contacto_email'],
            $company['estado_text'],
            $company['fecha_solicitud'],
            $company['fecha_aprobacion']
        ];
    }
    
    return $export;
}

/**
 * Prepare general stats data for export
 *
 * @param array $data Report data
 * @return array Export-ready data
 */
function prepare_general_export($data) {
    $stats = $data['general_stats'];
    
    $export = [
        'headers' => [
            get_string('statistic', 'local_conocer_cert'),
            get_string('value', 'local_conocer_cert')
        ],
        'data' => [
            [get_string('total_candidates', 'local_conocer_cert'), $stats['total_candidates']],
            [get_string('total_companies', 'local_conocer_cert'), $stats['total_companies']],
            [get_string('total_competencies', 'local_conocer_cert'), $stats['total_competencies']],
            [get_string('total_evaluators', 'local_conocer_cert'), $stats['total_evaluators']],
            [get_string('total_certifications', 'local_conocer_cert'), $stats['total_certifications']],
            [get_string('approved_certifications', 'local_conocer_cert'), $stats['approved_certifications']],
            [get_string('rejected_certifications', 'local_conocer_cert'), $stats['rejected_certifications']],
            [get_string('pending_evaluations', 'local_conocer_cert'), $stats['pending_evaluations']],
            [get_string('active_processes', 'local_conocer_cert'), $stats['active_processes']]
        ]
    ];
    
    return $export;
}