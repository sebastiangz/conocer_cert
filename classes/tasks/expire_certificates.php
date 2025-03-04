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
 * Task for expiring certificates.
 *
 * @package    local_conocer_cert
 * @copyright  2025 Tu Institución
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_conocer_cert\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Task to expire certificates that have reached their expiration date.
 */
class expire_certificates extends \core\task\scheduled_task {

    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name() {
        return get_string('task_expire_certificates', 'local_conocer_cert');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        global $DB, $CFG;
        
        require_once($CFG->dirroot . '/local/conocer_cert/locallib.php');
        
        mtrace('Iniciando proceso de expiración de certificados...');
        
        // Buscar certificados que ya han vencido pero siguen activos
        $now = time();
        
        $sql = "SELECT cert.id, cert.proceso_id, cert.numero_folio, cert.fecha_vencimiento, 
                       p.candidato_id, c.userid, c.competencia_id, c.nivel,
                       u.firstname, u.lastname, comp.nombre as competencia_nombre
                FROM {local_conocer_certificados} cert
                JOIN {local_conocer_procesos} p ON cert.proceso_id = p.id
                JOIN {local_conocer_candidatos} c ON p.candidato_id = c.id
                JOIN {user} u ON c.userid = u.id
                JOIN {local_conocer_competencias} comp ON c.competencia_id = comp.id
                WHERE cert.fecha_vencimiento < :now
                AND cert.estatus = 'activo'";
        
        $expiredCertificates = $DB->get_records_sql($sql, ['now' => $now]);
        
        if (empty($expiredCertificates)) {
            mtrace('No se encontraron certificados vencidos.');
            return;
        }
        
        mtrace('Encontrados ' . count($expiredCertificates) . ' certificados vencidos.');
        
        $count = 0;
        foreach ($expiredCertificates as $cert) {
            // Actualizar estado del certificado a vencido
            $DB->set_field('local_conocer_certificados', 'estatus', 'vencido', ['id' => $cert->id]);
            
            // Enviar notificación al propietario
            $success = \local_conocer_cert\util\notification::send($cert->userid, 'certificado_vencido', [
                'firstname' => $cert->firstname,
                'lastname' => $cert->lastname,
                'competencia' => $cert->competencia_nombre,
                'nivel' => $cert->nivel,
                'folio' => $cert->numero_folio,
                'fecha_vencimiento' => userdate($cert->fecha_vencimiento),
                'dias_vencido' => floor(($now - $cert->fecha_vencimiento) / DAYSECS),
                'certificado_id' => $cert->id,
                'contexturl' => new \moodle_url('/local/conocer_cert/candidate/renew_certificate.php', ['id' => $cert->proceso_id]),
                'contexturlname' => get_string('renew_certificate', 'local_conocer_cert')
            ]);
            
            if ($success) {
                mtrace("  Certificado marcado como vencido y notificación enviada: Folio {$cert->numero_folio} - Usuario: {$cert->firstname} {$cert->lastname}");
                $count++;
            } else {
                mtrace("  Certificado marcado como vencido pero falló el envío de la notificación: Folio {$cert->numero_folio}");
                $count++;
            }
            
            // Registrar el evento
            $eventdata = [
                'objectid' => $cert->id,
                'context' => \context_system::instance(),
                'relateduserid' => $cert->userid,
                'other' => [
                    'proceso_id' => $cert->proceso_id,
                    'competencia_id' => $cert->competencia_id,
                    'nivel' => $cert->nivel
                ]
            ];
            
            $event = \local_conocer_cert\event\certificate_expired::create($eventdata);
            $event->trigger();
        }
        
        mtrace("Total de certificados procesados como vencidos: $count");
        
        // Generar informe para administradores si hay certificados vencidos
        if ($count > 0) {
            $this->send_admin_report($expiredCertificates);
        }
    }
    
    /**
     * Send a report to administrators about expired certificates.
     *
     * @param array $certificates List of expired certificates
     */
    protected function send_admin_report($certificates) {
        global $DB;
        
        mtrace('Enviando informe de certificados vencidos a administradores...');
        
        // Obtener todos los usuarios con capacidad para gestionar certificaciones
        $context = \context_system::instance();
        $admins = get_users_by_capability($context, 'local/conocer_cert:managecandidates');
        
        if (empty($admins)) {
            mtrace('No se encontraron administradores para enviar el informe.');
            return;
        }
        
        // Preparar datos para el informe
        $reportData = [
            'total_vencidos' => count($certificates),
            'certificados' => []
        ];
        
        foreach ($certificates as $cert) {
            $reportData['certificados'][] = [
                'folio' => $cert->numero_folio,
                'usuario' => $cert->firstname . ' ' . $cert->lastname,
                'competencia' => $cert->competencia_nombre,
                'nivel' => $cert->nivel,
                'fecha_vencimiento' => userdate($cert->fecha_vencimiento)
            ];
        }
        
        // Generar resumen por competencia
        $compSummary = [];
        foreach ($certificates as $cert) {
            if (!isset($compSummary[$cert->competencia_id])) {
                $compSummary[$cert->competencia_id] = [
                    'nombre' => $cert->competencia_nombre,
                    'count' => 0
                ];
            }
            $compSummary[$cert->competencia_id]['count']++;
        }
        $reportData['resumen_competencias'] = array_values($compSummary);
        
        // Enviar notificación a cada administrador
        $count = 0;
        foreach ($admins as $admin) {
            $success = \local_conocer_cert\util\notification::send($admin->id, 'informe_certificados_vencidos', [
                'firstname' => $admin->firstname,
                'lastname' => $admin->lastname,
                'fecha_informe' => userdate(time()),
                'total_vencidos' => $reportData['total_vencidos'],
                'detalles' => json_encode($reportData),
                'contexturl' => new \moodle_url('/local/conocer_cert/admin/expired_certificates.php'),
                'contexturlname' => get_string('view_expired_certificates', 'local_conocer_cert')
            ]);
            
            if ($success) {
                $count++;
                mtrace("  Informe enviado al administrador: {$admin->firstname} {$admin->lastname}");
            }
        }
        
        mtrace("Total de informes enviados a administradores: $count");
    }
}
