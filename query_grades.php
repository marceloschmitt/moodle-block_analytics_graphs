<?php
require_once("../../config.php");
global $DB;
require_once($CFG->dirroot.'/lib/moodlelib.php');

$course_id = required_param('course_id', PARAM_INT);
$form_data = required_param_array('form_data', PARAM_INT);
require_login($course_id);
$context = context_course::instance($course_id);
require_capability('block/analytics_graphs:viewpages', $context);

$inclause[] = $form_data;
list($insql, $inparams) = $DB->get_in_or_equal($inclause);

$sql = "SELECT userid AS id, rawgrade/(rawgrademax-rawgrademin) AS grade 
		FROM {grade_grades}
		WHERE itemid $insql";

$result = $DB->get_records_sql($sql, $insql);

echo json_encode($result);
?>