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
 * giportfolio module core interaction API
 *
 * @package    mod_giportfolio
 * @copyright  2004-2011 Petr Skoda  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Returns list of available numbering types
 * @return array
 */
function giportfolio_get_numbering_types() {
    require_once(dirname(__FILE__).'/locallib.php');

    return array(
        PORTFOLIO_NUM_NONE => get_string('numbering0', 'mod_giportfolio'),
        PORTFOLIO_NUM_NUMBERS => get_string('numbering1', 'mod_giportfolio'),
        PORTFOLIO_NUM_BULLETS => get_string('numbering2', 'mod_giportfolio'),
        PORTFOLIO_NUM_INDENTED => get_string('numbering3', 'mod_giportfolio')
    );
}

/**
 * Returns all other caps used in module
 * @return array
 */
function giportfolio_get_extra_capabilities() {
    // Used for group-members-only.
    return array('moodle/site:accessallgroups');
}

/**
 * Add giportfolio instance.
 *
 * @param stdClass $data
 * @param stdClass $mform
 * @return int new giportfolio instance id
 */
function giportfolio_add_instance($giportfolio, $mform) {
    global $DB;

    $giportfolio->timecreated = time();
    $giportfolio->timemodified = $giportfolio->timecreated;
    if (!isset($giportfolio->customtitles)) {
        $giportfolio->customtitles = 0;
    }

    // Add custom fileds for each module.
    if (!isset($giportfolio->printing)) {
        $giportfolio->printing = 0;
    }
    if (!isset($giportfolio->publishnotification)) {
        $giportfolio->publishnotification = 0;
    }

    // Synergy add grade item and default chapters.

    $giportfolio->id = $DB->insert_record('giportfolio', $giportfolio);

    if ($giportfolio) {
        for ($ch = 0; $ch < $giportfolio->chapternumber; $ch++) {
            $initchapter = new stdClass();
            $initchapter->giportfolioid = $giportfolio->id;
            $initchapter->pagenum = $ch + 1;
            $initchapter->subchapter = 0;
            $initchapter->title = 'Chapter'.($ch + 1);
            $initchapter->content = '<p>'.get_string('addcontent', 'mod_giportfolio').'</p><hr><p></p>';
            $initchapter->contentformat = '1';
            $initchapter->hidden = '0';
            $initchapter->timecreated = time();
            $initchapter->timemodified = time();
            $initchapter->importsrc = '';
            $initchapter->userid = 0;
            $DB->insert_record('giportfolio_chapters', $initchapter);
        }
    }

    giportfolio_grade_item_update($giportfolio);
    return $giportfolio->id;
}

/**
 * Update giportfolio instance.
 *
 * @param stdClass $data
 * @param stdClass $mform
 * @return bool true
 */
function giportfolio_update_instance($data, $mform) {
    global $DB;

    $data->timemodified = time();

    $regrade = $data->automaticgrading
        && $data->chapternumber != $DB->get_field('giportfolio', 'chapternumber', array('id' => $data->instance));

    $data->id = $data->instance;
    if (!isset($data->customtitles)) {
        $data->customtitles = 0;
    }
    if (!isset($data->printing)) {
        $data->printing = 0;
    }

    if (!isset($data->publishnotification)) {
        $data->publishnotification = 0;
    }

    $DB->update_record('giportfolio', $data);

    $giportfolio = $DB->get_record('giportfolio', array('id' => $data->id));
    $DB->set_field('giportfolio', 'revision', $giportfolio->revision + 1, array('id' => $giportfolio->id));

    if ($regrade) {
        giportfolio_regrade($giportfolio);
    }

    return true;
}

/**
 *
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id
 * @return bool success
 */
