<?php
require_once("../../config.php");
global $DB;
require_once($CFG->dirroot.'/lib/moodlelib.php');

$course_id = required_param('course_id', PARAM_INT);
$form_data = required_param_array('form_data', PARAM_INT);
require_login($course_id);
$context = context_course::instance($course_id);
require_capability('block/analytics_graphs:viewpages', $context);

list($insql, $inparams) = $DB->get_in_or_equal($form_data);

$sql = "SELECT itemid, userid, rawgrade/(rawgrademax-rawgrademin) AS grade 
		FROM {grade_grades} WHERE itemid $insql AND rawgrade IS NOT NULL
		ORDER BY itemid";

$result = $DB->get_records_sql($sql, $inparams);

$task_grades = array();
foreach($result as $task => $task_attrs){
	if(!array_key_exists($task, $task_grades)){
		$task_grades[$task] = array("userids" => array(), "grades" => array());
	}
	$task_grades[$task]["userids"][] = $task_attrs->userid;
	$task_grades[$task]["grades"][] = floatval($task_attrs->grade);
}

echo json_encode($task_grades);
?>