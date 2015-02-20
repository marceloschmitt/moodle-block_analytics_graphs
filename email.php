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
global $USER;
require_once($CFG->dirroot.'/lib/moodlelib.php');
$course = required_param('id', PARAM_INT);

/* Access control */
require_login($course);
$context = get_context_instance(CONTEXT_COURSE, $course);
require_capability('block/analytics_graphs:viewpages', $context);

$dstination = explode(',', $_POST['emails']);
$destinationid = explode(',', $_POST['ids']);
$other = $_POST['other'];

$touser = new stdClass();
$fromuser = new stdClass();
$touser->mailformat = 0;
$fromuser->email = $USER->email;
$fromuser->firstname = $USER->firstname;
$fromuser->maildisplay = true;
$fromuser->lastname = $USER->lastname;
$fromuser->id = $USER->id;
$subject = $_POST['subject'];
$messagetext = $_POST['texto'];
$messagehtml = $_POST['texto'];

foreach ($dstination as $i => $x) {
        $touser->email = $x;
        $touser->id = $destinationid[$i];
        email_to_user($touser, $fromuser, $subject, $messagetext, $messagehtml, '', '', true);
}

$mensagem = "ok";
echo json_encode($mensagem);
$event = \block_analytics_graphs\event\block_analytics_graphs_event_send_email::create(array(
    'objectid' => $course,
    'context' => $context,
    'other' => $other,
));
$event->trigger();
