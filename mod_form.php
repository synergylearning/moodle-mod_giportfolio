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
 * Instance add/edit form
 *
 * @package    mod_giportfolio
 * @copyright  2004-2011 Petr Skoda  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

global $CFG;
require_once($CFG->dirroot.'/mod/giportfolio/locallib.php');
require_once($CFG->dirroot.'/course/moodleform_mod.php');

class mod_giportfolio_mod_form extends moodleform_mod {

    public function definition() {
        global $CFG;

        $mform = $this->_form;

        $config = get_config('giportfolio');

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('name'), array('size' => '64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');

        if ($CFG->branch < 29) {
            $this->add_intro_editor($config->requiremodintro, get_string('summary'));
        } else {
            $this->standard_intro_elements(get_string('summary'));
        }

        $alloptions = giportfolio_get_numbering_types();
        $allowed = explode(',', $config->numberingoptions);
        $options = array();
        foreach ($allowed as $type) {
            if (isset($alloptions[$type])) {
                $options[$type] = $alloptions[$type];
            }
        }
        if ($this->current->instance) {
            if (!isset($options[$this->current->numbering])) {
                if (isset($alloptions[$this->current->numbering])) {
                    $options[$this->current->numbering] = $alloptions[$this->current->numbering];
                }
            }
        }
        $mform->addElement('select', 'numbering', get_string('numbering', 'giportfolio'), $options);
        $mform->addHelpButton('numbering', 'numbering', 'mod_giportfolio');
        $mform->setDefault('numbering', $config->numbering);

        $mform->addElement('checkbox', 'printing', get_string('printing', 'giportfolio'));
        $mform->addHelpButton('printing', 'printing', 'mod_giportfolio');
        $mform->setDefault('printing', 0);

        $mform->addElement('checkbox', 'customtitles', get_string('customtitles', 'giportfolio'));
        $mform->addHelpButton('customtitles', 'customtitles', 'mod_giportfolio');
        $mform->setDefault('customtitles', 0);

        // SYNERGY - add collapsesubchapters option to settings.
        $mform->addElement('selectyesno', 'collapsesubchapters', get_string('collapsesubchapters', 'giportfolio'));
        $mform->addElement('selectyesno', 'participantadd', get_string('participantadd', 'giportfolio'));
        $mform->setDefault('participantadd', 1);

        $mform->addElement('text', 'chapternumber', get_string('chapternumberinit', 'giportfolio'), 'Required');
        $mform->addRule('chapternumber', 'Required', 'required', null, 'client');
        $mform->setDefault('chapternumber', 1);
        $mform->setType('chapternumber', PARAM_INT);
        $mform->addRule('chapternumber', 'must be numeric', 'numeric', null, 'client');
        $mform->addRule('chapternumber', 'add valid number', 'nonzero', null, 'client');
        $mform->addRule('chapternumber', 'add positive number', 'regex', '|^[1-9][0-9]*$|', 'client'); // Positive number.

        $mform->addElement('checkbox', 'publishnotification', get_string('publishnotification', 'giportfolio'));
        $mform->setDefault('publishnotification', 0);

        $mform->addElement('selectyesno', 'notifyaddentry', get_string('notifyaddentry', 'giportfolio'));
        $mform->setDefault('newentrynotification', 0);

        $mform->addElement('selectyesno', 'automaticgrading', get_string('automaticgrading', 'giportfolio'));
        $mform->setDefault('automaticgrading', 0);

        $mform->addElement('selectyesno', 'skipintro', get_string('skipintro', 'giportfolio'));
        $mform->setDefault('skipintro', 0);

        $mform->addElement('selectyesno', 'myactivitylink', get_string('myactivitylink', 'giportfolio'));
        $mform->setDefault('myactivitylink', 1);

        if (giportfolio_include_klassenbuchtrainer()) {
            $mform->addElement('selectyesno', 'klassenbuchtrainer', get_string('klassenbuchtrainer', 'giportfolio'));
            $mform->addHelpButton('klassenbuchtrainer', 'klassenbuchtrainer', 'mod_giportfolio');
            $mform->setDefault('klassenbuchtrainer', 0);
        } else {
            $mform->addElement('hidden', 'klassenbuchtrainer', 0);
            $mform->setType('klassenbuchtrainer', 0);
        }

        $this->standard_grading_coursemodule_elements();

        $this->standard_coursemodule_elements();

        $this->add_action_buttons();
    }

    function definition_after_data() {
        parent::definition_after_data();

        $mform = $this->_form;
        if ($id = $mform->getElementValue('update')) {
            // Do not allow the 'klassenbuchtrainer' field to change after the portfolio has been created.
            $mform->hardFreeze('klassenbuchtrainer');
        }
    }

}
