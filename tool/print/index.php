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
 * giportfolio printing
 *
 * @package    giportfoliotool
 * @subpackage print
 * @copyright  2012 Synergy Learning / Manolescu Dorel based on book module
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(dirname(__FILE__).'/../../../../config.php');
require_once(dirname(__FILE__).'/locallib.php');

global $CFG, $DB, $OUTPUT, $PAGE, $SITE, $USER;

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
    // Single chapter printing - only visible!
    $chapter = $DB->get_record('giportfolio_chapters', array(
                                                            'id' => $chapterid, 'giportfolioid' => $giportfolio->id
                                                       ), '*', MUST_EXIST);
    if ($chapter->userid && !$chapter->userid == $USER->id) {
        throw new moodle_exception('notyourchapter', 'mod_giportfolio');
    }
} else {
    // Complete giportfolio.
    $chapter = false;
}

$PAGE->set_url('/mod/giportfolio/print.php', array('id' => $id, 'chapterid' => $chapterid));

unset($id);
unset($chapterid);

// Security checks END.

// Read chapters.
$chapters = giportfolio_preload_chapters($giportfolio);

$additionalchapters = giportfolio_preload_userchapters($giportfolio);
if ($additionalchapters) {
    $chapters = $chapters + $additionalchapters;
}

$strgiportfolios = get_string('modulenameplural', 'mod_giportfolio');
$strgiportfolio = get_string('modulename', 'mod_giportfolio');
$strtop = get_string('top', 'mod_giportfolio');

@header('Cache-Control: private, pre-check=0, post-check=0, max-age=0');
@header('Pragma: no-cache');
@header('Expires: ');
@header('Accept-Ranges: none');
@header('Content-type: text/html; charset=utf-8');

