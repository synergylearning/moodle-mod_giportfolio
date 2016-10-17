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

require_once("../../config.php");
require_once("lib.php");
require_once(dirname(__FILE__).'/locallib.php');

global $CFG, $DB, $USER, $DB, $OUTPUT, $PAGE;

require_once($CFG->libdir.'/plagiarismlib.php');
require_once($CFG->libdir.'/gradelib.php');
require_once("search_form.php");

$id = optional_param('id', 0, PARAM_INT); // Course module ID.
$p = optional_param('p', 0, PARAM_INT); // Giportfolio ID.
$currenttab = optional_param('tab', 'all', PARAM_ALPHA); // What tab are we in?
$username = optional_param('username', '', PARAM_ALPHA); // Giportfolio ID.

$url = new moodle_url('/mod/giportfolio/submissions.php');
if ($id) {
    $cm = get_coursemodule_from_id('giportfolio', $id, 0, false, MUST_EXIST);
    $giportfolio = $DB->get_record("giportfolio", array("id" => $cm->instance), '*', MUST_EXIST);
    $course = $DB->get_record("course", array("id" => $giportfolio->course), '*', MUST_EXIST);
    $url->param('id', $id);
} else {
    $giportfolio = $DB->get_record("giportfolio", array("id" => $p), '*', MUST_EXIST);
    $course = $DB->get_record("course", array("id" => $giportfolio->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance("giportfolio", $giportfolio->id, $course->id, false, MUST_EXIST);
    $url->param('p', $p);
}

if ($currenttab !== 'all') {
    $url->param('tab', $currenttab);
}
$PAGE->set_url($url);
require_login($course->id, false, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/giportfolio:gradegiportfolios', $context);
require_capability('mod/giportfolio:viewgiportfolios', $context);

$PAGE->set_title(format_string($giportfolio->name));
echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($giportfolio->name));

// Set up the list of tabs.
$allurl = new moodle_url($PAGE->url);
$allurl->remove_params('tab');
$sincelastloginurl = new moodle_url($PAGE->url, array('tab' => 'sincelastlogin'));
$nocommentsurl = new moodle_url($PAGE->url, array('tab' => 'nocomments'));
$tabs = array(
    new tabobject('all', $allurl, get_string('allusers', 'mod_giportfolio')),
    new tabobject('sincelastlogin', $sincelastloginurl, get_string('sincelastlogin', 'mod_giportfolio')),
    new tabobject('nocomments', $nocommentsurl, get_string('nocomments', 'mod_giportfolio')),
);

echo get_string('studentgiportfolios', 'mod_giportfolio');
echo '</br>';
echo $OUTPUT->tabtree($tabs, $currenttab);
echo get_string('filterlist', 'mod_giportfolio');

$tabindex = 1; // Tabindex for quick grading tabbing; Not working for dropdowns yet.

// Check to see if groups are being used in this assignment.

// Find out current groups mode.
$groupmode = groups_get_activity_groupmode($cm);
$currentgroup = groups_get_activity_group($cm, true);
groups_print_activity_menu($cm, $CFG->wwwroot.'/mod/giportfolio/submissions.php?id='.$cm->id);

$updatepref = optional_param('updatepref', 0, PARAM_BOOL);

if ($updatepref) {
    $perpage = optional_param('perpage', 10, PARAM_INT);
    $perpage = ($perpage <= 0) ? 10 : $perpage;
    $filter = optional_param('filter', 0, PARAM_INT);
    set_user_preference('giportfolio_perpage', $perpage);
    set_user_preference('giportfolio_quickgrade', optional_param('quickgrade', 0, PARAM_BOOL));
    set_user_preference('giportfolio_filter', $filter);
}

$perpage = get_user_preferences('giportfolio_perpage', 10);
$quickgrade = get_user_preferences('giportfolio_quickgrade', 0);
$filter = get_user_preferences('giportfoliot_filter', 0);

$page = optional_param('page', 0, PARAM_INT);
$strsaveallfeedback = get_string('saveallfeedback', 'mod_giportfolio');
$fastg = optional_param('fastg', 0, PARAM_BOOL);
if ($fastg) { // Update the grade and the feedback.
    if (isset($_POST["menu"])) {
        $menu = $_POST["menu"];
        giportfolio_quick_update_grades($cm->id, $menu, $currentgroup, $giportfolio->id);
    }
    if (isset($_POST["submissionfeedback"])) {
        $submissionfeedback = $_POST["submissionfeedback"];
        giportfolio_quick_update_feedback($cm->id, $submissionfeedback, $currentgroup, $giportfolio->id);
    }
    echo html_writer::start_tag('div', array('class' => 'notifysuccess'));
    echo get_string('changessaved');
    echo html_writer::end_tag('div');
}
$mform = new giportfolio_search_form(null, array('id' => $id, 'tab' => $currenttab));
$mform->display();

// Print quickgrade form around the table.
if ($quickgrade) {
    $formattrs = array();
    $formattrs['action'] = new moodle_url('/mod/giportfolio/submissions.php');
    $formattrs['id'] = 'fastg';
    $formattrs['method'] = 'post';

    echo html_writer::start_tag('form', $formattrs);
    echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'id', 'value' => $cm->id));
    echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'mode', 'value' => 'fastgrade'));
    echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'page', 'value' => $page));
    echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()));
}

