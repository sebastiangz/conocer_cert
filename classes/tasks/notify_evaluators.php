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
 * Task for sending notifications to evaluators.
 *
 * @package    local_conocer_cert
 * @copyright  2025 Sebastian Gonzalez Zepeda sgonzalez@infraestructuragis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_conocer_cert\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Task to send scheduled notifications to evaluators.
 */
class notify_evaluators extends \core\task\scheduled_task {

    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name() {
        return get_string('task_notify_evaluators', 'local_conocer_cert');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        global $DB, $CFG;
        
        require_once($CFG->dirroot . '/local/conocer_cert/locallib.php');
        
        mtrace('Iniciando envío de notificaciones a evaluadores...');
        
        // Recordatorios para evaluaciones pendientes
        $this->send_pending_evaluation_reminders();
        
        // Notificaciones de nuevas asignaciones
        $this->send_new_assignment_notifications();
        
        // Recordatorios para evaluaciones por vencer
        $this->send_deadline_approaching_reminders();
        
        mtrace('Envío de notificaciones a evaluadores completado.');
    }

    /**
     * Send reminders for pending evaluations.
     */
    protected function send_pending_evaluation_reminders() {
        global $DB;
        
        mtrace('Enviando recordatorios de evaluaciones pendientes...');
        
        // Definir tiempo límite (3 días desde asignación)
        $timelimit = time() - (3 * DAYSECS);
        
        // Encontrar procesos con evaluador asignado que no han sido evaluados
        $sql = "SELECT p.id, p.candidato_id, p.evaluador_id, p.fecha_inicio,
                       c.competencia_id, c.nivel, c.userid as candidate_userid,
                       u.firstname, u.lastname, u.email,
                       eu.firstname as evaluador_firstname, eu.lastname as evaluador_lastname,
                       comp.nombre as competencia_nombre
                FROM {local_conocer_procesos} p
                JOIN {local_conocer_candidatos} c ON p.candidato_id = c.id
                JOIN {user} u ON c.userid = u.id
                JOIN {user} eu ON p.evaluador_id = eu.id
                JOIN {local_conocer_competencias} comp ON c.competencia_id = comp.id
                WHERE p.etapa = 'evaluacion' 
                AND p.fecha_inicio < :timelimit
                AND (p.fecha_evaluacion IS NULL OR p.fecha_evaluacion = 0)";
        
        $pendingEvaluations = $DB->get_records_sql($sql, ['timelimit' => $timelimit]);
        
        $count = 0;
        foreach ($pendingEvaluations as $evaluation) {
            // Verificar si ya se ha enviado un recordatorio en las últimas 24 horas
            $recentNotification = $DB->record_exists_select(
                'local_conocer_notificaciones',
                "userid = :userid AND tipo = 'recordatorio_evaluador' AND timecreated > :timelimit",
                ['userid' => $evaluation->evaluador_id, 'timelimit' => time() - DAYSECS]
            );
            
            if (!$recentNotification) {
                $success = \local_conocer_cert\util\notification::send($evaluation->evaluador_id, 'recordatorio_evaluador', [
                    'firstname' => $evaluation->evaluador_firstname,
                    'lastname' => $evaluation->evaluador_lastname,
                    'candidate_name' => "{$evaluation->firstname} {$evaluation->lastname}",
                    'competencia' => $evaluation->competencia_nombre,
                    'nivel' => $evaluation->nivel,
                    'dias_pendiente' => floor((time() - $evaluation->fecha_inicio) / DAYSECS),
                    'contexturl' => new \moodle_url('/local/conocer_cert/evaluator/evaluate.php', ['id' => $evaluation->candidato_id]),
                    'contexturlname' => get_string('evaluate_candidate', 'local_conocer_cert')
                ]);
                
                if ($success) {
                    $count++;
                    mtrace("  Recordatorio enviado a: {$evaluation->evaluador_firstname} {$evaluation->evaluador_lastname} (ID: {$evaluation->evaluador_id})");
                }
            }
        }
        
        mtrace("Total de recordatorios de evaluaciones pendientes enviados: $count");
    }

    /**
     * Send notifications for new assignments.
     */
    protected function send_new_assignment_notifications() {
        global $DB;
        
        mtrace('Enviando notificaciones de nuevas asignaciones...');
        
        // Buscar asignaciones recientes (últimas 24 horas)
        $timelimit = time() - DAYSECS;
        
        $sql = "SELECT p.id, p.candidato_id, p.evaluador_id, p.fecha_inicio,
                       c.competencia_id, c.nivel, c.userid as candidate_userid,
                       u.firstname, u.lastname,
                       eu.firstname as evaluador_firstname, eu.lastname as evaluador_lastname,
                       comp.nombre as competencia_nombre
                FROM {local_conocer_procesos} p
                JOIN {local_conocer_candidatos} c ON p.candidato_id = c.id
                JOIN {user} u ON c.userid = u.id
                JOIN {user} eu ON p.evaluador_id = eu.id
                JOIN {local_conocer_competencias} comp ON c.competencia_id = comp.id
                LEFT JOIN {local_conocer_notificaciones} n ON 
                    n.userid = p.evaluador_id AND 
                    n.tipo = 'evaluador_nueva_asignacion' AND 
                    n.details LIKE CONCAT('%\"proceso_id\":', p.id, '%')
                WHERE p.timemodified > :timelimit
                AND p.etapa = 'evaluacion' 
                AND p.evaluador_id IS NOT NULL
                AND n.id IS NULL";
        
        $newAssignments = $DB->get_records_sql($sql, ['timelimit' => $timelimit]);
        
        $count = 0;
        foreach ($newAssignments as $assignment) {
            $success = \local_conocer_cert\util\notification::send($assignment->evaluador_id, 'evaluador_nueva_asignacion', [
                'firstname' => $assignment->evaluador_firstname,
                'lastname' => $assignment->evaluador_lastname,
                'candidate_name' => "{$assignment->firstname} {$assignment->lastname}",
                'competencia' => $assignment->competencia_nombre,
                'nivel' => $assignment->nivel,
                'proceso_id' => $assignment->id,
                'contexturl' => new \moodle_url('/local/conocer_cert/evaluator/evaluate.php', ['id' => $assignment->candidato_id]),
                'contexturlname' => get_string('evaluate_candidate', 'local_conocer_cert')
            ]);
            
            if ($success) {
                $count++;
                mtrace("  Notificación de nueva asignación enviada a: {$assignment->evaluador_firstname} {$assignment->evaluador_lastname} (ID: {$assignment->evaluador_id})");
            }
        }
        
        mtrace("Total de notificaciones de nuevas asignaciones enviadas: $count");
    }