function giportfolio_delete_instance($id) {
    global $DB, $CFG;

    if (!$giportfolio = $DB->get_record('giportfolio', array('id' => $id))) {
        return false;
    }

    // Delete grade item.
    giportfolio_grade_item_delete($giportfolio);

    $DB->delete_records('giportfolio_contributions', array('giportfolioid' => $giportfolio->id));
    $DB->delete_records('giportfolio_chapters', array('giportfolioid' => $giportfolio->id));
    $DB->delete_records('giportfolio', array('id' => $giportfolio->id));

    if ($giportfolio->klassenbuchtrainer && giportfolio_include_klassenbuchtrainer()) {
        klassenbuchtool_lernschritte_delete_instance($giportfolio, 'giportfolio');
    }

    return true;
}

function giportfolio_include_klassenbuchtrainer() {
    global $CFG;
    if (!file_exists($CFG->dirroot.'/mod/klassenbuch/tool/lernschritte/lib.php')) {
        return false;
    }
    require_once($CFG->dirroot.'/mod/klassenbuch/tool/lernschritte/lib.php');
    return true;
}

/**
 * Return use outline
 *
 * @param stdClass $course
 * @param stdClass $user
 * @param stdClass $mod
 * @param object $giportfolio
 * @return object|null
 */
function giportfolio_user_outline($course, $user, $mod, $giportfolio) {
    global $DB;

    if ($logs = $DB->get_records('log', array('userid' => $user->id, 'module' => 'giportfolio',
                                             'action' => 'view', 'info' => $giportfolio->id), 'time ASC')) {

        $numviews = count($logs);
        $lastlog = array_pop($logs);

        $result = new stdClass();
        $result->info = get_string('numviews', '', $numviews);
        $result->time = $lastlog->time;

        return $result;
    }
    return null;
}

/**
 * Print a detailed representation of what a  user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @param $course
 * @param $user
 * @param $mod
 * @param $giportfolio
 * @return bool
 */
function giportfolio_user_complete($course, $user, $mod, $giportfolio) {
    return true;
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in giportfolio activities and print it out.
 * Return true if there was output, or false is there was none.
 * @param $course
 * @param $isteacher
 * @param $timestart
 * @return bool
 */
function giportfolio_print_recent_activity($course, $isteacher, $timestart) {
    return false; // True if anything was printed, otherwise false.
}

function giportfolio_regrade($instance) {
    global $DB;
    $contributors = $DB->get_fieldset_sql('SELECT DISTINCT userid FROM {giportfolio_contributions} WHERE giportfolioid = ?',
            array('id' => $instance->id));
    foreach ($contributors as $userid) {
        giportfolio_automatic_grading($instance, $userid);
    }
}

/**
 * No cron in giportfolio.
 *
 * @return bool
 */
function giportfolio_cron() {
    return true;
}

/**
 * No grading in giportfolio.
 *
 * @param $giportfolioid
 * @return null
 */
function giportfolio_grades($giportfolioid) {
    return true;
}

// Gradebook API.

function giportfolio_get_user_grades($giportfolio, $userid = 0) {
    return false;
}

/**
 * Update activity grades
 *
 * @param object $giportfolio
 * @param int $userid specific user only, 0 means all
 * @param bool $nullifnone
 */
function giportfolio_update_grades($giportfolio, $userid = 0, $nullifnone = true) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    if ($giportfolio->grade == 0) {
        giportfolio_grade_item_update($giportfolio);

    } else if ($grades = giportfolio_get_user_grades($giportfolio, $userid)) {
        foreach ($grades as $k => $v) {
            if ($v->rawgrade == -1) {
                $grades[$k]->rawgrade = null;
            }
        }
        giportfolio_grade_item_update($giportfolio, $grades);

    } else {
        giportfolio_grade_item_update($giportfolio);
    }
}

/**
 * Update all grades in gradebook.
 */
