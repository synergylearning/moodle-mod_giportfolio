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
 * Print lib
 *
 * @package    giportfoliotool
 * @subpackage print
 * @copyright  2004-2011 Petr Skoda  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

function giportfoliotool_print_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $giportfolionode) {
    global $PAGE, $DB;

    if ($PAGE->cm->modname !== 'giportfolio') {
        return;
    }

    $context = context_module::instance($PAGE->cm->id);
    $params = $PAGE->url->params();

    if (empty($params['id']) or empty($params['chapterid'])) {
        return;
    }

    $giportfolio = $DB->get_record('giportfolio', array('id' => $PAGE->cm->instance), '*', MUST_EXIST);
    if ($giportfolio->klassenbuchtrainer) {
        return; // Not available when using Klassenbuch trainer mode.
    }

    if (has_capability('giportfoliotool/print:print', $context)) {
        $url1 = new moodle_url('/mod/giportfolio/tool/print/index.php', array('id' => $params['id']));
        $url2 = new moodle_url('/mod/giportfolio/tool/print/index.php',
                               array('id' => $params['id'], 'chapterid' => $params['chapterid']));
        $action = new action_link($url1, get_string('printgiportfolio', 'giportfoliotool_print'), new popup_action('click', $url1));
        $giportfolionode->add(get_string('printgiportfolio', 'giportfoliotool_print'), $action, navigation_node::TYPE_SETTING,
                              null, null, new pix_icon('giportfolio', '', 'giportfoliotool_print', array('class' => 'icon')));
        $action = new action_link($url2, get_string('printchapter', 'giportfoliotool_print'), new popup_action('click', $url2));
        $giportfolionode->add(get_string('printchapter', 'giportfoliotool_print'), $action, navigation_node::TYPE_SETTING,
                              null, null, new pix_icon('chapter', '', 'giportfoliotool_print', array('class' => 'icon')));
    }
}

/**
 * Return read actions.
 * @return array
 */
function giportfoliotool_print_get_view_actions() {
    return array('print');
}
