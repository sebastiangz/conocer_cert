<?php
// Archivo: local/conocer_cert/classes/output/renderable.php
// Clases renderizables para el plugin de certificaciones CONOCER

namespace local_conocer_cert\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use templatable;
use renderer_base;

/**
 * Clase base para todas las páginas renderizables
 */
abstract class base_page implements renderable, templatable {
    /** @var string Título de la página */
    protected $title;
    
    /** @var array Datos adicionales para la página */
    protected $data;
    
    /**
     * Constructor
     *
     * @param string $title Título de la página
     * @param array $data Datos adicionales
     */
    public function __construct($title, $data = []) {
        $this->title = $title;
        $this->data = $data;
    }
    
    /**
     * Exporta los datos para la plantilla
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output) {
        global $DB, $USER;
        
        // Obtener datos del usuario asociado al candidato
        $user = $DB->get_record('user', ['id' => $this->candidate->userid]);
        
        // Obtener competencia
        $competencia = $DB->get_record('local_conocer_competencias', ['id' => $this->candidate->competencia_id]);
        
        $data = [
            'id' => $this->candidate->id,
            'process_id' => $this->process->id,
            'fullname' => fullname($user),
            'email' => $user->email,
            'curp' => $this->candidate->curp,
            'telefono' => $this->candidate->telefono,
            'direccion' => $this->candidate->direccion,
            'competencia' => $competencia ? $competencia->nombre : '',
            'codigo_competencia' => $competencia ? $competencia->codigo : '',
            'nivel' => $this->candidate->nivel,
            'experiencia' => $this->candidate->experiencia,
            'etapa' => $this->process->etapa,
            'etapa_texto' => get_string('etapa_' . $this->process->etapa, 'local_conocer_cert'),
            'fecha_inicio' => userdate($this->process->fecha_inicio),
            'documents' => [],
            'has_documents' => !empty($this->documents),
            'puede_evaluar' => has_capability('local/conocer_cert:evaluatecandidates', \context_system::instance()) && 
                              ($USER->id == $this->process->evaluador_id || has_capability('local/conocer_cert:managecandidates', \context_system::instance())),
            'is_evaluator' => ($USER->id == $this->process->evaluador_id)
        ];
        
        // Procesar documentos
        foreach ($this->documents as $doc) {
            $data['documents'][] = [
                'id' => $doc->id,
                'nombre' => $doc->nombre_archivo,
                'tipo' => get_string('doc_' . $doc->tipo, 'local_conocer_cert'),
                'fecha' => userdate($doc->fecha_subida),
                'estado' => $doc->estado,
                'view_url' => new \moodle_url('/local/conocer_cert/document.php', ['id' => $doc->id, 'action' => 'view'])
            ];
        }
        
        // Añadir datos adicionales
        foreach ($this->data as $key => $value) {
            $data[$key] = $value;
        }
        
        return $data;
    }
}

/**
 * Clase para la tarjeta de certificación
 */
class certification_card implements renderable, templatable {
    /** @var \stdClass Datos del proceso de certificación */
    protected $process;
    
    /** @var \stdClass Datos del candidato */
    protected $candidate;
    
    /** @var \stdClass Datos de la competencia */
    protected $competency;
    
    /**
     * Constructor
     *
     * @param \stdClass $process Proceso de certificación
     * @param \stdClass $candidate Datos del candidato
     * @param \stdClass $competency Datos de la competencia
     */
    public function __construct($process, $candidate, $competency) {
        $this->process = $process;
        $this->candidate = $candidate;
        $this->competency = $competency;
    }
    
    /**
     * Exporta los datos para la plantilla
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output) {
        global $DB;
        
        // Obtener usuario
        $user = $DB->get_record('user', ['id' => $this->candidate->userid]);
        
        // Obtener certificado si existe
        $certificado = null;
        if (!empty($this->process->certificado_id)) {
            $certificado = $DB->get_record('local_conocer_certificados', ['id' => $this->process->certificado_id]);
        }
        
        $data = [
            'process_id' => $this->process->id,
            'candidate_id' => $this->candidate->id,
            'competency_id' => $this->competency->id,
            'fullname' => fullname($user),
            'competencia' => $this->competency->nombre,
            'codigo' => $this->competency->codigo,
            'nivel' => $this->candidate->nivel,
            'nivel_texto' => get_string('level' . $this->candidate->nivel, 'local_conocer_cert'),
            'fecha' => userdate($this->process->fecha_fin),
            'resultado' => $this->process->resultado,
            'resultado_texto' => get_string('resultado_' . $this->process->resultado, 'local_conocer_cert'),
            'has_certificado' => !empty($certificado),
            'view_url' => new \moodle_url('/local/conocer_cert/candidate/view_certification.php', ['id' => $this->process->id])
        ];
        
        // Añadir datos del certificado si existe
        if ($certificado) {
            $data['certificado'] = [
                'id' => $certificado->id,
                'folio' => $certificado->numero_folio,
                'fecha_emision' => userdate($certificado->fecha_emision),
                'download_url' => new \moodle_url('/local/conocer_cert/candidate/download_certificate.php', ['id' => $certificado->id])
            ];
            
            if (!empty($certificado->fecha_vencimiento)) {
                $data['certificado']['fecha_vencimiento'] = userdate($certificado->fecha_vencimiento);
                $data['certificado']['has_vencimiento'] = true;
                
                // Verificar si está vencido
                $data['certificado']['is_expired'] = (time() > $certificado->fecha_vencimiento);
            } else {
                $data['certificado']['has_vencimiento'] = false;
            }
        }
        
        // Determinar clase CSS según resultado
        switch ($this->process->resultado) {
            case 'aprobado':
                $data['resultado_class'] = 'success';
                break;
            case 'rechazado':
                $data['resultado_class'] = 'danger';
                break;
            default:
                $data['resultado_class'] = 'info';
        }
        
        return $data;
    }
}

/**
 * Clase para el indicador de estado
 */
class status_indicator implements renderable, templatable {
    /** @var string Estado a mostrar */
    protected $status;
    
    /** @var string Contexto (candidato, empresa, documento, etc.) */
    protected $context;
    