function giportfolio_upgrade_grades() {
    global $DB;

    $sql = "SELECT COUNT('x')
              FROM {giportfolio} a, {course_modules} cm, {modules} m
             WHERE m.name='giportfolio' AND m.id=cm.module AND cm.instance=a.id";
    $count = $DB->count_records_sql($sql);

    $sql = "SELECT a.*, cm.idnumber AS cmidnumber, a.course AS courseid
              FROM {giportfolio} a, {course_modules} cm, {modules} m
             WHERE m.name='giportfolio' AND m.id=cm.module AND cm.instance=a.id";
    $rs = $DB->get_recordset_sql($sql);
    if ($rs->valid()) {
        // Too much debug output.
        $pbar = new progress_bar('giportfolioupgradegrades', 500, true);
        $i = 0;
        foreach ($rs as $giportfolio) {
            $i++;
            upgrade_set_timeout(60 * 5); // Set up timeout, may also abort execution.
            giportfolio_update_grades($giportfolio);
            $pbar->update($i, $count, "Updating giportfolio grades ($i/$count).");
        }
        upgrade_set_timeout(); // Reset to default timeout.
    }
    $rs->close();
}

/**
 * Create grade item for given giportfolio
 *
 * @param object $giportfolio object with extra cmidnumber
 * @param mixed optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return int 0 if ok, error code otherwise
 */
function giportfolio_grade_item_update($giportfolio, $grades = null) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    if (array_key_exists('cmidnumber', $giportfolio)) { // May not be always present.
        $params = array('itemname' => $giportfolio->name, 'idnumber' => $giportfolio->cmidnumber);
    } else {
        $params = array('itemname' => $giportfolio->name);
    }

    if (!isset($giportfolio->courseid)) {
        $giportfolio->courseid = $giportfolio->course;
    }

    if ($giportfolio->grade > 0) {
        $params['gradetype'] = GRADE_TYPE_VALUE;
        $params['grademax'] = $giportfolio->grade;
        $params['grademin'] = 0;

    } else if ($giportfolio->grade < 0) {
        $params['gradetype'] = GRADE_TYPE_SCALE;
        $params['scaleid'] = -$giportfolio->grade;

    } else {
        $params['gradetype'] = GRADE_TYPE_TEXT; // Allow text comments only.
    }

    if ($grades === 'reset') {
        $params['reset'] = true;
        $grades = null;
    }

    return grade_update('mod/giportfolio', $giportfolio->courseid, 'mod', 'giportfolio', $giportfolio->id, 0, $grades, $params);
}

/**
 * Delete grade item for given giportfolio
 *
 * @param object $giportfolio object
 * @return object giportfolio
 */
function giportfolio_grade_item_delete($giportfolio) {
    return true;
}

function giportfolio_get_participants($giportfolioid) {
    // Must return an array of user records (all data) who are participants
    // for a given instance of giportfolio. Must include every user involved
    // in the instance, independent of his role (student, teacher, admin...)
    // See other modules as example.

    return false;
}

/**
 * This function returns if a scale is being used by one giportfolio
 * it it has support for grading and scales. Commented code should be
 * modified if necessary. See giportfolio, glossary or journal modules
 * as reference.
 *
 * @param $giportfolioid int
 * @param $scaleid int
 * @return boolean True if the scale is used by any journal
 */
function giportfolio_scale_used($giportfolioid, $scaleid) {
    return false;
}

/**
 * Checks if scale is being used by any instance of giportfolio
 *
 * This is used to find out if scale used anywhere
 *
 * @param $scaleid int
 * @return boolean True if the scale is used by any journal
 */
function giportfolio_scale_used_anywhere($scaleid) {
    return false;
}

/**
 * Return read actions.
 * @return array
 */
function giportfolio_get_view_actions() {
    $return = array('view', 'view all');

    $plugins = get_plugin_list('giportfoliotool');
    foreach ($plugins as $plugin => $dir) {
        if (file_exists("$dir/lib.php")) {
            require_once("$dir/lib.php");
        }
        $function = 'giportfoliotool_'.$plugin.'_get_view_actions';
        if (function_exists($function)) {
            if ($actions = $function()) {
                $return = array_merge($return, $actions);
            }
        }
    }

    return $return;
}

/**
 * Return write actions.
 * @return array
 */
