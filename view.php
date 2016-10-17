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
 * giportfolio view page
 *
 * @package    mod_giportfolio
 * @copyright  2012 Synergy Learning / Manolescu Dorel based on book module
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(dirname(__FILE__).'/../../config.php');
require_once(dirname(__FILE__).'/locallib.php');

global $CFG, $DB, $USER, $OUTPUT, $PAGE;
require_once($CFG->libdir.'/completionlib.php');
require_once($CFG->libdir.'/gradelib.php');

$id = optional_param('id', 0, PARAM_INT); // Course Module ID.
$bid = optional_param('b', 0, PARAM_INT); // Giportfolio id.
$edit = optional_param('edit', -1, PARAM_BOOL); // Edit mode.

// Security checks START - teachers edit; students view.
if ($id) {
    $cm = get_coursemodule_from_id('giportfolio', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $giportfolio = $DB->get_record('giportfolio', array('id' => $cm->instance), '*', MUST_EXIST);
} else {
    $giportfolio = $DB->get_record('giportfolio', array('id' => $bid), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('giportfolio', $giportfolio->id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $id = $cm->id;
}

require_course_login($course, true, $cm);

$chapters = giportfolio_preload_chapters($giportfolio);

// SYNERGY - add fake user chapters.
$additionalchapters = giportfolio_preload_userchapters($giportfolio);
if ($additionalchapters) {
    $chapters = $chapters + $additionalchapters;
}
// SYNERGY.

$context = context_module::instance($cm->id);
require_capability('mod/giportfolio:view', $context);

$allowedit = has_capability('mod/giportfolio:edit', $context);
$allowcontribute = has_capability('mod/giportfolio:submitportfolio', $context);
$allowreport = has_capability('report/outline:view', $context->get_course_context());

if ($allowedit) {
    if ($edit != -1 and confirm_sesskey()) {
        $USER->editing = $edit;
    } else {
        if (isset($USER->editing)) {
            $edit = $USER->editing;
        } else {
            $edit = 0;
        }
    }
} else {
    $edit = 0;
}

if ($giportfolio->skipintro) {
    if ($allowcontribute && !$allowedit) {
        // Redirect to the 'update contribution' page.
        redirect(new moodle_url('/mod/giportfolio/viewgiportfolio.php', array('id' => $cm->id)));
    }
}

// Read chapters.

$PAGE->set_url('/mod/giportfolio/view.php', array('id' => $id));

// Unset all page parameters.
unset($id);
unset($bid);

// Security checks  END.

\mod_giportfolio\event\course_module_viewed::create_from_giportfolio($giportfolio, $context)->trigger();

// Read standard strings.
$strgiportfolios = get_string('modulenameplural', 'mod_giportfolio');
$strgiportfolio = get_string('modulename', 'mod_giportfolio');
$strtoc = get_string('toc', 'mod_giportfolio');

// Prepare header.
$PAGE->set_title(format_string($giportfolio->name));
$PAGE->add_body_class('mod_giportfolio');
$PAGE->set_heading(format_string($course->fullname));

// Giportfolio display HTML code.

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($giportfolio->name));
echo $OUTPUT->box_start('generalbox giportfolio_content');

$intro = file_rewrite_pluginfile_urls($giportfolio->intro, 'pluginfile.php', $context->id, 'mod_giportfolio', 'intro', '');
echo format_text($intro, $giportfolio->intro, array('noclean' => true, 'context' => $context));

$usercontribution = 0;
if ($allowedit) {
    $usersgiportfolios = giportfolio_get_giportfolios_number($giportfolio->id, $cm->id);
    echo html_writer::start_tag('div', array('class' => 'giportfolioteacher'));
    echo '</br>';
    // Replace link with button.
    $form = new stdClass();
    $form->url = new moodle_url('/mod/giportfolio/viewgiportfolio.php', array('id' => $cm->id));
    $form->text = get_string('viewtemplate', 'mod_giportfolio');
    echo $OUTPUT->single_button($form->url, $form->text, '', array());

    echo '</br>';
    echo html_writer::link(new moodle_url('/mod/giportfolio/submissions.php', array('id' => $cm->id)),
                           get_string('submitedporto', 'mod_giportfolio').' '.$usersgiportfolios);
    echo html_writer::end_tag('div');
} else if ($allowcontribute) {
    $usercontribution = giportfolio_get_user_contribution_status($giportfolio->id, $USER->id);
    if ($usercontribution) {
        // Get user grade and feedback.
        $usergrade = grade_get_grades($course->id, 'mod', 'giportfolio', $giportfolio->id, $USER->id);
        if ($usergrade->items) {
            $gradeitemgrademax = $usergrade->items[0]->grademax;
            $userfinalgrade = $usergrade->items[0]->grades[$USER->id];
        }

        echo html_writer::start_tag('div', array('class' => 'giportfolioupdated'));
        echo '</br>';
        echo $OUTPUT->single_button(new moodle_url('/mod/giportfolio/viewgiportfolio.php', array('id' => $cm->id)),
                                    get_string('continuecontrib', 'mod_giportfolio'), '', array());
        if ($allowreport && $giportfolio->myactivitylink) {
            $reporturl = new moodle_url('/report/outline/user.php',
                                        array('id' => $USER->id, 'course' => $course->id, 'mode' => 'outline'));
            echo $OUTPUT->single_button($reporturl, get_string('courseoverview', 'mod_giportfolio'), 'get');
        }
        echo '</br>';
        echo get_string('lastupdated', 'mod_giportfolio').date('l jS \of F Y h:i:s A', $usercontribution);
        echo '</br>';
        echo get_string('chapternumber', 'mod_giportfolio').count($chapters);
        echo '</br>';
        echo '</br>';
        if ($usergrade->items && $userfinalgrade->grade) {
            $percentage = explode("/", $userfinalgrade->str_long_grade);
            echo get_string('usergraded', 'mod_giportfolio').number_format($userfinalgrade->grade, 2).
                '  ('.$userfinalgrade->str_long_grade.') - '.round(($percentage[0] / $percentage[1]) * 100, 4).'%';
            echo '</br>';
            if ($userfinalgrade->feedback) {
                echo get_string('usergradefeedback', 'mod_giportfolio').$userfinalgrade->feedback;
            }
        }
        echo '</br>';
        echo html_writer::end_tag('div');
    } else {
        echo html_writer::start_tag('div', array('class' => 'giportfolioupdated'));
        echo '</br>';
        echo $OUTPUT->single_button(new moodle_url('/mod/giportfolio/viewgiportfolio.php', array('id' => $cm->id)),
                                    get_string('startcontrib', 'mod_giportfolio'), '', array());
        echo '</br>';
        echo get_string('chapternumber', 'mod_giportfolio').'  '.count($chapters);
        echo '</br>';
        echo html_writer::end_tag('div');
    }
}

echo $OUTPUT->box_end();
echo $OUTPUT->footer();

