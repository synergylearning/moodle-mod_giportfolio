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
 * edit contribution form
 *
 * @package    mod_giportfolio
 * @copyright  2012 Synergy Learning / Manolescu Dorel based on book module
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir.'/formslib.php');
require_once('lib.php');

class mod_giportfolio_contribution_edit_form extends moodleform {
    public function definition() {
        $editoroptions = $this->_customdata['editoroptions'];
        $attachmentoptions = $this->_customdata['attachmentoptions'];

        $mform = $this->_form;

        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'chapterid', 0);
        $mform->setType('chapterid', PARAM_INT);
        $mform->addElement('hidden', 'contributionid', 0);
        $mform->setType('contributionid', PARAM_INT);

        $mform->addElement('header', 'contribheader', get_string('contribmodform', 'giportfolio'));
        $mform->addElement('text', 'title', get_string('contributiontitle', 'giportfolio'));
        $mform->addRule('title', 'Required', 'required', null, 'client');
        $mform->setType('title', PARAM_TEXT);

        $opts = array(0 => get_string('show'), 1 => get_string('hide'));
        $mform->addElement('select', 'hidden', get_string('visibility', 'giportfolio'), $opts, 0);

        $mform->addElement('editor', 'content_editor', get_string('content', 'mod_giportfolio'), null, $editoroptions);
        $mform->addRule('content_editor', get_string('required'), 'required', null, 'client');

        $mform->addElement('filemanager', 'attachment_filemanager', get_string('attachment', 'giportfolio'),
                           null, $attachmentoptions);

        $this->add_action_buttons(true, get_string('updatecontrib', 'giportfolio'));
    }
}
