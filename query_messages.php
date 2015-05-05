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

$sql = 
    "SELECT subject, message
    FROM {block_analytics_graphs_msg} AS msg
    LEFT JOIN {block_analytics_graphs_dest} AS dest ON dest.messageid = msg.id
    WHERE msg.courseid = :course_id and dest.toid = :student_id";

$params = array('student_id'=>$student_ids, 'course_id'=>$course_id);

$result = $DB->get_records_sql($sql, $params);

if(count($result) > 0){
    echo json_encode($result);
}
else{
    echo json_encode(array());
}
?>