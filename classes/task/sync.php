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
 * Meta+ course enrolment plugin.
 *
 * @package    enrol_metaplus
 * @copyright  2015 University of Kent
 * @author     Skylar Kelty
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_metaplus\task;

/**
 * Meta enrolment sync
 */
class sync extends \core\task\scheduled_task
{
    public function get_name() {
        return "Meta+ Sync";
    }

    public function execute() {
        // Generate expected list.
        $expected = $this->get_expected_enrolments();

        // Generate current list.
        $current = $this->get_current_enrolments();

        // Build delta.
        print_r($current);
        print_r($expected);

        // Apply delta changes.
    }

    /**
     * Returns a list of expected enrolments on courses.
     * @todo Roles
     */
    private function get_expected_enrolments() {
        global $DB;

        $sql = <<<SQL
        SELECT ue.id, e.courseid, ue.userid
        FROM {enrol} e

        INNER JOIN {enrol_metaplus} mp
            ON mp.enrolid = e.id

        INNER JOIN {enrol} e2
            ON e2.courseid = mp.courseid

        INNER JOIN {user_enrolments} ue
            ON ue.enrolid = e2.id

        WHERE e.enrol = :plugin
        GROUP BY e.courseid, ue.userid
SQL;

        return $DB->get_recordset_sql($sql, array('plugin' => 'metaplus'));

    }

    /**
     * Returns a list of current enrolments on courses.
     * @todo Roles
     */
    private function get_current_enrolments() {
        global $DB;

        $sql = <<<SQL
        SELECT ue.id, e.courseid, ue.userid
        FROM {user_enrolments} ue
        INNER JOIN {enrol} e
            ON e.id = ue.enrolid
        WHERE e.enrol = :plugin
SQL;

        return $DB->get_recordset_sql($sql, array('plugin' => 'metaplus'));
    }
}
