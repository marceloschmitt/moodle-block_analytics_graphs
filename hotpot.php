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


require('../../config.php');
require('graph_submission.php');
require('javascriptfunctions.php');
require('lib.php');

$course = required_param('id', PARAM_INT);

$title = get_string('submissions_hotopot', 'block_analytics_graphs');
$submissions_graph = new graph_submission($course, $title);
$tudents = block_analytics_graphs_get_students($course);
$result = block_analytics_graphs_get_hotpot_submission($course, $students);
$submissions_graph_options = $submissions_graph->create_graph($result, $students);
?>

<!--DOCTYPE HTML-->
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <title><?php echo get_string('submissions', 'block_analytics_graphs'); ?></title>
        <link rel="stylesheet" href="http://code.jquery.com/ui/1.11.1/themes/smoothness/jquery-ui.css">        
        <!--<script src="http://code.jquery.com/jquery-1.10.2.js"></script>-->
        <script src="http://ajax.aspnetcdn.com/ajax/jQuery/jquery-1.11.1.js"></script>        
        <script src="http://code.jquery.com/ui/1.11.1/jquery-ui.js"></script>
        <script src="http://code.highcharts.com/highcharts.js"></script>
        <script src="http://code.highcharts.com/modules/no-data-to-display.js"></script>
        <script src="http://code.highcharts.com/modules/exporting.js"></script> 
        <script type="text/javascript">
    	    var courseid = <?php echo json_encode($submissions_graph->get_course()); ?>;
            function parseObjToString(obj) {
                var array = $.map(obj, function(value) {
                    return [value];
                });
                return array;
            }
        </script>
    </head>
    <body>
    	<div id="container" style="min-width: 310px; min-width: 800px; height: 650px; margin: 0 auto"></div>
    	<script>
    		$(function(){
    			$('#container').highcharts(<?php echo $submissions_graph_options; ?>);
    		})

	    	var geral = <?php echo $submissions_graph->get_statistics(); ?>;
	        geral = parseObjToString(geral);
	        $.each(geral, function(index, value) {
	            var nome = value.assign;
	            div = "";
	            if (typeof value.in_time_submissions != 'undefined')
	            {
	        		title = <?php echo json_encode($submissions_graph->get_coursename()); ?> +
				        "</h3>" + 
				        <?php echo json_encode(get_string('in_time_submission', 'block_analytics_graphs')); ?> +
	                    " - " +  nome ;
	                div += "<div class='div_nomes' id='" + index + "-0'>" + 
	                    createEmailForm(title, value.in_time_submissions, courseid, "assign.php") +
	                    "</div>";
	            }
	            if (typeof value.latesubmissions != 'undefined')
	            {
	        	 	title = <?php echo json_encode($submissions_graph->get_coursename()); ?> +
				        "</h3>" +
				        <?php echo json_encode(get_string('late_submission', 'block_analytics_graphs')); ?> +
	                    " - " +  nome ;
	                div += "<div class='div_nomes' id='" + index + "-1'>" +
	                    createEmailForm(title, value.latesubmissions, courseid, "assign.php") +
	                    "</div>";
	            }
	    	    if (typeof value.no_submissions != 'undefined')
	            {
	        		title = <?php echo json_encode($submissions_graph->get_coursename()); ?> +
				        "</h3>" + 
	                    <?php echo json_encode(get_string('no_submission', 'block_analytics_graphs')); ?> +
	                    " - " +  nome ;
	                div += "<div class='div_nomes' id='" + index + "-2'>" +
	                    createEmailForm(title, value.no_submissions, courseid, "assign.php") +
	                    "</div>";
	            }
	            document.write(div);
	        });
	    	sendEmail();
        </script>
    </body>
</html>