function giportfolio_get_post_actions() {
    $return = array('update');

    $plugins = get_plugin_list('giportfoliotool');
    foreach ($plugins as $plugin => $dir) {
        if (file_exists("$dir/lib.php")) {
            require_once("$dir/lib.php");
        }
        $function = 'giportfoliotool_'.$plugin.'_get_post_actions';
        if (function_exists($function)) {
            if ($actions = $function()) {
                $return = array_merge($return, $actions);
            }
        }
    }

    return $return;
}

/**
 * Supported features
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know
 */
function giportfolio_supports($feature) {
    switch ($feature) {
        case FEATURE_GROUPS:
            return true;
        case FEATURE_GROUPINGS:
            return true;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return true;
        case FEATURE_GRADE_OUTCOMES:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_ADVANCED_GRADING:
            return true;
        case FEATURE_COMPLETION_HAS_RULES:
            return false;

        default:
            return null;
    }
}

/**
 * Adds module specific settings to the settings block
 *
 * @param settings_navigation $settingsnav The settings navigation object
 * @param navigation_node $giportfolionode The node to add module settings to
 * @return void
 */
function giportfolio_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $giportfolionode) {
    global $USER, $PAGE, $DB;

    if ($PAGE->cm->modname !== 'giportfolio') {
        return;
    }

    $context = context_module::instance($PAGE->cm->id);
    $plugins = get_plugin_list('giportfoliotool');
    foreach ($plugins as $plugin => $dir) {
        if (file_exists("$dir/lib.php")) {
            require_once("$dir/lib.php");
        }
        $function = 'giportfoliotool_'.$plugin.'_extend_settings_navigation';
        if (function_exists($function)) {
            $function($settingsnav, $giportfolionode);
        }
    }

    $params = $PAGE->url->params();

    // SYNERGY - add grade console link.
    if (!empty($params['id']) and !empty($params['chapterid']) and
        has_capability('mod/giportfolio:viewgiportfolios', $context)) {

        $gradeconsole = get_string('studentgiportfolio', 'mod_giportfolio');
        $url = new moodle_url('/mod/giportfolio/submissions.php', array('id' => $params['id']));
        $giportfolionode->add($gradeconsole, $url, navigation_node::TYPE_SETTING, null, null,
                              new pix_icon('console', '', 'giportfoliotool_print', array('class' => 'icon')));
    }
    // Add publish- unpublish links only if the user has at least one contribution.

    $allowedit = has_capability('mod/giportfolio:edit', $context);
    $giportfolio = $DB->get_record('giportfolio', array('id' => $PAGE->cm->instance), '*', MUST_EXIST);

    if (!empty($params['id']) && !empty($params['chapterid']) && !$allowedit &&
        giportfolio_get_user_contribution_status($giportfolio->id, $USER->id)) {

        if (!$giportfolio->klassenbuchtrainer) {
            $url = new moodle_url('/mod/giportfolio/tool/print/pdfgiportfolio.php', array(
                'id' => $params['id'], 'sesskey' => sesskey()
            )); // Add pdf export link.
            // Open as new window.
            $action = new action_link($url, get_string('exportpdf', 'mod_giportfolio'), new popup_action('click', $url));
            $giportfolionode->add(get_string('exportpdf', 'mod_giportfolio'), $action, navigation_node::TYPE_SETTING, null, null,
                                  new pix_icon('pdf', '', 'giportfoliotool_print', array('class' => 'icon')));

            // SYNERGY LEARNING - Export as zip option.
            $url = new moodle_url('/mod/giportfolio/tool/export/zipgiportfolio.php', array(
                'id' => $params['id']
            )); // Add zip export link.
            $giportfolionode->add(get_string('exportzip', 'mod_giportfolio'), $url, navigation_node::TYPE_SETTING, null, null,
                                  new pix_icon('zip', '', 'giportfoliotool_export', array('class' => 'icon')));
            // END SYNERGY LEARNING - Export as zip option.
        }

    }

    // Turn student editing on.
    if (!empty($params['id']) and !empty($params['chapterid']) and
        (giportfolio_get_collaborative_status($giportfolio)) and !$allowedit) {

        $useredit = optional_param('useredit', 0, PARAM_BOOL); // Edit mode.
        if (!empty($useredit)) {
            $tocedit = get_string('stopedit', 'mod_giportfolio');
            $edit = '0';
        } else {
            $tocedit = get_string('edityourchapters', 'mod_giportfolio');
            $edit = '1';
        }
        $url = new moodle_url('/mod/giportfolio/viewgiportfolio.php', array(
                                                                           'id' => $params['id'],
                                                                           'chapterid' => $params['chapterid'],
                                                                           'sesskey' => sesskey(), 'useredit' => $edit
                                                                      ));
        $giportfolionode->add($tocedit, $url, navigation_node::TYPE_SETTING, null, null,
                              new pix_icon('editstatus', '', 'giportfoliotool_print', array('class' => 'icon')));
    }

    // SYNERGY.

    if (!empty($params['id']) and !empty($params['chapterid']) and has_capability('mod/giportfolio:edit', $context)) {
        if (!empty($USER->editing)) {
            $string = get_string("turneditingoff");
            $edit = '0';
        } else {
            $string = get_string("turneditingon");
            $edit = '1';
        }
        $url = new moodle_url('/mod/giportfolio/viewgiportfolio.php', array(
                                                                           'id' => $params['id'],
                                                                           'chapterid' => $params['chapterid'], 'edit' => $edit,
                                                                           'sesskey' => sesskey()
                                                                      ));
        $giportfolionode->add($string, $url, navigation_node::TYPE_SETTING);
    }
}

