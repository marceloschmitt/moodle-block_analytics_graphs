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

$sql = "SELECT itemid + (userid*1000000) as id, itemid, userid, rawgrade/(rawgrademax-rawgrademin) AS grade
                FROM {grade_grades} WHERE itemid $insql AND rawgrade IS NOT NULL
                ORDER BY id";

$result = $DB->get_records_sql($sql, $inparams);
$task_grades = new stdClass();
foreach($result as $id => $task_attrs){
        $itemid = $task_attrs->itemid;
        if(!property_exists($task_grades, $itemid)){
                $task_grades->{$itemid} = new stdClass();
                $task_grades->{$itemid}->userids = array();
                $task_grades->{$itemid}->grades = array();
        }
        $task_grades->{$itemid}->userids[] = $task_attrs->userid;
        $task_grades->{$itemid}->grades[] = floatval($task_attrs->grade);
}

echo json_encode($task_grades);
?>