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
 * giportfolio module local lib functions
 *
 * @package    mod_giportfolio
 * @copyright  2012 Synergy Learning / Manolescu Dorel based on book module
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot.'/mod/giportfolio/lib.php');
require_once($CFG->libdir.'/filelib.php');

define('PORTFOLIO_NUM_NONE', '0');
define('PORTFOLIO_NUM_NUMBERS', '1');
define('PORTFOLIO_NUM_BULLETS', '2');
define('PORTFOLIO_NUM_INDENTED', '3');

/**
 * Preload giportfolio chapters and fix toc structure if necessary.
 *
 * Returns array of chapters with standard 'pagenum', 'id, pagenum, subchapter, title, hidden'
 * and extra 'parent, number, subchapters, prev, next'.
 * Please note the content/text of chapters is not included.
 *
 * @param  stdClass $giportfolio
 * @return array of id=>chapter
 */
function giportfolio_preload_chapters($giportfolio) {
    global $DB;
    $chapters = $DB->get_records('giportfolio_chapters', array('giportfolioid' => $giportfolio->id, 'userid' => 0),
                                 'pagenum', 'id, pagenum, subchapter, title, hidden, userid');
    if (!$chapters) {
        return array();
    }

    $prev = null;
    $prevsub = null;

    $first = true;
    $hidesub = true;
    $parent = null;
    $pagenum = 0; // Chapter sort.
    $i = 0; // Main chapter num.
    $j = 0; // Subchapter num.
    foreach ($chapters as $id => $ch) {
        $oldch = clone($ch);
        $pagenum++;
        $ch->pagenum = $pagenum;
        if ($first) {
            // Giportfolio can not start with a subchapter.
            $ch->subchapter = 0;
            $first = false;
        }
        if (!$ch->subchapter) {
            $ch->prev = $prev;
            $ch->next = null;
            if ($prev) {
                $chapters[$prev]->next = $ch->id;
            }
            if ($ch->hidden) {
                if ($giportfolio->numbering == PORTFOLIO_NUM_NUMBERS) {
                    $ch->number = 'x';
                } else {
                    $ch->number = null;
                }
            } else {
                $i++;
                $ch->number = $i;
            }
            $j = 0;
            $prevsub = null;
            $hidesub = $ch->hidden;
            $parent = $ch->id;
            $ch->parent = null;
            $ch->subchpaters = array();
        } else {
            $ch->prev = $prevsub;
            $ch->next = null;
            if ($prevsub) {
                $chapters[$prevsub]->next = $ch->id;
            }
            $ch->parent = $parent;
            $ch->subchpaters = null;
            $chapters[$parent]->subchapters[$ch->id] = $ch->id;
            if ($hidesub) {
                // All subchapters in hidden chapter must be hidden too.
                $ch->hidden = 1;
            }
            if ($ch->hidden) {
                if ($giportfolio->numbering == PORTFOLIO_NUM_NUMBERS) {
                    $ch->number = 'x';
                } else {
                    $ch->number = null;
                }
            } else {
                $j++;
                $ch->number = $j;
            }
        }
        if ($oldch->subchapter != $ch->subchapter or $oldch->pagenum != $ch->pagenum or $oldch->hidden != $ch->hidden) {
            // Update only if something changed.
            $DB->update_record('giportfolio_chapters', $ch);
        }
        $chapters[$id] = $ch;
    }

    return $chapters;
}

/**
 * Preload giportfolio chapters and fix toc structure if necessary.
 *
 * Returns array of chapters with standard 'pagenum', 'id, pagenum, subchapter, title, hidden'
 * and extra 'parent, number, subchapters, prev, next'.
 * Please note the content/text of chapters is not included.
 *
 * @param  stdClass $giportfolio
 * @param int $userid (optional) defaults to current user
 * @return array of id=>chapter
 */
function giportfolio_preload_userchapters($giportfolio, $userid = null) {
    global $DB, $USER;
    if (!$userid) {
        $userid = $USER->id;
    }
    $chapters = $DB->get_records('giportfolio_chapters', array('giportfolioid' => $giportfolio->id, 'userid' => $userid),
                                 'pagenum', 'id, pagenum, subchapter, title, hidden, userid');
    if (!$chapters) {
        return array();
    }

    $lastpage = giportfolio_get_last_chapter($giportfolio->id);

    $prev = null;
    $prevsub = null;

    $first = true;
    $hidesub = true;
    $parent = null;
    $pagenum = $lastpage->pagenum; // Chapter sort.
    $i = $lastpage->pagenum; // Main chapter num.
    $j = 0; // Subchapter num.
    foreach ($chapters as $id => $ch) {
        $oldch = clone($ch);
        $pagenum++;
        $ch->pagenum = $pagenum;
        if ($first) {
            // Giportfolio can not start with a subchapter.
            $ch->subchapter = 0;
            $first = false;
        }
        if (!$ch->subchapter) {
            $ch->prev = $prev;
            $ch->next = null;
            if ($prev) {
                $chapters[$prev]->next = $ch->id;
            }
            if ($ch->hidden) {
                if ($giportfolio->numbering == PORTFOLIO_NUM_NUMBERS) {
                    $ch->number = 'x';
                } else {
                    $ch->number = null;
                }
            } else {
                $i++;
                $ch->number = $i;
            }
            $j = 0;
            $prevsub = null;
            $hidesub = $ch->hidden;
            $parent = $ch->id;
            $ch->parent = null;
            $ch->subchpaters = array();
        } else {
            $ch->prev = $prevsub;
            $ch->next = null;
            if ($prevsub) {
                $chapters[$prevsub]->next = $ch->id;
            }
            $ch->parent = $parent;
            $ch->subchpaters = null;
            $chapters[$parent]->subchapters[$ch->id] = $ch->id;
            if ($hidesub) {
                // All subchapters in hidden chapter must be hidden too.
                $ch->hidden = 1;
            }
            if ($ch->hidden) {
                if ($giportfolio->numbering == PORTFOLIO_NUM_NUMBERS) {
                    $ch->number = 'x';
                } else {
                    $ch->number = null;
                }
            } else {
                $j++;
                $ch->number = $j;
            }
        }
        if ($oldch->subchapter != $ch->subchapter or $oldch->pagenum != $ch->pagenum or $oldch->hidden != $ch->hidden) {
            // Update only if something changed.
            $DB->update_record('giportfolio_chapters', $ch);
        }
        $chapters[$id] = $ch;
    }

    return $chapters;
}

