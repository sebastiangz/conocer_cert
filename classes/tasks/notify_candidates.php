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
 * Task for sending notifications to candidates.
 *
 * @package    local_conocer_cert
 * @copyright  2025 Tu Institución
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_conocer_cert\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Task to send scheduled notifications to candidates.
 */
class notify_candidates extends \core\task\scheduled_task {

    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name() {
        return get_string('task_notify_candidates', 'local_conocer_cert');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        global $DB, $CFG;
        
        require_once($CFG->dirroot . '/local/conocer_cert/locallib.php');
        
        mtrace('Iniciando envío de notificaciones a candidatos...');
        
        // Recordatorios para documentos pendientes
        $this->send_document_reminders();
        
        // Notificaciones de cambios de estado
        $this->send_status_change_notifications();
        
        // Recordatorios de certificados próximos a vencer
        $this->send_certificate_expiry_reminders();
        
        // Notificaciones de evaluador asignado
        $this->send_evaluator_assigned_notifications();
        
        mtrace('Envío de notificaciones a candidatos completado.');
    }

    /**
     * Send reminders to candidates with pending documents.
     */
    protected function send_document_reminders() {
        global $DB;
        
        mtrace('Enviando recordatorios de documentos pendientes...');
        
        // Definir tiempo límite (7 días desde la solicitud)
        $timelimit = time() - (7 * DAYSECS);
        
        // Encontrar candidatos con solicitudes antiguas y documentos pendientes
        $sql = "SELECT c.id, c.userid, c.competencia_id, c.nivel, c.fecha_solicitud,
                       u.firstname, u.lastname, comp.nombre as competencia_nombre
                FROM {local_conocer_candidatos} c
                JOIN {user} u ON c.userid = u.id
                JOIN {local_conocer_competencias} comp ON c.competencia_id = comp.id
                LEFT JOIN (
                    SELECT candidato_id, COUNT(id) as doc_count
                    FROM {local_conocer_documentos}
                    GROUP BY candidato_id
                ) d ON c.id = d.candidato_id
                WHERE c.estado = 'pendiente' 
                AND c.fecha_solicitud < :timelimit
                AND (d.doc_count IS NULL OR d.doc_count < 5)";
        
        $candidates = $DB->get_records_sql($sql, ['timelimit' => $timelimit]);
        
        $count = 0;
        foreach ($candidates as $candidate) {
            // Verificar si ya se ha enviado un recordatorio en las últimas 48 horas
            $recentNotification = $DB->record_exists_select(
                'local_conocer_notificaciones',
                "userid = :userid AND tipo = 'recordatorio_documentos' AND timecreated > :timelimit",
                ['userid' => $candidate->userid, 'timelimit' => time() - (2 * DAYSECS)]
            );
            
            if (!$recentNotification) {
                $success = \local_conocer_cert\util\notification::send($candidate->userid, 'recordatorio_documentos', [
                    'firstname' => $candidate->firstname,
                    'lastname' => $candidate->lastname,
                    'competencia' => $candidate->competencia_nombre,
                    'nivel' => $candidate->nivel,
                    'dias_transcurridos' => floor((time() - $candidate->fecha_solicitud) / DAYSECS),
                    'contexturl' => new \moodle_url('/local/conocer_cert/candidate/upload_documents.php', ['id' => $candidate->id]),
                    'contexturlname' => get_string('upload_documents', 'local_conocer_cert')
                ]);
                
                if ($success) {
                    $count++;
                    mtrace("  Recordatorio enviado a: {$candidate->firstname} {$candidate->lastname} (ID: {$candidate->userid})");
                }
            }
        }
        
        mtrace("Total de recordatorios de documentos enviados: $count");
    }

    /**
     * Send notifications about status changes.
     */
    protected function send_status_change_notifications() {
        global $DB;
        
        mtrace('Enviando notificaciones de cambios de estado...');
        
        // Buscar procesos recién aprobados o rechazados (en las últimas 24 horas)
        $timelimit = time() - DAYSECS;
        
        $sql = "SELECT p.id, p.candidato_id, p.resultado, p.fecha_fin, p.notas,
                       c.userid, c.competencia_id, c.nivel,
                       u.firstname, u.lastname, comp.nombre as competencia_nombre
                FROM {local_conocer_procesos} p
                JOIN {local_conocer_candidatos} c ON p.candidato_id = c.id
                JOIN {user} u ON c.userid = u.id
                JOIN {local_conocer_competencias} comp ON c.competencia_id = comp.id
                LEFT JOIN {local_conocer_notificaciones} n ON n.userid = c.userid AND n.tipo = 'proceso_completado' AND n.details LIKE CONCAT('%\"proceso_id\":', p.id, '%')
                WHERE p.fecha_fin > :timelimit
                AND p.etapa IN ('aprobado', 'rechazado')
                AND p.resultado IS NOT NULL
                AND n.id IS NULL";
        
        $processes = $DB->get_records_sql($sql, ['timelimit' => $timelimit]);
        
        $count = 0;
        foreach ($processes as $process) {
            $success = \local_conocer_cert\util\notification::notify_certification_completed($process->id);
            
            if ($success) {
                $count++;
                mtrace("  Notificación de resultado enviada a: {$process->firstname} {$process->lastname} (ID: {$process->userid}) - Resultado: {$process->resultado}");
            }
        }
        
        mtrace("Total de notificaciones de cambio de estado enviadas: $count");
    }

