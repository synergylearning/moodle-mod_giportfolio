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
 * Define all the backup steps that will be used by the backup_giportfolio_activity_task
 *
 * @package    mod_giportfolio
 * @copyright  2012 Synergy Learning / Manolescu Dorel (based on book module)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Define the complete giportfolio structure for backup, with file and id annotations
 */
class backup_giportfolio_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated.
        $giportfolio = new backup_nested_element('giportfolio', array('id'),
                                                 array('name', 'intro', 'introformat', 'numbering', 'customtitles',
                                                      'timecreated', 'timemodified', 'collapsesubchapters', 'grade', 'printing',
                                                      'participantadd', 'chapternumber', 'publishnotification', 'notifyaddentry',
                                                      'automaticgrading', 'skipintro', 'myactivitylink'));
        $chapters = new backup_nested_element('chapters');
        $chapter = new backup_nested_element('chapter', array('id'),
                                             array('pagenum', 'subchapter', 'title', 'content', 'contentformat', 'hidden',
                                                  'timemcreated', 'timemodified', 'importsrc', 'userid'));

        $contributions = new backup_nested_element('contributions');
        $contribution = new backup_nested_element('contribution', array('id'),
                                                  array('chapterid', 'pagenum', 'subchapter', 'title', 'content', 'contentformat',
                                                       'hidden', 'timemcreated', 'timemodified', 'importsrc', 'userid'));

        $giportfolio->add_child($chapters);

        $chapters->add_child($chapter);

        $chapter->add_child($contributions);
        $contributions->add_child($contribution);

        // Define sources.
        $giportfolio->set_source_table('giportfolio', array('id' => backup::VAR_ACTIVITYID));
        $chapter->set_source_table('giportfolio_chapters', array('giportfolioid' => backup::VAR_PARENTID));
        if ($userinfo) {
            $contribution->set_source_table('giportfolio_contributions', array('chapterid' => backup::VAR_PARENTID));
        }

        // Define file annotations.
        $giportfolio->annotate_files('mod_giportfolio', 'intro', null); // This file area hasn't itemid.
        $chapter->annotate_files('mod_giportfolio', 'chapter', 'id');
        $contribution->annotate_files('mod_giportfolio', 'contribution', 'id');

        $contribution->annotate_files('mod_giportfolio', 'attachment', 'id');

        // Return the root element (giportfolio), wrapped into standard activity structure.
        return $this->prepare_activity_structure($giportfolio);
    }
}