function giportfolio_get_chapter_title($chid, $chapters, $giportfolio, $context) {

    $ch = $chapters[$chid];

    $title = trim(format_string($ch->title, true, array('context' => $context)));
    $numbers = array();
    if ($giportfolio->numbering == PORTFOLIO_NUM_NUMBERS) {
        if ($ch->parent and $chapters[$ch->parent]->number) {
            $numbers[] = $chapters[$ch->parent]->number;
        }
        if ($ch->number) {

            $numbers[] = $ch->number;
        }
    }

    if ($numbers) {
        $title = implode('.', $numbers).' '.$title;
    }

    return $title;
}

/**
 * General logging to table
 * @param string $str1
 * @param string $str2
 * @param int $level
 * @return void
 */
function giportfolio_log($str1, $str2, $level = 0) {
    switch ($level) {
        case 1:
            echo '<tr><td><span class="dimmed_text">'.$str1.'</span></td><td><span class="dimmed_text">'.$str2.'</span></td></tr>';
            break;
        case 2:
            echo '<tr><td><span style="color: rgb(255, 0, 0);">'.$str1.'</span></td><td><span style="color: rgb(255, 0, 0);">'.
                $str2.'</span></td></tr>';
            break;
        default:
            echo '<tr><td>'.$str1.'</class></td><td>'.$str2.'</td></tr>';
            break;
    }
}

function giportfolio_add_fake_block($chapters, $chapter, $giportfolio, $cm, $edit, $userdit) {
    global $OUTPUT, $PAGE, $USER, $COURSE;

    $context = context_module::instance($cm->id);
    $context = $context->get_course_context();
    $allowreport = has_capability('report/outline:view', $context);

    if (giportfolio_get_collaborative_status($giportfolio) && !$edit) {
        $toc = giportfolio_get_usertoc($chapters, $chapter, $giportfolio, $cm, $edit, $USER->id, $userdit);
    } else {
        $toc = giportfolio_get_toc($chapters, $chapter, $giportfolio, $cm, $edit, 0);
    }

    if ($edit) {
        $toc .= '<div class="giportfolio_faq">';
        $toc .= $OUTPUT->help_icon('faq', 'mod_giportfolio', get_string('faq', 'mod_giportfolio'));
        $toc .= '</div>';
    }

    $bc = new block_contents();
    $bc->title = get_string('toc', 'mod_giportfolio');
    $bc->attributes['class'] = 'block';
    $bc->content = $toc;
    if ($allowreport && $giportfolio->myactivitylink) {
        $reportlink = new moodle_url('/report/outline/user.php',
                                     array('id' => $USER->id, 'course' => $COURSE->id, 'mode' => 'outline'));
        $bc->content .= $OUTPUT->single_button($reportlink, get_string('courseoverview', 'mod_giportfolio'), 'get');
    }

    $regions = $PAGE->blocks->get_regions();
    $firstregion = reset($regions);
    $PAGE->blocks->add_fake_block($bc, $firstregion);

    // SYNERGY - add javascript to control subchapter collapsing.
    if (!$edit) {
        $jsmodule = array(
            'name' => 'mod_giportfolio_collapse',
            'fullpath' => new moodle_url('/mod/giportfolio/collapse.js'),
            'requires' => array('yui2-treeview')
        );

        $PAGE->requires->js_init_call('M.mod_giportfolio_collapse.init', array(), true, $jsmodule);
    }
    // SYNERGY - add javascript to control subchapter collapsing.
}

function giportfolio_add_fakeuser_block($chapters, $chapter, $giportfolio, $cm, $edit, $userid) {
    global $OUTPUT, $PAGE;

    if (!$edit) {
        $toc = giportfolio_get_userviewtoc($chapters, $chapter, $giportfolio, $cm, $edit, $userid);
    } else {
        $toc = giportfolio_get_toc($chapters, $chapter, $giportfolio, $cm, $edit, 0);
    }

    if ($edit) {
        $toc .= '<div class="giportfolio_faq">';
        $toc .= $OUTPUT->help_icon('faq', 'mod_giportfolio', get_string('faq', 'mod_giportfolio'));
        $toc .= '</div>';
    }

    $bc = new block_contents();
    $bc->title = get_string('usertoc', 'mod_giportfolio');
    $bc->attributes['class'] = 'block';
    $bc->content = $toc;

    $regions = $PAGE->blocks->get_regions();
    $firstregion = reset($regions);
    $PAGE->blocks->add_fake_block($bc, $firstregion);

    // SYNERGY - add javascript to control subchapter collapsing.
    if (!$edit) {
        $jsmodule = array(
            'name' => 'mod_giportfolio_collapse',
            'fullpath' => new moodle_url('/mod/giportfolio/collapse.js'),
            'requires' => array('yui2-treeview')
        );
        $PAGE->requires->js_init_call('M.mod_giportfolio_collapse.init', array(), true, $jsmodule);
    }
    // SYNERGY - add javascript to control subchapter collapsing.
}

/**
 * Generate toc structure
 *
 * @param array $chapters
 * @param stdClass $chapter
 * @param stdClass $giportfolio
 * @param stdClass $cm
 * @param bool $edit
 * @return string
 */
