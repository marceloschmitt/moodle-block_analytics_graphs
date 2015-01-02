<?php
require('../../config.php');
require('graphDateOfAccess.php');
require('javascript_functions.php');
$course = required_param('id', PARAM_INT);
$legacy = required_param('legacy', PARAM_INT);


$x = new graphDateOfAccess($course, $legacy);
$titulo = get_string('title_one','block_analytics_graphs');
$x->setTitle($titulo);
$x->createGraph();
?>
