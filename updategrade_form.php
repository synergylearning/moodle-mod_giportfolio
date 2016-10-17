<?php
// This file is part of giportfolio module for Moodle - http://moodle.org/
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
 * update grade form
 *
 * @package    mod_giportfolio
 * @copyright  2012 Synergy Learning / Manolescu Dorel based on book module
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir.'/formslib.php');
require_once('lib.php');

class mod_giporotfolio_grading_form extends moodleform {
    public function definition() {
        global $OUTPUT;
        $mform = $this->_form;

        $user = $this->_customdata['user'];
        $lastupdate = $this->_customdata['lastupdate'];
        $dategraded = $this->_customdata['dategraded'];

        // Hidden params.
        $mform->addElement('hidden', 'userid', 0);
        $mform->setType('userid', PARAM_INT);
        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('static', 'picture', $OUTPUT->user_picture($user),
                           fullname($user, true).'<br/>'.
                           get_string('lastupdated', 'mod_giportfolio').date('l jS \of F Y ', $lastupdate)
        );

        $this->add_grades_section();

        $mform->addElement('header', 'Feed Back', get_string('feedback', 'grades'));
        $mform->addElement('textarea', 'feedback', get_string('feedback', 'grades'), 'wrap="virtual" rows="10" cols="50"');

        if ($dategraded) {
            $datestring = userdate($dategraded)."&nbsp; (".format_time(time() - $dategraded).")";
            $mform->addElement('header', 'Last Grade', get_string('lastgrade', 'mod_giportfolio'));
            $mform->addElement('static', 'lastgrade', get_string('lastgrade', 'mod_giportfolio').':', $datestring);
        }
        // Buttons.
        $this->add_action_buttons();

        $mform->setDisableShortforms(true);
    }

    protected function add_grades_section() {
        global $CFG;
        $mform = $this->_form;

        $mform->addElement('header', 'Grades', get_string('grades', 'grades'));

        $grademenu = make_grades_menu($this->_customdata['gradesetting']);

        $mform->addElement('select', 'xgrade', get_string('grade').':', $grademenu);
        $mform->setType('xgrade', PARAM_INT);

        $course = $this->_customdata['course'];
        $context = context_course::instance($course->id);
        if (has_capability('gradereport/grader:view', $context) && has_capability('moodle/grade:viewall', $context)) {
            $grade = '<a href="'.$CFG->wwwroot.'/grade/report/grader/index.php?id='.$course->id.'" >'.
                $this->_customdata['str_grade'].'</a>';
        } else {
            $grade = $this->_customdata['str_grade'];
        }
        $mform->addElement('static', 'finalgrade', get_string('currentgrade', 'assign').':', $grade);
        $mform->setType('finalgrade', PARAM_INT);
    }
}