function giportfolio_get_toc($chapters, $chapter, $giportfolio, $cm, $edit) {
    global $USER, $OUTPUT;

    $toc = ''; // Representation of toc (HTML).
    $nch = 0; // Chapter number.
    $ns = 0; // Subchapter number.
    $first = 1;

    $context = context_module::instance($cm->id);

    // SYNERGY - add 'giportfolio-toc' ID.
    $tocid = ' id="giportfolio-toc" ';
    switch ($giportfolio->numbering) {
        case PORTFOLIO_NUM_NONE:
            $toc .= '<div class="giportfolio_toc_none" '.$tocid.'>';
            break;
        case PORTFOLIO_NUM_NUMBERS:
            $toc .= '<div class="giportfolio_toc_numbered" '.$tocid.'>';
            break;
        case PORTFOLIO_NUM_BULLETS:
            $toc .= '<div class="giportfolio_toc_bullets" '.$tocid.'>';
            break;
        case PORTFOLIO_NUM_INDENTED:
            $toc .= '<div class="giportfolio_toc_indented" '.$tocid.'>';
            break;
    }
    // SYNERGY - add 'giportfolio-toc' ID.

    if ($edit) { // Teacher's TOC.
        $toc .= '<ul>';
        $i = 0;
        foreach ($chapters as $ch) {
            $i++;
            $title = trim(format_string($ch->title, true, array('context' => $context)));
            if (!$ch->subchapter) {
                $toc .= ($first) ? '<li>' : '</ul></li><li>';
                if (!$ch->hidden) {
                    $nch++;
                    $ns = 0;
                    if ($giportfolio->numbering == PORTFOLIO_NUM_NUMBERS) {
                        $title = "$nch $title";
                    }
                } else {
                    if ($giportfolio->numbering == PORTFOLIO_NUM_NUMBERS) {
                        $title = "x $title";
                    }
                    $title = '<span class="dimmed_text">'.$title.'</span>';
                }
            } else {
                $toc .= ($first) ? '<li><ul><li>' : '<li>';
                if (!$ch->hidden) {
                    $ns++;
                    if ($giportfolio->numbering == PORTFOLIO_NUM_NUMBERS) {
                        $title = "$nch.$ns $title";
                    }
                } else {
                    if ($giportfolio->numbering == PORTFOLIO_NUM_NUMBERS) {
                        $title = "x.x $title";
                    }
                    $title = '<span class="dimmed_text">'.$title.'</span>';
                }
            }

            if ($ch->id == $chapter->id) {
                $toc .= '<strong>'.$title.'</strong>';
            } else {
                $toc .= '<a title="'.s($title).'" href="viewgiportfolio.php?id='.$cm->id.'&amp;chapterid='.$ch->id.'">'.
                    $title.'</a>';
            }
            $toc .= '&nbsp;&nbsp;';
            if ($i != 1) {
                $toc .= ' <a title="'.get_string('up').'" href="move.php?id='.$cm->id.'&amp;chapterid='.$ch->id.
                    '&amp;up=1&amp;sesskey='.$USER->sesskey.'">
                    <img src="'.$OUTPUT->pix_url('t/up').'" class="iconsmall" alt="'.get_string('up').'" /></a>';
            }
            if ($i != count($chapters)) {
                $toc .= ' <a title="'.get_string('down').'" href="move.php?id='.$cm->id.'&amp;chapterid='.$ch->id.
                    '&amp;up=0&amp;sesskey='.$USER->sesskey.'">
                    <img src="'.$OUTPUT->pix_url('t/down').'" class="iconsmall" alt="'.get_string('down').'" /></a>';
            }
            $toc .= ' <a title="'.get_string('edit').'" href="edit.php?cmid='.$cm->id.'&amp;id='.$ch->id.'">
            <img src="'.$OUTPUT->pix_url('t/edit').'" class="iconsmall" alt="'.get_string('edit').'" /></a>';
            $toc .= ' <a title="'.get_string('delete').'" href="delete.php?id='.$cm->id.'&amp;chapterid='.$ch->id.
                '&amp;sesskey='.$USER->sesskey.'">
                <img src="'.$OUTPUT->pix_url('t/delete').'" class="iconsmall" alt="'.get_string('delete').'" /></a>';
            if ($ch->hidden) {
                $toc .= ' <a title="'.get_string('show').'" href="show.php?id='.$cm->id.'&amp;chapterid='.$ch->id.
                    '&amp;sesskey='.$USER->sesskey.'">
                    <img src="'.$OUTPUT->pix_url('t/show').'" class="iconsmall" alt="'.get_string('show').'" /></a>';
            } else {
                $toc .= ' <a title="'.get_string('hide').'" href="show.php?id='.$cm->id.'&amp;chapterid='.$ch->id.
                    '&amp;sesskey='.$USER->sesskey.'">
                    <img src="'.$OUTPUT->pix_url('t/hide').'" class="iconsmall" alt="'.get_string('hide').'" /></a>';
            }
            // Synergy  only if the giportfolio activity has not yet contributions.
            $toc .= ' <a title="'.get_string('addafter', 'mod_giportfolio').'" href="edit.php?cmid='.$cm->id.
                '&amp;pagenum='.$ch->pagenum.'&amp;subchapter='.$ch->subchapter.'">
                <img src="'.$OUTPUT->pix_url('add', 'mod_giportfolio').'" class="iconsmall" alt="'.
                get_string('addafter', 'mod_giportfolio').'" /></a>';
            $toc .= (!$ch->subchapter) ? '<ul>' : '</li>';
            $first = 0;
        }
        $toc .= '</ul></li></ul>';
    } else { // Normal students view.
        $toc .= '<ul>';
        // SYNERGY - Find the open chapter.
        $currentch = 0;
        $opench = 0;
        foreach ($chapters as $ch) {
            if (!$currentch || !$ch->subchapter) {
                $currentch = $ch->id;
            }
            if ($ch->id == $chapter->id) {
                $opench = $currentch;
                break;
            }
        }
        // SYNERGY - Find the open chapter.
        foreach ($chapters as $ch) {
            $title = trim(format_string($ch->title, true, array('context' => $context)));
            if (!$ch->hidden) {
                if (!$ch->subchapter) {
                    $nch++;
                    $ns = 0;
                    // SYNERGY - Make sure the right subchapters are expanded by default.
                    $li = '<li>';
                    if ($ch->id == $opench || !$giportfolio->collapsesubchapters) {
                        $li = '<li class="expanded">';
                    }
                    $toc .= ($first) ? $li : '</ul></li>'.$li;
                    // SYNERGY - Make sure the right subchapters are expanded by default.
                    if ($giportfolio->numbering == PORTFOLIO_NUM_NUMBERS) {
                        $title = "$nch $title";
                    }
                } else {
                    $ns++;
                    $toc .= ($first) ? '<li><ul><li>' : '<li>';
                    if ($giportfolio->numbering == PORTFOLIO_NUM_NUMBERS) {
                        $title = "$nch.$ns $title";
                    }
                }
                if ($ch->id == $chapter->id) {
                    $toc .= '<strong>'.$title.'</strong>';
                } else {
                    $toc .= '<a title="'.s($title).'" href="viewgiportfolio.php?id='.$cm->id.'&amp;chapterid='.$ch->id.'">'.
                        $title.'</a>';
                }
                $toc .= (!$ch->subchapter) ? '<ul>' : '</li>';
                $first = 0;
            }
        }
        $toc .= '</ul></li></ul>';
    }

    $toc .= '</div>';

    $toc = str_replace('<ul></ul>', '', $toc); // Cleanup of invalid structures.

    return $toc;
}

