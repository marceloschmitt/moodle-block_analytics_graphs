<?php
require('../../config.php');
include("lib.php");

$studentid = required_param('student_id', PARAM_INT);
$courseid = required_param('course_id', PARAM_INT);

$result =  block_analytics_graphs_get_user_resource_url_page_access($courseid, $studentid, 0);

echo json_encode($result);
?>