if ($chapter) {

    if ($chapter->hidden) {
        require_capability('mod/giportfolio:viewhiddenchapters', $context);
    }
    \giportfoliotool_print\event\chapter_printed::create_from_chapter($giportfolio, $context, $chapter)->trigger();

    // Page header.
    ?>
    <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
    <html>
    <head>
        <title><?php echo format_string($giportfolio->name, true, array('context' => $context)) ?></title>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <meta name="description" content="<?php echo s(format_string($giportfolio->name, true, array('context' => $context))) ?>"/>
        <link rel="stylesheet" type="text/css" href="print.css"/>
    </head>
    <body>
    <a name="top"></a>
    <div class="chapter">
    <?php


    if (!$giportfolio->customtitles) {
        if (!$chapter->subchapter) {
            $currtitle = giportfolio_get_chapter_title($chapter->id, $chapters, $giportfolio, $context);
            echo '<p class="giportfolio_chapter_title">'.$currtitle.'</p>';
        } else {
            $currtitle = giportfolio_get_chapter_title($chapters[$chapter->id]->parent, $chapters, $giportfolio, $context);
            $currsubtitle = giportfolio_get_chapter_title($chapter->id, $chapters, $giportfolio, $context);
            echo '<p class="giportfolio_chapter_title">'.$currtitle.'<br />'.$currsubtitle.'</p>';
        }
    }

    $chaptertext = file_rewrite_pluginfile_urls($chapter->content, 'pluginfile.php', $context->id, 'mod_giportfolio',
                                                'chapter', $chapter->id);
    echo format_text($chaptertext, $chapter->contentformat, array('noclean' => true, 'context' => $context));
    $contriblist = giportfolio_get_user_contributions($chapter->id, $chapter->giportfolioid, $USER->id);
    if ($contriblist) {
        foreach ($contriblist as $contrib) {
            $contribtitle = file_rewrite_pluginfile_urls($contrib->title, 'pluginfile.php', $context->id, 'mod_giportfolio',
                                                         'contribution', $contrib->id);
            echo '<strong>'.$contribtitle.'</strong></br>';
            echo date('l jS F Y', $contrib->timemodified);
            echo '</br></br>';
            $contribtext = file_rewrite_pluginfile_urls($contrib->content, 'pluginfile.php', $context->id, 'mod_giportfolio',
                                                        'contribution', $contrib->id);
            echo format_text($contribtext, $contrib->contentformat, array('noclean' => true, 'context' => $context));
            echo '</br>';
            echo '</br>';
        }
    }

    echo '</div>';
    echo '</body> </html>';

} else {
    $params = array(
        'context' => $context,
        'objectid' => $giportfolio->id
    );
    \giportfoliotool_print\event\giportfolio_printed::create($params)->trigger();

    $allchapters = $DB->get_records('giportfolio_chapters', array('giportfolioid' => $giportfolio->id, 'userid' => 0), 'pagenum');
    $alluserchapters = $DB->get_records('giportfolio_chapters', array('giportfolioid' => $giportfolio->id, 'userid' => $USER->id),
                                        'pagenum');
    if ($alluserchapters) {
        $allchapters = $alluserchapters + $allchapters;
    }

    // Page header.
    ?>
    <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
    <html>
    <head>
        <title><?php echo format_string($giportfolio->name, true, array('context' => $context)) ?></title>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <meta name="description"
              content="<?php echo s(format_string($giportfolio->name, true, array('noclean' => true, 'context' => $context))) ?>"/>
        <link rel="stylesheet" type="text/css" href="print.css"/>
    </head>
<body>
<a name="top"></a>

<p class="giportfolio_title"><?php echo format_string($giportfolio->name, true, array('context' => $context)) ?></p>

<p class="giportfolio_summary"><?php echo format_text($giportfolio->intro, $giportfolio->introformat, array(
                                                                                                           'noclean' => true,
                                                                                                           'context' => $context
                                                                                                      )) ?></p>

<div class="giportfolio_info">
    <table>
        <tr>
            <td><?php echo get_string('site') ?>:</td>
            <td>
                <a href="<?php echo $CFG->wwwroot ?>">
                    <?php echo format_string($SITE->fullname, true, array('context' => $context)) ?></a>
            </td>
        </tr>
        <tr>
            <td><?php echo get_string('course') ?>:</td>
            <td><?php echo format_string($course->fullname, true, array('context' => $context)) ?></td>
        </tr>
        <tr>
            <td><?php echo get_string('modulename', 'mod_giportfolio') ?>:</td>
            <td><?php echo format_string($giportfolio->name, true, array('context' => $context)) ?></td>
        </tr>
        <tr>
            <td><?php echo get_string('printedby', 'giportfoliotool_print') ?>:</td>
            <td><?php echo fullname($USER, true) ?></td>
        </tr>
        <tr>
            <td><?php echo get_string('printdate', 'giportfoliotool_print') ?>:</td>
            <td><?php echo userdate(time()) ?></td>
        </tr>
    </table>
</div>

    <?php
    list($toc, $titles) = giportfoliotool_print_get_toc($chapters, $giportfolio, $cm);
    echo $toc;
    // Chapters.
    $link1 = $CFG->wwwroot.'/mod/giportfolio/viewgiportfolio.php?id='.$course->id.'&chapterid=';
    $link2 = $CFG->wwwroot.'/mod/giportfolio/viewgiportfolio.php?id='.$course->id;
    foreach ($chapters as $ch) {
        $chapter = $allchapters[$ch->id];
        if ($chapter->hidden) {
            continue;
        }
        echo '<div class="giportfolio_chapter"><a name="ch'.$ch->id.'"></a>';
        if (!$giportfolio->customtitles) {
            echo '<p class="giportfolio_chapter_title">'.$titles[$ch->id].'</p>';
        }
        $content = str_replace($link1, '#ch', $chapter->content);
        $content = str_replace($link2, '#top', $content);
        $content = file_rewrite_pluginfile_urls($content, 'pluginfile.php', $context->id, 'mod_giportfolio', 'chapter', $ch->id);
        echo format_text($content, $chapter->contentformat, array('noclean' => true, 'context' => $context));

        $contriblist = giportfolio_get_user_contributions($chapter->id, $chapter->giportfolioid, $USER->id);
        if ($contriblist) {
            foreach ($contriblist as $contrib) {
                $contribtitle = file_rewrite_pluginfile_urls($contrib->title, 'pluginfile.php', $context->id, 'mod_giportfolio',
                                                             'contribution', $contrib->id);
                echo '<strong>'.$contribtitle.'</strong></br>';
                echo date('l jS F Y', $contrib->timemodified);
                echo '</br></br>';
                $contribtext = file_rewrite_pluginfile_urls($contrib->content, 'pluginfile.php', $context->id, 'mod_giportfolio',
                                                            'contribution', $contrib->id);
                echo format_text($contribtext, $contrib->contentformat, array('noclean' => true, 'context' => $context));
                echo '</br>';
                echo '</br>';
            }
        }

        echo '</div>';
    }
    echo '</body> </html>';
}

