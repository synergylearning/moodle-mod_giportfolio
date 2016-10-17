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
 * Delete giportfolio chapter
 *
 * @package    mod_giportfolio
 * @copyright  2012 Synergy Learning / Manolescu Dorel based on book module
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(dirname(__FILE__).'/../../config.php');
require_once(dirname(__FILE__).'/locallib.php');

global $DB, $PAGE, $OUTPUT;

$id = required_param('id', PARAM_INT); // Course Module ID.
$chapterid = required_param('chapterid', PARAM_INT); // Chapter ID.
$confirm = optional_param('confirm', 0, PARAM_BOOL);

$cm = get_coursemodule_from_id('giportfolio', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$giportfolio = $DB->get_record('giportfolio', array('id' => $cm->instance), '*', MUST_EXIST);

require_login($course, false, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/giportfolio:edit', $context);

$PAGE->set_url('/mod/giportfolio/delete.php', array('id' => $id, 'chapterid' => $chapterid));

$chapter = $DB->get_record('giportfolio_chapters', array('id' => $chapterid, 'giportfolioid' => $giportfolio->id,
                                                         'userid' => 0), '*', MUST_EXIST);

// Header and strings.
$PAGE->set_title(format_string($giportfolio->name));
$PAGE->add_body_class('mod_giportfolio');
$PAGE->set_heading(format_string($course->fullname));

// Form processing.
if ($confirm) { // The operation was confirmed.
    require_sesskey();
    $fs = get_file_storage();
    if (!$chapter->subchapter) { // Delete all its subchapters if any.
        $chapters = $DB->get_records('giportfolio_chapters', array('giportfolioid' => $giportfolio->id, 'userid' => 0),
                                     'pagenum', 'id, subchapter');
        $found = false;
        foreach ($chapters as $ch) {
            if ($ch->id == $chapter->id) {
                $found = true;
            } else if ($found and $ch->subchapter) {
                $fs->delete_area_files($context->id, 'mod_giportfolio', 'chapter', $ch->id);
                $DB->delete_records('giportfolio_chapters', array('id' => $ch->id));
                giportfolio_delete_chapter_contributions($ch->id, $cm->id, $giportfolio->id);
            } else if ($found) {
                break;
            }
        }
    }
    $fs->delete_area_files($context->id, 'mod_giportfolio', 'chapter', $chapter->id);
    $DB->delete_records('giportfolio_chapters', array('id' => $chapter->id));
    giportfolio_delete_chapter_contributions($chapter->id, $cm->id, $giportfolio->id);

    \mod_giportfolio\event\chapter_deleted::create_from_chapter($giportfolio, $context, $chapter)->trigger();

    giportfolio_preload_chapters($giportfolio); // Fix structure.
    $DB->set_field('giportfolio', 'revision', $giportfolio->revision + 1, array('id' => $giportfolio->id));

    giportfolio_regrade($giportfolio);
    redirect('viewgiportfolio.php?id='.$cm->id);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($giportfolio->name));

// The operation has not been confirmed yet so ask the user to do so.
if ($chapter->subchapter) {
    $strconfirm = get_string('confchapterdelete', 'mod_giportfolio');
    $warnstrname = 'deletecontribwarning';
} else {
    $strconfirm = get_string('confchapterdeleteall', 'mod_giportfolio');
    $warnstrname = 'deletecontribwarningsub';
}
$contribwarning = '';
if ($contrib = giportfolio_chapter_count_contributions($giportfolio->id, $chapter->id)) {
    $contribwarning = html_writer::tag('p', get_string($warnstrname, 'mod_giportfolio', $contrib),
                                       array('class' => 'contribwarning'));
}
echo '<br />';
$continue = new moodle_url('/mod/giportfolio/delete.php', array('id' => $cm->id, 'chapterid' => $chapter->id, 'confirm' => 1));
$cancel = new moodle_url('/mod/giportfolio/viewgiportfolio.php', array('id' => $cm->id, 'chapterid' => $chapter->id));
echo $OUTPUT->confirm("<strong>$chapter->title</strong>$contribwarning<p>$strconfirm</p>", $continue, $cancel);

echo $OUTPUT->footer();
