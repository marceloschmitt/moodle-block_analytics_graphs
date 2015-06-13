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


require_once('../../config.php');
require_once("lib.php");
require_once($CFG->dirroot.'/lib/moodlelib.php');

$studentid = required_param('student_id', PARAM_INT);
$courseid = required_param('course_id', PARAM_INT);
$legacy = required_param('legacy', PARAM_INT);

/* Access control */
require_login($courseid);
$context = context_course::instance($courseid);
require_capability('block/analytics_graphs:viewpages', $context);

$resourceaccess = block_analytics_graphs_get_user_resource_url_page_access($courseid, $studentid, $legacy);
$assigninfo = block_analytics_graphs_get_user_assign_submission($courseid, $studentid);

echo json_encode(array("resources" => $resourceaccess, "assign" => $assigninfo));