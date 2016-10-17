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
 * mod_giportfolio data generator
 *
 * @package    mod_giportfolio
 * @category   test
 * @copyright  2013 Davo Smith, Synergy Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


class mod_giportfolio_generator extends testing_module_generator {

    /**
     * @var int keep track of how many chapters have been created.
     */
    protected $chaptercount = 0;

    /**
     * To be called from data reset code only,
     * do not use in tests.
     * @return void
     */
    public function reset() {
        $this->chaptercount = 0;
        parent::reset();
    }

    public function create_instance($record = null, array $options = null) {
        global $CFG;
        require_once("$CFG->dirroot/mod/giportfolio/locallib.php");

        $record = (object)(array)$record;

        if (!isset($record->chapternumber)) {
            $record->chapternumber = 1;
        }
        if (!isset($record->grade)) {
            $record->grade = 100;
        }
        if (!isset($record->numbering)) {
            $record->numbering = PORTFOLIO_NUM_NUMBERS;
        }
        if (!isset($record->customtitles)) {
            $record->customtitles = 0;
        }

        return parent::create_instance($record, (array)$options);
    }

    public function create_chapter($record = null, array $options = null) {
        global $DB;

        $record = (object) (array) $record;
        $options = (array) $options;
        $this->chaptercount++;

        if (empty($record->giportfolioid)) {
            throw new coding_exception('Chapter generator requires $record->giportfolioid');
        }

        if (empty($record->title)) {
            $record->title = "Chapter {$this->chaptercount}";
        }
        if (empty($record->pagenum)) {
            $record->pagenum = 1;
        }
        if (!isset($record->subchapter)) {
            $record->subchapter = 0;
        }
        if (!isset($record->hidden)) {
            $record->hidden = 0;
        }
        if (!isset($record->importsrc)) {
            $record->importsrc = '';
        }
        if (!isset($record->content)) {
            $record->content = "Chapter {$this->chaptercount} content";
        }
        if (!isset($record->contentformat)) {
            $record->contentformat = FORMAT_MOODLE;
        }
        if (!isset($record->timecreated)) {
            $record->timecreated = time();
        }
        if (!isset($record->timemodified)) {
            $record->timemodified = time();
        }
        if (!isset($record->userid)) {
            $record->userid = 0;
        }

        // Make room for new page.
        $sql = "UPDATE {giportfolio_chapters}
                   SET pagenum = pagenum + 1
                 WHERE giportfolioid = ? AND pagenum >= ?";
        $DB->execute($sql, array($record->giportfolioid, $record->pagenum));
        $record->id = $DB->insert_record('giportfolio_chapters', $record);

        $sql = "UPDATE {giportfolio}
                   SET revision = revision + 1
                 WHERE id = ?";
        $DB->execute($sql, array($record->giportfolioid));

        return $record;
    }

    public function create_content($instance, $record = array()) {
        $record = (array)$record + array(
                'giportfolioid' => $instance->id
            );
        return $this->create_chapter($record);
    }
}
