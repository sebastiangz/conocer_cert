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
 * Database upgrade script for the CONOCER certification plugin.
 *
 * @package    local_conocer_cert
 * @copyright  2025 Sebastian Gonzalez Zepeda sgonzalez@infraestructuragis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Function executed when upgrading the plugin.
 *
 * @param int $oldversion Previous version
 * @return bool
 */
function xmldb_local_conocer_cert_upgrade($oldversion) {
    global $DB;
    
    $dbman = $DB->get_manager();
    
    if ($oldversion < 2025030100) {
        // Initial version, no changes required.
        upgrade_plugin_savepoint(true, 2025030100, 'local', 'conocer_cert');
    }
    
    if ($oldversion < 2025030101) {
        // Add new field to track certificate downloads
        $table = new xmldb_table('local_conocer_certificados');
        $field = new xmldb_field('descargas', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0, 'estatus');
        
        // Add field if it doesn't exist
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
        // Savepoint reached
        upgrade_plugin_savepoint(true, 2025030101, 'local', 'conocer_cert');
    }
    
    if ($oldversion < 2025030102) {
        // Add new field to store certificate template ID
        $table = new xmldb_table('local_conocer_certificados');
        $field = new xmldb_field('template_id', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'descargas');
        
        // Add field if it doesn't exist
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
        // Create new table for certificate templates
        $table = new xmldb_table('local_conocer_cert_templates');
        
        // Add fields
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('nombre', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('descripcion', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('plantilla', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('activo', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('creado_por', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        
        // Add keys
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        
        // Create the table if it doesn't exist
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        
        // Savepoint reached
        upgrade_plugin_savepoint(true, 2025030102, 'local', 'conocer_cert');
    }
    
    if ($oldversion < 2025030103) {
        // Add field for evaluation criteria
        $table = new xmldb_table('local_conocer_competencias');
        $field = new xmldb_field('criterios_evaluacion', XMLDB_TYPE_TEXT, null, null, null, null, null, 'fecha_fin');
        
        // Add field if it doesn't exist
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
        // Create new table for evaluation criteria
        $table = new xmldb_table('local_conocer_criterios');
        
        // Add fields
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('competencia_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('nombre', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('descripcion', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('ponderacion', XMLDB_TYPE_NUMBER, '5, 2', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('activo', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        
        // Add keys
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('competencia_fk', XMLDB_KEY_FOREIGN, ['competencia_id'], 'local_conocer_competencias', ['id']);
        
        // Create the table if it doesn't exist
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        
        // Create new table for criteria evaluations
        $table = new xmldb_table('local_conocer_criterios_eval');
        
        // Add fields
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('evaluacion_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('criterio_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('valoracion', XMLDB_TYPE_NUMBER, '5, 2', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('comentario', XMLDB_TYPE_TEXT, null, null, null, null, null);
        
        // Add keys
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('evaluacion_fk', XMLDB_KEY_FOREIGN, ['evaluacion_id'], 'local_conocer_evaluaciones', ['id']);
        $table->add_key('criterio_fk', XMLDB_KEY_FOREIGN, ['criterio_id'], 'local_conocer_criterios', ['id']);
        
        // Create the table if it doesn't exist
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        
        // Savepoint reached
        upgrade_plugin_savepoint(true, 2025030103, 'local', 'conocer_cert');
    }
    
    if ($oldversion < 2025030200) {
        // Add field for tracking certificate verification attempts
        $table = new xmldb_table('local_conocer_certificados');
        $field = new xmldb_field('verificaciones', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0, 'template_id');
        
        // Add field if it doesn't exist
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
        // Create new table for verification log
        $table = new xmldb_table('local_conocer_verificaciones');
        
        // Add fields
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('certificado_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('ip', XMLDB_TYPE_CHAR, '45', null, XMLDB_NOTNULL, null, null);
        $table->add_field('metodo', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('resultado', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('detalles', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        
        // Add keys
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('certificado_fk', XMLDB_KEY_FOREIGN, ['certificado_id'], 'local_conocer_certificados', ['id']);
        
        // Create the table if it doesn't exist
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        
        // Savepoint reached
        upgrade_plugin_savepoint(true, 2025030200, 'local', 'conocer_cert');
    }
    
    if ($oldversion < 2025030201) {
        // Add field for tracking user satisfaction with the certification process
        $table = new xmldb_table('local_conocer_procesos');
        $field = new xmldb_field('satisfaccion', XMLDB_TYPE_INTEGER, '1', null, null, null, null, 'notas');
        
        // Add field if it doesn't exist
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
        // Add field for feedback comments
        $field = new xmldb_field('feedback', XMLDB_TYPE_TEXT, null, null, null, null, null, 'satisfaccion');
        
        // Add field if it doesn't exist
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
        // Create new table for process feedback
        $table = new xmldb_table('local_conocer_feedback');
        
        // Add fields
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('proceso_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('calificacion', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null);
        $table->add_field('comentario', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('aspecto', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        
        // Add keys
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('proceso_fk', XMLDB_KEY_FOREIGN, ['proceso_id'], 'local_conocer_procesos', ['id']);
        $table->add_key('usuario_fk', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
        
        // Create the table if it doesn't exist
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        
        // Savepoint reached
        upgrade_plugin_savepoint(true, 2025030201, 'local', 'conocer_cert');
    }
    
    if ($oldversion < 2025030301) {
        // Current version
        // Future upgrades would go here
        
        // Savepoint reached
        upgrade_plugin_savepoint(true, 2025030301, 'local', 'conocer_cert');
    }
    
    return true;
}
