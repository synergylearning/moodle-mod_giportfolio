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
 * Events tests.
 *
 * @package    giportfoliotool_print
 * @category   phpunit
 * @copyright  2013 Frédéric Massart
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;

/**
 * Events tests class.
 *
 * @package    giportfoliotool_print
 * @category   phpunit
 * @copyright  2013 Frédéric Massart
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class giportfoliotool_print_events_testcase extends advanced_testcase {

    public function setUp() {
        $this->resetAfterTest();
    }

    public function test_giportfolio_printed() {
        // There is no proper API to call to test the event, so what we are
        // doing here is simply making sure that the events returns the right information.

        $course = $this->getDataGenerator()->create_course();
        $giportfolio = $this->getDataGenerator()->create_module('giportfolio', array('course' => $course->id));
        $context = context_module::instance($giportfolio->cmid);

        $event = \giportfoliotool_print\event\giportfolio_printed::create_from_giportfolio($giportfolio, $context);

        // Triggering and capturing the event.
        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\giportfoliotool_print\event\giportfolio_printed', $event);
        $this->assertEquals(context_module::instance($giportfolio->cmid), $event->get_context());
        $this->assertEquals($giportfolio->id, $event->objectid);
        $expected = array($course->id, 'giportfolio',  'print', 'tool/print/index.php?id=' . $giportfolio->cmid, $giportfolio->id, $giportfolio->cmid);
        $this->assertEventLegacyLogData($expected, $event);
        $this->assertEventContextNotUsed($event);
    }


    public function test_chapter_printed() {
        // There is no proper API to call to test the event, so what we are
        // doing here is simply making sure that the events returns the right information.

        $course = $this->getDataGenerator()->create_course();
        $giportfolio = $this->getDataGenerator()->create_module('giportfolio', array('course' => $course->id));
        $giportfoliogenerator = $this->getDataGenerator()->get_plugin_generator('mod_giportfolio');
        $chapter = $giportfoliogenerator->create_chapter(array('giportfolioid' => $giportfolio->id));
        $context = context_module::instance($giportfolio->cmid);

        $event = \giportfoliotool_print\event\chapter_printed::create_from_chapter($giportfolio, $context, $chapter);

        // Triggering and capturing the event.
        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\giportfoliotool_print\event\chapter_printed', $event);
        $this->assertEquals(context_module::instance($giportfolio->cmid), $event->get_context());
        $this->assertEquals($chapter->id, $event->objectid);
        $expected = array($course->id, 'giportfolio', 'print chapter', 'tool/print/index.php?id=' . $giportfolio->cmid .
            '&chapterid=' . $chapter->id, $chapter->id, $giportfolio->cmid);
        $this->assertEventLegacyLogData($expected, $event);
        $this->assertEventContextNotUsed($event);
    }

}