/**
 * if return=html, then return a html string.
 * if return=text, then return a text-only string.
 * otherwise, print HTML for non-images, and return image HTML
 *     if attachment is an image, $align set its aligment.
 *
 * @global object
 * @global object
 * @param object $contribution
 * @param object $cm
 * @param string $type html, txt, empty
 * @param string $align left or right
 * @return string image string or nothing depending on $type param
 */
function giportfolio_print_attachments($contribution, $cm, $type = null, $align = "right") {
    global $OUTPUT;

    if (!$context = context_module::instance($cm->id)) {
        return '';
    }
    $filecontext = $context;
    $strattachment = get_string('attachment', 'giportfolio');

    $fs = get_file_storage();

    $output = '';
    /** @var stored_file[] $files */
    if ($files = $fs->get_area_files($filecontext->id, 'mod_giportfolio', 'attachment', $contribution->id, "timemodified", false)) {
        foreach ($files as $file) {
            $filename = $file->get_filename();
            $mimetype = $file->get_mimetype();
            $iconimage = '<img src="'.$OUTPUT->pix_url(file_mimetype_icon($mimetype)).'" class="icon" alt="'.$mimetype.'" />';
            $path = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(),
                                                    $file->get_itemid(), $file->get_filepath(), $file->get_filename());

            if ($type == 'html') {
                $output .= "<a href=\"$path\">$iconimage</a> ";
                $output .= "<a href=\"$path\">".s($filename)."</a>";
                $output .= "<br />";

            } else if ($type == 'text') {
                $output .= "$strattachment ".s($filename).":\n$path\n";

            } else {
                if (in_array($mimetype, array('image/gif', 'image/jpeg', 'image/png', 'image/jpg'))) {
                    // Image attachments don't get printed as links.
                    $output .= "<a href=\"$path\">$iconimage</a> ";
                    $output .= format_text("<a href=\"$path\">".s($filename)."</a>", FORMAT_HTML, array('context' => $context));
                    $output .= '<br />';
                } else {
                    $output .= "<a href=\"$path\">$iconimage</a> ";
                    $output .= format_text("<a href=\"$path\">".s($filename)."</a>", FORMAT_HTML, array('context' => $context));
                    $output .= '<br />';
                }
            }
        }
    }

    return $output;
}

/**
 * Lists all browsable file areas
 * @param object $course
 * @param object $cm
 * @param object $context
 * @return array
 */
function giportfolio_get_file_areas($course, $cm, $context) {
    $areas = array();
    $areas['chapter'] = get_string('chapters', 'mod_giportfolio');
    $areas['contribution'] = get_string('contributions', 'mod_giportfolio');
    $areas['attachment'] = get_string('attachment', 'mod_giportfolio');
    return $areas;
}

