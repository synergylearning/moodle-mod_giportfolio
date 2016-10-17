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
 * Genarator tests.
 *
 * @package    mod_giportfolio
 * @copyright  2013 Frédéric Massart
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Genarator tests class.
 *
 * @package    mod_giportfolio
 * @copyright  2013 Frédéric Massart
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_giportfolio_generator_testcase extends advanced_testcase {

    public function test_create_instance() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();

        $this->assertFalse($DB->record_exists('giportfolio', array('course' => $course->id)));
        $giportfolio = $this->getDataGenerator()->create_module('giportfolio', array('course' => $course->id));
        $this->assertEquals(1, $DB->count_records('giportfolio', array('course' => $course->id)));
        $this->assertTrue($DB->record_exists('giportfolio', array('course' => $course->id, 'id' => $giportfolio->id)));

        $params = array('course' => $course->id, 'name' => 'One more giportfolio');
        $giportfolio = $this->getDataGenerator()->create_module('giportfolio', $params);
        $this->assertEquals(2, $DB->count_records('giportfolio', array('course' => $course->id)));
        $this->assertEquals('One more giportfolio', $DB->get_field_select('giportfolio', 'name', 'id = :id', array('id' => $giportfolio->id)));
    }

    public function test_create_chapter() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $giportfolio = $this->getDataGenerator()->create_module('giportfolio', array('course' => $course->id));
        /** @var mod_giportfolio_generator $giportfoliogenerator */
        $giportfoliogenerator = $this->getDataGenerator()->get_plugin_generator('mod_giportfolio');

        $this->assertEquals(1, $DB->count_records('giportfolio_chapters', array('giportfolioid' => $giportfolio->id)));
        $giportfoliogenerator->create_chapter(array('giportfolioid' => $giportfolio->id));
        $this->assertEquals(2, $DB->count_records('giportfolio_chapters', array('giportfolioid' => $giportfolio->id)));

        $chapter = $giportfoliogenerator->create_chapter(array('giportfolioid' => $giportfolio->id, 'content' => 'Yay!', 'title' => 'Oops'));
        $this->assertEquals(3, $DB->count_records('giportfolio_chapters', array('giportfolioid' => $giportfolio->id)));
        $this->assertEquals('Oops', $DB->get_field_select('giportfolio_chapters', 'title', 'id = :id', array('id' => $chapter->id)));
        $this->assertEquals('Yay!', $DB->get_field_select('giportfolio_chapters', 'content', 'id = :id', array('id' => $chapter->id)));

        $chapter = $giportfoliogenerator->create_content($giportfolio);
        $this->assertEquals(4, $DB->count_records('giportfolio_chapters', array('giportfolioid' => $giportfolio->id)));
    }

}
