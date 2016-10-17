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
 * update grade
 *
 * @package    mod_giportfolio
 * @copyright  2012 Synergy Learning / Manolescu Dorel based on book module
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(dirname(__FILE__).'/../../config.php');

global $DB, $CFG, $PAGE, $OUTPUT, $USER;
require_once($CFG->libdir.'/gradelib.php');
require_once($CFG->dirroot.'/grade/grading/lib.php');
require_once($CFG->dirroot.'/mod/giportfolio/locallib.php');
require_once($CFG->dirroot.'/mod/giportfolio/updategrade_form.php');

$id = required_param('id', PARAM_INT); // Course Module ID.
$userid = required_param('userid', PARAM_INT); // Portfolio ID.

$cm = get_coursemodule_from_id('giportfolio', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$giportfolio = $DB->get_record('giportfolio', array('id' => $cm->instance), '*', MUST_EXIST);

require_login($course, false, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/giportfolio:gradegiportfolios', $context);

$user = $DB->get_record('user', array('id' => $userid));

$gradeitemgrademax = null;
$userfinalgrade = null;
$usergrade = grade_get_grades($course->id, 'mod', 'giportfolio', $giportfolio->id, $user->id);
if ($usergrade->items) {
    $gradeitemgrademax = $usergrade->items[0]->grademax;
    if (isset($usergrade->items[0]->grades[$userid])) {
        $userfinalgrade = $usergrade->items[0]->grades[$userid];
    }
}

$PAGE->set_url('/mod/giportfolio/updategrade.php', array('id' => $id, 'userid' => $userid));

$formdata = new stdClass();
$formdata->userid = $user->id;
$formdata->id = $cm->id;
$formdata->xgrade = $userfinalgrade ? $userfinalgrade->grade : null;
$formdata->feedback = $userfinalgrade ? $userfinalgrade->feedback : null;

$custom = array(
    'course' => $course,
    'str_grade' => $userfinalgrade ? $userfinalgrade->str_grade: '',
    'user' => $user,
    'lastupdate' => giportfolio_get_user_contribution_status($giportfolio->id, $user->id),
    'gradesetting' => $giportfolio->grade,
    'dategraded' => $userfinalgrade ? $userfinalgrade->dategraded : null,
);

$mform = new mod_giporotfolio_grading_form(null, $custom);

$mform->set_data($formdata);

if ($mform->is_cancelled()) {
    redirect("submissions.php?id=$cm->id");
} else if ($gradeinfo = $mform->get_data()) {
    global $DB;

    $grade = (object)array(
        'userid' => $user->id,
        'usermodified' => $USER->id,
        'rawgrade' => $gradeinfo->xgrade,
        'feedback' => $gradeinfo->feedback,
    );

    $grades = array(
        $user->id => $grade,
    );

    giportfolio_grade_item_update($giportfolio, $grades);

    redirect("submissions.php?id=$cm->id");
}

// Header and strings.
$PAGE->set_title(format_string($giportfolio->name));
$PAGE->add_body_class('mod_giportfolio');
$PAGE->set_heading(format_string($course->fullname));
$realuser = $DB->get_record('user', array('id' => $userid));
$PAGE->navbar->add(get_string('studentgiportfolio', 'mod_giportfolio'),
                   new moodle_url('submissions.php?=', array('id' => $cm->id)));
$PAGE->navbar->add(fullname($realuser));
echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($giportfolio->name));
echo $OUTPUT->heading(get_string('giportfolioof', 'mod_giportfolio').' '.fullname($user, true), 3);
$mform->display();
echo '<br />';
echo $OUTPUT->footer();
