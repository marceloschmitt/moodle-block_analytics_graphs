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



require_once("../../config.php");
global $DB;
require_once($CFG->dirroot.'/lib/moodlelib.php');

$courseid = required_param('course_id', PARAM_INT);
$formdata = required_param_array('form_data', PARAM_INT);
require_login($courseid);
$context = context_course::instance($courseid);
require_capability('block/analytics_graphs:viewpages', $context);

list($insql, $inparams) = $DB->get_in_or_equal($formdata);

$sql = "SELECT itemid + (userid*1000000) AS id, itemid, userid, usr.firstname,
        usr.lastname, usr.email, rawgrade/(rawgrademax-rawgrademin) AS grade
            FROM {grade_grades}
            LEFT JOIN {user} usr ON usr.id = userid
            WHERE itemid $insql AND rawgrade IS NOT NULL
            ORDER BY id";

$result = $DB->get_records_sql($sql, $inparams);
$taskgrades = new stdClass();
foreach ($result as $id => $taskattrs) {
    $itemid = $taskattrs->itemid;
    $record = new stdClass();
    $record->userid = $taskattrs->userid;
    $record->grade = floatval($taskattrs->grade);
    $record->email = $taskattrs->email;
    $record->name = $taskattrs->firstname . " " . $taskattrs->lastname;
    if (!property_exists($taskgrades, $itemid)) {
        $taskgrades->{$itemid} = array();
    }
    $taskgrades->{$itemid}[] = $record;
}

echo json_encode($taskgrades);