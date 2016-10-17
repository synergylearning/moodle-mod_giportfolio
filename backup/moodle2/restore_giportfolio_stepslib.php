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
 * Define all the restore steps that will be used by the restore_giportfolio_activity_task
 *
 * @package    mod_giportfolio
 * @copyright  2012 Synergy Learning / Manolescu Dorel (based on book module)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Structure step to restore one giportfolio activity
 */
class restore_giportfolio_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();

        $paths[] = new restore_path_element('giportfolio', '/activity/giportfolio');
        $paths[] = new restore_path_element('giportfolio_chapter', '/activity/giportfolio/chapters/chapter');

        $paths[] = new restore_path_element('giportfolio_contribution',
                                            '/activity/giportfolio/chapters/chapter/contributions/contribution');

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    protected function process_giportfolio($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        $newitemid = $DB->insert_record('giportfolio', $data);
        $this->apply_activity_instance($newitemid);
    }

    protected function process_giportfolio_chapter($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        $data->giportfolioid = $this->get_new_parentid('giportfolio');

        $newitemid = $DB->insert_record('giportfolio_chapters', $data);
        $this->set_mapping('giportfolio_chapter', $oldid, $newitemid, true);
    }


    protected function process_giportfolio_contribution($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        $data->chapterid = $this->get_new_parentid('giportfolio_chapter');
        $data->giportfolioid = $this->get_new_parentid('giportfolio');
        $data->timemodified = $this->apply_date_offset($data->timemodified);
        $data->timecreated = time();
        $newitemid = $DB->insert_record('giportfolio_contributions', $data);
        $this->set_mapping('giportfolio_contribution', $oldid, $newitemid, true);
    }

    protected function after_execute() {

        // Add giportfolio related files.
        $this->add_related_files('mod_giportfolio', 'intro', null);
        $this->add_related_files('mod_giportfolio', 'chapter', 'giportfolio_chapter');
        $this->add_related_files('mod_giportfolio', 'contribution', 'giportfolio_contribution');

        $this->add_related_files('mod_giportfolio', 'attachment', 'giportfolio_contribution');
    }
}
