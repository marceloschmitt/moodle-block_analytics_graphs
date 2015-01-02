<?php
require_once("../../config.php");
global $CFG;
global $USER;
require_once($CFG->dirroot.'/lib/moodlelib.php');

$destino = explode(',',$_POST['emails']);
$destino_id = explode(',',$_POST['ids']);

$toUser = new stdClass();
$fromUser = new stdClass();
$toUser->mailformat = 0;
$fromUser->email = $USER->email;
$fromUser->firstname = $USER->firstname;
$fromUser->maildisplay = true;
$fromUser->lastname = $USER->lastname;
$fromUser->id = $USER->id;
$subject = $_POST['subject'];
$messageText = $_POST['texto'];
$messageHtml  =  $_POST['texto'];

foreach($destino as $i => $x)
{
        $toUser->email = $x;
        $toUser->id = $destino_id[$i];
	email_to_user($toUser, $fromUser, $subject, $messageText, $messageHtml, '', '', true);
}

$mensagem = "ok";
echo json_encode($mensagem);
?>