$allusers = get_users_by_capability($context, 'mod/giportfolio:submitportfolio', 'u.id,u.picture,u.firstname,u.lastname,u.idnumber',
                                    'u.firstname ASC', '', '', $currentgroup, '', false, true);

$alluserids = array();
foreach ($allusers as $user) {
    array_push($alluserids, $user->id);
}

$listusersids = "'".implode("', '", $alluserids)."'";
// Generate table.

$extrafields = get_extra_user_fields($context);
$tablecolumns = array_merge(array('picture', 'fullname'), $extrafields,
                            array('lastupdate', 'viewgiportfolio', 'grade', 'feedback'));

$extrafieldnames = array();
foreach ($extrafields as $field) {
    $extrafieldnames[] = get_user_field_name($field);
}
$tableheaders = array_merge(
    array('', get_string('fullnameuser')),
    $extrafieldnames,
    array(
         get_string('lastupdated', 'giportfolio'),
         get_string('viewgiportfolio', 'giportfolio'),
         get_string('grade'),
         get_string('feedback'),
    ));

require_once($CFG->libdir.'/tablelib.php');
$table = new flexible_table('mod-giportfolio-submissions');

$table->define_columns($tablecolumns);
$table->define_headers($tableheaders);
$table->define_baseurl($PAGE->url);

$table->sortable(true, 'lastname'); // Sorted by lastname by default.
$table->collapsible(true);
$table->initialbars(true);

$table->column_suppress('picture');
$table->column_suppress('fullname');

$table->column_class('picture', 'picture');
$table->column_class('fullname', 'fullname');
foreach ($extrafields as $field) {
    $table->column_class($field, $field);
}

$table->column_class('lastupdate', 'lastupdate');
$table->column_class('viewgiportfolio', 'viewgiportfolio');
$table->column_class('grade', 'grade');
$table->column_class('feedback', 'feedback');

$table->set_attribute('cellspacing', '0');
$table->set_attribute('id', 'attempts');
$table->set_attribute('class', 'submissions');
$table->set_attribute('width', '100%');

$table->no_sorting('lastupdate');
$table->no_sorting('feedback');
$table->no_sorting('grade');
$table->no_sorting('viewgiportfolio');

// Start working -- this is necessary as soon as the niceties are over.
$table->setup();

// Construct the SQL.

$extratables = '';
list($where, $params) = $table->get_sql_where();
if ($where) {
    $where .= ' AND ';
}

