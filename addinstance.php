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
 * Adds new instance of enrol_metaplus to specified course.
 *
 * @package    enrol_metaplus
 * @copyright  2015 University of Kent
 * @author     Skylar Kelty
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(dirname(__FILE__) . '/../../config.php');
require_once("{$CFG->dirroot}/enrol/metaplus/addinstance_form.php");
require_once("{$CFG->dirroot}/enrol/meta/locallib.php");

$id = required_param('id', PARAM_INT); // course id
$instanceid = optional_param('enrolid', 0, PARAM_INT);

$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);
$context = context_course::instance($course->id, MUST_EXIST);

$PAGE->set_url('/enrol/metaplus/addinstance.php', array('id' => $course->id));
$PAGE->set_pagelayout('admin');

$returnurl = new moodle_url('/enrol/instances.php', array('id' => $course->id));
if (!enrol_is_enabled('metaplus')) {
    redirect($returnurl);
}

navigation_node::override_active_url($returnurl);

require_login($course);
require_capability('moodle/course:enrolconfig', $context);

$enrol = enrol_get_plugin('metaplus');
if ($instanceid) {
    require_capability('enrol/meta:config', $context);
    $instance = $DB->get_record('enrol', array(
        'courseid' => $course->id,
        'enrol' => 'metaplus',
        'id' => $instanceid
    ), '*', MUST_EXIST);

} else {
    if (!$enrol->get_newinstance_link($course->id)) {
        redirect($returnurl);
    }
    $instance = null;
}

$mform = new enrol_metaplus_addinstance_form(null, array(
    'course' => $course,
    'instance' => $instance
));

if ($mform->is_cancelled()) {
    redirect($returnurl);
}

if ($data = $mform->get_data()) {
    // Go through every link and grab a group.
    $links = array();
    foreach ($data->link as $c) {
        $links[$c] = enrol_meta_create_new_group($course->id, $c);
    }

    $roleexclusions = implode(',', $data->roleexclusions);
    if ($instance) {
        $records = $DB->get_records('enrol_metaplus', array(
            'enrolid' => $instance->id,
        ));

        // Removals.
        $courseids = array();
        foreach ($records as $record) {
            if (!isset($links[$record->courseid])) {
                $DB->delete_record('enrol_metaplus', array(
                    $record->id
                ));
            }

            $courseids[] = $record->courseid;
        }

        // Additions.
        foreach ($links as $c => $group) {
            if (!in_array($c, $courseids)) {
                $DB->insert_record('enrol_metaplus', array(
                    'enrolid' => $instance->id,
                    'courseid' => $c,
                    'groupid' => $group,
                ));
            }
        }

        // Ensure role exclusions are updated.
        if ($instance->customtext1 !== $roleexclusions) {
             $DB->update_record('enrol', array(
                'id' => $instance->id,
                'customtext1' => $roleexclusions
            ));
        }
    } else {
        // Insert new record.
        $eid = $enrol->add_instance($course, array(
            'customtext1' => $roleexclusions
        ));

        // Insert records.
        foreach ($links as $c => $group) {
            $DB->insert_record('enrol_metaplus', array(
                'enrolid' => $eid,
                'courseid' => $c,
                'groupid' => $group,
            ));
        }
    }

    redirect($returnurl);
}

$PAGE->set_heading($course->fullname);
$PAGE->set_title(get_string('pluginname', 'enrol_metaplus'));

echo $OUTPUT->header();

$message = optional_param('message', null, PARAM_TEXT);
if ($message === 'added') {
    echo $OUTPUT->notification(get_string('instanceadded', 'enrol'), 'notifysuccess');
}

$mform->display();

echo $OUTPUT->footer();
