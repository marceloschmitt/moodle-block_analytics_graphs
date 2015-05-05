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
require('lib.php');
global $USER;
global $DB;
require_once($CFG->dirroot.'/lib/moodlelib.php');
$course = required_param('id', PARAM_INT);
$ids = required_param('ids', PARAM_TEXT);
$other = required_param('other', PARAM_INT);
$subject = required_param('subject', PARAM_TEXT);
$messagetext = required_param('texto', PARAM_TEXT);
$messagehtml = $messagetext;

/* Access control */
require_login($course);
$context = context_course::instance($course);
require_capability('block/analytics_graphs:viewpages', $context);

$destination = explode(',', $ids);

$touser = new stdClass();
$fromuser = new stdClass();
$touser->mailformat = 0;
$fromuser->email = $USER->email;
$fromuser->firstname = $USER->firstname;
$fromuser->maildisplay = true;
$fromuser->lastname = $USER->lastname;
$fromuser->id = $USER->id;

$record_msg = new stdClass();
$record_msg->fromid = $fromuser->id;
$record_msg->subject = $subject;
$record_msg->message = $messagetext;
$messageid = $DB->insert_record('block_analytics_graphs_msg', $record_msg, true);
$record_dest = new stdClass();
$record_dest->messageid = $messageid;
$record_dest->courseid = $course;

foreach ($destination as $i => $x) {
        $touser->id = $destination[$i];
        $record_dest->toid = $touser->id;
        $touser->email = $DB->get_field('user', 'email', array('id' => $destination[$i]));
        email_to_user($touser, $fromuser, $subject, $messagetext, $messagehtml, '', '', true);
        $DB->insert_record('block_analytics_graphs_dest', $record_dest, false);
}

$messagetext = get_string('mailcopyalert', 'block_analytics_graphs') . $messagetext;
$messagehtml = get_string('mailcopyalert', 'block_analytics_graphs') . $messagehtml;
$userstocopyemail = block_analytics_graphs_get_teachers($course);

foreach ($userstocopyemail as $i) {
    $touser->id = $i->id;
    $touser->email = $DB->get_field('user', 'email', array('id' => $i->id));
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