/**
 * File browsing support for giportfolio module ontent area.
 * @param object $browser
 * @param object $areas
 * @param object $course
 * @param object $cm
 * @param object $context
 * @param string $filearea
 * @param int $itemid
 * @param string $filepath
 * @param string $filename
 * @return object file_info instance or null if not found
 */
function giportfolio_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    global $CFG, $DB;

    // Note: 'intro' area is handled in file_browser automatically.
    if (!has_capability('mod/giportfolio:view', $context)) {
        return null;
    }

    if ($filearea !== 'chapter' && $filearea !== 'attachment' && $filearea !== 'contribution') {
        return null;
    }

    require_once("$CFG->dirroot/mod/giportfolio/locallib.php");

    if (is_null($itemid)) {
        return new giportfolio_file_info($browser, $course, $cm, $context, $areas, $filearea, $itemid);
    }

    $fs = get_file_storage();
    $filepath = is_null($filepath) ? '/' : $filepath;
    $filename = is_null($filename) ? '.' : $filename;
    if (!$storedfile = $fs->get_file($context->id, 'mod_giportfolio', $filearea, $itemid, $filepath, $filename)) {
        return null;
    }

    // Modifications may be tricky - may cause caching problems.
    $canwrite = has_capability('mod/giportfolio:view', $context);

    $chaptername = $DB->get_field('giportfolio_chapters', 'title', array('giportfolioid' => $cm->instance, 'id' => $itemid));
    $chaptername = format_string($chaptername, true, array('context' => $context));

    $urlbase = $CFG->wwwroot.'/pluginfile.php';
    return new file_info_stored($browser, $context, $storedfile, $urlbase, $chaptername, true, true, $canwrite, false);
}

/**
 * Serves the giportfolio attachments. Implements needed access control ;-)
 *
 * @param object $course
 * @param object $cm
 * @param object $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @return bool false if file not found, does not return if found - just send the file
 */
function giportfolio_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload) {
    global $DB, $USER;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_course_login($course, true, $cm);

    if ($filearea !== 'chapter' && $filearea !== 'contribution' && $filearea !== 'attachment') {
        return false;
    }

    if (!has_capability('mod/giportfolio:view', $context)) {
        return false;
    }

    $chid = (int)array_shift($args);

    if (!$giportfolio = $DB->get_record('giportfolio', array('id' => $cm->instance))) {
        return false;
    }
    // SYNERGY check if chapter is also in user chapters table.
    if ($filearea === 'chapter') {
        if (!$chapter = $DB->get_record('giportfolio_chapters', array('id' => $chid, 'giportfolioid' => $giportfolio->id))) {
            return false;
        }
        if ($chapter->userid && $chapter->userid != $USER->id) {
            if (!has_capability('mod/giportfolio:viewgiportfolios', $context)) {
                return false;
            }
        }
    }

    if ($filearea === 'contribution') {
        if (!$contribution = $DB->get_record('giportfolio_contributions', array(
                                                                               'id' => $chid, 'giportfolioid' => $giportfolio->id
                                                                          ))
        ) {
            return false;
        }
        if ($contribution->userid != $USER->id) {
            // The contribution belongs to another user.
            if (!$contribution->shared && !has_capability('mod/giportfolio:viewgiportfolios', $context)) {
                // The contribution has not been shared and the viewing user does not have permission to view portfolios.
                return false;
            }
        }
        if (!$chapter = $DB->get_record('giportfolio_chapters', array('id' => $contribution->chapterid,
                                                                      'giportfolioid' => $giportfolio->id))) {
            return false;
        }
        if ($chapter->userid && $chapter->userid != $USER->id) {
            if (!has_capability('mod/giportfolio:viewgiportfolios', $context)) {
                return false;
            }
        }
    }

    if (isset($chapter)) {
        if ($chapter->hidden and !has_capability('mod/giportfolio:viewhiddenchapters', $context)) {
            return false;
        }
    }

    $fs = get_file_storage();
    $relativepath = implode('/', $args);

    if ($filearea === 'chapter') {
        $fullpath = "/$context->id/mod_giportfolio/chapter/$chid/$relativepath";
    } else if ($filearea === 'contribution') {
        $fullpath = "/$context->id/mod_giportfolio/contribution/$chid/$relativepath";
    } else if ($filearea === 'attachment') {
        $fullpath = "/$context->id/mod_giportfolio/attachment/$chid/$relativepath";
    }

    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        return false;
    }

    // Finally send the file.
    send_stored_file($file, 360, 0, false);
}