/**
 * Generate user toc structure
 *
 * @param array $chapters
 * @param stdClass $chapter
 * @param stdClass $giportfolio
 * @param stdClass $cm
 * @param bool $edit
 * @param $userid
 * @param $useredit
 * @return string
 */
function giportfolio_get_usertoc($chapters, $chapter, $giportfolio, $cm, $edit, $userid, $useredit) {
    global $USER, $OUTPUT;

    $toc = ''; // Representation of toc (HTML).
    $nch = 0; // Chapter number.
    $ns = 0; // Subchapter number.
    $first = 1;

    $context = context_module::instance($cm->id);

    // SYNERGY - add 'giportfolio-toc' ID.
    $tocid = ' id="giportfolio-toc" ';
    switch ($giportfolio->numbering) {
        case PORTFOLIO_NUM_NONE:
            $toc .= '<div class="giportfolio_toc_none" '.$tocid.'>';
            break;
        case PORTFOLIO_NUM_NUMBERS:
            $toc .= '<div class="giportfolio_toc_numbered" '.$tocid.'>';
            break;
        case PORTFOLIO_NUM_BULLETS:
            $toc .= '<div class="giportfolio_toc_bullets" '.$tocid.'>';
            break;
        case PORTFOLIO_NUM_INDENTED:
            $toc .= '<div class="giportfolio_toc_indented" '.$tocid.'>';
            break;
    }
    // SYNERGY - add 'giportfolio-toc' ID.

    $allowuser = giportfolio_get_collaborative_status($giportfolio);
    if ($allowuser && $useredit) { // Edit students view.
        $toc .= '<ul>';
        $i = 0;
        // SYNERGY - Find the open chapter.
        $currentch = 0;
        $opench = 0;
        foreach ($chapters as $ch) {
            if (!$currentch || !$ch->subchapter) {
                $currentch = $ch->id;
            }
            if ($ch->id == $chapter->id) {
                $opench = $currentch;
                break;
            }
        }
        // SYNERGY - Find the open chapter.
        echo '<br/>';
        foreach ($chapters as $ch) {
            $i++;
            $title = trim(format_string($ch->title, true, array('context' => $context)));
            if (!$ch->subchapter) {
                $toc .= ($first) ? '<li>' : '</ul></li><li>';
                if (!$ch->hidden) {
                    $nch++;
                    $ns = 0;
                    // SYNERGY - Make sure the right subchapters are expanded by default.
                    $li = '<li>';
                    if ($ch->id == $opench || !$giportfolio->collapsesubchapters) {
                        $li = '<li class="expanded">';
                    }
                    $toc .= ($first) ? $li : '</ul></li>'.$li;
                    // SYNERGY - Make sure the right subchapters are expanded by default.
                    if ($giportfolio->numbering == PORTFOLIO_NUM_NUMBERS) {
                        $title = "$nch $title";
                    }
                } else {
                    if ($giportfolio->numbering == PORTFOLIO_NUM_NUMBERS) {
                        $title = "x $title";
                    }
                    $title = '<span class="dimmed_text">'.$title.'</span>';
                }
            } else {
                $toc .= ($first) ? '<li><ul><li>' : '<li>';
                if (!$ch->hidden) {
                    $ns++;
                    if ($giportfolio->numbering == PORTFOLIO_NUM_NUMBERS) {
                        $title = "$nch.$ns $title";
                    }
                } else {
                    if ($giportfolio->numbering == PORTFOLIO_NUM_NUMBERS) {
                        $title = "x.x $title";
                    }
                    $title = '<span class="dimmed_text">'.$title.'</span>';
                }
            }

            if ($ch->id == $chapter->id) {
                $toc .= '<strong>'.$title.'</strong>';
            } else {
                $toc .= '<a title="'.s($title).'" href="viewgiportfolio.php?id='.$cm->id.'&amp;chapterid='.$ch->id.
                    '&amp;useredit=1'.'">'.$title.'</a>';
            }
            $toc .= '&nbsp;&nbsp;';
            if (giportfolio_check_user_chapter($ch, $userid)) {
                if ($i != 1) {
                    if (!giportfolio_get_first_userchapter($giportfolio->id, $ch->id, $userid)) {
                        $toc .= ' <a title="'.get_string('up').'" href="moveuserchapter.php?id='.$cm->id.
                            '&amp;chapterid='.$ch->id.'&amp;up=1&amp;sesskey='.$USER->sesskey.'">
                            <img src="'.$OUTPUT->pix_url('t/up').'" class="iconsmall" alt="'.get_string('up').'" /></a>';

                    }
                }
                if ($i != count($chapters)) {
                    $toc .= ' <a title="'.get_string('down').'" href="moveuserchapter.php?id='.$cm->id.
                        '&amp;chapterid='.$ch->id.'&amp;up=0&amp;sesskey='.$USER->sesskey.'">
                        <img src="'.$OUTPUT->pix_url('t/down').'" class="iconsmall" alt="'.get_string('down').'" /></a>';
                }
            }

            if (giportfolio_check_user_chapter($ch, $userid)) {
                $toc .= ' <a title="'.get_string('edit').'" href="editstudent.php?cmid='.$cm->id.'&amp;id='.$ch->id.'">
                <img src="'.$OUTPUT->pix_url('t/edit').'" class="iconsmall" alt="'.get_string('edit').'" /></a>';
            }

            if (giportfolio_check_user_chapter($ch, $userid)) {
                $toc .= ' <a title="'.get_string('delete').'" href="deleteuserchapter.php?id='.$cm->id.'&amp;chapterid='.$ch->id.
                    '&amp;sesskey='.$USER->sesskey.'">
                    <img src="'.$OUTPUT->pix_url('t/delete').'" class="iconsmall" alt="'.get_string('delete').'" /></a>';
            }

            if (giportfolio_check_user_chapter($ch, $userid) ||
                giportfolio_get_last_chapter($giportfolio->id, $ch->id)) {

                $toc .= ' <a title="'.get_string('addafter', 'mod_giportfolio').'" href="editstudent.php?cmid='.$cm->id.
                    '&amp;pagenum='.$ch->pagenum.'&amp;subchapter='.$ch->subchapter.'">
                    <img src="'.$OUTPUT->pix_url('add', 'mod_giportfolio').'" class="iconsmall" alt="'.
                    get_string('addafter', 'mod_giportfolio').'" /></a>';
            }
            $toc .= (!$ch->subchapter) ? '<ul>' : '</li>';
            $first = 0;
        }
        $toc .= '</ul></li></ul>';
    } else {
        // Normal stdent nonediting view.
        $toc .= '<ul>';
        // SYNERGY - Find the open chapter.
        $currentch = 0;
        $opench = 0;
        foreach ($chapters as $ch) {
            if (!$currentch || !$ch->subchapter) {
                $currentch = $ch->id;
            }
            if ($ch->id == $chapter->id) {
                $opench = $currentch;
                break;
            }
        }
        // SYNERGY - Find the open chapter.
        foreach ($chapters as $ch) {
            $title = trim(format_string($ch->title, true, array('context' => $context)));
            if (!$ch->hidden) {
                if (!$ch->subchapter) {
                    $nch++;
                    $ns = 0;
                    // SYNERGY - Make sure the right subchapters are expanded by default.
                    $li = '<li>';
                    if ($ch->id == $opench || !$giportfolio->collapsesubchapters) {
                        $li = '<li class="expanded">';
                    }
                    $toc .= ($first) ? $li : '</ul></li>'.$li;
                    // SYNERGY - Make sure the right subchapters are expanded by default.
                    if ($giportfolio->numbering == PORTFOLIO_NUM_NUMBERS) {
                        $title = "$nch $title";
                    }
                } else {
                    $ns++;
                    $toc .= ($first) ? '<li><ul><li>' : '<li>';
                    if ($giportfolio->numbering == PORTFOLIO_NUM_NUMBERS) {
                        $title = "$nch.$ns $title";
                    }
                }
                if ($ch->id == $chapter->id) {
                    $toc .= '<strong>'.$title.'</strong>';
                } else {
                    $toc .= '<a title="'.s($title).'" href="viewgiportfolio.php?id='.$cm->id.'&amp;chapterid='.$ch->id.'">'.
                        $title.'</a>';
                }
                $toc .= (!$ch->subchapter) ? '<ul>' : '</li>';
                $first = 0;
            }
        }
        $toc .= '</ul></li></ul>';

    }

    $toc .= '</div>';

    $toc = str_replace('<ul></ul>', '', $toc); // Cleanup of invalid structures.

    return $toc;
}