    /**
     * Constructor
     *
     * @param string $status Estado a mostrar
     * @param string $context Contexto
     */
    public function __construct($status, $context = 'general') {
        $this->status = $status;
        $this->context = $context;
    }
    
    /**
     * Exporta los datos para la plantilla
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output) {
        // Determinar clase CSS según estado
        $statusClass = 'info';
        $iconClass = 'fa-info-circle';
        
        switch ($this->status) {
            case 'pendiente':
                $statusClass = 'warning';
                $iconClass = 'fa-clock-o';
                break;
            case 'aprobado':
            case 'activo':
            case 'completado':
                $statusClass = 'success';
                $iconClass = 'fa-check-circle';
                break;
            case 'rechazado':
            case 'suspendido':
            case 'cancelado':
                $statusClass = 'danger';
                $iconClass = 'fa-times-circle';
                break;
            case 'evaluacion':
            case 'revision':
                $statusClass = 'primary';
                $iconClass = 'fa-clipboard';
                break;
            default:
                $statusClass = 'info';
                $iconClass = 'fa-info-circle';
        }
        
        $data = [
            'status' => $this->status,
            'status_text' => get_string($this->context . '_status_' . $this->status, 'local_conocer_cert'),
            'status_class' => $statusClass,
            'icon_class' => $iconClass
        ];
        
        return $data;
    }
}

/**
 * Clase para la barra de progreso de certificación
 */
class certification_progress implements renderable, templatable {
    /** @var string Etapa actual del proceso */
    protected $stage;
    
    /** @var array Etapas del proceso */
    protected $stages;
    
    /** @var string Resultado (si el proceso ha finalizado) */
    protected $result;
    
    /**
     * Constructor
     *
     * @param string $stage Etapa actual
     * @param array $stages Etapas del proceso (opcional)
     * @param string $result Resultado (opcional)
     */
    public function __construct($stage, $stages = null, $result = null) {
        $this->stage = $stage;
        
        // Etapas por defecto si no se especifican
        if ($stages === null) {
            $this->stages = [
                'solicitud', 
                'documentacion', 
                'evaluacion', 
                'resultados'
            ];
        } else {
            $this->stages = $stages;
        }
        
        $this->result = $result;
    }
    
    /**
     * Exporta los datos para la plantilla
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output) {
        $data = [
            'current_stage' => $this->stage,
            'has_result' => !empty($this->result),
            'stages' => []
        ];
        
        if (!empty($this->result)) {
            $data['result'] = $this->result;
            
            // Determinar clase CSS según resultado
            switch ($this->result) {
                case 'aprobado':
                    $data['result_class'] = 'success';
                    break;
                case 'rechazado':
                    $data['result_class'] = 'danger';
                    break;
                default:
                    $data['result_class'] = 'info';
            }
        }
        
        // Procesar cada etapa
        $currentReached = false;
        foreach ($this->stages as $index => $stage) {
            // Determinar si la etapa actual ya se alcanzó
            if ($stage == $this->stage) {
                $currentReached = true;
            }
            
            // Determinar estado de la etapa (completada, actual, pendiente)
            $stageStatus = 'pending';
            if ($currentReached === false) {
                $stageStatus = 'completed';
            } else if ($stage == $this->stage) {
                $stageStatus = 'current';
            }
            
            $data['stages'][] = [
                'name' => $stage,
                'label' => get_string('stage_' . $stage, 'local_conocer_cert'),
                'position' => $index + 1,
                'status' => $stageStatus,
                'is_current' => ($stage == $this->stage),
                'is_completed' => ($stageStatus == 'completed'),
                'is_pending' => ($stageStatus == 'pending')
            ];
        }
        
        // Calcular porcentaje de progreso
        $totalStages = count($this->stages);
        $currentIndex = array_search($this->stage, $this->stages);
        
        if ($currentIndex !== false) {
            $data['progress_percent'] = round(($currentIndex / ($totalStages - 1)) * 100);
        } else {
            $data['progress_percent'] = 0;
        }
        
        return $data;
    }
}

/**
 * Clase para la lista de notificaciones
 */
class notifications_list implements renderable, templatable {
    /** @var array Lista de notificaciones */
    protected $notifications;
    
    /** @var int Cantidad máxima a mostrar */
    protected $limit;
    
    /**
     * Constructor
     *
     * @param array $notifications Lista de notificaciones
     * @param int $limit Cantidad máxima a mostrar
     */
    public function __construct($notifications, $limit = 5) {
        $this->notifications = $notifications;
        $this->limit = $limit;
    }
    
    /**
     * Exporta los datos para la plantilla
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output) {
        global $DB;
        
        $data = [
            'has_notifications' => !empty($this->notifications),
            'count' => count($this->notifications),
            'notifications' => []
        ];
        
        // Limitar cantidad si es necesario
        $notifications = array_slice($this->notifications, 0, $this->limit);
        
        // Procesar cada notificación
        foreach ($notifications as $notification) {
            // Obtener datos del remitente
            $from = $DB->get_record('user', ['id' => $notification->remitente_id]);
            
            $data['notifications'][] = [
                'id' => $notification->id,
                'asunto' => $notification->asunto,
                'mensaje' => $notification->mensaje,
                'leido' => $notification->leido,
                'is_unread' => ($notification->leido == 0),
                'fecha' => userdate($notification->timecreated),
                'from_name' => $from ? fullname($from) : get_string('system', 'local_conocer_cert'),
                'mark_read_url' => new \moodle_url('/local/conocer_cert/notifications.php', ['id' => $notification->id, 'action' => 'mark_read']),
                'view_url' => new \moodle_url('/local/conocer_cert/notifications.php', ['id' => $notification->id, 'action' => 'view'])
            ];
        }
        
        // Si hay más notificaciones que el límite, agregar enlace para ver todas
        if (count($this->notifications) > $this->limit) {
            $data['has_more'] = true;
            $data['view_all_url'] = new \moodle_url('/local/conocer_cert/notifications.php');
        } else {
            $data['has_more'] = false;
        }
        
        return $data;
    }
}

/**
 * Clase para la página de impresión de certificado
 */
class print_certificate implements renderable, templatable {
    /** @var \stdClass Datos del certificado */
    protected $certificate;
    
