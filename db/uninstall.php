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
 * Meta+ link enrolment plugin uninstallation.
 *
 * @package    enrol_metaplus
 * @copyright  2015 University of Kent
 * @author     Skylar Kelty
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_enrol_metaplus_uninstall() {
    global $CFG, $DB;

    $meta = enrol_get_plugin('metaplus');
    $rs = $DB->get_recordset('enrol', array('enrol' => 'metaplus'));
    foreach ($rs as $instance) {
        $meta->delete_instance($instance);
    }
    $rs->close();

    role_unassign_all(array('component' => 'enrol_metaplus'));

    return true;
}