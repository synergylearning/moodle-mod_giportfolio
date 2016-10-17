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
 * @package    giportfoliotool_print
 * @copyright  2015 Synergy Learning
 * @author     Phil Lello <phil@dunlop-lello.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/mod/giportfolio/locallib.php');

/**
 * Generate summary information for a portfolio
 *
 * @param int $context
 * @param stdClass $user
 * @param stdClass $course
 * @param stdClass $giportfolio
 * @return string HTML
 */
function giportfoliotool_export_get_summary($context, $course, $giportfolio, $user = null) {
    global $CFG, $SITE, $USER;

    if ($user === null) {
        $user = $USER;
    }

    $output = '';

    $site = '<a href="'.$CFG->wwwroot.'">'.format_string($SITE->fullname, true, array('context' => $context)).'</a>';

    $output .= '<table class="giportfolio_summary">';
    $output .= '<tr><td class="fieldname">'.get_string('site').'</td><td class="value">'.$site.'</td></tr>';
    $output .= '<tr><td class="fieldname">'.get_string('course').'</td><td class="value">'.$course->fullname.'</td></tr>';
    $output .= '<tr><td class="fieldname">'.get_string('modulename', 'mod_giportfolio').'</td><td class="value">'.$giportfolio->name.'</td></tr>';
    $output .= '<tr><td class="fieldname">'.get_string('exportedby', 'giportfoliotool_export').'</td><td class="value">'.fullname($user).'</td></tr>';
    $output .= '<tr><td class="fieldname">'.get_string('exportdate', 'giportfoliotool_export').'</td><td class="value">'.userdate(time()).'</td></tr>';
    $output .= '</table>';
    return $output;
}

/**
 * Generate toc structure and titles
 *
 * @param array $chapters
 * @param stdClass $giportfolio
 * @param stdClass $cm
 * @return array
 */
function giportfoliotool_export_get_toc($chapters, $giportfolio, $cm) {
    $first = true;
    $titles = array();

    $context = context_module::instance($cm->id);

    $toc = ''; // Representation of toc (HTML).

    switch ($giportfolio->numbering) {
        case PORTFOLIO_NUM_NONE:
            $toc .= '<div class="giportfolio_toc_none">';
            break;
        case PORTFOLIO_NUM_NUMBERS:
            $toc .= '<div class="giportfolio_toc_numbered">';
            break;
        case PORTFOLIO_NUM_BULLETS:
            $toc .= '<div class="giportfolio_toc_bullets">';
            break;
        case PORTFOLIO_NUM_INDENTED:
            $toc .= '<div class="giportfolio_toc_indented">';
            break;
    }

    $toc .= '<a name="toc"></a>'; // Representation of toc (HTML).

    if ($giportfolio->customtitles) {
        $toc .= '<h1>'.get_string('toc', 'mod_giportfolio').'</h1>';
    } else {
        $toc .= '<p class="giportfolio_chapter_title">'.get_string('toc', 'mod_giportfolio').'</p>';
    }
    $toc .= '<ul>';
    foreach ($chapters as $ch) {
        if (!$ch->hidden) {
            $title = giportfolio_get_chapter_title($ch->id, $chapters, $giportfolio, $context);
            if (!$ch->subchapter) {
                $toc .= $first ? '<li>' : '</ul></li><li>';
            } else {
                $toc .= $first ? '<li><ul><li>' : '<li>';
            }
            $titles[$ch->id] = $title;
            $toc .= '<a title="'.s($title).'" href="#ch'.$ch->id.'">'.$title.'</a>';
            $toc .= (!$ch->subchapter) ? '<ul>' : '</li>';
            $first = false;
        }
    }
    $toc .= '</ul></li></ul>';
    $toc .= '</div>';
    $toc = str_replace('<ul></ul>', '', $toc); // Cleanup of invalid structures.

    return array($toc, $titles);
}

/**
 * Add the contents of a mod_giportfolio filearea to a zip
 *
 * @param $zip zip_archive to add files to
 * @param $contextid int context for filearea
 * @param $filearea string filearea identifier
 * @param $itemid int id of specific item to add
 * @return string HTML snippet of a UL containing LI for each file
 */
function giportfoliotool_export_add_filearea_to_zip($zip, $contextid, $filearea, $itemid) {

    $fs = get_file_storage();
    $files = $fs->get_area_files($contextid, 'mod_giportfolio', $filearea, $itemid, '', false);
    $result = '';
    foreach ($files as $hash => $file) {
        $filename = $file->get_filename();
        $zip->add_file_from_string($filearea.'/'.$filename, $fs->get_file_by_hash($hash)->get_content());
        $result .= "<li class='file'><a href='$filearea/$filename'>$filename</a></li>";
    }
    if ($result) {
        $result = '<p>'.get_string('attachment', 'mod_giportfolio').'</p><ul class="file-list">'.$result.'</ul>';
    }
    return $result;
}

/**
 * Generate HTML for a contribution, add export the files
 *
 * @param zip_archive $zip
 * @param stdClass $context
 * @param stdClass $contrib
 * @return string HTML snippet
 */
function giportfoliotool_export_add_contribution($zip, $context, $contrib) {

    $output = '';

    // Add contribution title, fixing links to embedded resources.
    giportfoliotool_export_add_filearea_to_zip($zip, $context->id, 'chapter', $contrib->chapterid);
    $contribtitle = str_replace('@@PLUGINFILE@@', 'chapter/', $contrib->title);
    $output .= "<div class='giportfolio_contribution_title'>$contribtitle</div>";
    $output .= "<div class='giportfolio_contribution_data'>".userdate($contrib->timemodified)."</div>";

    // Add contribution title, fixing links to embedded resources.
    $contribtext = $contrib->content;
    giportfoliotool_export_add_filearea_to_zip($zip, $context->id, 'contribution', $contrib->id);
    $contribtext = str_replace('@@PLUGINFILE@@', 'contribution/', $contribtext);
    $output .= "<div class='giportfolio_contribution_content'>$contribtext</div>";

    // Add attachments.
    $output .= "<div class='giportfolio_contribution_attachments'>";
    $output .= giportfoliotool_export_add_filearea_to_zip($zip, $context->id, 'attachment', $contrib->id);
    $output .= "</div>";

    return $output;
}