    /** @var \stdClass Datos del proceso */
    protected $process;
    
    /** @var \stdClass Datos del candidato */
    protected $candidate;
    
    /** @var \stdClass Datos de la competencia */
    protected $competency;
    
    /**
     * Constructor
     *
     * @param \stdClass $certificate Datos del certificado
     * @param \stdClass $process Datos del proceso
     * @param \stdClass $candidate Datos del candidato
     * @param \stdClass $competency Datos de la competencia
     */
    public function __construct($certificate, $process, $candidate, $competency) {
        $this->certificate = $certificate;
        $this->process = $process;
        $this->candidate = $candidate;
        $this->competency = $competency;
    }
    
    /**
     * Exporta los datos para la plantilla
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output) {
        global $DB, $SITE;
        
        // Obtener datos del usuario
        $user = $DB->get_record('user', ['id' => $this->candidate->userid]);
        
        // Obtener datos del emisor
        $emisor = $DB->get_record('user', ['id' => $this->certificate->emitido_por]);
        
        $data = [
            'certificate_id' => $this->certificate->id,
            'folio' => $this->certificate->numero_folio,
            'fecha_emision' => userdate($this->certificate->fecha_emision),
            'fullname' => fullname($user),
            'curp' => $this->candidate->curp,
            'competencia' => $this->competency->nombre,
            'codigo_competencia' => $this->competency->codigo,
            'nivel' => $this->candidate->nivel,
            'nivel_texto' => get_string('level' . $this->candidate->nivel, 'local_conocer_cert'),
            'site_name' => $SITE->fullname,
            'verification_url' => new \moodle_url('/local/conocer_cert/verify.php', ['hash' => $this->certificate->hash_verificacion]),
            'verification_hash' => $this->certificate->hash_verificacion,
            'has_vencimiento' => !empty($this->certificate->fecha_vencimiento)
        ];
        
        // Añadir fecha de vencimiento si existe
        if (!empty($this->certificate->fecha_vencimiento)) {
            $data['fecha_vencimiento'] = userdate($this->certificate->fecha_vencimiento);
        }
        
        // Añadir datos del emisor
        if ($emisor) {
            $data['emisor_nombre'] = fullname($emisor);
        } else {
            $data['emisor_nombre'] = get_string('system', 'local_conocer_cert');
        }
        
        return $data;
    }
}

/**
 * Clase para la tabla de certificaciones de candidato
 */
class candidate_certifications_table implements renderable, templatable {
    /** @var array Certificaciones del candidato */
    protected $certifications;
    
    /** @var int ID del usuario */
    protected $userid;
    
    /**
     * Constructor
     *
     * @param array $certifications Certificaciones
     * @param int $userid ID del usuario
     */
    public function __construct($certifications, $userid) {
        $this->certifications = $certifications;
        $this->userid = $userid;
    }
    
    /**
     * Exporta los datos para la plantilla
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output) {
        global $DB;
        
        $data = [
            'userid' => $this->userid,
            'has_certifications' => !empty($this->certifications),
            'certifications' => []
        ];
        
        foreach ($this->certifications as $cert) {
            // Obtener competencia
            $competencia = $DB->get_record('local_conocer_competencias', ['id' => $cert->competencia_id]);
            
            // Datos básicos
            $certData = [
                'id' => $cert->id,
                'competencia' => $competencia ? $competencia->nombre : '',
                'codigo' => $competencia ? $competencia->codigo : '',
                'nivel' => $cert->nivel,
                'nivel_texto' => get_string('level' . $cert->nivel, 'local_conocer_cert'),
                'fecha' => userdate($cert->fecha_fin),
                'resultado' => $cert->resultado,
                'resultado_texto' => get_string('resultado_' . $cert->resultado, 'local_conocer_cert'),
                'view_url' => new \moodle_url('/local/conocer_cert/candidate/view_certification.php', ['id' => $cert->id])
            ];
            
            // Determinar clase CSS según resultado
            switch ($cert->resultado) {
                case 'aprobado':
                    $certData['resultado_class'] = 'success';
                    break;
                case 'rechazado':
                    $certData['resultado_class'] = 'danger';
                    break;
                default:
                    $certData['resultado_class'] = 'info';
            }
            
            // Verificar si tiene certificado
            if (!empty($cert->certificado_id)) {
                $certificado = $DB->get_record('local_conocer_certificados', ['id' => $cert->certificado_id]);
                if ($certificado) {
                    $certData['has_certificado'] = true;
                    $certData['folio'] = $certificado->numero_folio;
                    $certData['download_url'] = new \moodle_url('/local/conocer_cert/candidate/download_certificate.php', ['id' => $certificado->id]);
                    
                    // Verificar si está vencido
                    if (!empty($certificado->fecha_vencimiento)) {
                        $certData['is_expired'] = (time() > $certificado->fecha_vencimiento);
                    }
                } else {
                    $certData['has_certificado'] = false;
                }
            } else {
                $certData['has_certificado'] = false;
            }
            
            $data['certifications'][] = $certData;
        }
        
        return $data;
    }
}

     * @param renderer_base $output
     * @return array
     */
    abstract public function export_for_template(renderer_base $output);
    
    /**
     * Obtiene el título de la página
     *
     * @return string
     */
    public function get_title() {
        return $this->title;
    }
}

/**
 * Clase para la página del dashboard del candidato
 */
class candidate_dashboard_page extends base_page {
    /** @var \local_conocer_cert\dashboard\candidate_dashboard Dashboard del candidato */
    protected $dashboard;
    
    /**
     * Constructor
     *
     * @param \local_conocer_cert\dashboard\candidate_dashboard $dashboard
     * @param string $title Título de la página
     * @param array $data Datos adicionales
     */
    public function __construct($dashboard, $title, $data = []) {
        parent::__construct($title, $data);
        $this->dashboard = $dashboard;
    }
    
    /**
     * Exporta los datos para la plantilla
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output) {
        $data = $this->dashboard->export_for_template($output);
        $data['title'] = $this->title;
        
        // Añadir datos adicionales
        foreach ($this->data as $key => $value) {
            $data[$key] = $value;
        }
        
        return $data;
    }
}

/**
 * Clase para la página del dashboard de la empresa
 */
class company_dashboard_page extends base_page {
    /** @var \local_conocer_cert\dashboard\company_dashboard Dashboard de la empresa */
    protected $dashboard;
    
