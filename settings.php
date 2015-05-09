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
 * giportfolio plugin settings
 *
 * @package    mod_giportfolio
 * @copyright  2004-2011 Petr Skoda  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
global $CFG;

if ($ADMIN->fulltree) {
    require_once("$CFG->dirroot/mod/giportfolio/lib.php");

    // General settings.

    if ($CFG->branch < 29) {
        $settings->add(new admin_setting_configcheckbox('giportfolio/requiremodintro',
                       get_string('requiremodintro', 'admin'), get_string('configrequiremodintro', 'admin'), 1));
    }

    $options = giportfolio_get_numbering_types();

    $settings->add(new admin_setting_configmultiselect('giportfolio/numberingoptions',
        get_string('numberingoptions', 'mod_giportfolio'), get_string('numberingoptions_help', 'mod_giportfolio'),
        array_keys($options), $options));

    $settings->add(new admin_setting_configcheckbox('giportfolio/contributioncount',
                   get_string('contributioncount', 'mod_giportfolio'), get_string('contributioncount_desc', 'mod_giportfolio'), 0));


    // Modedit defaults.
    $settings->add(new admin_setting_heading('giportfoliomodeditdefaults', get_string('modeditdefaults', 'admin'),
                                             get_string('condifmodeditdefaults', 'admin')));

    $settings->add(new admin_setting_configselect('giportfolio/numbering',
        get_string('numbering', 'mod_giportfolio'), '', PORTFOLIO_NUM_NUMBERS, $options));

}