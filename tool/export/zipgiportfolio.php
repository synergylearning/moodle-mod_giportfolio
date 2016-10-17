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
 * giportfolio export zip
 *
 * @package    giportfoliotool_export
 * @copyright  2015 Synergy Learning
 * @author     Phil Lello <phil@dunlop-lello.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(dirname(__FILE__).'/../../../../config.php');
require_once($CFG->libdir.'/filestorage/zip_archive.php');
require_once($CFG->dirroot.'/mod/giportfolio/tool/export/locallib.php');

global $CFG, $DB, $USER, $PAGE, $SITE;

$id = required_param('id', PARAM_INT); // Course Module ID.
$chapterid = optional_param('chapterid', 0, PARAM_INT); // Chapter ID.

// Security checks START - teachers and students view.

$cm = get_coursemodule_from_id('giportfolio', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$giportfolio = $DB->get_record('giportfolio', array('id' => $cm->instance), '*', MUST_EXIST);

require_course_login($course, true, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/giportfolio:view', $context);
require_capability('giportfoliotool/print:print', $context);

// Check all variables.
if ($chapterid) {
    // Single chapter exporting - only visible!
    $chapter = $DB->get_record('giportfolio_chapters', array('id' => $chapterid, 'giportfolioid' => $giportfolio->id),
                               '*', MUST_EXIST);
    if ($chapter->userid && $chapter->userid != $USER->id) {
        throw new moodle_exception('notyourchapter', 'mod_giportfolio');
    }
} else {
    // Complete giportfolio.
    $chapter = false;
}

$PAGE->set_url('/mod/giportfolio/zipgiportfolio.php', array('id' => $id, 'chapterid' => $chapterid));

unset($id);
unset($chapterid);

// Security checks END.

// Read chapters.
$chapters = giportfolio_preload_chapters($giportfolio);

$additionalchapters = giportfolio_preload_userchapters($giportfolio);
if ($additionalchapters) {
    $chapters = $chapters + $additionalchapters;
}

$allchapters = $DB->get_records('giportfolio_chapters', array('giportfolioid' => $giportfolio->id, 'userid' => 0), 'pagenum');
$alluserchapters = $DB->get_records('giportfolio_chapters', array('giportfolioid' => $giportfolio->id, 'userid' => $USER->id),
                                    'pagenum');
if ($alluserchapters) {
    $allchapters = $alluserchapters + $allchapters;
}

// Create a work area
$workdir = make_temp_directory('mod_giportfolio/zipgiportfolio');

// Create the zip
$filename = "{$course->id}-{$giportfolio->id}-{$USER->id}.zip";
$zipfile = new zip_archive();
@unlink($workdir.'/'.$filename);
$zipfile->open($workdir.'/'.$filename);

// Start building content...
$output = '';

$output .= "<div class='giportfolio_name'>".$giportfolio->name."</div>";

giportfoliotool_export_add_filearea_to_zip($zipfile, $context->id, 'intro', 0);
$intro = str_replace('@@PLUGINFILE@@', 'intro/', $giportfolio->intro);

$output .= "<div class='giportfolio_intro'>$intro</div>";

$output .= giportfoliotool_export_get_summary($context, $course, $giportfolio);

list($toc, $titles) = giportfoliotool_export_get_toc($chapters, $giportfolio, $cm);
$output .= $toc;

$link1 = $CFG->wwwroot.'/mod/giportfolio/viewgiportfolio.php?id='.$course->id.'&chapterid=';
$link2 = $CFG->wwwroot.'/mod/giportfolio/viewgiportfolio.php?id='.$course->id;

foreach ($chapters as $ch) {
    $chapter = $allchapters[$ch->id];

    // Skip hidden chapters
    if ($chapter->hidden) {
        continue;
    }

    $output .= '<div class="giportfolio_chapter"><a name="ch'.$ch->id.'"></a>';
    if (!$giportfolio->customtitles) {
        $output .= '<p class="giportfolio_chapter_title">'.$titles[$ch->id].'</p>';
    }
    $content = str_replace($link1, '#ch', $chapter->content);
    $content = str_replace($link2, '#top', $content);

    // Add suport for admin images in content.
    giportfoliotool_export_add_filearea_to_zip($zipfile, $context->id, 'chapter', $ch->id);
    $content = str_replace('@@PLUGINFILE@@', 'chapter/', $content);
    $output .= $content;

    $contriblist = giportfolio_get_user_contributions($chapter->id, $chapter->giportfolioid, $USER->id);
    if ($contriblist) {
        foreach ($contriblist as $contrib) {
            $output .= giportfoliotool_export_add_contribution($zipfile, $context, $contrib);
        }
    }
}

$zipfile->add_file_from_pathname('styles.css', dirname(__FILE__).'/styles.css');
$head = "<link rel='stylesheet' type='text/css' href='styles.css'>";
$html = "<html><head>$head</head><body>$output</body></html>";
$zipfile->add_file_from_string('index.html', $html);
$zipfile->close();

// Send the generated zip
header("Content-Type: application/zip");
header("Content-Disposition: attachment; filename=Portfolio.zip");
header("Content-Length: " . filesize("$workdir/$filename"));
readfile("$workdir/$filename");
unlink("$workdir/$filename");