    /**
     * Constructor
     *
     * @param \local_conocer_cert\dashboard\company_dashboard $dashboard
     * @param string $title Título de la página
     * @param array $data Datos adicionales
     */
    public function __construct($dashboard, $title, $data = []) {
        parent::__construct($title, $data);
        $this->dashboard = $dashboard;
    }
    
    /**
     * Exporta los datos para la plantilla
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output) {
        $data = $this->dashboard->export_for_template($output);
        $data['title'] = $this->title;
        
        // Añadir datos adicionales
        foreach ($this->data as $key => $value) {
            $data[$key] = $value;
        }
        
        return $data;
    }
}

/**
 * Clase para la página del dashboard del evaluador
 */
class evaluator_dashboard_page extends base_page {
    /** @var \local_conocer_cert\dashboard\evaluator_dashboard Dashboard del evaluador */
    protected $dashboard;
    
    /**
     * Constructor
     *
     * @param \local_conocer_cert\dashboard\evaluator_dashboard $dashboard
     * @param string $title Título de la página
     * @param array $data Datos adicionales
     */
    public function __construct($dashboard, $title, $data = []) {
        parent::__construct($title, $data);
        $this->dashboard = $dashboard;
    }
    
    /**
     * Exporta los datos para la plantilla
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output) {
        $data = $this->dashboard->export_for_template($output);
        $data['title'] = $this->title;
        
        // Añadir datos adicionales
        foreach ($this->data as $key => $value) {
            $data[$key] = $value;
        }
        
        return $data;
    }
}

/**
 * Clase para la página del dashboard del administrador
 */
class admin_dashboard_page extends base_page {
    /** @var \local_conocer_cert\dashboard\admin_dashboard Dashboard del administrador */
    protected $dashboard;
    
    /**
     * Constructor
     *
     * @param \local_conocer_cert\dashboard\admin_dashboard $dashboard
     * @param string $title Título de la página
     * @param array $data Datos adicionales
     */
    public function __construct($dashboard, $title, $data = []) {
        parent::__construct($title, $data);
        $this->dashboard = $dashboard;
    }
    
    /**
     * Exporta los datos para la plantilla
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output) {
        $data = $this->dashboard->export_for_template($output);
        $data['title'] = $this->title;
        
        // Añadir datos adicionales
        foreach ($this->data as $key => $value) {
            $data[$key] = $value;
        }
        
        return $data;
    }
}

/**
 * Clase para la página de detalles del candidato
 */
class candidate_details_page implements renderable, templatable {
    /** @var \stdClass Datos del candidato */
    protected $candidate;
    
    /** @var array Documentos del candidato */
    protected $documents;
    
    /** @var \stdClass Proceso de certificación */
    protected $process;
    
    /** @var array Datos adicionales */
    protected $data;
    
    /**
     * Constructor
     *
     * @param \stdClass $candidate Datos del candidato
     * @param array $documents Documentos del candidato
     * @param \stdClass $process Proceso de certificación
     * @param array $data Datos adicionales
     */
    public function __construct($candidate, $documents = [], $process = null, $data = []) {
        $this->candidate = $candidate;
        $this->documents = $documents;
        $this->process = $process;
        $this->data = $data;
    }
    
    /**
     * Exporta los datos para la plantilla
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output) {
        global $DB, $USER;
        
        // Obtener datos del usuario asociado al candidato
        $user = $DB->get_record('user', ['id' => $this->candidate->userid]);
        
        // Obtener competencia
        $competencia = $DB->get_record('local_conocer_competencias', ['id' => $this->candidate->competencia_id]);
        
        $data = [
            'id' => $this->candidate->id,
            'fullname' => fullname($user),
            'email' => $user->email,
            'curp' => $this->candidate->curp,
            'telefono' => $this->candidate->telefono,
            'direccion' => $this->candidate->direccion,
            'competencia' => $competencia ? $competencia->nombre : '',
            'codigo_competencia' => $competencia ? $competencia->codigo : '',
            'nivel' => $this->candidate->nivel,
            'estado' => $this->candidate->estado,
            'fecha_solicitud' => userdate($this->candidate->fecha_solicitud),
            'experiencia' => $this->candidate->experiencia,
            'documents' => [],
            'has_documents' => !empty($this->documents),
            'has_process' => !empty($this->process),
            'can_edit' => has_capability('local/conocer_cert:managecandidates', \context_system::instance()) || $USER->id == $this->candidate->userid
        ];
        
        // Procesar documentos
        foreach ($this->documents as $doc) {
            $data['documents'][] = [
                'id' => $doc->id,
                'nombre' => $doc->nombre_archivo,
                'tipo' => get_string('doc_' . $doc->tipo, 'local_conocer_cert'),
                'fecha' => userdate($doc->fecha_subida),
                'estado' => $doc->estado,
                'view_url' => new \moodle_url('/local/conocer_cert/document.php', ['id' => $doc->id, 'action' => 'view'])
            ];
        }
        
        // Procesar proceso de certificación
        if ($this->process) {
            $data['process'] = [
                'id' => $this->process->id,
                'etapa' => $this->process->etapa,
                'etapa_texto' => get_string('etapa_' . $this->process->etapa, 'local_conocer_cert'),
                'fecha_inicio' => userdate($this->process->fecha_inicio),
                'resultado' => $this->process->resultado,
                'notas' => $this->process->notas
            ];
            
            // Si hay evaluador asignado
            if (!empty($this->process->evaluador_id)) {
                $evaluador = $DB->get_record('user', ['id' => $this->process->evaluador_id]);
                $data['process']['evaluador'] = fullname($evaluador);
                $data['process']['has_evaluador'] = true;
            } else {
                $data['process']['has_evaluador'] = false;
            }
        }
        
        // Añadir datos adicionales
        foreach ($this->data as $key => $value) {
            $data[$key] = $value;
        }
        
        return $data;
    }
}

/**
 * Clase para la página de detalles de la empresa
 */
class company_details_page implements renderable, templatable {
    /** @var \stdClass Datos de la empresa */
    protected $company;
    