function giportfolio_get_userviewtoc($chapters, $chapter, $giportfolio, $cm, $edit, $userid) {
    $toc = ''; // Representation of toc (HTML).
    $nch = 0; // Chapter number.
    $ns = 0; // Subchapter number.
    $first = 1;

    $context = context_module::instance($cm->id);

    // SYNERGY - add 'giportfolio-toc' ID.
    $tocid = ' id="giportfolio-toc" ';
    switch ($giportfolio->numbering) {
        case PORTFOLIO_NUM_NONE:
            $toc .= '<div class="giportfolio_toc_none" '.$tocid.'>';
            break;
        case PORTFOLIO_NUM_NUMBERS:
            $toc .= '<div class="giportfolio_toc_numbered" '.$tocid.'>';
            break;
        case PORTFOLIO_NUM_BULLETS:
            $toc .= '<div class="giportfolio_toc_bullets" '.$tocid.'>';
            break;
        case PORTFOLIO_NUM_INDENTED:
            $toc .= '<div class="giportfolio_toc_indented" '.$tocid.'>';
            break;
    }
    // SYNERGY - add 'giportfolio-toc' ID.

    if ($tocid) { // Normal students view.
        $toc .= '<ul>';
        $i = 0;

        // SYNERGY - Find the open chapter.
        $currentch = 0;
        $opench = 0;
        foreach ($chapters as $ch) {
            if (!$currentch || !$ch->subchapter) {
                $currentch = $ch->id;
            }
            if ($ch->id == $chapter->id) {
                $opench = $currentch;
                break;
            }
        }
        foreach ($chapters as $ch) {
            $i++;
            $title = trim(format_string($ch->title, true, array('context' => $context)));
            if (!$ch->subchapter) {
                if (!$ch->hidden) {
                    $nch++;
                    $ns = 0;
                    // SYNERGY - Make sure the right subchapters are expanded by default.
                    $li = '<li>';
                    if ($ch->id == $opench || !$giportfolio->collapsesubchapters) {
                        $li = '<li class="expanded">';
                    }

                    $toc .= ($first) ? $li : '</ul></li>'.$li;

                    if ($giportfolio->numbering == PORTFOLIO_NUM_NUMBERS) {
                        $title = "$nch $title";
                    }

                } else {
                    if ($giportfolio->numbering == PORTFOLIO_NUM_NUMBERS) {
                        $title = "x $title";
                    }
                    $title = '<span class="dimmed_text">'.$title.'</span>';
                }
            } else {
                $toc .= ($first) ? '<li><ul><li>' : '<li>';
                if (!$ch->hidden) {
                    $ns++;
                    if ($giportfolio->numbering == PORTFOLIO_NUM_NUMBERS) {
                        $title = "$nch.$ns $title";
                    }
                } else {
                    if ($giportfolio->numbering == PORTFOLIO_NUM_NUMBERS) {
                        $title = "x.x $title";
                    }
                    $title = '<span class="dimmed_text">'.$title.'</span>';
                }
            }

            if ($ch->id == $chapter->id) {
                $toc .= '<strong>'.$title.'</strong>';
            } else {
                $toc .= '<a title="'.s($title).'" href="viewcontribute.php?id='.$cm->id.'&amp;chapterid='.$ch->id.
                    '&amp;userid='.$userid.'">'.$title.'</a>';
            }
            $toc .= '&nbsp;&nbsp;';
            $toc .= (!$ch->subchapter) ? '<ul>' : '</li>';
            $first = 0;
        }
        $toc .= '</ul></li></ul>';
    }
    $toc .= '</div>';

    $toc = str_replace('<ul></ul>', '', $toc); // Cleanup of invalid structures.

    return $toc;
}