if ($username) {
    $where .= ' (u.lastname like \'%'.$username.'%\' OR u.firstname like \'%'.$username.'%\' ) AND ';
}

if ($currenttab == 'sincelastlogin') {
    $extratables = 'JOIN {giportfolio_contributions} c ON c.giportfolioid = :portfolioid
                                                       AND c.timemodified > :lastlogin
                                                       AND c.userid = u.id';
    $params['portfolioid'] = $giportfolio->id;
    $params['lastlogin'] = $USER->lastlogin;
} else if ($currenttab == 'nocomments') {

    $extratables = 'JOIN {giportfolio_contributions} c ON c.giportfolioid = :portfolioid AND c.userid = u.id
                    LEFT JOIN {grade_grades} g ON g.itemid = :gradeid AND g.userid = u.id';
    $params['gradeid'] = $DB->get_field('grade_items', 'id', array(
                                                                  'itemtype' => 'mod', 'itemmodule' => 'giportfolio',
                                                                  'iteminstance' => $giportfolio->id
                                                             ));
    $params['portfolioid'] = $giportfolio->id;
    $where .= "(g.feedback IS null OR g.feedback = '') AND ";
}

if ($sort = $table->get_sql_sort()) {
    $sort = ' ORDER BY '.$sort;
}

$ufields = user_picture::fields('u', $extrafields);

if (!empty($allusers)) {
    $select = "SELECT DISTINCT $ufields ";

    $sql = 'FROM {user} u '.$extratables.
        ' WHERE '.$where.'u.id IN ('.$listusersids.') ';

    $pusers = $DB->get_records_sql($select.$sql.$sort, $params, $table->get_page_start(), $table->get_page_size());
    $table->pagesize($perpage, count($pusers));

    $offset = $page * $perpage;
    $grademenu = make_grades_menu($giportfolio->grade);

    $rowclass = null;

    $endposition = $offset + $perpage;
    $currentposition = 0;

    $strview = get_string('view');
    $strnotstarted = get_string('notstarted', 'mod_giportfolio');
    $strprivate = get_string('private', 'mod_giportfolio');
    $strgrade = get_string('grade');

    foreach ($pusers as $puser) {
        if ($currentposition == $offset && $offset < $endposition) {
            $picture = $OUTPUT->user_picture($puser);
            $usercontribution = giportfolio_get_user_contribution_status($giportfolio->id, $puser->id);
            $private = false;
            if (!$usercontribution) {
                $private = $DB->record_exists('giportfolio_contributions', array(
                                                                                'giportfolioid' => $giportfolio->id,
                                                                                'userid' => $puser->id
                                                                           ));
            }
            $statuspublish = '';
            $userfinalgrade = new stdClass();
            $userfinalgrade->grade = null;
            $userfinalgrade->str_grade = '-';
            if ($usercontribution) {
                $lastupdated = date('l jS \of F Y ', $usercontribution);
                $usergrade = grade_get_grades($course->id, 'mod', 'giportfolio', $giportfolio->id, $puser->id);
                if ($usergrade->items) {
                    $gradeitemgrademax = $usergrade->items[0]->grademax;
                    $userfinalgrade = $usergrade->items[0]->grades[$puser->id];

                    if ($quickgrade && !$userfinalgrade->locked) {
                        $attributes = array();
                        $attributes['tabindex'] = $tabindex++;
                        $menu = html_writer::select(make_grades_menu($giportfolio->grade), 'menu['.$puser->id.']',
                                                    round(($userfinalgrade->grade), 0), array(-1 => get_string('nograde')),
                                                    $attributes);
                        $userfinalgrade->grade = '<div id="g'.$puser->id.'">'.$menu.'</div>';
                    }

                    if ($userfinalgrade->feedback && !$quickgrade) {
                        $feedback = $userfinalgrade->feedback;
                    } else if ($quickgrade) {
                        $feedback = '<div id="feedback'.$puser->id.'">'
                            .'<textarea tabindex="'.$tabindex++.'" name="submissionfeedback['.$puser->id.']" id="submissionfeedback'
                            .$puser->id.'" rows="2" cols="20">'.($userfinalgrade->feedback).'</textarea></div>';
                    } else {
                        $feedback = '-';
                    }
                }
            } else {
                $lastupdated = '-';
                $feedback = '-';
                $rowclass = '';
            }

            if ($usercontribution) {
                $params = array('id' => $cm->id, 'userid' => $puser->id);
                $viewurl = new moodle_url('/mod/giportfolio/viewcontribute.php', $params);
                $gradeurl = new moodle_url('/mod/giportfolio/updategrade.php', $params);
                $statuspublish = html_writer::link($viewurl, $strview);
                $statuspublish .= ' | '.html_writer::link($gradeurl, $strgrade);
                $rowclass = '';
            } else if ($private) {
                $statuspublish = $strprivate;
                $rowclass = 'late';
            } else {
                $statuspublish = $strnotstarted;
                $rowclass = 'late';
            }

            $userlink = '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$puser->id.'&amp;course='.$course->id.'">'.
                fullname($puser, has_capability('moodle/site:viewfullnames', $context)).'</a>';
            $extradata = array();
            foreach ($extrafields as $field) {
                $extradata[] = $puser->{$field};
            }
            $row = array_merge(array($picture, $userlink), $extradata,
                               array($lastupdated, $statuspublish, $userfinalgrade->str_grade, $feedback));
            $offset++;
            $table->add_data($row, $rowclass);
        }
        $currentposition++;
    }
    $table->print_html();
} else {
    echo html_writer::tag('div', get_string('nosubmisson', 'mod_giportfolio'), array('class' => 'nosubmisson'));
}
// End table.
// Print quickgrade form around the table.
if ($quickgrade && $table->started_output && !empty($allusers)) {

    $savefeedback = html_writer::empty_tag('input', array(
                                                         'type' => 'submit', 'name' => 'fastg',
                                                         'value' => get_string('saveallfeedback', 'mod_giportfolio')
                                                    ));
    echo html_writer::tag('div', $savefeedback, array('class' => 'fastgbutton'));

    echo html_writer::end_tag('form');
} else if ($quickgrade) {
    echo html_writer::end_tag('form');
}