    /** @var array Documentos de la empresa */
    protected $documents;
    
    /** @var array Competencias de interés */
    protected $competencies;
    
    /** @var array Datos adicionales */
    protected $data;
    
    /**
     * Constructor
     *
     * @param \stdClass $company Datos de la empresa
     * @param array $documents Documentos de la empresa
     * @param array $competencies Competencias de interés
     * @param array $data Datos adicionales
     */
    public function __construct($company, $documents = [], $competencies = [], $data = []) {
        $this->company = $company;
        $this->documents = $documents;
        $this->competencies = $competencies;
        $this->data = $data;
    }
    
    /**
     * Exporta los datos para la plantilla
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output) {
        global $DB, $USER;
        
        $data = [
            'id' => $this->company->id,
            'nombre' => $this->company->nombre,
            'rfc' => $this->company->rfc,
            'direccion' => $this->company->direccion,
            'contacto_nombre' => $this->company->contacto_nombre,
            'contacto_email' => $this->company->contacto_email,
            'contacto_telefono' => $this->company->contacto_telefono,
            'estado' => $this->company->estado,
            'estado_texto' => get_string('estado_' . $this->company->estado, 'local_conocer_cert'),
            'fecha_solicitud' => userdate($this->company->fecha_solicitud),
            'documents' => [],
            'competencies' => [],
            'has_documents' => !empty($this->documents),
            'has_competencies' => !empty($this->competencies),
            'can_edit' => has_capability('local/conocer_cert:managecompanies', \context_system::instance()) || 
                          (isset($this->company->contacto_userid) && $USER->id == $this->company->contacto_userid)
        ];
        
        // Procesar documentos
        foreach ($this->documents as $doc) {
            $data['documents'][] = [
                'id' => $doc->id,
                'nombre' => $doc->nombre,
                'tipo' => get_string('doc_empresa_' . $doc->tipo, 'local_conocer_cert'),
                'fecha' => userdate($doc->fecha),
                'view_url' => new \moodle_url('/local/conocer_cert/company_document.php', ['id' => $doc->id, 'action' => 'view'])
            ];
        }
        
        // Procesar competencias
        foreach ($this->competencies as $comp) {
            $data['competencies'][] = [
                'id' => $comp->id,
                'nombre' => $comp->nombre,
                'codigo' => $comp->codigo
            ];
        }
        
        // Añadir datos adicionales
        foreach ($this->data as $key => $value) {
            $data[$key] = $value;
        }
        
        return $data;
    }
}

/**
 * Clase para la página de detalles de competencia
 */
class competency_details_page implements renderable, templatable {
    /** @var \stdClass Datos de la competencia */
    protected $competency;
    
    /** @var array Datos adicionales */
    protected $data;
    
    /**
     * Constructor
     *
     * @param \stdClass $competency Datos de la competencia
     * @param array $data Datos adicionales
     */
    public function __construct($competency, $data = []) {
        $this->competency = $competency;
        $this->data = $data;
    }
    
    /**
     * Exporta los datos para la plantilla
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output) {
        global $DB;
        
        // Procesar niveles disponibles
        $niveles = explode(',', $this->competency->niveles_disponibles);
        $nivelesData = [];
        foreach ($niveles as $nivel) {
            $descripcionField = 'descripcion_nivel' . $nivel;
            $nivelesData[] = [
                'nivel' => $nivel,
                'nivel_texto' => get_string('level' . $nivel, 'local_conocer_cert'),
                'descripcion' => isset($this->competency->$descripcionField) ? $this->competency->$descripcionField : ''
            ];
        }
        
        $data = [
            'id' => $this->competency->id,
            'codigo' => $this->competency->codigo,
            'nombre' => $this->competency->nombre,
            'descripcion' => $this->competency->descripcion,
            'sector' => $this->competency->sector,
            'sector_texto' => isset($this->competency->sector) ? get_string('sector_' . $this->competency->sector, 'local_conocer_cert') : '',
            'activo' => $this->competency->activo,
            'niveles' => $nivelesData,
            'has_niveles' => !empty($nivelesData),
            'tipo_evaluacion' => isset($this->competency->tipo_evaluacion) ? $this->competency->tipo_evaluacion : '',
            'tipo_evaluacion_texto' => isset($this->competency->tipo_evaluacion) ? get_string('evaltype_' . $this->competency->tipo_evaluacion, 'local_conocer_cert') : '',
            'duracion_estimada' => isset($this->competency->duracion_estimada) ? $this->competency->duracion_estimada : '',
            'costo' => isset($this->competency->costo) ? $this->competency->costo : '',
            'requisitos' => isset($this->competency->requisitos) ? $this->competency->requisitos : '',
            'can_edit' => has_capability('local/conocer_cert:managecompetencies', \context_system::instance())
        ];
        
        // Fechas
        if (!empty($this->competency->fecha_inicio)) {
            $data['fecha_inicio'] = userdate($this->competency->fecha_inicio);
            $data['has_fecha_inicio'] = true;
        } else {
            $data['has_fecha_inicio'] = false;
        }
        
        if (!empty($this->competency->fecha_fin)) {
            $data['fecha_fin'] = userdate($this->competency->fecha_fin);
            $data['has_fecha_fin'] = true;
        } else {
            $data['has_fecha_fin'] = false;
        }
        
        // Documentos requeridos
        if (!empty($this->competency->documentos_requeridos)) {
            $docreqs = explode(',', $this->competency->documentos_requeridos);
            $docreqsData = [];
            foreach ($docreqs as $doc) {
                $docreqsData[] = [
                    'id' => $doc,
                    'nombre' => get_string('doc_' . $doc, 'local_conocer_cert')
                ];
            }
            $data['documentos_requeridos'] = $docreqsData;
            $data['has_documentos_requeridos'] = true;
        } else {
            $data['has_documentos_requeridos'] = false;
        }
        
        // Estadísticas
        $stats = [];
        $stats['total_candidatos'] = $DB->count_records('local_conocer_candidatos', ['competencia_id' => $this->competency->id]);
        $stats['aprobados'] = $DB->count_records_sql(
            "SELECT COUNT(*) FROM {local_conocer_candidatos} c
             JOIN {local_conocer_procesos} p ON c.id = p.candidato_id
             WHERE c.competencia_id = :competenciaid AND p.resultado = 'aprobado'",
            ['competenciaid' => $this->competency->id]
        );
        $stats['en_proceso'] = $DB->count_records_sql(
            "SELECT COUNT(*) FROM {local_conocer_candidatos} c
             LEFT JOIN {local_conocer_procesos} p ON c.id = p.candidato_id
             WHERE c.competencia_id = :competenciaid 
             AND (p.etapa IN ('solicitud', 'evaluacion', 'pendiente_revision') OR p.id IS NULL)",
            ['competenciaid' => $this->competency->id]
        );
        
        $data['stats'] = $stats;
        
        // Añadir datos adicionales
        foreach ($this->data as $key => $value) {
            $data[$key] = $value;
        }
        
        return $data;
    }
}

/**
 * Clase para la página de evaluación de candidato
 */
class candidate_evaluation_page implements renderable, templatable {
    /** @var \stdClass Datos del candidato */
    protected $candidate;
    