/**
 * Return a list of page types
 *
 * @param string $pagetype current page type
 * @param stdClass $parentcontext Block's parent context
 * @param stdClass $currentcontext Current context of block
 * @return array
 */
function giportfolio_page_type_list($pagetype, $parentcontext, $currentcontext) {
    $modulepagetype = array('mod-giportfolio-*' => get_string('page-mod-giportfolio-x', 'mod_giportfolio'));
    return $modulepagetype;
}

function mod_giportfolio_comment_validate($opts) {
    global $DB, $USER;

    if ($opts->commentarea != 'giportfolio_contribution') {
        return false; // Invalid comment area.
    }

    if (!has_capability('mod/giportfolio:view', $opts->context)) {
        return false; // No access to this portfolio activity.
    }

    if (!has_capability('mod/giportfolio:viewgiportfolios', $opts->context)) {
        // Not able to view other user's portfolioes => check if this is this user's contribution.
        $userid = $DB->get_field('giportfolio_contributions', 'userid', array(
                                                                             'id' => $opts->itemid,
                                                                             'giportfolioid' => $opts->cm->instance
                                                                        ));
        if ($userid != $USER->id) {
            return false;
        }
    }

    return true;
}

function giportfolio_automatic_grading($giportfolio, $userid) {
    if ($giportfolio->automaticgrading) {
        global $DB;
        $sql = "SELECT COUNT(id) FROM {giportfolio_chapters} WHERE giportfolioid = ?";
        $chapters = $DB->get_field_sql($sql, array($giportfolio->id));
        $sql = "SELECT COUNT(DISTINCT chapterid) FROM {giportfolio_contributions} WHERE userid=? AND giportfolioid=?";
        $params = array($userid, $giportfolio->id);
        $complete = $DB->get_field_sql($sql, $params);

        $progress = $complete / $chapters * 100;
        $grade = array(
                        'userid' => $userid,
                        'rawgrade' => $progress,
        );
        giportfolio_grade_item_update($giportfolio, $grade);
    }
}

function mod_giportfolio_comment_permissions($opts) {
    global $DB, $USER;
    $viewcap = true;
    $postcap = true;

    // Already checked that the user has access to the comments (mod_giportfolio_comment_validate).
    // Now just need to check if has capability to add comments.
    if (!has_capability('mod/giportfolio:viewgiportfolios', $opts->context)) {
        $contribution = $DB->get_record('giportfolio_contributions', array('id' => $opts->itemid), 'id, userid', MUST_EXIST);
        if ($contribution->userid != $USER->id) {
            $postcap = false;
        }
    }

    return array('view' => $viewcap, 'post' => $postcap);
}

function mod_giportfolio_cm_info_view(cm_info $cm) {
    global $USER, $DB;

    static $enabled = null;
    if ($enabled === null) {
        $enabled = get_config('giportfolio', 'contributioncount');
    }
    if (!$enabled) {
        return;
    }
    if (!has_capability('mod/giportfolio:submitportfolio', context_module::instance($cm->id))) {
        return;
    }

    $count = $DB->count_records('giportfolio_contributions', array('giportfolioid' => $cm->instance, 'userid' => $USER->id));
    $count = get_string('contributions', 'mod_giportfolio').' '.html_writer::tag('span', $count, array('class' => 'count-inner'));
    $count = html_writer::tag('span', $count, array('class' => 'giportfolio-count'));
    $cm->set_after_link($count);
}
