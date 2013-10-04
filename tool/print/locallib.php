<?php
// This file is part of giportfolio plugin for Moodle - http://moodle.org/
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
 * HTML import lib
 *
 * @package    giportfoliotool
 * @subpackage print
 * @copyright  2011 Petr Skoda  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once(dirname(__FILE__).'/lib.php');
global $CFG;
require_once($CFG->dirroot.'/mod/giportfolio/locallib.php');

/**
 * Generate toc structure and titles
 *
 * @param array $chapters
 * @param stdClass $giportfolio
 * @param stdClass $cm
 * @return array
 */
function giportfoliotool_print_get_toc($chapters, $giportfolio, $cm) {
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
