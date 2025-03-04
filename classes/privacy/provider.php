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
 * Privacy provider implementation for local_conocer_cert.
 *
 * @package    local_conocer_cert
 * @copyright  2025 Sebastian Gonzalez Zepeda sgonzalez@infraestructuragis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_conocer_cert\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\writer;
use core_privacy\local\request\helper;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider for the CONOCER Certification plugin.
 */
class provider implements
    // This plugin stores personal data.
    \core_privacy\local\metadata\provider,
    
    // This plugin processes data in the system context.
    \core_privacy\local\request\plugin\provider,
    
    // This plugin can provide information about multiple users.
    \core_privacy\local\request\core_userlist_provider {
    
    /**
     * Returns meta data about this system.
     *
     * @param collection $collection The collection to add metadata to.
     * @return collection The collection with added metadata.
     */
    public static function get_metadata(collection $collection): collection {
        // Candidate data.
        $collection->add_database_table(
            'local_conocer_candidatos',
            [
                'userid' => 'privacy:metadata:local_conocer_candidatos:userid',
                'competencia_id' => 'privacy:metadata:local_conocer_candidatos:competencia_id',
                'nivel' => 'privacy:metadata:local_conocer_candidatos:nivel',
                'estado' => 'privacy:metadata:local_conocer_candidatos:estado',
                'experiencia' => 'privacy:metadata:local_conocer_candidatos:experiencia',
                'curp' => 'privacy:metadata:local_conocer_candidatos:curp',
                'telefono' => 'privacy:metadata:local_conocer_candidatos:telefono',
                'direccion' => 'privacy:metadata:local_conocer_candidatos:direccion',
                'fecha_solicitud' => 'privacy:metadata:local_conocer_candidatos:fecha_solicitud',
                'fecha_modificacion' => 'privacy:metadata:local_conocer_candidatos:fecha_modificacion',
                'notas' => 'privacy:metadata:local_conocer_candidatos:notas',
            ],
            'privacy:metadata:local_conocer_candidatos'
        );
        
        // Company data.
        $collection->add_database_table(
            'local_conocer_empresas',
            [
                'nombre' => 'privacy:metadata:local_conocer_empresas:nombre',
                'rfc' => 'privacy:metadata:local_conocer_empresas:rfc',
                'direccion' => 'privacy:metadata:local_conocer_empresas:direccion',
                'contacto_nombre' => 'privacy:metadata:local_conocer_empresas:contacto_nombre',
                'contacto_email' => 'privacy:metadata:local_conocer_empresas:contacto_email',
                'contacto_telefono' => 'privacy:metadata:local_conocer_empresas:contacto_telefono',
                'contacto_userid' => 'privacy:metadata:local_conocer_empresas:contacto_userid',
                'estado' => 'privacy:metadata:local_conocer_empresas:estado',
                'fecha_solicitud' => 'privacy:metadata:local_conocer_empresas:fecha_solicitud',
            ],
            'privacy:metadata:local_conocer_empresas'
        );
        
        // Document data.
        $collection->add_database_table(
            'local_conocer_documentos',
            [
                'candidato_id' => 'privacy:metadata:local_conocer_documentos:candidato_id',
                'tipo' => 'privacy:metadata:local_conocer_documentos:tipo',
                'nombre_archivo' => 'privacy:metadata:local_conocer_documentos:nombre_archivo',
                'ruta_archivo' => 'privacy:metadata:local_conocer_documentos:ruta_archivo',
                'estado' => 'privacy:metadata:local_conocer_documentos:estado',
                'comentarios' => 'privacy:metadata:local_conocer_documentos:comentarios',
                'revisado_por' => 'privacy:metadata:local_conocer_documentos:revisado_por',
                'fecha_subida' => 'privacy:metadata:local_conocer_documentos:fecha_subida',
            ],
            'privacy:metadata:local_conocer_documentos'
        );
        
        // Process data.
        $collection->add_database_table(
            'local_conocer_procesos',
            [
                'candidato_id' => 'privacy:metadata:local_conocer_procesos:candidato_id',
                'etapa' => 'privacy:metadata:local_conocer_procesos:etapa',
                'evaluador_id' => 'privacy:metadata:local_conocer_procesos:evaluador_id',
                'fecha_inicio' => 'privacy:metadata:local_conocer_procesos:fecha_inicio',
                'fecha_evaluacion' => 'privacy:metadata:local_conocer_procesos:fecha_evaluacion',
                'fecha_fin' => 'privacy:metadata:local_conocer_procesos:fecha_fin',
                'resultado' => 'privacy:metadata:local_conocer_procesos:resultado',
                'notas' => 'privacy:metadata:local_conocer_procesos:notas',
            ],
            'privacy:metadata:local_conocer_procesos'
        );
        
        // Evaluator data.
        $collection->add_database_table(
            'local_conocer_evaluadores',
            [
                'userid' => 'privacy:metadata:local_conocer_evaluadores:userid',
                'curp' => 'privacy:metadata:local_conocer_evaluadores:curp',
                'telefono' => 'privacy:metadata:local_conocer_evaluadores:telefono',
                'direccion' => 'privacy:metadata:local_conocer_evaluadores:direccion',
                'experiencia' => 'privacy:metadata:local_conocer_evaluadores:experiencia',
                'certificaciones' => 'privacy:metadata:local_conocer_evaluadores:certificaciones',
                'competencias' => 'privacy:metadata:local_conocer_evaluadores:competencias',
                'estatus' => 'privacy:metadata:local_conocer_evaluadores:estatus',
                'notas' => 'privacy:metadata:local_conocer_evaluadores:notas',
            ],
            'privacy:metadata:local_conocer_evaluadores'
        );
        
        // Evaluations data.
        $collection->add_database_table(
            'local_conocer_evaluaciones',
            [
                'proceso_id' => 'privacy:metadata:local_conocer_evaluaciones:proceso_id',
                'evaluador_id' => 'privacy:metadata:local_conocer_evaluaciones:evaluador_id',
                'calificacion' => 'privacy:metadata:local_conocer_evaluaciones:calificacion',
                'comentarios' => 'privacy:metadata:local_conocer_evaluaciones:comentarios',
                'recomendaciones' => 'privacy:metadata:local_conocer_evaluaciones:recomendaciones',
            ],
            'privacy:metadata:local_conocer_evaluaciones'
        );
        
        // Certificate data.
        $collection->add_database_table(
            'local_conocer_certificados',
            [
                'proceso_id' => 'privacy:metadata:local_conocer_certificados:proceso_id',
                'numero_folio' => 'privacy:metadata:local_conocer_certificados:numero_folio',
                'fecha_emision' => 'privacy:metadata:local_conocer_certificados:fecha_emision',
                'fecha_vencimiento' => 'privacy:metadata:local_conocer_certificados:fecha_vencimiento',
                'emitido_por' => 'privacy:metadata:local_conocer_certificados:emitido_por',
                'estatus' => 'privacy:metadata:local_conocer_certificados:estatus',
            ],
            'privacy:metadata:local_conocer_certificados'
        );
        
        // Notification data.
        $collection->add_database_table(
            'local_conocer_notificaciones',
            [
                'userid' => 'privacy:metadata:local_conocer_notificaciones:userid',
                'remitente_id' => 'privacy:metadata:local_conocer_notificaciones:remitente_id',
                'tipo' => 'privacy:metadata:local_conocer_notificaciones:tipo',
                'asunto' => 'privacy:metadata:local_conocer_notificaciones:asunto',
                'mensaje' => 'privacy:metadata:local_conocer_notificaciones:mensaje',
                'leido' => 'privacy:metadata:local_conocer_notificaciones:leido',
                'timecreated' => 'privacy:metadata:local_conocer_notificaciones:timecreated',
            ],
            'privacy:metadata:local_conocer_notificaciones'
        );
        
        // Security log data.
        $collection->add_database_table(
            'local_conocer_security_log',
            [
                'userid' => 'privacy:metadata:local_conocer_security_log:userid',
                'action' => 'privacy:metadata:local_conocer_security_log:action',
                'ip' => 'privacy:metadata:local_conocer_security_log:ip',
                'details' => 'privacy:metadata:local_conocer_security_log:details',
                'timecreated' => 'privacy:metadata:local_conocer_security_log:timecreated',
            ],
            'privacy:metadata:local_conocer_security_log'
        );
        
        return $collection;
    }
    
    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid The user to search.
     * @return contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        
        // Add the system context as all plugin data is stored at system context.
        $contextlist->add_system_context();
        
        return $contextlist;
    }
    
    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();
        
        // Only the system context is supported.
        if (!$context instanceof \context_system) {
            return;
        }
        
        // Candidate users.
        $sql = "SELECT userid FROM {local_conocer_candidatos}";
        $userlist->add_from_sql('userid', $sql, []);
        
        // Company contact users.
        $sql = "SELECT contacto_userid FROM {local_conocer_empresas} WHERE contacto_userid IS NOT NULL";
        $userlist->add_from_sql('contacto_userid', $sql, []);
        
        // Evaluator users.
        $sql = "SELECT userid FROM {local_conocer_evaluadores}";
        $userlist->add_from_sql('userid', $sql, []);
        
        // Process evaluator users.
        $sql = "SELECT evaluador_id FROM {local_conocer_procesos} WHERE evaluador_id IS NOT NULL";
        $userlist->add_from_sql('evaluador_id', $sql, []);
        
        // Document reviewer users.
        $sql = "SELECT revisado_por FROM {local_conocer_documentos} WHERE revisado_por IS NOT NULL";
        $userlist->add_from_sql('revisado_por', $sql, []);
        
        // Evaluation users.
        $sql = "SELECT evaluador_id FROM {local_conocer_evaluaciones}";
        $userlist->add_from_sql('evaluador_id', $sql, []);
        
        // Certificate issuer users.
        $sql = "SELECT emitido_por FROM {local_conocer_certificados} WHERE emitido_por IS NOT NULL";
        $userlist->add_from_sql('emitido_por', $sql, []);
        
        // Notification users.
        $sql = "SELECT userid FROM {local_conocer_notificaciones}";
        $userlist->add_from_sql('userid', $sql, []);
        
        // Notification sender users.
        $sql = "SELECT remitente_id FROM {local_conocer_notificaciones} WHERE remitente_id IS NOT NULL";
        $userlist->add_from_sql('remitente_id', $sql, []);
        
        // Security log users.
        $sql = "SELECT userid FROM {local_conocer_security_log}";
        $userlist->add_from_sql('userid', $sql, []);
    }
    
    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        // If the user has data, then only the system context should be present.
        $systemcontext = \context_system::instance();
        if (count($contextlist) != 1 || !in_array($systemcontext->id, $contextlist->get_contextids())) {
            return;
        }
        
        $user = $contextlist->get_user();
        $userid = $user->id;
        
        // Export candidate data.
        self::export_candidate_data($userid);
        
        // Export company data.
        self::export_company_data($userid);
        
        // Export evaluator data.
        self::export_evaluator_data($userid);
        
        // Export document review data.
        self::export_document_reviewer_data($userid);
        
        // Export evaluation data.
        self::export_evaluation_data($userid);
        
        // Export certificate issuer data.
        self::export_certificate_issuer_data($userid);
        
        // Export notification data.
        self::export_notification_data($userid);
        
        // Export security log data.
        self::export_security_log_data($userid);
    }
    
    /**
     * Delete all data for all users in the specified context.
     *
     * @param context $context The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        // Only the system context is relevant.
        if (!$context instanceof \context_system) {
            return;
        }
        
        global $DB;
        
        // Delete all records from all tables.
        $DB->delete_records('local_conocer_candidatos', []);
        $DB->delete_records('local_conocer_empresas', []);
        $DB->delete_records('local_conocer_documentos', []);
        $DB->delete_records('local_conocer_procesos', []);
        $DB->delete_records('local_conocer_evaluadores', []);
        $DB->delete_records('local_conocer_evaluaciones', []);
        $DB->delete_records('local_conocer_certificados', []);
        $DB->delete_records('local_conocer_notificaciones', []);
        $DB->delete_records('local_conocer_security_log', []);
    }
    
    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        // If the user has data, then only the system context should be present.
        $systemcontext = \context_system::instance();
        if (count($contextlist) != 1 || !in_array($systemcontext->id, $contextlist->get_contextids())) {
            return;
        }
        
        $userid = $contextlist->get_user()->id;
        
        global $DB;
        
        // Get all candidate IDs for this user.
        $candidateids = $DB->get_fieldset_select('local_conocer_candidatos', 'id', 'userid = :userid', ['userid' => $userid]);
        
        // Delete candidate documents.
        if (!empty($candidateids)) {
            list($insql, $inparams) = $DB->get_in_or_equal($candidateids, SQL_PARAMS_NAMED);
            $DB->delete_records_select('local_conocer_documentos', "candidato_id $insql", $inparams);
            
            // Get process IDs for these candidates.
            $processids = $DB->get_fieldset_select('local_conocer_procesos', 'id', "candidato_id $insql", $inparams);
            
            // Delete evaluations and certificates for these processes.
            if (!empty($processids)) {
                list($procinsql, $procinparams) = $DB->get_in_or_equal($processids, SQL_PARAMS_NAMED);
                $DB->delete_records_select('local_conocer_evaluaciones', "proceso_id $procinsql", $procinparams);
                $DB->delete_records_select('local_conocer_certificados', "proceso_id $procinsql", $procinparams);
            }
            
            // Delete processes.
            $DB->delete_records_select('local_conocer_procesos', "candidato_id $insql", $inparams);
        }
        
        // Delete candidate records.
        $DB->delete_records('local_conocer_candidatos', ['userid' => $userid]);
        
        // Delete company records where user is contact.
        $DB->delete_records('local_conocer_empresas', ['contacto_userid' => $userid]);
        
        // Delete evaluator records.
        $DB->delete_records('local_conocer_evaluadores', ['userid' => $userid]);
        
        // Update processes where user is evaluator.
        $DB->set_field('local_conocer_procesos', 'evaluador_id', null, ['evaluador_id' => $userid]);
        
        // Update documents where user is reviewer.
        $DB->set_field('local_conocer_documentos', 'revisado_por', null, ['revisado_por' => $userid]);
        
        // Delete evaluations where user is evaluator.
        $DB->delete_records('local_conocer_evaluaciones', ['evaluador_id' => $userid]);
        
        // Update certificates where user is issuer.
        $DB->set_field('local_conocer_certificados', 'emitido_por', null, ['emitido_por' => $userid]);
        
        // Delete notifications for this user.
        $DB->delete_records('local_conocer_notificaciones', ['userid' => $userid]);
        
        // Update notifications where user is sender.
        $DB->set_field('local_conocer_notificaciones', 'remitente_id', null, ['remitente_id' => $userid]);
        
        // Delete security logs for this user.
        $DB->delete_records('local_conocer_security_log', ['userid' => $userid]);
    }
    
    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        $context = $userlist->get_context();
        
        // Only the system context is supported.
        if (!$context instanceof \context_system) {
            return;
        }
        
        global $DB;
        
        // Get the list of users to delete data for.
        $userids = $userlist->get_userids();
        
        if (empty($userids)) {
            return;
        }
        
        list($usersql, $userparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        
        // Get all candidate IDs for these users.
        $candidateids = $DB->get_fieldset_select('local_conocer_candidatos', 'id', "userid $usersql", $userparams);
        
        // Delete candidate documents.
        if (!empty($candidateids)) {
            list($insql, $inparams) = $DB->get_in_or_equal($candidateids, SQL_PARAMS_NAMED);
            $DB->delete_records_select('local_conocer_documentos', "candidato_id $insql", $inparams);
            
            // Get process IDs for these candidates.
            $processids = $DB->get_fieldset_select('local_conocer_procesos', 'id', "candidato_id $insql", $inparams);
            
            // Delete evaluations and certificates for these processes.
            if (!empty($processids)) {
                list($procinsql, $procinparams) = $DB->get_in_or_equal($processids, SQL_PARAMS_NAMED);
                $DB->delete_records_select('local_conocer_evaluaciones', "proceso_id $procinsql", $procinparams);
                $DB->delete_records_select('local_conocer_certificados', "proceso_id $procinsql", $procinparams);
            }
            
            // Delete processes.
            $DB->delete_records_select('local_conocer_procesos', "candidato_id $insql", $inparams);
        }
        
        // Delete candidate records.
        $DB->delete_records_select('local_conocer_candidatos', "userid $usersql", $userparams);
        
        // Delete company records where user is contact.
        $DB->delete_records_select('local_conocer_empresas', "contacto_userid $usersql", $userparams);
        
        // Delete evaluator records.
        $DB->delete_records_select('local_conocer_evaluadores', "userid $usersql", $userparams);
        
        // Update processes where user is evaluator.
        $DB->set_field_select('local_conocer_procesos', 'evaluador_id', null, "evaluador_id $usersql", $userparams);
        
        // Update documents where user is reviewer.
        $DB->set_field_select('local_conocer_documentos', 'revisado_por', null, "revisado_por $usersql", $userparams);
        
        // Delete evaluations where user is evaluator.
        $DB->delete_records_select('local_conocer_evaluaciones', "evaluador_id $usersql", $userparams);
        
        // Update certificates where user is issuer.
        $DB->set_field_select('local_conocer_certificados', 'emitido_por', null, "emitido_por $usersql", $userparams);
        
        // Delete notifications for these users.
        $DB->delete_records_select('local_conocer_notificaciones', "userid $usersql", $userparams);
        
        // Update notifications where user is sender.
        $DB->set_field_select('local_conocer_notificaciones', 'remitente_id', null, "remitente_id $usersql", $userparams);
        
        // Delete security logs for these users.
        $DB->delete_records_select('local_conocer_security_log', "userid $usersql", $userparams);
    }
    
    /**
     * Export candidate data for a user.
     *
     * @param int $userid The user ID.
     */
    protected static function export_candidate_data(int $userid) {
        global $DB;
        
        $candidates = $DB->get_records('local_conocer_candidatos', ['userid' => $userid]);
        
        if (empty($candidates)) {
            return;
        }
        
        $systemcontext = \context_system::instance();
        
        foreach ($candidates as $candidate) {
            // Get competency information.
            $competencia = $DB->get_record('local_conocer_competencias', ['id' => $candidate->competencia_id]);
            $candidate->competencia_nombre = $competencia ? $competencia->nombre : '';
            $candidate->competencia_codigo = $competencia ? $competencia->codigo : '';
            
            // Get documents.
            $documents = $DB->get_records('local_conocer_documentos', ['candidato_id' => $candidate->id]);
            
            // Get processes.
            $processes = $DB->get_records('local_conocer_procesos', ['candidato_id' => $candidate->id]);
            
            foreach ($processes as $process) {
                // Get evaluations.
                $evaluations = $DB->get_records('local_conocer_evaluaciones', ['proceso_id' => $process->id]);
                
                // Get certificates.
                $certificates = $DB->get_records('local_conocer_certificados', ['proceso_id' => $process->id]);
                
                $process->evaluations = $evaluations;
                $process->certificates = $certificates;
            }
            
            $data = [
                'candidate' => $candidate,
                'documents' => $documents,
                'processes' => $processes
            ];
            
            $subcontext = [
                get_string('privacy:certificationssubcontext', 'local_conocer_cert'),
                $candidate->id
            ];
            
            writer::with_context($systemcontext)->export_data($subcontext, (object)$data);
        }
    }
    
    /**
     * Export company data for a user.
     *
     * @param int $userid The user ID.
     */
    protected static function export_company_data(int $userid) {
        global $DB;
        
        $companies = $DB->get_records('local_conocer_empresas', ['contacto_userid' => $userid]);
        
        if (empty($companies)) {
            return;
        }
        
        $systemcontext = \context_system::instance();
        
        foreach ($companies as $company) {
            $data = [
                'company' => $company
            ];
            
            $subcontext = [
                get_string('privacy:companiessubcontext', 'local_conocer_cert'),
                $company->id
            ];
            
            writer::with_context($systemcontext)->export_data($subcontext, (object)$data);
        }
    }
    
    /**
     * Export evaluator data for a user.
     *
     * @param int $userid The user ID.
     */
    protected static function export_evaluator_data(int $userid) {
        global $DB;
        
        $evaluator = $DB->get_record('local_conocer_evaluadores', ['userid' => $userid]);
        
        if (!$evaluator) {
            return;
        }
        
        $systemcontext = \context_system::instance();
        
        // Get processes where user is evaluator.
        $processes = $DB->get_records('local_conocer_procesos', ['evaluador_id' => $userid]);
        
        // Get evaluations by this evaluator.
        $evaluations = $DB->get_records('local_conocer_evaluaciones', ['evaluador_id' => $userid]);
        
        $data = [
            'evaluator' => $evaluator,
            'processes' => $processes,
            'evaluations' => $evaluations
        ];
        
        $subcontext = [
            get_string('privacy:evaluatorsubcontext', 'local_conocer_cert')
        ];
        
        writer::with_context($systemcontext)->export_data($subcontext, (object)$data);
    }
    
    /**
     * Export document reviewer data for a user.
     *
     * @param int $userid The user ID.
     */
    protected static function export_document_reviewer_data(int $userid) {
        global $DB;
        
        $documents = $DB->get_records('local_conocer_documentos', ['revisado_por' => $userid]);
        
        if (empty($documents)) {
            return;
        }
        
        $systemcontext = \context_system::instance();
        
        $data = [
            'reviewed_documents' => $documents
        ];
        
        $subcontext = [
            get_string('privacy:reviewersubcontext', 'local_conocer_cert')
        ];
        
        writer::with_context($systemcontext)->export_data($subcontext, (object)$data);
    }
    
    /**
     * Export evaluation data for a user.
     *
     * @param int $userid The user ID.
     */
    protected static function export_evaluation_data(int $userid) {
        global $DB;
        
        $evaluations = $DB->get_records('local_conocer_evaluaciones', ['evaluador_id' => $userid]);
        
        if (empty($evaluations)) {
            return;
        }
        
        $systemcontext = \context_system::instance();
        
        $data = [
            'evaluations' => $evaluations
        ];
        
        $subcontext = [
            get_string('privacy:evaluationssubcontext', 'local_conocer_cert')
        ];
        
        writer::with_context($systemcontext)->export_data($subcontext, (object)$data);
    }
    
    /**
     * Export certificate issuer data for a user.
     *
     * @param int $userid The user ID.
     */
    protected static function export_certificate_issuer_data(int $userid) {
        global $DB;
        
        $certificates = $DB->get_records('local_conocer_certificados', ['emitido_por' => $userid]);
        
        if (empty($certificates)) {
            return;
        }
        
        $systemcontext = \context_system::instance();
        
        $data = [
            'issued_certificates' => $certificates
        ];
        
        $subcontext = [
            get_string('privacy:certificateissuersubcontext', 'local_conocer_cert')
        ];
        
        writer::with_context($systemcontext)->export_data($subcontext, (object)$data);
    }
    
    /**
     * Export notification data for a user.
     *
     * @param int $userid The user ID.
     */
    protected static function export_notification_data(int $userid) {
        global $DB;
        
        // Notifications received by user
        $notifications_received = $DB->get_records('local_conocer_notificaciones', ['userid' => $userid]);
        
        // Notifications sent by user
        $notifications_sent = $DB->get_records('local_conocer_notificaciones', ['remitente_id' => $userid]);
        
        if (empty($notifications_received) && empty($notifications_sent)) {
            return;
        }
        
        $systemcontext = \context_system::instance();
        
        $data = [
            'notifications_received' => $notifications_received,
            'notifications_sent' => $notifications_sent
        ];
        
        $subcontext = [
            get_string('privacy:notificationssubcontext', 'local_conocer_cert')
        ];
        
        writer::with_context($systemcontext)->export_data($subcontext, (object)$data);
    }
    
    /**
     * Export security log data for a user.
     *
     * @param int $userid The user ID.
     */
    protected static function export_security_log_data(int $userid) {
        global $DB;
        
        $logs = $DB->get_records('local_conocer_security_log', ['userid' => $userid]);
        
        if (empty($logs)) {
            return;
        }
        
        $systemcontext = \context_system::instance();
        
        $data = [
            'security_logs' => $logs
        ];
        
        $subcontext = [
            get_string('privacy:securitylogsubcontext', 'local_conocer_cert')
        ];
        
        writer::with_context($systemcontext)->export_data($subcontext, (object)$data);
    }
}
    