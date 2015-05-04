<?php
global $DB;
$student_ids = required_param('student_ids', PARAM_TEXT);
$course_id = required_param('course_id', PARAM_INT);

/* Access control */
require_login($course_id);
$context = context_course::instance($course_id);
require_capability('block/analytics_graphs:viewpages', $context);

$num_students = count($student_ids);
$student_ids = implode(",", $student_ids);

$sql = 
    "SELECT
        {block_analytics_graphs_msg}.fromid, {block_analytics_graphs_msg}.subject, {block_analytics_graphs_msg}.messagetext
    FROM
        block_analytics_graphs_msg, block_analytics_graphs_dest
    WHERE
        {block_analytics_graphs_msg}.id = {block_analytics_graphs_dest}.messageid AND
        {block_analytics_graphs_dest}.toid IN :student_ids";

$params = array('student_ids'=>$student_ids);

$result = $DB->get_records_sql($sql, $params);

if(count($result) > 0){
    echo json_encode($result);
}
else{
    echo json_encode(array());
}
?>