    /**
     * Send reminders for certificates about to expire.
     */
    protected function send_certificate_expiry_reminders() {
        global $DB;
        
        mtrace('Enviando recordatorios de certificados próximos a vencer...');
        
        // Buscar certificados que vencen en 30 días
        $expiry_limit = time() + (30 * DAYSECS);
        $lower_limit = time() + (29 * DAYSECS);
        
        $sql = "SELECT cert.id, cert.proceso_id, cert.numero_folio, cert.fecha_vencimiento, 
                       p.candidato_id, c.userid, c.competencia_id, c.nivel,
                       u.firstname, u.lastname, comp.nombre as competencia_nombre
                FROM {local_conocer_certificados} cert
                JOIN {local_conocer_procesos} p ON cert.proceso_id = p.id
                JOIN {local_conocer_candidatos} c ON p.candidato_id = c.id
                JOIN {user} u ON c.userid = u.id
                JOIN {local_conocer_competencias} comp ON c.competencia_id = comp.id
                LEFT JOIN {local_conocer_notificaciones} n ON 
                    n.userid = c.userid AND 
                    n.tipo = 'certificado_por_vencer' AND 
                    n.details LIKE CONCAT('%\"certificado_id\":', cert.id, '%') AND
                    n.timecreated > :recentlimit
                WHERE cert.fecha_vencimiento BETWEEN :lowerlimit AND :upperlimit
                AND cert.estatus = 'activo'
                AND n.id IS NULL";
        
        $certificates = $DB->get_records_sql($sql, [
            'lowerlimit' => $lower_limit,
            'upperlimit' => $expiry_limit,
            'recentlimit' => time() - (15 * DAYSECS) // No enviar si ya se envió en los últimos 15 días
        ]);
        
        $count = 0;
        foreach ($certificates as $cert) {
            $dias_restantes = floor(($cert->fecha_vencimiento - time()) / DAYSECS);
            
            $success = \local_conocer_cert\util\notification::send($cert->userid, 'certificado_por_vencer', [
                'firstname' => $cert->firstname,
                'lastname' => $cert->lastname,
                'competencia' => $cert->competencia_nombre,
                'nivel' => $cert->nivel,
                'folio' => $cert->numero_folio,
                'fecha_vencimiento' => userdate($cert->fecha_vencimiento),
                'dias_restantes' => $dias_restantes,
                'certificado_id' => $cert->id,
                'contexturl' => new \moodle_url('/local/conocer_cert/candidate/view_certification.php', ['id' => $cert->proceso_id]),
                'contexturlname' => get_string('view_certification', 'local_conocer_cert')
            ]);
            
            if ($success) {
                $count++;
                mtrace("  Recordatorio de vencimiento enviado a: {$cert->firstname} {$cert->lastname} (ID: {$cert->userid}) - Días restantes: {$dias_restantes}");
            }
        }
        
        mtrace("Total de recordatorios de vencimiento enviados: $count");
    }

    /**
     * Send notifications when an evaluator is assigned.
     */
    protected function send_evaluator_assigned_notifications() {
        global $DB;
        
        mtrace('Enviando notificaciones de evaluador asignado...');
        
        // Buscar asignaciones recientes (últimas 24 horas)
        $timelimit = time() - DAYSECS;
        
        $sql = "SELECT p.id, p.candidato_id, p.evaluador_id, p.fecha_inicio,
                       c.userid, c.competencia_id, c.nivel,
                       u.firstname, u.lastname, comp.nombre as competencia_nombre,
                       ev.firstname as evaluador_firstname, ev.lastname as evaluador_lastname
                FROM {local_conocer_procesos} p
                JOIN {local_conocer_candidatos} c ON p.candidato_id = c.id
                JOIN {user} u ON c.userid = u.id
                JOIN {local_conocer_competencias} comp ON c.competencia_id = comp.id
                JOIN {user} ev ON p.evaluador_id = ev.id
                LEFT JOIN {local_conocer_notificaciones} n ON 
                    n.userid = c.userid AND 
                    n.tipo = 'evaluador_asignado' AND 
                    n.details LIKE CONCAT('%\"proceso_id\":', p.id, '%')
                WHERE p.timemodified > :timelimit
                AND p.etapa = 'evaluacion' 
                AND p.evaluador_id IS NOT NULL
                AND n.id IS NULL";
        
        $assignments = $DB->get_records_sql($sql, ['timelimit' => $timelimit]);
        
        $count = 0;
        foreach ($assignments as $assignment) {
            $success = \local_conocer_cert\util\notification::send($assignment->userid, 'evaluador_asignado', [
                'firstname' => $assignment->firstname,
                'lastname' => $assignment->lastname,
                'competencia' => $assignment->competencia_nombre,
                'nivel' => $assignment->nivel,
                'evaluador_nombre' => "{$assignment->evaluador_firstname} {$assignment->evaluador_lastname}",
                'proceso_id' => $assignment->id,
                'contexturl' => new \moodle_url('/local/conocer_cert/candidate/view_process.php', ['id' => $assignment->id]),
                'contexturlname' => get_string('view_process', 'local_conocer_cert')
            ]);
            
            if ($success) {
                $count++;
                mtrace("  Notificación de evaluador asignado enviada a: {$assignment->firstname} {$assignment->lastname} (ID: {$assignment->userid})");
            }
        }
        
        mtrace("Total de notificaciones de evaluador asignado enviadas: $count");
    }
}
