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
 * Define all the restore tasks
 *
 * @package    mod_giportfolio
 * @copyright  2012 Synergy Learning / Manolescu Dorel (based on book module)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/giportfolio/backup/moodle2/restore_giportfolio_stepslib.php'); // Because it exists (must).

/**
 * giportfolio restore task that provides all the settings and steps to perform one
 * complete restore of the activity
 */
class restore_giportfolio_activity_task extends restore_activity_task {

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
        // Choice only has one structure step.
        $this->add_step(new restore_giportfolio_activity_structure_step('giportfolio_structure', 'giportfolio.xml'));
    }

    /**
     * Define the contents in the activity that must be
     * processed by the link decoder
     *
     * @return array
     */
    static public function define_decode_contents() {
        $contents = array();

        $contents[] = new restore_decode_content('giportfolio', array('intro'), 'giportfolio');
        $contents[] = new restore_decode_content('giportfolio_chapters', array('content'), 'giportfolio_chapter');

        $contents[] = new restore_decode_content('giportfolio_contributions', array('content'), 'giportfolio_contribution');

        return $contents;
    }

    /**
     * Define the decoding rules for links belonging
     * to the activity to be executed by the link decoder
     *
     * @return array
     */
    static public function define_decode_rules() {
        $rules = array();

        // List of giportfolios in course.
        $rules[] = new restore_decode_rule('GIPORTFOLIOINDEX', '/mod/giportfolio/index.php?id=$1', 'course');

        // Giportfolio by cm->id.
        $rules[] = new restore_decode_rule('GIPORTFOLIOVIEWBYID', '/mod/giportfolio/viewgiportfolio.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('GIPORTFOLIOVIEWBYIDCH', '/mod/giportfolio/viewgiportfolio.php?id=$1&amp;chapterid=$2',
                                           array('course_module', 'giportfolio_chapter'));

        // Giportfolio by giportfolio->id.
        $rules[] = new restore_decode_rule('GIPORTFOLIOVIEWBYB', '/mod/giportfolio/viewgiportfolio.php?b=$1', 'giportfolio');
        $rules[] = new restore_decode_rule('GIPORTFOLIOVIEWBYBCH', '/mod/giportfolio/viewgiportfolio.php?b=$1&amp;chapterid=$2',
                                           array('giportfolio', 'giportfolio_chapter'));

        // View base page of Portfolio by cm->id.
        $rules[] = new restore_decode_rule('GIPORTFOLIOVIEWBASEBYID', '/mod/giportfolio/view.php?id=$1', 'course_module');

        return $rules;
    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * giportfolio logs. It must return one array
     * of {@link restore_log_rule} objects
     *
     * @return array
     */
    static public function define_restore_log_rules() {
        $rules = array();

        $rules[] = new restore_log_rule('giportfolio', 'update', 'viewgiportfolio.php?id={course_module}', '{giportfolio}');
        $rules[] = new restore_log_rule('giportfolio', 'view', 'viewgiportfolio.php?id={course_module}', '{giportfolio}');
        $rules[] = new restore_log_rule('giportfolio', 'view all', 'viewgiportfolio.php?id={course_module}', '{giportfolio}');
        $rules[] = new restore_log_rule('giportfolio', 'print', 'viewgiportfolio.php?id={course_module}', '{giportfolio}');

        return $rules;
    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * course logs. It must return one array
     * of {@link restore_log_rule} objects
     *
     * Note this rules are applied when restoring course logs
     * by the restore final task, but are defined here at
     * activity level. All them are rules not linked to any module instance (cmid = 0)
     *
     * @return array
     */
    static public function define_restore_log_rules_for_course() {
        $rules = array();

        return $rules;
    }
}
