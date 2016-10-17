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
 * giportfolio module upgrade code
 *
 * @package    mod_giportfolio
 * @copyright  2012 Synergy Learning / Manolescu Dorel based on book module
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

function xmldb_giportfolio_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2004081100) {
        throw new upgrade_exception('mod_giportfolio', $oldversion,
                                    'Can not upgrade such an old giportfolio module, sorry, you should have upgraded it long time '.
                                    'ago in 1.9 already.');
    }

    if ($oldversion < 2007052001) {

        // Changing type of field importsrc on table giportfolio_chapters to char.
        $table = new xmldb_table('giportfolio_chapters');
        $field = new xmldb_field('importsrc', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'timemodified');

        // Launch change of type for field importsrc.
        $dbman->change_field_type($table, $field);

        upgrade_mod_savepoint(true, 2007052001, 'giportfolio');
    }

    // ===== 1.9.0 upgrade line ======//

    if ($oldversion < 2010120801) {
        // Rename field summary on table giportfolio to intro.
        $table = new xmldb_table('giportfolio');
        $field = new xmldb_field('summary', XMLDB_TYPE_TEXT, 'small', null, null, null, null, 'name');

        // Launch rename field summary.
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'intro');
        }

        // Giportfolio savepoint reached.
        upgrade_mod_savepoint(true, 2010120801, 'giportfolio');
    }

    if ($oldversion < 2010120802) {
        // Rename field summary on table giportfolio to intro.
        $table = new xmldb_table('giportfolio');
        $field = new xmldb_field('intro', XMLDB_TYPE_TEXT, 'big', null, null, null, null, 'name');

        // Launch rename field summary.
        $dbman->change_field_precision($table, $field);

        // Giportfolio savepoint reached.
        upgrade_mod_savepoint(true, 2010120802, 'giportfolio');
    }

    if ($oldversion < 2010120803) {
        // Define field introformat to be added to giportfolio.
        $table = new xmldb_table('giportfolio');
        $field = new xmldb_field('introformat', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0', 'intro');

        // Launch add field introformat.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Conditionally migrate to html format in intro.
        if ($CFG->texteditors !== 'textarea') {
            $rs = $DB->get_recordset('giportfolio', array('introformat' => FORMAT_MOODLE), '', 'id,intro,introformat');
            foreach ($rs as $b) {
                $b->intro = text_to_html($b->intro, false, false, true);
                $b->introformat = FORMAT_HTML;
                $DB->update_record('giportfolio', $b);
                upgrade_set_timeout();
            }
            unset($b);
            $rs->close();
        }

        // Giportfolio savepoint reached.
        upgrade_mod_savepoint(true, 2010120803, 'giportfolio');
    }

    if ($oldversion < 2010120804) {
        // Define field introformat to be added to giportfolio.
        $table = new xmldb_table('giportfolio_chapters');
        $field = new xmldb_field('contentformat', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0', 'content');

        // Launch add field introformat.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $DB->set_field('giportfolio_chapters', 'contentformat', FORMAT_HTML, array());

        // Giportfolio savepoint reached.
        upgrade_mod_savepoint(true, 2010120804, 'giportfolio');
    }

    if ($oldversion < 2010120805) {
        require_once("$CFG->dirroot/mod/giportfolio/db/upgradelib.php");

        $sqlfrom = "FROM {giportfolio} b
                    JOIN {modules} m ON m.name = 'giportfolio'
                    JOIN {course_modules} cm ON (cm.module = m.id AND cm.instance = b.id)";

        $count = $DB->count_records_sql("SELECT COUNT('x') $sqlfrom");

        if ($rs = $DB->get_recordset_sql("SELECT b.id, b.course, cm.id AS cmid $sqlfrom ORDER BY b.course, b.id")) {

            $pbar = new progress_bar('migrategiportfoliofiles', 500, true);

            $i = 0;
            foreach ($rs as $giportfolio) {
                $i++;
                upgrade_set_timeout(360); // Set up timeout, may also abort execution.
                $pbar->update($i, $count, "Migrating giportfolio files - $i/$count.");

                $context = context_module::instance($giportfolio->cmid);

                mod_giportfolio_migrate_moddata_dir_to_legacy($giportfolio, $context, '/');

                // Remove dirs if empty.
                @rmdir("$CFG->dataroot/$giportfolio->course/$CFG->moddata/giportfolio/$giportfolio->id/");
                @rmdir("$CFG->dataroot/$giportfolio->course/$CFG->moddata/giportfolio/");
                @rmdir("$CFG->dataroot/$giportfolio->course/$CFG->moddata/");
                @rmdir("$CFG->dataroot/$giportfolio->course/");
            }
            $rs->close();
        }

        // Giportfolio savepoint reached.
        upgrade_mod_savepoint(true, 2010120805, 'giportfolio');
    }

    if ($oldversion < 2011011600) {
        // Define field disableprinting to be dropped from giportfolio.
        $table = new xmldb_table('giportfolio');
        $field = new xmldb_field('disableprinting');

        // Conditionally launch drop field disableprinting.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Giportfolio savepoint reached.
        upgrade_mod_savepoint(true, 2011011600, 'giportfolio');
    }

    if ($oldversion < 2011011601) {
        unset_config('giportfolio_tocwidth');

        // Giportfolio savepoint reached.
        upgrade_mod_savepoint(true, 2011011601, 'giportfolio');
    }

    if ($oldversion < 2011090800) {
        require_once("$CFG->dirroot/mod/giportfolio/db/upgradelib.php");

        mod_giportfolio_migrate_all_areas();

        upgrade_mod_savepoint(true, 2011090800, 'giportfolio');
    }

    if ($oldversion < 2011100900) {

        // Define field revision to be added to giportfolio.
        $table = new xmldb_table('giportfolio');
        $field = new xmldb_field('revision', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'customtitles');

        // Conditionally launch add field revision.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Giportfolio savepoint reached.
        upgrade_mod_savepoint(true, 2011100900, 'giportfolio');
    }

    // SYNERGY - add new 'collapsesubchapters' field to db.
    if ($oldversion < 2011110902) {
        // Define field collapsesubchapters to be added to giportfolio.
        $table = new xmldb_table('giportfolio');
        $field = new xmldb_field('collapsesubchapters', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'timemodified');

        // Conditionally launch add field collapsesubchapters.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Giportfolio savepoint reached.
        upgrade_mod_savepoint(true, 2011110912, 'giportfolio');
    }
    // SYNERGY - add new 'collapsesubchapters' field to db.
    // SYNERGY - add new 'grade' field to db.
    if ($oldversion < 2011110903) {

        // Define field grade to be added to giportfolio.
        $table = new xmldb_table('giportfolio');
        $field = new xmldb_field('grade', XMLDB_TYPE_INTEGER, '3', null, null, null, null, 'collapsesubchapters');

        // Conditionally launch add field grade.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Giportfolio savepoint reached.
        upgrade_mod_savepoint(true, 2011110912, 'giportfolio');
    }

    if ($oldversion < 2011110907) {

        // Define field printing to be added to giportfolio.
        $table = new xmldb_table('giportfolio');
        $field = new xmldb_field('printing', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'grade');

        // Conditionally launch add field printing.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Giportfolio savepoint reached.
        upgrade_mod_savepoint(true, 2011110912, 'giportfolio');
    }

    if ($oldversion < 2011110907) {

        // Define field participantadd to be added to giportfolio.
        $table = new xmldb_table('giportfolio');
        $field = new xmldb_field('participantadd', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'printing');

        // Conditionally launch add field participantadd.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Giportfolio savepoint reached.
        upgrade_mod_savepoint(true, 2011110907, 'giportfolio');
    }

    if ($oldversion < 2011110910) {

        // Define field chapternumber to be added to giportfolio.
        $table = new xmldb_table('giportfolio');
        $field = new xmldb_field('chapternumber', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, '0', 'participantadd');

        // Conditionally launch add field chapternumber.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Giportfolio savepoint reached.
        upgrade_mod_savepoint(true, 2011110912, 'giportfolio');
    }

    if ($oldversion < 2011110912) {

        // Define field publishnotification to be added to giportfolio.
        $table = new xmldb_table('giportfolio');
        $field = new xmldb_field('publishnotification', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'chapternumber');

        // Conditionally launch add field publishnotification.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Giportfolio savepoint reached.
        upgrade_mod_savepoint(true, 2011110912, 'giportfolio');
    }

    if ($oldversion < 2011110916) {

        // Define table giportfolio_contributions to be created.
        $table = new xmldb_table('giportfolio_contributions');

        // Adding fields to table giportfolio_contributions.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('chapterid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('giportfolioid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('pagenum', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('subchapter', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('title', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('content', XMLDB_TYPE_TEXT, 'big', null, XMLDB_NOTNULL, null, null);
        $table->add_field('contentformat', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, null);
        $table->add_field('hidden', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table giportfolio_contributions.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for giportfolio_contributions.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Giportfolio savepoint reached.
        upgrade_mod_savepoint(true, 2011110916, 'giportfolio');
    }

    if ($oldversion < 2011110920) {

        // Define table giportfolio_status to be created.
        $table = new xmldb_table('giportfolio_status');

        // Adding fields to table giportfolio_status.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('giportfolioid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('status', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table giportfolio_status.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for giportfolio_status.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Giportfolio savepoint reached.
        upgrade_mod_savepoint(true, 2011110920, 'giportfolio');
    }

    if ($oldversion < 2011110926) {

        // Define table giportfolio_userchapters to be created.
        $table = new xmldb_table('giportfolio_userchapters');

        // Adding fields to table giportfolio_userchapters.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('giportfolioid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('pagenum', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('subchapter', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('title', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('content', XMLDB_TYPE_TEXT, 'big', null, XMLDB_NOTNULL, null, null);
        $table->add_field('contentformat', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, null);
        $table->add_field('hidden', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('importsrc', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('iduser', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table giportfolio_userchapters.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for giportfolio_userchapters.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Giportfolio savepoint reached.
        upgrade_mod_savepoint(true, 2011110926, 'giportfolio');
    }

    if ($oldversion < 2012072600) {
        // Clear the course cache for any courses with Portfolios in them.
        $moduleid = $DB->get_field('modules', 'id', array('name' => 'giportfolio'));
        $courseids = $DB->get_fieldset_select('course_modules', 'DISTINCT course', 'module = ?', array($moduleid));
        foreach ($courseids as $courseid) {
            rebuild_course_cache($courseid, true);
        }

        // Move any portofolio grades over to the GIPortfolio module.
        $DB->set_field('grade_items', 'itemmodule', 'giportfolio', array('itemmodule' => 'portofolio'));
    }

    if ($oldversion < 2013071700) {

        // Define field newentrynotification to be added to giportfolio.
        $table = new xmldb_table('giportfolio');
        $field = new xmldb_field('notifyaddentry', XMLDB_TYPE_INTEGER, '4', null, null, null, '0', 'publishnotification');

        // Conditionally launch add field newentrynotification.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Giportfolio savepoint reached.
        upgrade_mod_savepoint(true, 2013071700, 'giportfolio');
    }

    if ($oldversion < 2015061600) {

        // Define field skipintro to be added to giportfolio.
        $table = new xmldb_table('giportfolio');
        $field = new xmldb_field('skipintro', XMLDB_TYPE_INTEGER, '4', null, null, null, '0');

        // Conditionally launch add field skipintro.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Giportfolio savepoint reached.
        upgrade_mod_savepoint(true, 2015061600, 'giportfolio');
    }

    if ($oldversion < 2015061601) {

        // Define field myactivitylink to be added to giportfolio.
        $table = new xmldb_table('giportfolio');
        $field = new xmldb_field('myactivitylink', XMLDB_TYPE_INTEGER, '4', null, null, null, '1');

        // Conditionally launch add field myactivitylink.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Giportfolio savepoint reached.
        upgrade_mod_savepoint(true, 2015061601, 'giportfolio');
    }

    if ($oldversion < 2015061701) {

        // Define field shared to be added to giportfolio_contributions.
        $table = new xmldb_table('giportfolio_contributions');
        $field = new xmldb_field('shared', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0', 'userid');

        // Conditionally launch add field shared.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Giportfolio savepoint reached.
        upgrade_mod_savepoint(true, 2015061701, 'giportfolio');
    }

    if ($oldversion < 2015061702) {

        // Define field userid to be added to giportfolio_chapters.
        $table = new xmldb_table('giportfolio_chapters');
        $field = new xmldb_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'importsrc');

        // Conditionally launch add field userid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define key userid (foreign) to be added to giportfolio_chapters.
        $key = new xmldb_key('userid', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));

        // Launch add key userid.
        $dbman->add_key($table, $key);

        // Giportfolio savepoint reached.
        upgrade_mod_savepoint(true, 2015061702, 'giportfolio');
    }

    if ($oldversion < 2015061703) {
        require_once($CFG->dirroot.'/mod/giportfolio/db/upgradelib.php');

        mtrace('Migrating Portfolio user chapters - this may take a long time if there are a lot of user chapters');
        mod_giportfolio_migrate_userchapters();

        // Giportfolio savepoint reached.
        upgrade_mod_savepoint(true, 2015061703, 'giportfolio');
    }

    if ($oldversion < 2015061704) {
        // Define field klassenbuchtrainer to be added to giportfolio.
        $table = new xmldb_table('giportfolio');
        $field = new xmldb_field('klassenbuchtrainer', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0', 'myactivitylink');

        // Conditionally launch add field klassenbuchtrainer.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Giportfolio savepoint reached.
        upgrade_mod_savepoint(true, 2015061704, 'giportfolio');
    }

    if ($oldversion < 2015090300) {

        // Define field newentrynotification to be added to giportfolio.
        $table = new xmldb_table('giportfolio');
        $field = new xmldb_field('automaticgrading', XMLDB_TYPE_INTEGER, '4', null, null, null, '0', 'notifyaddentry');

        // Conditionally launch add field newentrynotification.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Giportfolio savepoint reached.
        upgrade_mod_savepoint(true, 2015090300, 'giportfolio');
    }
    // SYNERGY - end add new fields to database.

    return true;
}