    /** @var \stdClass Proceso de certificación */
    protected $process;
    
    /** @var array Documentos del candidato */
    protected $documents;
    
    /** @var array Datos adicionales */
    protected $data;
    
    /** @var \moodleform Formulario de evaluación */
    protected $form;
    
    /** @var array Criterios de evaluación */
    protected $criteria;
    
    /**
     * Constructor
     *
     * @param \stdClass $candidate Datos del candidato
     * @param \stdClass $process Proceso de certificación
     * @param array $documents Documentos del candidato
     * @param \moodleform $form Formulario de evaluación (opcional)
     * @param array $criteria Criterios de evaluación (opcional)
     * @param array $data Datos adicionales
     */
    public function __construct($candidate, $process, $documents = [], $form = null, $criteria = [], $data = []) {
        $this->candidate = $candidate;
        $this->process = $process;
        $this->documents = $documents;
        $this->form = $form;
        $this->criteria = $criteria;
        $this->data = $data;
    }
    
    /**
     * Exporta los datos para la plantilla
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output) {
        global $DB, $USER;
        
        // Obtener datos del usuario asociado al candidato
        $user = $DB->get_record('user', ['id' => $this->candidate->userid]);
        
        // Obtener competencia
        $competencia = $DB->get_record('local_conocer_competencias', ['id' => $this->candidate->competencia_id]);
        
        $data = [
            'id' => $this->candidate->id,
            'process_id' => $this->process->id,
            'fullname' => fullname($user),
            'email' => $user->email,
            'curp' => $this->candidate->curp,
            'telefono' => $this->candidate->telefono,
            'direccion' => $this->candidate->direccion,
            'competencia' => $competencia ? $competencia->nombre : '',
            'codigo_competencia' => $competencia ? $competencia->codigo : '',
            'nivel' => $this->candidate->nivel,
            'nivel_texto' => get_string('level' . $this->candidate->nivel, 'local_conocer_cert'),
            'experiencia' => $this->candidate->experiencia,
            'etapa' => $this->process->etapa,
            'etapa_texto' => get_string('etapa_' . $this->process->etapa, 'local_conocer_cert'),
            'fecha_inicio' => userdate($this->process->fecha_inicio),
            'documents' => [],
            'has_documents' => !empty($this->documents),
            'puede_evaluar' => has_capability('local/conocer_cert:evaluatecandidates', \context_system::instance()) && 
                              ($USER->id == $this->process->evaluador_id || has_capability('local/conocer_cert:managecandidates', \context_system::instance())),
            'is_evaluator' => ($USER->id == $this->process->evaluador_id),
            'title' => get_string('evaluate_candidate', 'local_conocer_cert')
        ];
        
        // Si tiene formulario, añadirlo
        if ($this->form) {
            $data['has_form'] = true;
            $data['form_html'] = $this->form->render();
        } else {
            $data['has_form'] = false;
        }
        
        // Procesar documentos
        foreach ($this->documents as $doc) {
            $data['documents'][] = [
                'id' => $doc->id,
                'nombre' => $doc->nombre_archivo,
                'tipo' => get_string('doc_' . $doc->tipo, 'local_conocer_cert'),
                'fecha' => userdate($doc->fecha_subida),
                'estado' => $doc->estado,
                'estado_texto' => get_string('doc_status_' . $doc->estado, 'local_conocer_cert'),
                'view_url' => new \moodle_url('/local/conocer_cert/document.php', ['id' => $doc->id, 'action' => 'view'])
            ];
        }
        
        // Procesar criterios de evaluación
        if (!empty($this->criteria)) {
            $data['has_criteria'] = true;
            $data['criteria'] = [];
            
            foreach ($this->criteria as $criterio) {
                $data['criteria'][] = [
                    'id' => $criterio->id,
                    'nombre' => $criterio->nombre,
                    'descripcion' => $criterio->descripcion,
                    'ponderacion' => $criterio->ponderacion
                ];
            }
        } else {
            $data['has_criteria'] = false;
        }
        
        // Verificar si ya tiene una evaluación previa
        if (!empty($this->process->resultado)) {
            $data['has_evaluation'] = true;
            $data['resultado'] = $this->process->resultado;
            $data['resultado_texto'] = get_string('resultado_' . $this->process->resultado, 'local_conocer_cert');
            
            // Determinar clase CSS según resultado
            switch ($this->process->resultado) {
                case 'aprobado':
                    $data['resultado_class'] = 'success';
                    break;
                case 'rechazado':
                    $data['resultado_class'] = 'danger';
                    break;
                default:
                    $data['resultado_class'] = 'info';
            }
            
            // Si tiene calificación
            if (isset($this->process->calificacion)) {
                $data['has_calificacion'] = true;
                $data['calificacion'] = $this->process->calificacion;
            } else {
                $data['has_calificacion'] = false;
            }
            
            // Si tiene fecha de evaluación
            if (!empty($this->process->fecha_evaluacion)) {
                $data['fecha_evaluacion'] = userdate($this->process->fecha_evaluacion);
            }
            
            // Si tiene notas
            if (!empty($this->process->notas)) {
                $data['notas'] = $this->process->notas;
            }
            
            // Verificar si puede modificar
            $data['puede_modificar'] = has_capability('local/conocer_cert:managecandidates', \context_system::instance());
        } else {
            $data['has_evaluation'] = false;
        }
        
        // Añadir datos del evaluador
        if (!empty($this->process->evaluador_id)) {
            $evaluador = $DB->get_record('user', ['id' => $this->process->evaluador_id]);
            if ($evaluador) {
                $data['has_evaluador'] = true;
                $data['evaluador_nombre'] = fullname($evaluador);
                $data['evaluador_email'] = $evaluador->email;
            } else {
                $data['has_evaluador'] = false;
            }
        } else {
            $data['has_evaluador'] = false;
        }
        
        // Añadir barra de progreso
        $progress = new certification_progress($this->process->etapa);
        $data['progress'] = $progress->export_for_template($output);
        
        // Añadir datos adicionales
        foreach ($this->data as $key => $value) {
            $data[$key] = $value;
        }
        
        return $data;
    }
}
/*******************************************************************************/
// Complemento de clases renderizables para el plugin
// Estas clases deben añadirse al final del archivo renderable.php

/**
 * Clase para la tabla de empresas
 */
class companies_table implements renderable, templatable {
    /** @var array Lista de empresas */
    protected $companies;
    
