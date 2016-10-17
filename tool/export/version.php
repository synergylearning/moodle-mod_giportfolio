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
 * giportfolio print plugin version info
 *
 * @package    giportfoliotool_export
 * @copyright  2015 Synergy Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$plugin->version   = 2015050100; // The current plugin version (Date: YYYYMMDDXX).
$plugin->requires  = 2013051409; // Requires this Moodle version.
$plugin->cron      = 0;          // Persion for cron to check this module (secs).
$plugin->component = 'giportfoliotool_export'; // Full name of the plugin (used for diagnostics).
$plugin->maturity  = MATURITY_STABLE;
$plugin->release   = 'v2.2+ (2015050100)'; // User-friendly version number.
