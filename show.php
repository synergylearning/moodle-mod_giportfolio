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
 * Show/hide giportfolio chapter
 *
 * @package    mod_giportfolio
 * @copyright  2012 Synergy Learning / Manolescu Dorel (based on book module)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(dirname(__FILE__).'/../../config.php');
require_once(dirname(__FILE__).'/locallib.php');

global $DB, $PAGE;
$id = required_param('id', PARAM_INT); // Course Module ID.
$chapterid = required_param('chapterid', PARAM_INT); // Chapter ID.

$cm = get_coursemodule_from_id('giportfolio', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$giportfolio = $DB->get_record('giportfolio', array('id' => $cm->instance), '*', MUST_EXIST);

require_login($course, false, $cm);
require_sesskey();

$context = context_module::instance($cm->id);
require_capability('mod/giportfolio:edit', $context);

$PAGE->set_url('/mod/giportfolio/show.php', array('id' => $id, 'chapterid' => $chapterid));

$chapter = $DB->get_record('giportfolio_chapters', array('id' => $chapterid, 'giportfolioid' => $giportfolio->id, 'userid' => 0),
                           '*', MUST_EXIST);

// Switch hidden state.
$chapter->hidden = $chapter->hidden ? 0 : 1;

// Update record.
$DB->update_record('giportfolio_chapters', $chapter);

// Change visibility of subchapters too.
if (!$chapter->subchapter) {
    $chapters = $DB->get_records('giportfolio_chapters', array('giportfolioid' => $giportfolio->id, 'userid' => 0),
                                 'pagenum', 'id, subchapter, hidden');
    $found = 0;
    foreach ($chapters as $ch) {
        if ($ch->id == $chapter->id) {
            $found = 1;
        } else if ($found and $ch->subchapter) {
            $ch->hidden = $chapter->hidden;
            $DB->update_record('giportfolio_chapters', $ch);
        } else if ($found) {
            break;
        }
    }
}

\mod_giportfolio\event\chapter_updated::create_from_chapter($giportfolio, $context, $chapter)->trigger();

giportfolio_preload_chapters($giportfolio); // Fix structure.
$DB->set_field('giportfolio', 'revision', $giportfolio->revision + 1, array('id' => $giportfolio->id));

redirect('viewgiportfolio.php?id='.$cm->id.'&chapterid='.$chapter->id);