    /**
     * Send reminders for evaluations with approaching deadlines.
     */
    protected function send_deadline_approaching_reminders() {
        global $DB;
        
        mtrace('Enviando recordatorios de plazos próximos a vencer...');
        
        // Obtener tiempo límite para cada competencia (según configuración)
        $competencias = $DB->get_records('local_conocer_competencias', ['activo' => 1]);
        $plazosEvaluacion = [];
        
        foreach ($competencias as $competencia) {
            if (!empty($competencia->duracion_estimada)) {
                $plazosEvaluacion[$competencia->id] = $competencia->duracion_estimada * DAYSECS;
            } else {
                // Por defecto, 30 días
                $plazosEvaluacion[$competencia->id] = 30 * DAYSECS;
            }
        }
        
        // Encontrar procesos que están próximos a vencer (5 días antes del plazo)
        $evaluationsToNotify = [];
        
        $sql = "SELECT p.id, p.candidato_id, p.evaluador_id, p.fecha_inicio,
                       c.competencia_id, c.nivel, c.userid as candidate_userid,
                       u.firstname, u.lastname,
                       eu.firstname as evaluador_firstname, eu.lastname as evaluador_lastname,
                       comp.nombre as competencia_nombre
                FROM {local_conocer_procesos} p
                JOIN {local_conocer_candidatos} c ON p.candidato_id = c.id
                JOIN {user} u ON c.userid = u.id
                JOIN {user} eu ON p.evaluador_id = eu.id
                JOIN {local_conocer_competencias} comp ON c.competencia_id = comp.id
                WHERE p.etapa = 'evaluacion' 
                AND p.fecha_evaluacion IS NULL";
        
        $pendingEvaluations = $DB->get_records_sql($sql);
        
        foreach ($pendingEvaluations as $evaluation) {
            if (isset($plazosEvaluacion[$evaluation->competencia_id])) {
                $plazo = $plazosEvaluacion[$evaluation->competencia_id];
                $fechaLimite = $evaluation->fecha_inicio + $plazo;
                $diasRestantes = floor(($fechaLimite - time()) / DAYSECS);
                
                // Si quedan 5 días o menos, enviar recordatorio
                if ($diasRestantes <= 5 && $diasRestantes > 0) {
                    $evaluation->dias_restantes = $diasRestantes;
                    $evaluation->fecha_limite = $fechaLimite;
                    $evaluationsToNotify[] = $evaluation;
                }
            }
        }
        
        $count = 0;
        foreach ($evaluationsToNotify as $evaluation) {
            // Verificar si ya se ha enviado un recordatorio en las últimas 24 horas
            $recentNotification = $DB->record_exists_select(
                'local_conocer_notificaciones',
                "userid = :userid AND tipo = 'plazo_evaluacion_vencimiento' AND timecreated > :timelimit",
                ['userid' => $evaluation->evaluador_id, 'timelimit' => time() - DAYSECS]
            );
            
            if (!$recentNotification) {
                $success = \local_conocer_cert\util\notification::send($evaluation->evaluador_id, 'plazo_evaluacion_vencimiento', [
                    'firstname' => $evaluation->evaluador_firstname,
                    'lastname' => $evaluation->evaluador_lastname,
                    'candidate_name' => "{$evaluation->firstname} {$evaluation->lastname}",
                    'competencia' => $evaluation->competencia_nombre,
                    'nivel' => $evaluation->nivel,
                    'dias_restantes' => $evaluation->dias_restantes,
                    'fecha_limite' => userdate($evaluation->fecha_limite),
                    'contexturl' => new \moodle_url('/local/conocer_cert/evaluator/evaluate.php', ['id' => $evaluation->candidato_id]),
                    'contexturlname' => get_string('evaluate_candidate', 'local_conocer_cert')
                ]);
                
                if ($success) {
                    $count++;
                    mtrace("  Recordatorio de plazo próximo a vencer enviado a: {$evaluation->evaluador_firstname} {$evaluation->evaluador_lastname} (ID: {$evaluation->evaluador_id}) - Días restantes: {$evaluation->dias_restantes}");
                }
            }
        }
        
        mtrace("Total de recordatorios de plazos próximos a vencer enviados: $count");
    }
}
