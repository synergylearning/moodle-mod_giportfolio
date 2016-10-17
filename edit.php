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
 * Edit giportfolio chapter
 *
 * @package    mod_giportfolio
 * @copyright  2004-2011 Petr Skoda  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(dirname(__FILE__).'/../../config.php');
require_once(dirname(__FILE__).'/locallib.php');
require_once(dirname(__FILE__).'/edit_form.php');
global $CFG, $OUTPUT, $DB, $PAGE, $SESSION;

$cmid = required_param('cmid', PARAM_INT); // Giportfolio Course Module ID.
$chapterid = optional_param('id', 0, PARAM_INT); // Chapter ID.
$pagenum = optional_param('pagenum', 0, PARAM_INT);
$subchapter = optional_param('subchapter', 0, PARAM_BOOL);

$cm = get_coursemodule_from_id('giportfolio', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$giportfolio = $DB->get_record('giportfolio', array('id' => $cm->instance), '*', MUST_EXIST);

require_login($course, false, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/giportfolio:edit', $context);

$PAGE->set_url('/mod/giportfolio/edit.php', array('cmid' => $cmid, 'id' => $chapterid,
                                                 'pagenum' => $pagenum, 'subchapter' => $subchapter));
$PAGE->set_pagelayout('admin'); // This is a bloody hack!

if ($chapterid) {
    $chapter = $DB->get_record('giportfolio_chapters', array('id' => $chapterid, 'giportfolioid' => $giportfolio->id,
                                                             'userid' => 0), '*', MUST_EXIST);
} else {
    $chapter = new stdClass();
    $chapter->id = null;
    $chapter->subchapter = $subchapter;
    $chapter->pagenum = $pagenum + 1;
}
$chapter->cmid = $cm->id;

$options = array('noclean' => true, 'subdirs' => true, 'maxfiles' => -1, 'maxbytes' => 0, 'context' => $context);
$chapter = file_prepare_standard_editor($chapter, 'content', $options, $context, 'mod_giportfolio', 'chapter', $chapter->id);

$mform = new giportfolio_chapter_edit_form(null, array('chapter' => $chapter, 'options' => $options));

// If data submitted, then process and store.
if ($mform->is_cancelled()) {
    if (empty($chapter->id)) {
        redirect("viewgiportfolio.php?id=$cm->id");
    } else {
        redirect("viewgiportfolio.php?id=$cm->id&chapterid=$chapter->id");
    }

} else if ($data = $mform->get_data()) {

    if ($data->id) {
        // Store the files.
        $data = file_postupdate_standard_editor($data, 'content', $options, $context, 'mod_giportfolio', 'chapter', $data->id);
        $DB->update_record('giportfolio_chapters', $data);


        \mod_giportfolio\event\chapter_updated::create_from_chapter($giportfolio, $context, $data)->trigger();

    } else {
        // Adding new chapter.
        $data->giportfolioid = $giportfolio->id;
        $data->hidden = 0;
        $data->timecreated = time();
        $data->timemodified = time();
        $data->importsrc = '';
        $data->content = ''; // Updated later.
        $data->contentformat = FORMAT_HTML; // Updated later.
        $data->userid = 0; // Database default, but set here as well to be clear.

        // Make room for new page.
        $sql = "UPDATE {giportfolio_chapters}
                   SET pagenum = pagenum + 1
                 WHERE giportfolioid = ? AND pagenum >= ? AND userid = 0";
        $DB->execute($sql, array($giportfolio->id, $data->pagenum));

        $data->id = $DB->insert_record('giportfolio_chapters', $data);

        // Store the files.
        $data = file_postupdate_standard_editor($data, 'content', $options, $context, 'mod_giportfolio', 'chapter', $data->id);
        $DB->update_record('giportfolio_chapters', $data);
        $DB->set_field('giportfolio', 'revision', $giportfolio->revision + 1, array('id' => $giportfolio->id));

        \mod_giportfolio\event\chapter_created::create_from_chapter($giportfolio, $context, $data)->trigger();
    }

    giportfolio_preload_chapters($giportfolio); // Fix structure.

    if ($giportfolio->automaticgrading) {
        giportfolio_regrade($giportfolio);
    }

    redirect("viewgiportfolio.php?id=$cm->id&chapterid=$data->id");
}

// Otherwise fill and print the form.
$PAGE->set_title(format_string($giportfolio->name));
$PAGE->add_body_class('mod_giportfolio');
$PAGE->set_heading(format_string($course->fullname));

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($giportfolio->name));

$mform->display();

echo $OUTPUT->footer();

