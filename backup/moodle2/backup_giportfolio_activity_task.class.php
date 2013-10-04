<?php
// This file is part of Moodle - http://moodle.org/
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
 * Description of giportfolio backup task
 *
 * @package    mod_giportfolio
 * @copyright  2010-2011 Petr Skoda  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/mod/giportfolio/backup/moodle2/backup_giportfolio_stepslib.php');    // Because it exists (must).
require_once($CFG->dirroot.'/mod/giportfolio/backup/moodle2/backup_giportfolio_settingslib.php'); // Because it exists (optional).

/**
 * giportfolio backup task that provides all the settings and steps to perform one
 * complete backup of the activity
 */
class backup_giportfolio_activity_task extends backup_activity_task {

    /**
     * Define (add) particular settings this activity can have
     *
     * @return void
     */
    protected function define_my_settings() {
        // No particular settings for this activity.
    }

    /**
     * Define (add) particular steps this activity can have
     *
     * @return void
     */
    protected function define_my_steps() {
        // Giportfolio only has one structure step.
        $this->add_step(new backup_giportfolio_activity_structure_step('giportfolio_structure', 'giportfolio.xml'));
    }

    /**
     * Code the transformations to perform in the activity in
     * order to get transportable (encoded) links
     *
     * @param string $content
     * @return string encoded content
     */
    static public function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, "/");

        // Link to the list of giportfolios.
        $search  = "/($base\/mod\/giportfolio\/index.php\?id=)([0-9]+)/";
        $content = preg_replace($search, '$@GIPORTFOLIOINDEX*$2@$', $content);

        // Link to giportfolio view by moduleid.
        $search  = "/($base\/mod\/giportfolio\/viewgiportfolio.php\?id=)([0-9]+)(&|&amp;)chapterid=([0-9]+)/";
        $content = preg_replace($search, '$@GIPORTFOLIOVIEWBYIDCH*$2*$4@$', $content);

        $search  = "/($base\/mod\/giportfolio\/viewgiportfolio.php\?id=)([0-9]+)/";
        $content = preg_replace($search, '$@GIPORTFOLIOVIEWBYID*$2@$', $content);

        // Link to giportfolio view by giportfolioid.
        $search  = "/($base\/mod\/giportfolio\/viewgiportfolio.php\?b=)([0-9]+)(&|&amp;)chapterid=([0-9]+)/";
        $content = preg_replace($search, '$@GIPORTFOLIOVIEWBYBCH*$2*$4@$', $content);

        $search  = "/($base\/mod\/giportfolio\/viewgiportfolio.php\?b=)([0-9]+)/";
        $content = preg_replace($search, '$@GIPORTFOLIOVIEWBYB*$2@$', $content);

        // View base giportfolio.
        $search  = "/($base\/mod\/giportfolio\/view.php\?id=)([0-9]+)/";
        $content = preg_replace($search, '$@GIPORTFOLIOVIEWBASEBYID*$2@$', $content);

        return $content;
    }
}