    /** @var array Opciones de configuración */
    protected $options;
    
    /**
     * Constructor
     *
     * @param array $companies Lista de empresas
     * @param array $options Opciones de configuración
     */
    public function __construct($companies, $options = []) {
        $this->companies = $companies;
        $this->options = $options;
    }
    
    /**
     * Exporta los datos para la plantilla
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output) {
        $data = [
            'has_companies' => !empty($this->companies),
            'companies' => [],
            'show_actions' => isset($this->options['show_actions']) ? $this->options['show_actions'] : true,
            'show_status' => isset($this->options['show_status']) ? $this->options['show_status'] : true
        ];
        
        foreach ($this->companies as $company) {
            $companyData = [
                'id' => $company->id,
                'nombre' => $company->nombre,
                'rfc' => $company->rfc,
                'contacto_nombre' => $company->contacto_nombre,
                'contacto_email' => $company->contacto_email,
                'estado' => $company->estado,
                'fecha_solicitud' => userdate($company->fecha_solicitud),
                'view_url' => new \moodle_url('/local/conocer_cert/admin/view_company.php', ['id' => $company->id])
            ];
            
            // Crear indicador de estado si es necesario
            if ($data['show_status']) {
                $indicator = new status_indicator($company->estado, 'company');
                $companyData['status_indicator'] = $indicator->export_for_template($output);
            }
            
            $data['companies'][] = $companyData;
        }
        
        return $data;
    }
}

/**
 * Clase para la tabla de competencias
 */
class competencies_table implements renderable, templatable {
    /** @var array Lista de competencias */
    protected $competencies;
    
    /** @var bool Mostrar botones de acción */
    protected $showActions;
    
    /**
     * Constructor
     *
     * @param array $competencies Lista de competencias
     * @param bool $showActions Mostrar botones de acción
     */
    public function __construct($competencies, $showActions = true) {
        $this->competencies = $competencies;
        $this->showActions = $showActions;
    }
    
    /**
     * Exporta los datos para la plantilla
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output) {
        $data = [
            'has_competencies' => !empty($this->competencies),
            'competencies' => [],
            'show_actions' => $this->showActions
        ];
        
        foreach ($this->competencies as $competency) {
            // Procesar niveles disponibles
            $niveles = explode(',', $competency->niveles_disponibles);
            $nivelesTxt = [];
            foreach ($niveles as $nivel) {
                $nivelesTxt[] = get_string('level' . $nivel, 'local_conocer_cert');
            }
            
            $competencyData = [
                'id' => $competency->id,
                'codigo' => $competency->codigo,
                'nombre' => $competency->nombre,
                'niveles' => implode(', ', $nivelesTxt),
                'activo' => $competency->activo,
                'view_url' => new \moodle_url('/local/conocer_cert/admin/view_competency.php', ['id' => $competency->id]),
                'edit_url' => new \moodle_url('/local/conocer_cert/admin/edit_competency.php', ['id' => $competency->id])
            ];
            
            // Si está inactiva, añadir clase CSS
            if (!$competency->activo) {
                $competencyData['inactive_class'] = 'text-muted';
            }
            
            $data['competencies'][] = $competencyData;
        }
        
        return $data;
    }
}

/**
 * Clase para la tabla de evaluadores
 */
class evaluators_table implements renderable, templatable {
    /** @var array Lista de evaluadores */
    protected $evaluators;
    
    /** @var bool Mostrar botones de acción */
    protected $showActions;
    
    /**
     * Constructor
     *
     * @param array $evaluators Lista de evaluadores
     * @param bool $showActions Mostrar botones de acción
     */
    public function __construct($evaluators, $showActions = true) {
        $this->evaluators = $evaluators;
        $this->showActions = $showActions;
    }
    
    /**
     * Exporta los datos para la plantilla
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output) {
        global $DB;
        
        $data = [
            'has_evaluators' => !empty($this->evaluators),
            'evaluators' => [],
            'show_actions' => $this->showActions
        ];
        
        foreach ($this->evaluators as $evaluator) {
            // Obtener usuario
            $user = $DB->get_record('user', ['id' => $evaluator->userid]);
            
            // Obtener competencias asignadas
            $competencias = [];
            if (!empty($evaluator->competencias)) {
                $compIds = json_decode($evaluator->competencias);
                if (!empty($compIds)) {
                    list($sql, $params) = $DB->get_in_or_equal($compIds);
                    $competencias = $DB->get_records_select('local_conocer_competencias', "id $sql", $params);
                }
            }
            
            // Contar candidatos asignados
            $asignados = $DB->count_records('local_conocer_procesos', ['evaluador_id' => $evaluator->userid]);
            
            $evaluatorData = [
                'id' => $evaluator->id,
                'userid' => $evaluator->userid,
                'fullname' => $user ? fullname($user) : '',
                'email' => $user ? $user->email : '',
                'estatus' => $evaluator->estatus,
                'experiencia_anios' => $evaluator->experiencia_anios,
                'competencias_count' => count($competencias),
                'asignados_count' => $asignados,
                'view_url' => new \moodle_url('/local/conocer_cert/admin/view_evaluator.php', ['id' => $evaluator->id]),
                'edit_url' => new \moodle_url('/local/conocer_cert/admin/edit_evaluator.php', ['id' => $evaluator->id])
            ];
            
            // Crear indicador de estado
            $indicator = new status_indicator($evaluator->estatus, 'evaluator');
            $evaluatorData['status_indicator'] = $indicator->export_for_template($output);
            
            $data['evaluators'][] = $evaluatorData;
        }
        
        return $data;
    }
}

/**
 * Clase para la página de solicitud de certificación
 */
class certification_request_page implements renderable, templatable {
    /** @var \moodleform Formulario de solicitud */
    protected $form;
    
