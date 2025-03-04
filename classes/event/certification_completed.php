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
 * Event for certification completion.
 *
 * @package    local_conocer_cert
 * @copyright  2025 Sebastian Gonzalez Zepeda sgonzalez@infraestructuragis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_conocer_cert\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Event triggered when a certification process is completed.
 */
class certification_completed extends \core\event\base {
    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'u'; // Update operation
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'local_conocer_procesos';
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event_certification_completed', 'local_conocer_cert');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        global $DB;
        
        // Get process details
        $process = $DB->get_record('local_conocer_procesos', ['id' => $this->objectid]);
        
        if (!$process) {
            return get_string('event_certification_completed_unknown', 'local_conocer_cert');
        }
        
        // Get candidate details
        $candidate = $DB->get_record('local_conocer_candidatos', ['id' => $process->candidato_id]);
        
        // Get competency details
        $competencyname = '';
        if ($candidate && !empty($candidate->competencia_id)) {
            $competency = $DB->get_record('local_conocer_competencias', ['id' => $candidate->competencia_id]);
            if ($competency) {
                $competencyname = $competency->nombre;
            }
        }
        
        // Get result text
        $result = isset($process->resultado) ? $process->resultado : 'unknown';
        $resultText = get_string('result_' . $result, 'local_conocer_cert');
        
        return get_string('event_certification_completed_desc', 'local_conocer_cert', [
            'userid' => $this->relateduserid,
            'competencia' => $competencyname,
            'nivel' => $candidate ? $candidate->nivel : '',
            'resultado' => $resultText
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
            'certification',
            'complete',
            'process/view.php?id=' . $this->objectid,
            get_string('certificationcompleted', 'local_conocer_cert') . ': ' . $this->objectid
        ];
    }

    /**
     * Get URL related to the action.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/local/conocer_cert/process/view.php', [
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
        
        if (empty($this->other['resultado'])) {
            throw new \coding_exception('The \'resultado\' value must be set in other array.');
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
        
        // Add process ID if not already set
        if (!isset($data['other']['processid'])) {
            $data['other']['processid'] = $this->objectid;
        }
        
        return $data;
    }

    /**
     * Create a new event instance for certification completion.
     *
     * @param \stdClass $process The certification process record
     * @param \context $context The context to use with this event
     * @param int $userid The user ID related to this certification
     * @return self
     */
    public static function create_from_process($process, $context, $userid) {
        global $DB;
        
        // Get candidate to obtain competency info
        $candidate = $DB->get_record('local_conocer_candidatos', ['id' => $process->candidato_id]);
        
        $params = [
            'objectid' => $process->id,
            'context' => $context,
            'relateduserid' => $userid,
            'other' => [
                'candidateid' => $process->candidato_id,
                'resultado' => $process->resultado,
                'competenciaid' => $candidate ? $candidate->competencia_id : 0,
                'nivel' => $candidate ? $candidate->nivel : 0
            ]
        ];
        
        $event = self::create($params);
        
        return $event;
    }
}