function giportfolio_get_collaborative_status($giportfolio) { // Check if the activity is allowing users to add chapters.
    return $giportfolio->participantadd;
}

function giportfolio_get_user_contributions($chapterid, $giportfolioid, $userid, $showshared = false) { // Return user contributions for a chapter-page.
    global $DB;

    $sharedsql = '';
    if ($showshared) {
        $sharedsql = ' OR shared = 1';
    }
    $sql = "SELECT * FROM {giportfolio_contributions}
            WHERE chapterid = :chapterid AND giportfolioid= :giportfolioid
            AND (userid = :userid $sharedsql)
            ORDER BY timemodified DESC
            ";
    $params = array('giportfolioid' => $giportfolioid, 'chapterid' => $chapterid, 'userid' => $userid);

    return $DB->get_records_sql($sql, $params);
}

function giportfolio_get_user_chapters($giportfolioid, $userid) { // Return user added chapters for a giportfolio.
    global $DB;

    $sql = "SELECT * FROM {giportfolio_chapters}
            WHERE giportfolioid = :giportfolioid AND userid = :userid
            ORDER BY pagenum ASC
            ";
    $params = array('giportfolioid' => $giportfolioid, 'userid' => $userid);

    $userchapters = $DB->get_records_sql($sql, $params);

    if ($userchapters) {
        return $userchapters;
    } else {
        return 0;
    }
}

function giportfolio_get_user_contribution_status($giportfolioid, $userid) {
    // Return (if exists) the last contribution date to a giportfolio for a user.
    global $DB;

    $params = array(
        'giportfolioid' => $giportfolioid,
        'userid' => $userid,
        'hidden' => 0,
    );
    $contribtime = $DB->get_field('giportfolio_contributions', 'MAX(timemodified)', $params);
    $chaptertime = $DB->get_field('giportfolio_chapters', 'MAX(timemodified)', $params);

    return (int)max($contribtime, $chaptertime);
}

function giportfolio_get_giportfolios_number($giportfolioid, $cmid) {
    // Return (if exists) the number of student giportfolios for each activity.
    global $DB;

    $context = context_module::instance($cmid);
    $userids = get_users_by_capability($context, 'mod/giportfolio:submitportfolio', 'u.id', '', '', '', '', '', false, true);
    if (empty($userids)) {
        return 0;
    }

    list($usql, $params) = $DB->get_in_or_equal(array_keys($userids), SQL_PARAMS_NAMED);
    $sql = "SELECT COUNT(DISTINCT submitted.userid)
              FROM (
                SELECT userid
                  FROM {giportfolio_contributions}
                 WHERE giportfolioid= :giportfolioid
                 GROUP BY userid
                 UNION
                SELECT userid
                  FROM {giportfolio_chapters}
                 WHERE giportfolioid= :giportfolioid2
                 GROUP BY userid
              ) AS submitted
             WHERE submitted.userid $usql
            ";
    $params['giportfolioid'] = $giportfolioid;
    $params['giportfolioid2'] = $giportfolioid;

    $giportfolionumber = $DB->count_records_sql($sql, $params);

    return $giportfolionumber;
}

function giportfolio_chapter_count_contributions($giportfolioid, $chapterid) {
    global $DB;

    $chapterids = array($chapterid);
    $chapters = $DB->get_records('giportfolio_chapters', array('giportfolioid' => $giportfolioid), 'pagenum', 'id, subchapter');
    $found = false;
    foreach ($chapters as $ch) {
        if ($ch->id == $chapterid) {
            $found = true;
            if ($ch->subchapter) {
                break; // This chapter is already a subchapter, so won't have subchapters of its own.
            }
        } else if ($found and $ch->subchapter) {
            $chapterids[] = $ch->id;
        } else if ($found) {
            break;
        }
    }

    list($csql, $params) = $DB->get_in_or_equal($chapterids, SQL_PARAMS_NAMED);
    $select = "giportfolioid = :giportfolioid AND chapterid $csql";
    $params['giportfolioid'] = $giportfolioid;

    return $DB->count_records_select('giportfolio_contributions', $select, $params);
}

