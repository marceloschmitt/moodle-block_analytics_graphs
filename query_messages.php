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
$studentids = required_param('student_ids', PARAM_INT);
$courseid = required_param('course_id', PARAM_INT);

/* Access control */
require_login($courseid);
$context = context_course::instance($courseid);
require_capability('block/analytics_graphs:viewpages', $context);

$inclause[] = $studentids;
list($insql, $inparams) = $DB->get_in_or_equal($inclause);
$params = array_merge(array($courseid), $inparams);

$sql = "SELECT msg.id, CONCAT(firstname, ' ', lastname) fromid, subject, message, msg.timecreated
        FROM {block_analytics_graphs_msg} AS msg
        LEFT JOIN {block_analytics_graphs_dest} AS dest ON dest.messageid = msg.id
        LEFT JOIN {user} AS usr ON usr.id = fromid
        WHERE courseid = ? AND toid $insql
        ORDER BY timecreated";

$result = $DB->get_records_sql($sql, $params);

if (count($result) > 0) {
    $keys = array_keys($result);
    for ($x = 0; $x < count($keys); $x++) {
        $result[$keys[$x]]->timecreated = userdate($result[$keys[$x]]->timecreated, get_string('strftimerecentfull'));
    }
    echo json_encode($result);
} else {
    echo json_encode(array());
}