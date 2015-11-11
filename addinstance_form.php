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
 * Adds instance form
 *
 * @package    enrol_meta
 * @copyright  2010 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");

class enrol_metaplus_addinstance_form extends moodleform {
    protected $course;

    public function definition() {
        global $CFG, $DB;

        $mform  = $this->_form;
        $course = $this->_customdata['course'];
        $instance = $this->_customdata['instance'];
        $this->course = $course;

        $existing = array();
        if ($instance) {
            $existing = $DB->get_records('enrol_metaplus', array(
                'enrolid' => $instance->id
            ), '', 'courseid, id');
        }

        $courses = array();
        $select = context_helper::get_preload_record_columns_sql('ctx');
        $sql = <<<SQL
            SELECT c.id, c.fullname, c.shortname, c.visible, $select
            FROM {course} c
            LEFT JOIN {context} ctx
                ON (ctx.instanceid = c.id AND ctx.contextlevel = :contextlevel)
SQL;
        $rs = $DB->get_recordset_sql($sql, array('contextlevel' => CONTEXT_COURSE));
        foreach ($rs as $c) {
            if ($c->id == SITEID || $c->id == $course->id) {
                continue;
            }
            context_helper::preload_from_record($c);
            $coursecontext = context_course::instance($c->id);
            if (!$c->visible && !has_capability('moodle/course:viewhiddencourses', $coursecontext)) {
                continue;
            }

            if (!has_capability('enrol/meta:selectaslinked', $coursecontext)) {
                continue;
            }

            $courses[$c->id] = $coursecontext->get_context_name(false);
        }
        $rs->close();

        $mform->addElement('header', 'general', get_string('pluginname', 'enrol_meta'));

        $mform->addElement('select', 'link', get_string('linkedcourse', 'enrol_metaplus'), $courses, array('multiple' => 'multiple', 'class' => 'chosen'));
        $mform->addRule('link', get_string('required'), 'required', null, 'server');

        // Add role sync list.
        $coursecontext = \context_course::instance($course->id);
        $roles = get_assignable_roles($coursecontext);
        $mform->addElement('select', 'roleexclusions', get_string('roleexclusions', 'enrol_metaplus'), $roles, array('multiple' => 'multiple'));

        $mform->addElement('hidden', 'id', null);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'enrolid');
        $mform->setType('enrolid', PARAM_INT);

        $data = array('id' => $course->id);
        if ($instance) {
            $data['link'] = implode(',', array_keys($existing));
            $data['enrolid'] = $instance->id;
            $data['roleexclusions'] = $instance->customtext1;
            $this->add_action_buttons();
        } else {
            $this->add_add_buttons();
        }

        $this->set_data($data);
    }

    /**
     * Adds buttons on create new method form
     */
    protected function add_add_buttons() {
        $mform = $this->_form;
        $buttonarray = array();
        $buttonarray[0] = $mform->createElement('submit', 'submitbutton', get_string('addinstance', 'enrol'));
        $buttonarray[1] = $mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }

    public function validation($data, $files) {
        global $DB, $CFG;

        $errors = parent::validation($data, $files);

        if ($this->_customdata['instance']) {
            // Nothing to validate in case of editing.
            return $errors;
        }

        // We may have multiple courses.
        foreach ($data['link'] as $course) {
            if (!$c = $DB->get_record('course', array('id' => $course))) {
                $errors['link'] = get_string('required');
                continue;
            }

            $coursecontext = \context_course::instance($c->id);
            $existing = $DB->get_records('enrol', array(
                'enrol' => 'meta',
                'courseid' => $this->course->id
            ), '', 'customint1, id');

            if (!$c->visible and !has_capability('moodle/course:viewhiddencourses', $coursecontext)) {
                $errors['link'] = get_string('error');
            } else if (!has_capability('enrol/meta:selectaslinked', $coursecontext)) {
                $errors['link'] = get_string('error');
            } else if ($c->id == SITEID or $c->id == $this->course->id or isset($existing[$c->id])) {
                $errors['link'] = get_string('error');
            }
        }

        return $errors;
    }
}