function giportfolio_adduser_fake_block($userid, $giportfolio, $cm, $courseid) {
    global $OUTPUT, $PAGE, $CFG, $DB;

    require_once($CFG->libdir.'/gradelib.php');

    $ufields = user_picture::fields('u');

    $select = "SELECT $ufields ";

    $sql = 'FROM {user} u '.'WHERE u.id='.$userid;

    $user = $DB->get_record_sql($select.$sql);

    $picture = $OUTPUT->user_picture($user);

    $usercontribution = giportfolio_get_user_contribution_status($giportfolio->id, $userid);

    $lastupdated = '';
    $userfinalgrade = null;
    if ($usercontribution) {
        $lastupdated = date('l jS \of F Y ', $usercontribution);
        $usergrade = grade_get_grades($courseid, 'mod', 'giportfolio', $giportfolio->id, $userid);
        if ($usergrade->items && isset($usergrade->items[0]->grades[$userid])) {
            $userfinalgrade = $usergrade->items[0]->grades[$userid];
        }
    }

    $bc = new block_contents();
    $bc->title = get_string('giportfolioof', 'mod_giportfolio');
    $bc->attributes['class'] = 'block';
    $bc->content = '<strong>'.fullname($user, true).'</strong>';
    $bc->content .= '<br/>';
    $bc->content .= $picture;
    $bc->content .= '<br/>';
    $bc->content .= '<strong>'.get_string('lastupdated', 'mod_giportfolio').'</strong>';
    $bc->content .= '<br/>';
    $bc->content .= $lastupdated;

    $hasgrade = ($userfinalgrade && (!is_null($userfinalgrade->grade) || $userfinalgrade->feedback));
    $gradelocked = ($userfinalgrade && $userfinalgrade->locked);

    $bc->content .= '<br/>';
    if ($hasgrade) {
        $bc->content .= '<strong>'.get_string('grade').'</strong>';
        $bc->content .= '<br/>';
        $bc->content .= $userfinalgrade->grade.'  ';
    }
    if (!$gradelocked) {
        $gradeurl = new moodle_url('/mod/giportfolio/updategrade.php', array('id' => $cm->id, 'userid' => $userid));
        $strgrade = $hasgrade ? get_string('upgrade', 'mod_giportfolio') : get_string('insertgrade', 'mod_giportfolio');
        $bc->content .= html_writer::link($gradeurl, $strgrade);
    }
    if ($hasgrade) {
        if ($userfinalgrade->feedback) {
            $feedback = $userfinalgrade->feedback;
        } else {
            $feedback = '-';
        }
        $bc->content .= '<br/>';
        $bc->content .= '<strong>'.get_string('feedback').'</strong>';
        $bc->content .= '<br/>';
        $bc->content .= $feedback;
    }
    $bc->content .= '<br/>';

    $regions = $PAGE->blocks->get_regions();
    $firstregion = reset($regions);
    $PAGE->blocks->add_fake_block($bc, $firstregion);

}

function giportfolio_quick_update_grades($id, $menu, $currentgroup, $giportfolioid) {
    // Update or insert grades from quick gradelib form.
    global $USER, $DB;
    $context = context_module::instance($id);
    $allportousers = get_users_by_capability($context, 'mod/giportfolio:view', 'u.id,u.picture,u.firstname,u.lastname,u.idnumber',
                                             'u.firstname ASC', '', '', $currentgroup, '', false, true);
    $itemid = giportfolio_get_gradeitem($giportfolioid);

    foreach ($allportousers as $puser) {
        if (!empty($menu[$puser->id])) {
            if ($menu[$puser->id] == -1) {
                $menu[$puser->id] = 0;
            }
            $gradeid = giportfolio_get_usergrade_id($itemid, $puser->id);
            if ($gradeid) {
                $newgrade = new stdClass();
                $newgrade->id = $gradeid;
                $newgrade->itemid = $itemid;
                $newgrade->userid = $puser->id;
                $newgrade->usermodified = $USER->id;
                $newgrade->finalgrade = $menu[$puser->id];
                $newgrade->rawgrade = $menu[$puser->id];
                $newgrade->timemodified = time();
                $DB->update_record('grade_grades', $newgrade);

            } else {
                $insertgrade = new stdClass();
                $insertgrade->itemid = $itemid;
                $insertgrade->userid = $puser->id;
                $insertgrade->rawgrade = $menu[$puser->id];
                $insertgrade->rawgrademax = 100.00000;
                $insertgrade->rawgrademin = 0.00000;
                $insertgrade->usermodified = $USER->id;
                $insertgrade->finalgrade = $menu[$puser->id];
                $insertgrade->hidden = 0;
                $insertgrade->locked = 0;
                $insertgrade->locktime = 0;
                $insertgrade->exported = 0;
                $insertgrade->overridden = 0;
                $insertgrade->excluded = 0;
                $insertgrade->feedbackformat = 1;
                $insertgrade->informationformat = 0;

                $insertgrade->timecreated = time();
                $insertgrade->timemodified = time();

                $DB->insert_record('grade_grades', $insertgrade);
            }
        }
    }
}

function giportfolio_quick_update_feedback($id, $menu, $currentgroup, $giportfolioid) { // Update feedback from quick gradelib form.
    global $USER, $DB;
    $context = context_module::instance($id);
    $allportousers = get_users_by_capability($context, 'mod/giportfolio:view', 'u.id,u.picture,u.firstname,u.lastname,u.idnumber',
                                             'u.firstname ASC', '', '', $currentgroup, '', false, true);
    $itemid = giportfolio_get_gradeitem($giportfolioid);

    foreach ($allportousers as $puser) {
        if (!empty($menu[$puser->id])) {
            $gradeid = giportfolio_get_usergrade_id($itemid, $puser->id);

            $newgrade = new stdClass();
            $newgrade->id = $gradeid;
            $newgrade->itemid = $itemid;
            $newgrade->userid = $puser->id;
            $newgrade->usermodified = $USER->id;
            $newgrade->feedback = $menu[$puser->id];
            $newgrade->timemodified = time();
            $DB->update_record('grade_grades', $newgrade);
        }
    }
}

