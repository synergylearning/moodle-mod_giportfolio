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
 * This page lists all the instances of giportfolio in a particular course
 *
 * @package    mod_giportfolio
 * @copyright  2004-2011 Petr Skoda  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(dirname(__FILE__).'/../../config.php');
require_once(dirname(__FILE__).'/locallib.php');

$id = required_param('id', PARAM_INT); // Course Module ID.
global $DB, $PAGE, $OUTPUT, $CFG;

$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);

unset($id);

require_course_login($course, true);
$PAGE->set_pagelayout('incourse');

// Get all required strings.
$strgiportfolios = get_string('modulenameplural', 'mod_giportfolio');
$strgiportfolio = get_string('modulename', 'mod_giportfolio');
$strsectionname = get_string('sectionname', 'format_'.$course->format);
$strname = get_string('name');
$strintro = get_string('moduleintro');
$strlastmodified = get_string('lastmodified');

$PAGE->set_url('/mod/giportfolio/index.php', array('id' => $course->id));
$PAGE->set_title($course->shortname.': '.$strgiportfolios);
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add($strgiportfolios);
echo $OUTPUT->header();

\mod_giportfolio\event\course_module_instance_list_viewed::create_from_course($course)->trigger();

// Get all the appropriate data.
if (!$giportfolios = get_all_instances_in_course('giportfolio', $course)) {
    notice(get_string('thereareno', 'moodle', $strgiportfolios), "$CFG->wwwroot/course/viewgiportfolio.php?id=$course->id");
    die;
}

$sections = array();
$usesections = course_format_uses_sections($course->format);
if ($usesections) {
    $modinfo = get_fast_modinfo($course);
    $sections = $modinfo->get_section_info_all();
}

$table = new html_table();
$table->attributes['class'] = 'generaltable mod_index';

if ($usesections) {
    $table->head = array($strsectionname, $strname, $strintro);
    $table->align = array('center', 'left', 'left');
} else {
    $table->head = array($strlastmodified, $strname, $strintro);
    $table->align = array('left', 'left', 'left');
}

$modinfo = get_fast_modinfo($course);
$currentsection = '';
foreach ($giportfolios as $giportfolio) {
    $cm = $modinfo->get_cm($giportfolio->coursemodule);
    if ($usesections) {
        $printsection = '';
        if ($giportfolio->section !== $currentsection) {
            if ($giportfolio->section) {
                $printsection = get_section_name($course, $sections[$giportfolio->section]);
            }
            if ($currentsection !== '') {
                $table->data[] = 'hr';
            }
            $currentsection = $giportfolio->section;
        }
    } else {
        $printsection = '<span class="smallinfo">'.userdate($giportfolio->timemodified)."</span>";
    }

    $class = $giportfolio->visible ? '' : 'class="dimmed"'; // Hidden modules are dimmed.

    $table->data[] = array(
        $printsection,
        "<a $class href=\"viewgiportfolio.php?id=$cm->id\">".format_string($giportfolio->name)."</a>",
        format_module_intro('giportfolio', $giportfolio, $cm->id)
    );
}

echo html_writer::table($table);

echo $OUTPUT->footer();
