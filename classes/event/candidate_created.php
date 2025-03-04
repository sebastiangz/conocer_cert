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
 * Event for candidate creation.
 *
 * @package    local_conocer_cert
 * @copyright  2025 Sebastian Gonzalez Zepeda sgonzalez@infraestructuragis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_conocer_cert\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Event triggered when a certification candidate is created.
 */
class candidate_created extends \core\event\base {
    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'c'; // Create operation
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'local_conocer_candidatos';
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event_candidate_created', 'local_conocer_cert');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        global $DB;
        
        // Get candidate details
        $candidate = $DB->get_record('local_conocer_candidatos', ['id' => $this->objectid]);
        
        // Get competency name if available
        $competencyname = '';
        if ($candidate && !empty($candidate->competencia_id)) {
            $competency = $DB->get_record('local_conocer_competencias', ['id' => $candidate->competencia_id]);
            if ($competency) {
                $competencyname = $competency->nombre;
            }
        }
        
        if (!empty($competencyname)) {
            return get_string('event_candidate_created_desc', 'local_conocer_cert', [
                'userid' => $this->relateduserid,
                'competencia' => $competencyname,
                'nivel' => $candidate->nivel
            ]);
        } else {
            return get_string('event_candidate_created_basic_desc', 'local_conocer_cert', [
                'userid' => $this->relateduserid
            ]);
        }
    }

    /**
     * Return the legacy event log data.
     *
     * @return array
     */
    protected function get_legacy_logdata() {
        return [
            'local_conocer_cert',
            'candidate',
            'create',
            'candidate/view.php?id=' . $this->objectid,
            get_string('candidatecreated', 'local_conocer_cert') . ': ' . $this->objectid
        ];
    }

    /**
     * Get URL related to the action.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/local/conocer_cert/candidate/view.php', [
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
     * This method is used to get all of the event data in an array.
     * It is kept up-to-date whenever init() or create() is called.
     *
     * @return array
     */
    public function get_data() {
        $data = parent::get_data();
        
        if (!isset($data['other'])) {
            $data['other'] = [];
        }
        
        // Add a snapshot of the candidate if needed
        if (!isset($data['other']['candidateid'])) {
            $data['other']['candidateid'] = $this->objectid;
        }
        
        return $data;
    }

    /**
     * Create a new event instance for candidate creation.
     *
     * @param \stdClass $candidate The candidate record
     * @param \context $context The context to use with this event
     * @return self
     */
    public static function create_from_candidate($candidate, $context) {
        // Get user ID from the candidate
        $params = [
            'objectid' => $candidate->id,
            'context' => $context,
            'relateduserid' => $candidate->userid,
            'other' => [
                'competenciaid' => $candidate->competencia_id,
                'nivel' => $candidate->nivel,
                'estado' => $candidate->estado
            ]
        ];
        
        $event = self::create($params);
        
        return $event;
    }
}