// Mini form for setting user preference.

$formaction = new moodle_url('/mod/giportfolio/submissions.php', array('id' => $cm->id));
$mform = new MoodleQuickForm('optionspref', 'post', $formaction, '', array('class' => 'optionspref'));

$mform->addElement('hidden', 'updatepref');
$mform->setDefault('updatepref', 1);
$mform->addElement('header', 'qgprefs', get_string('optionalsettings', 'giportfolio'));

$mform->setDefault('filter', $filter);

$mform->addElement('text', 'perpage', get_string('pagesize', 'giportfolio'), array('size' => 1));
$mform->setDefault('perpage', $perpage);

$mform->addElement('checkbox', 'quickgrade', get_string('quickgrade', 'giportfolio'));
$mform->setDefault('quickgrade', $quickgrade);
$mform->addHelpButton('quickgrade', 'quickgrade', 'giportfolio');

$mform->addElement('submit', 'savepreferences', get_string('savepreferences'));

$mform->display();

echo $OUTPUT->footer();

/**
 * Checks if grading method allows quickgrade mode. At the moment it is hardcoded
 * that advanced grading methods do not allow quickgrade.
 *
 * Assignment type plugins are not allowed to override this method
 *
 * @param $cmid
 * @return boolean
 */
function quickgrade_mode_allowed($cmid) {
    global $CFG;
    require_once("$CFG->dirroot/grade/grading/lib.php");
    $context = context_module::instance($cmid);
    if ($controller = get_grading_manager($context->id, 'mod_giportfolio', 'submission')->get_active_controller()) {
        return false;
    }
    return true;
}
