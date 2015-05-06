<?php
require_once("../../config.php");
global $DB;
require_once($CFG->dirroot.'/lib/moodlelib.php');
$student_ids = required_param('student_ids', PARAM_INT);
$course_id = required_param('course_id', PARAM_INT);

/* Access control */
require_login($course_id);
$context = context_course::instance($course_id);
require_capability('block/analytics_graphs:viewpages', $context);

$inclause[] = $student_ids;
list($insql, $inparams) = $DB->get_in_or_equal($inclause);
$params = array_merge(array($course_id), $inparams);

$sql = "SELECT msg.id, CONCAT(firstname, ' ', lastname) fromid, subject, message, msg.timecreated
        FROM {block_analytics_graphs_msg} AS msg
        LEFT JOIN {block_analytics_graphs_dest} AS dest ON dest.messageid = msg.id
        LEFT JOIN {user} AS usr ON usr.id = fromid
        WHERE courseid = ? AND toid $insql
        ORDER BY timecreated";

$result = $DB->get_records_sql($sql, $params);

if(count($result) > 0){
    echo json_encode($result);
}
else{
    echo json_encode(array());
}
?>