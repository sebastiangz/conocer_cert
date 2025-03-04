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
 * Event for certificate expiration.
 *
 * @package    local_conocer_cert
 * @copyright  2025 Sebastian Gonzalez Zepeda sgonzalez@infraestructuragis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_conocer_cert\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Event triggered when a certificate expires.
 */
class certificate_expired extends \core\event\base {
    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'u'; // Update operation
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'local_conocer_certificados';
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event_certificate_expired', 'local_conocer_cert');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        global $DB;
        
        // Get certificate details
        $certificate = $DB->get_record('local_conocer_certificados', ['id' => $this->objectid]);
        
        if (!$certificate) {
            return get_string('event_certificate_expired_unknown', 'local_conocer_cert');
        }
        
        // Get process details
        $process = $DB->get_record('local_conocer_procesos', ['id' => $certificate->proceso_id]);
        
        // Get candidate and competency details
        $competencyname = '';
        $nivel = '';
        
        if ($process) {
            $candidate = $DB->get_record('local_conocer_candidatos', ['id' => $process->candidato_id]);
            if ($candidate) {
                $nivel = $candidate->nivel;
                
                $competency = $DB->get_record('local_conocer_competencias', ['id' => $candidate->competencia_id]);
                if ($competency) {
                    $competencyname = $competency->nombre;
                }
            }
        }
        
        return get_string('event_certificate_expired_desc', 'local_conocer_cert', [
            'folio' => $certificate->numero_folio,
            'userid' => $this->relateduserid,
            'competencia' => $competencyname,
            'nivel' => $nivel,
            'fecha_vencimiento' => userdate($certificate->fecha_vencimiento)
        ]);
    }

    /**
     * Return the legacy event log data.
     *
     * @return array
     */
    protected function get_legacy_logdata() {
        return [
            'local_conocer_cert',
            'certificate',
            'expire',
            'certificate/view.php?id=' . $this->objectid,
            get_string('certificateexpired', 'local_conocer_cert') . ': ' . $this->objectid
        ];
    }

    /**
     * Get URL related to the action.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/local/conocer_cert/certificate/view.php', [
            'id' => $this->objectid
        ]);
    }

    /**
     * Custom validation.
     *
     * @throws \coding_exception
     * @return void
     */
    protected function validate_data() {
        parent::validate_data();
        
        if (!isset($this->objectid)) {
            throw new \coding_exception('The \'objectid\' value must be set.');
        }
        
        if (!isset($this->relateduserid)) {
            throw new \coding_exception('The \'relateduserid\' value must be set.');
        }
    }

    /**
     * Get all of the event data.
     *
     * @return array
     */
    public function get_data() {
        $data = parent::get_data();
        
        if (!isset($data['other'])) {
            $data['other'] = [];
        }
        
        // Add certificate ID if not already set
        if (!isset($data['other']['certificateid'])) {
            $data['other']['certificateid'] = $this->objectid;
        }
        
        return $data;
    }

    /**
     * Create a new event instance for certificate expiration.
     *
     * @param \stdClass $certificate The certificate record
     * @param \context $context The context to use with this event
     * @param int $userid The user ID of the certificate owner
     * @return self
     */
    public static function create_from_certificate($certificate, $context, $userid) {
        global $DB;
        
        // Get process info
        $process = $DB->get_record('local_conocer_procesos', ['id' => $certificate->proceso_id]);
        
        $params = [
            'objectid' => $certificate->id,
            'context' => $context,
            'relateduserid' => $userid,
            'other' => [
                'proceso_id' => $certificate->proceso_id,
                'numero_folio' => $certificate->numero_folio,
                'fecha_vencimiento' => $certificate->fecha_vencimiento
            ]
        ];
        
        // Add competency and level info if available
        if ($process) {
            $candidate = $DB->get_record('local_conocer_candidatos', ['id' => $process->candidato_id]);
            if ($candidate) {
                $params['other']['competencia_id'] = $candidate->competencia_id;
                $params['other']['nivel'] = $candidate->nivel;
            }
        }
        
        $event = self::create($params);
        
        return $event;
    }
}
