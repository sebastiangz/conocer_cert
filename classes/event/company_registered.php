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
 * Event for company registration.
 *
 * @package    local_conocer_cert
 * @copyright  2025 Sebastian Gonzalez Zepeda sgonzalez@infraestructuragis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_conocer_cert\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Event triggered when a company is registered.
 */
class company_registered extends \core\event\base {
    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'c'; // Create operation
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'local_conocer_empresas';
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event_company_registered', 'local_conocer_cert');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        global $DB;
        
        // Get company details
        $company = $DB->get_record('local_conocer_empresas', ['id' => $this->objectid]);
        
        if (!$company) {
            return get_string('event_company_registered_unknown', 'local_conocer_cert');
        }
        
        // Check if we have a related user
        $userinfo = '';
        if (!empty($this->relateduserid)) {
            $userinfo = get_string('event_company_registered_by_user', 'local_conocer_cert', [
                'userid' => $this->relateduserid
            ]);
        }
        
        // Add sector information if available
        $sectorinfo = '';
        if (!empty($company->sector)) {
            $sectorinfo = get_string('event_company_registered_sector', 'local_conocer_cert', [
                'sector' => $company->sector
            ]);
        }
        
        return get_string('event_company_registered_desc', 'local_conocer_cert', [
            'nombre' => $company->nombre,
            'rfc' => $company->rfc,
            'userinfo' => $userinfo,
            'sectorinfo' => $sectorinfo
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
            'company',
            'register',
            'company/view.php?id=' . $this->objectid,
            get_string('companyregistered', 'local_conocer_cert') . ': ' . $this->objectid
        ];
    }

    /**
     * Get URL related to the action.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/local/conocer_cert/company/view.php', [
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
        
        // Add company ID if not already set
        if (!isset($data['other']['companyid'])) {
            $data['other']['companyid'] = $this->objectid;
        }
        
        return $data;
    }

    /**
     * Create a new event instance for company registration.
     *
     * @param \stdClass $company The company record
     * @param \context $context The context to use with this event
     * @return self
     */
    public static function create_from_company($company, $context) {
        $params = [
            'objectid' => $company->id,
            'context' => $context,
            'other' => [
                'nombre' => $company->nombre,
                'rfc' => $company->rfc,
                'sector' => $company->sector ?? '',
                'estado' => $company->estado
            ]
        ];
        
        // Add related user if available
        if (!empty($company->contacto_userid)) {
            $params['relateduserid'] = $company->contacto_userid;
        }
        
        $event = self::create($params);
        
        return $event;
    }
    
    /**
     * Create a new event instance for company registration by admin.
     *
     * @param \stdClass $company The company record
     * @param \context $context The context to use with this event
     * @param int $adminid ID of the admin user who registered the company
     * @return self
     */
    public static function create_from_company_by_admin($company, $context, $adminid) {
        $params = [
            'objectid' => $company->id,
            'context' => $context,
            'userid' => $adminid,
            'other' => [
                'nombre' => $company->nombre,
                'rfc' => $company->rfc,
                'sector' => $company->sector ?? '',
                'estado' => $company->estado,
                'created_by_admin' => true
            ]
        ];
        
        // Add related user if available
        if (!empty($company->contacto_userid)) {
            $params['relateduserid'] = $company->contacto_userid;
        }
        
        $event = self::create($params);
        
        return $event;
    }
}
