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
 * Event observers used in CONOCER certification plugin.
 *
 * @package    local_conocer_cert
 * @copyright  2025 Sebastian Gonzalez Zepeda sgonzalez@infraestructuragis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$observers = [
    // Observer para candidato creado
    [
        'eventname' => '\local_conocer_cert\event\candidate_created',
        'callback' => '\local_conocer_cert\observer\candidate::created',
        'priority' => 9999,
        'internal' => true
    ],
    
    // Observer para certificación completada
    [
        'eventname' => '\local_conocer_cert\event\certification_completed',
        'callback' => '\local_conocer_cert\observer\certification::completed',
        'priority' => 9999,
        'internal' => true
    ],
    
    // Observer para empresa registrada
    [
        'eventname' => '\local_conocer_cert\event\company_registered',
        'callback' => '\local_conocer_cert\observer\company::registered',
        'priority' => 9999,
        'internal' => true
    ],
    
    // Observer para certificado vencido
    [
        'eventname' => '\local_conocer_cert\event\certificate_expired',
        'callback' => '\local_conocer_cert\observer\certificate::expired',
        'priority' => 9999,
        'internal' => true
    ],
    
    // Observer para evaluador creado
    [
        'eventname' => '\local_conocer_cert\event\evaluator_created',
        'callback' => '\local_conocer_cert\observer\evaluator::created',
        'priority' => 9999,
        'internal' => true
    ],
    
    // Observer para evaluador asignado
    [
        'eventname' => '\local_conocer_cert\event\evaluator_assigned',
        'callback' => '\local_conocer_cert\observer\evaluator::assigned',
        'priority' => 9999,
        'internal' => true
    ],
    
    // Observer para evaluación enviada
    [
        'eventname' => '\local_conocer_cert\event\evaluation_submitted',
        'callback' => '\local_conocer_cert\observer\evaluation::submitted',
        'priority' => 9999,
        'internal' => true
    ],
    
    // Observers para eventos del sistema
    
    // Cuando se elimina un usuario, limpiar sus datos en el plugin
    [
        'eventname' => '\core\event\user_deleted',
        'callback' => '\local_conocer_cert\observer\user::deleted',
        'priority' => 1000,
        'internal' => false
    ],
    
    // Cuando se suspende un usuario, suspender sus certificaciones activas
    [
        'eventname' => '\core\event\user_suspended',
        'callback' => '\local_conocer_cert\observer\user::suspended',
        'priority' => 1000,
        'internal' => false
    ],
    
    // Cuando se restaura un usuario, restaurar sus certificaciones
    [
        'eventname' => '\core\event\user_unsuspended',
        'callback' => '\local_conocer_cert\observer\user::unsuspended',
        'priority' => 1000,
        'internal' => false
    ],
    
    // Cuando se crea un usuario, verificar si hay invitaciones pendientes
    [
        'eventname' => '\core\event\user_created',
        'callback' => '\local_conocer_cert\observer\user::created',
        'priority' => 2000,
        'internal' => false
    ],
    
    // Cuando se actualiza un usuario, actualizar información en certificaciones
    [
        'eventname' => '\core\event\user_updated',
        'callback' => '\local_conocer_cert\observer\user::updated',
        'priority' => 2000,
        'internal' => false
    ],
    
    // Cuando se sube un archivo, verificar si es un documento de certificación
    [
        'eventname' => '\core\event\file_uploaded',
        'callback' => '\local_conocer_cert\observer\file::uploaded',
        'priority' => 2000,
        'internal' => false
    ],
    
    // Cuando se elimina un archivo, actualizar estado de documentos
    [
        'eventname' => '\core\event\file_deleted',
        'callback' => '\local_conocer_cert\observer\file::deleted',
        'priority' => 2000,
        'internal' => false
    ]
];