    /** @var array Competencias disponibles */
    protected $competencies;
    
    /** @var array Datos adicionales */
    protected $data;
    
    /**
     * Constructor
     *
     * @param \moodleform $form Formulario de solicitud
     * @param array $competencies Competencias disponibles
     * @param array $data Datos adicionales
     */
    public function __construct($form, $competencies = [], $data = []) {
        $this->form = $form;
        $this->competencies = $competencies;
        $this->data = $data;
    }
    
    /**
     * Exporta los datos para la plantilla
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output) {
        $data = [
            'form_html' => $this->form->render(),
            'has_competencies' => !empty($this->competencies),
            'competencies' => [],
            'title' => get_string('request_certification', 'local_conocer_cert')
        ];
        
        // Procesar competencias disponibles
        foreach ($this->competencies as $competency) {
            $data['competencies'][] = [
                'id' => $competency->id,
                'codigo' => $competency->codigo,
                'nombre' => $competency->nombre
            ];
        }
        
        // Añadir datos adicionales
        foreach ($this->data as $key => $value) {
            $data[$key] = $value;
        }
        
        return $data;
    }
}

/**
 * Clase para la página de reportes
 */
class reports_page implements renderable, templatable {
    /** @var array Datos de reportes */
    protected $reports;
    
    /** @var string Tipo de reporte */
    protected $type;
    
    /** @var array Filtros aplicados */
    protected $filters;
    
    /**
     * Constructor
     *
     * @param array $reports Datos de reportes
     * @param string $type Tipo de reporte
     * @param array $filters Filtros aplicados
     */
    public function __construct($reports, $type = 'general', $filters = []) {
        $this->reports = $reports;
        $this->type = $type;
        $this->filters = $filters;
    }
    
    /**
     * Exporta los datos para la plantilla
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output) {
        $data = [
            'has_reports' => !empty($this->reports),
            'type' => $this->type,
            'title' => get_string('report_' . $this->type, 'local_conocer_cert'),
            'filters' => $this->filters
        ];
        
        // Dependiendo del tipo de reporte, formatear los datos
        switch ($this->type) {
            case 'certifications':
                $data['chart_data'] = json_encode($this->reports['chart_data']);
                $data['totals'] = $this->reports['totals'];
                $data['table_data'] = $this->reports['table_data'];
                break;
                
            case 'companies':
                $data['chart_data'] = json_encode($this->reports['chart_data']);
                $data['companies_count'] = $this->reports['companies_count'];
                $data['by_sector'] = $this->reports['by_sector'];
                break;
                
            case 'evaluators':
                $data['chart_data'] = json_encode($this->reports['chart_data']);
                $data['evaluators_count'] = $this->reports['evaluators_count'];
                $data['performance'] = $this->reports['performance'];
                break;
                
            case 'competencies':
                $data['chart_data'] = json_encode($this->reports['chart_data']);
                $data['most_requested'] = $this->reports['most_requested'];
                $data['by_level'] = $this->reports['by_level'];
                break;
                
            default:
                $data['general_stats'] = $this->reports['general_stats'];
                $data['monthly_data'] = json_encode($this->reports['monthly_data']);
                $data['recent_activity'] = $this->reports['recent_activity'];
        }
        
        return $data;
    }
}

/**
 * Clase para páginas de gestión de documentos
 */
class document_management_page implements renderable, templatable {
    /** @var array Lista de documentos */
    protected $documents;
    
    /** @var string Tipo de página (candidate, company) */
    protected $type;
    
    /** @var int ID del objeto relacionado (candidato o empresa) */
    protected $relatedId;
    
    /** @var array Datos adicionales */
    protected $data;
    
    /**
     * Constructor
     *
     * @param array $documents Lista de documentos
     * @param string $type Tipo de página
     * @param int $relatedId ID del objeto relacionado
     * @param array $data Datos adicionales
     */
    public function __construct($documents, $type, $relatedId, $data = []) {
        $this->documents = $documents;
        $this->type = $type;
        $this->relatedId = $relatedId;
        $this->data = $data;
    }
    
    /**
     * Exporta los datos para la plantilla
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output) {
        $data = [
            'has_documents' => !empty($this->documents),
            'type' => $this->type,
            'related_id' => $this->relatedId,
            'documents' => [],
            'upload_url' => new \moodle_url('/local/conocer_cert/' . $this->type . '/upload_document.php', ['id' => $this->relatedId])
        ];
        
        // Procesar documentos
        foreach ($this->documents as $doc) {
            $docData = [
                'id' => $doc->id,
                'nombre' => $doc->nombre_archivo,
                'tipo' => get_string('doc_' . ($this->type == 'candidate' ? '' : $this->type . '_') . $doc->tipo, 'local_conocer_cert'),
                'fecha' => userdate($doc->fecha_subida),
                'estado' => $doc->estado,
                'view_url' => new \moodle_url('/local/conocer_cert/' . $this->type . '/document.php', 
                    ['id' => $doc->id, 'action' => 'view'])
            ];
            
            // Crear indicador de estado
            $indicator = new status_indicator($doc->estado, 'document');
            $docData['status_indicator'] = $indicator->export_for_template($output);
            
            $data['documents'][] = $docData;
        }
        
        // Añadir datos adicionales
        foreach ($this->data as $key => $value) {
            $data[$key] = $value;
        }
        
        return $data;
    }
}