function giportfolio_get_gradeitem($giportfolioid) { // Return grade item id for update.
    global $DB;
    $itemid = $DB->get_record_sql("SELECT p.id,p.course,p.name,gi.id as itemid
    FROM {giportfolio} p
    LEFT JOIN {grade_items} gi on (p.id=gi.iteminstance AND p.course=gi.courseid)
    WHERE p.id= ? AND gi.itemmodule= 'giportfolio'
    ", array($giportfolioid));
    if ($itemid) {
        return $itemid->itemid;
    } else {
        return 0;
    }
}

function giportfolio_get_usergrade_id($itemid, $userid) { // Return grade item id for update.
    global $CFG, $DB;
    $gradeid = $DB->get_record_sql("SELECT gg.id,gg.itemid,gg.userid
    FROM {$CFG->prefix}grade_grades gg
    WHERE gg.itemid= $itemid AND gg.userid=$userid
    ");

    if ($gradeid) {
        return $gradeid->id;
    } else {
        return 0;
    }
}

function giportfolio_get_last_chapter($giportfolioid, $chapterid = null) {
    // Return the last chapter of a teacher defined giportfolio.
    global $DB;

    if ($chapterid) {
        $sql = "SELECT * FROM {giportfolio_chapters}
               WHERE pagenum= (
                  SELECT MAX(pagenum)
                    FROM {giportfolio_chapters}
                   WHERE giportfolioid= :giportfolioid AND userid = 0
               ) AND giportfolioid= :giportfolioid2 AND id= :chapterid
               ";
        $params = array('giportfolioid' => $giportfolioid, 'giportfolioid2' => $giportfolioid, 'chapterid' => $chapterid);
    } else {
        $sql = "SELECT * FROM {giportfolio_chapters}
               WHERE pagenum=(
                  SELECT MAX(pagenum)
                    FROM {giportfolio_chapters}
                   WHERE giportfolioid= :giportfolioid AND userid = 0
               ) AND giportfolioid= :giportfolioid2
               ";
        $params = array('giportfolioid' => $giportfolioid, 'giportfolioid2' => $giportfolioid);
    }

    return $DB->get_record_sql($sql, $params);
}

function giportfolio_get_first_userchapter($giportfolioid, $chapterid, $userid) { // Return the first user defined chapter.
    global $DB;

    $sql = "SELECT * FROM {giportfolio_chapters}
               WHERE pagenum=(
                  SELECT MIN(pagenum)
                    FROM {giportfolio_chapters}
                   WHERE giportfolioid= :giportfolioid AND userid = :userid
               ) AND giportfolioid= :giportfolioid2 AND id= :chapterid
               ";
    $params = array(
        'giportfolioid' => $giportfolioid, 'giportfolioid2' => $giportfolioid, 'chapterid' => $chapterid, 'userid' => $userid
    );

    return $DB->get_record_sql($sql, $params);
}

function giportfolio_check_user_chapter($chapter, $userid) { // Check if chapter is user defined one.
    if (!is_object($chapter)) {
        throw new coding_exception('Must pass full chapter object to giportfolio_check_user_chapter');
    }
    if ($chapter->userid && $chapter->userid != $userid) {
        throw new coding_exception('Chapter user does not match the user provided');
    }
    return (bool)($chapter->userid);
}

function giportfolio_delete_user_contributions($chapterid, $userid, $giportfolioid) {
    // Delete user contributions from their chapters before deleting the chapter.
    global $DB;

    $sql = "SELECT * FROM {giportfolio_contributions}
               WHERE giportfolioid= :giportfolioid AND userid= :userid AND chapterid= :chapterid
               ";

    $params = array('giportfolioid' => $giportfolioid, 'userid' => $userid, 'chapterid' => $chapterid);

    $usercontributions = $DB->get_records_sql($sql, $params);

    if ($usercontributions) {
        foreach ($usercontributions as $usercontrib) {
            $delcontrib = new stdClass();
            $delcontrib->id = $usercontrib->id;
            $delcontrib->chapterid = $usercontrib->chapterid;
            $delcontrib->userid = $usercontrib->userid;

            $DB->delete_records('giportfolio_contributions', array(
                                                                  'id' => $delcontrib->id, 'userid' => $delcontrib->userid,
                                                                  'chapterid' => $delcontrib->chapterid
                                                             ));
        }
    }

}

function giportfolio_delete_chapter_contributions($chapterid, $cmid, $giportfolioid) {
    global $DB;

    $params = array(
        'giportfolioid' => $giportfolioid,
        'chapterid' => $chapterid
    );

    // Delete any attached files.
    $context = context_module::instance($cmid);
    $fs = get_file_storage();
    $contributions = $DB->get_records('giportfolio_contributions', $params, '', 'id');
    foreach ($contributions as $contrib) {
        $fs->delete_area_files($context->id, 'mod_giportfolio', 'contribution', $contrib->id);
        $fs->delete_area_files($context->id, 'mod_giportfolio', 'attachment', $contrib->id);
    }

    // Delete the contributions.
    $DB->delete_records('giportfolio_contributions', $params);
}

/**
 * File browsing support class
 */
class giportfolio_file_info extends file_info {
    protected $course;
    protected $cm;
    protected $areas;
    protected $filearea;

    public function __construct($browser, $course, $cm, $context, $areas, $filearea) {
        parent::__construct($browser, $context);
        $this->course = $course;
        $this->cm = $cm;
        $this->areas = $areas;
        $this->filearea = $filearea;
    }

    /**
     * Returns list of standard virtual file/directory identification.
     * The difference from stored_file parameters is that null values
     * are allowed in all fields
     * @return array with keys contextid, filearea, itemid, filepath and filename
     */
    public function get_params() {
        return array(
            'contextid' => $this->context->id,
            'component' => 'mod_giportfolio',
            'filearea' => $this->filearea,
            'itemid' => null,
            'filepath' => null,
            'filename' => null
        );
    }

    /**
     * Returns localised visible name.
     * @return string
     */
    public function get_visible_name() {
        return $this->areas[$this->filearea];
    }

    /**
     * Can I add new files or directories?
     * @return bool
     */
    public function is_writable() {
        return false;
    }

    /**
     * Is directory?
     * @return bool
     */
    public function is_directory() {
        return true;
    }

    /**
     * Returns list of children.
     * @return array of file_info instances
     */
    public function get_children() {
        global $DB;

        $children = array();
        $chapters = $DB->get_records('giportfolio_chapters', array('giportfolioid' => $this->cm->instance),
                                     'pagenum', 'id, pagenum');
        foreach ($chapters as $itemid => $unused) {
            if ($child = $this->browser->get_file_info($this->context, 'mod_giportfolio', $this->filearea, $itemid)) {
                $children[] = $child;
            }
        }
        return $children;
    }

    /**
     * Returns parent file_info instance
     * @return file_info or null for root
     */
    public function get_parent() {
        return $this->browser->get_file_info($this->context);
    }
}
