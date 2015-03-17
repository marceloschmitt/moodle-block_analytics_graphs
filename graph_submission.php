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



class graph_submission {

    private $context;
    private $course;
    private $coursename;
    private $startdate;
    private $title;
    private $query_function;

    public function __construct($course) {
        $this->course = $course;

        // Control access.
        require_login($course);
        $this->context = context_course::instance($course);
        require_capability('block/analytics_graphs:viewpages', $this->context);

        $courseparams = get_course($course);
        $this->startdate = $courseparams->startdate;
        $this->coursename = get_string('course', 'block_analytics_graphs') . ": " . $courseparams->fullname;
    }


    public function set_title($name) {
        $this->title = $name;
    }


    public function set_query_function($func_name) {
        $this->$query_function = $func_name;
    }


    public function create_graph() {
        global $DB;
        require('lib.php');

        // Recover course students.
        $students = block_analytics_graphs_get_students($this->course);
        $numberofstudents = count($students);
        if ($numberofstudents == 0) {
            error(get_string('no_students', 'block_analytics_graphs'));
        }
        foreach ($students as $tuple) {
            $arrayofstudents[] = array('userid' => $tuple->id ,
                'nome' => $tuple->firstname.' '.$tuple->lastname, 'email' => $tuple->email);
        }

        // Recover submitted tasks.
        // $result = block_analytics_graphs_get_assign_submission($this->course, $students);
        $result = $this->$query_function($this->course, $students);

        $counter = 0;
        $numberofintimesubmissions = 0;
        $numberoflatesubmissions = 0;
        $assignmentid = 0;

        foreach ($result as $tuple) {
            if ($assignmentid == 0) { // First time in loop.
                $statistics[$counter]['assign'] = $tuple->name;
                $statistics[$counter]['duedate'] = $tuple->duedate;
                $statistics[$counter]['cutoffdate'] = $tuple->cutoffdate;
                if ($tuple->userid) { // If a student submitted.
                    if ($tuple->duedate >= $tuple->timecreated || $tuple->duedate == 0) { // In the right time.
                        $statistics[$counter]['in_time_submissions'][] = array('userid'  => $tuple->userid,
                            'nome'  => $tuple->firstname." ".$tuple->lastname,
                            'email'  => $tuple->email, 'timecreated'  => $tuple->timecreated);
                        $numberofintimesubmissions++;
                    } else { // Late.
                        $statistics[$counter]['latesubmissions'][] = array('userid'  => $tuple->userid,
                            'nome'  => $tuple->firstname." ".$tuple->lastname, 'email'  => $tuple->email,
                            'timecreated'  => $tuple->timecreated);
                        $numberoflatesubmissions++;
                    }
                }
                $assignmentid = $tuple->assignment;
            } else { // Not first time in loop.
                if ($assignmentid == $tuple->assignment and $tuple->userid) { // Same task -> add student.
                    if ($tuple->duedate >= $tuple->timecreated || $tuple->duedate == 0) { // Right time.
                        $statistics[$counter]['in_time_submissions'][] = array('userid'  => $tuple->userid,
                            'nome'  => $tuple->firstname." ".$tuple->lastname,
                            'email'  => $tuple->email, 'timecreated'  => $tuple->timecreated);
                        $numberofintimesubmissions++;
                    } else { // Late.
                        $statistics[$counter]['latesubmissions'][] = array('userid'  => $tuple->userid,
                            'nome'  => $tuple->firstname." ".$tuple->lastname,
                            'email'  => $tuple->email, 'timecreated'  => $tuple->timecreated);
                        $numberoflatesubmissions++;
                    }
                }

                if ($assignmentid != $tuple->assignment) { // Another task -> finish previous and start.
                    $statistics[$counter]['numberofintimesubmissions'] = $numberofintimesubmissions;
                    $statistics[$counter]['numberoflatesubmissions'] = $numberoflatesubmissions;
                    $statistics[$counter]['numberofnosubmissions'] =
                            $numberofstudents - $numberofintimesubmissions - $numberoflatesubmissions;
                    if ($statistics[$counter]['numberofnosubmissions'] == $numberofstudents) {
                        $statistics[$counter]['no_submissions'] = $arrayofstudents;
                    } else if ($numberoflatesubmissions == 0) {
                        $statistics[$counter]['no_submissions'] = block_analytics_graphs_subtract_student_arrays($arrayofstudents,
                            $statistics[$counter]['in_time_submissions']);
                    } else if ($numberofintimesubmissions == 0) {
                        $statistics[$counter]['no_submissions'] = block_analytics_graphs_subtract_student_arrays($arrayofstudents,
                            $statistics[$counter]['latesubmissions']);
                    } else {
                        $statistics[$counter]['no_submissions'] = block_analytics_graphs_subtract_student_arrays(
                            block_analytics_graphs_subtract_student_arrays($arrayofstudents,
                                $statistics[$counter]['in_time_submissions']),
                                $statistics[$counter]['latesubmissions']);
                    }
                    $counter++;
                    $numberofintimesubmissions = 0;
                    $numberoflatesubmissions = 0;
                    $statistics[$counter]['assign'] = $tuple->name;
                    $statistics[$counter]['duedate'] = $tuple->duedate;
                    $statistics[$counter]['cutoffdate'] = $tuple->cutoffdate;
                    $assignmentid = $tuple->assignment;
                    if ($tuple->userid) { // If a user has submitted
                        if ($tuple->duedate >= $tuple->timecreated || $tuple->duedate == 0) { // Right time.
                            $statistics[$counter]['in_time_submissions'][] = array('userid'  => $tuple->userid,
                                'nome' => $tuple->firstname." ".$tuple->lastname,
                                'email' => $tuple->email, 'timecreated'  => $tuple->timecreated);
                            $numberofintimesubmissions = 1;
                        } else { // Late.
                            $statistics[$counter]['latesubmissions'][] = array('userid'  => $tuple->userid,
                                'nome'  => $tuple->firstname." ".$tuple->lastname,
                                'email'  => $tuple->email, 'timecreated'  => $tuple->timecreated);
                            $numberoflatesubmissions = 1;
                        }
                    }
                }
            }
        }

        // Finishing of last access.
        $statistics[$counter]['numberofintimesubmissions'] = $numberofintimesubmissions;
        $statistics[$counter]['numberoflatesubmissions'] = $numberoflatesubmissions;
        $statistics[$counter]['numberofnosubmissions'] = $numberofstudents - $numberofintimesubmissions - $numberoflatesubmissions;

        if ($statistics[$counter]['numberofnosubmissions'] == $numberofstudents) {
            $statistics[$counter]['no_submissions'] = $arrayofstudents;
        } else if ($numberoflatesubmissions == 0) {
            $statistics[$counter]['no_submissions'] = block_analytics_graphs_subtract_student_arrays($arrayofstudents,
                    $statistics[$counter]['in_time_submissions']);
        } else if ($numberofintimesubmissions == 0) {
            $statistics[$counter]['no_submissions'] = block_analytics_graphs_subtract_student_arrays($arrayofstudents,
                    $statistics[$counter]['latesubmissions']);
        } else {
            $statistics[$counter]['no_submissions'] =
                block_analytics_graphs_subtract_student_arrays(block_analytics_graphs_subtract_student_arrays(
                    $arrayofstudents,
                    $statistics[$counter]['in_time_submissions']), $statistics[$counter]['latesubmissions']);
        }

        foreach ($statistics as $tuple) {
            $arrayofassignments[] = $tuple['assign'];
            $arrayofintimesubmissions[] = $tuple['numberofintimesubmissions'];
            $arrayoflatesubmissions[] = $tuple['numberoflatesubmissions'];
            $arrayofnosubmissions[] = $tuple['numberofnosubmissions'];
            $arrayofduedates[] = $tuple['duedate'];
            $arrayofcutoffdates[] = $tuple['cutoffdate']; // For future use.
        }
        $statistics = json_encode($statistics);

        $event = \block_analytics_graphs\event\block_analytics_graphs_event_view_graph::create(array(
            'objectid' => $this->course,
            'context' => $this->context,
            'other' => "assign.php",
        ));
        $event->trigger();
?>
<!--DOCTYPE HTML-->
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <title><?php echo get_string('submissions', 'block_analytics_graphs'); ?></title>
        <link rel="stylesheet" type="text/css" href="styles.css">
        <link rel="stylesheet" href="http://code.jquery.com/ui/1.11.1/themes/smoothness/jquery-ui.css">
        
        <!--<script src="http://code.jquery.com/jquery-1.10.2.js"></script>-->
        <script src="http://ajax.aspnetcdn.com/ajax/jQuery/jquery-1.11.1.js"></script>
        
        <script src="http://code.jquery.com/ui/1.11.1/jquery-ui.js"></script>
        <script src="http://code.highcharts.com/highcharts.js"></script>
        <script src="http://code.highcharts.com/modules/no-data-to-display.js"></script>
        <script src="http://code.highcharts.com/modules/exporting.js"></script> 

        <script type="text/javascript">
         var courseid = <?php echo json_encode($this->course); ?>;
    
            function parseObjToString(obj) {
                var array = $.map(obj, function(value) {
                    return [value];
                });
                return array;
            }
    
        
        
$(function () {
    $('#container').highcharts({
        chart: {
            zoomType: 'x',
        },

        title: {
            text: '<?php echo get_string('submissions', 'block_analytics_graphs'); ?>',
        margin: 60,
        },

        subtitle: {
            text: ' <?php echo $this->coursename . "<br>" .
                     get_string('begin_date', 'block_analytics_graphs') . ": " .
                     userdate($this->startdate, get_string('strftimerecentfull')); ?>',
        },

        legend: {
                        layout: 'vertical',
                        align: 'right',
                        verticalAlign: 'top',
                        x: -40,
                        y: 5,
                        floating: true,
                        borderWidth: 1,
                        backgroundColor: (Highcharts.theme && Highcharts.theme.legendBackgroundColor || '#FFFFFF'),
                        shadow: true
        },
        credits: {
            enabled: false
        },

        xAxis: [{
            categories: [
                <?php $arrlength = count($arrayofassignments);
        for ($x = 0; $x < $arrlength; $x++) {
            echo "'<b>";
            echo substr($arrayofassignments[$x], 0, 35);
            if ($arrayofduedates[$x]) {
                echo "</b><br>". userdate($arrayofduedates[$x], get_string('strftimerecentfull')) ."',";
            } else {
                echo "</b><br>".get_string('no_deadline', 'block_analytics_graphs') . "',";
            }
        } ?> 
            ],
            labels: {
                rotation: -45,
            }
        }],

        yAxis: [{ // Primary yAxis
                ceiling: 1,
                min: 0,
                tickInterval: 0.25,
                labels: {
                        format: '{value}',
                        style: {
                                color: Highcharts.getOptions().colors[1]
                        }
                },

                title: {
                        text: '<?php echo get_string('ratio', 'block_analytics_graphs');?>',
                        style: {
                                color: Highcharts.getOptions().colors[1]
                        }
                }
        },
        { // Secondary yAxis
                min: 0,
        	    ceiling: <?php echo $numberofstudents; ?>,
                tickInterval: <?php echo $numberofstudents / 4; ?>,
                opposite: true,
                labels: {
                        format: '{value}',
                        style: {
                                color: Highcharts.getOptions().colors[1]
                        }
                },

                title: {
                        text: '<?php echo get_string('number_of_students', 'block_analytics_graphs');?>',
                        style: {
                                color: Highcharts.getOptions().colors[1]
                        }
                }
        }],

        tooltip: {
            crosshairs: true
        },

        
        plotOptions: {
            series: {
                cursor: 'pointer',
                        
                point: {
                    events: {
                    click: function() {
                         var nome_conteudo = this.x + "-" + this.series.index;
                                            $(".div_nomes").dialog("close");
                                            $("#" + nome_conteudo).dialog("open");
                    }
                }
            }
        },
        
        bar: {
            dataLabels: {
                useHTML: this,
                enabled: true
            }
        }
    },
        
        series: [{
            yAxis: 1,
            name: '<?php echo get_string('in_time_submission', 'block_analytics_graphs');?>',
            type: 'column',
            data: [
            <?php $arrlength = count($arrayofassignments);
        for ($x = 0; $x < $arrlength; $x++) {
            echo $arrayofintimesubmissions[$x];
            echo ",";
        } ?>
            ],
            tooltip: {
                valueSuffix: ' <?php echo get_string('students', 'block_analytics_graphs');?>'
            }
        }, {
            yAxis: 1,
            name: '<?php echo get_string('late_submission', 'block_analytics_graphs');?>',
            type: 'column',
            data: [
            <?php $arrlength = count($arrayofassignments);
        for ($x = 0; $x < $arrlength; $x++) {
            echo $arrayoflatesubmissions[$x];
            echo ",";
        } ?> 
            ],
            tooltip: {
                valueSuffix: ' <?php echo get_string('students', 'block_analytics_graphs');?>'
            }

        }, {
            yAxis: 1,
            name: '<?php echo get_string('no_submission', 'block_analytics_graphs');?>',
            type: 'column',
            color: '#FF1111',    //cor 
            data: [
            <?php $arrlength = count($arrayofassignments);
        for ($x = 0; $x < $arrlength; $x++) {
            echo $arrayofnosubmissions[$x];
            echo ",";
        } ?>
            ],
            tooltip: {
                valueSuffix: ' <?php echo get_string('students', 'block_analytics_graphs');?>'
            }
        }, {
            yAxis: 0,
            name: '<?php echo get_string('submission_ratio', 'block_analytics_graphs');?>',
            type: 'spline',
            lineWidth: 2,
            lineColor: Highcharts.getOptions().colors[2],
            data: [
            <?php $arrlength = count($arrayofassignments);
        for ($x = 0; $x < $arrlength; $x++) {
            printf("%.2f", ($arrayofintimesubmissions[$x] + $arrayoflatesubmissions[$x]) /
                ($arrayofintimesubmissions[$x] + $arrayoflatesubmissions[$x] + $arrayofnosubmissions[$x]));
            echo ",";
        } ?>
            ],
            marker: {
                lineWidth: 2,
                lineColor: Highcharts.getOptions().colors[2],
                fillColor: 'white'
            },
        }, { 
            yAxis: 0,
            name: '<?php echo get_string('in_time_ratio', 'block_analytics_graphs');?>',
            type: 'spline',
            lineWidth: 2,
            lineColor: Highcharts.getOptions().colors[1],
            data: [
            <?php $arrlength = count($arrayofassignments);
        for ($x = 0; $x < $arrlength; $x++) {
            if ($arrayofduedates[$x] == 0 || $arrayofduedates[$x] > time()) {
                // If no duedate or duedate has not passed.
                echo 1;
            } else {
                printf ("%.2f", $arrayofintimesubmissions[$x] /
                    ($arrayofintimesubmissions[$x] + $arrayoflatesubmissions[$x] + $arrayofnosubmissions[$x]));
            }
            echo ",";
        } ?>
            ],
            marker: {
                lineWidth: 2,
                lineColor: Highcharts.getOptions().colors[1],
                fillColor: 'white'
            },
        }]
    });
});




        </script>
    </head>
    <body>

    <div id="container" style="min-width: 310px; min-width: 800px; height: 650px; margin: 0 auto"></div>
    <script>
    var geral = <?php echo $statistics; ?>;
        geral = parseObjToString(geral);
        $.each(geral, function(index, value) {
                var nome = value.assign;
 
                div = "";
                if (typeof value.in_time_submissions != 'undefined')
                {
            		title = <?php echo json_encode($this->coursename); ?> +
				        "</h3>" + 
				        <?php echo json_encode(get_string('in_time_submission', 'block_analytics_graphs')); ?> +
                        " - " +  nome ;
                    div += "<div class='div_nomes' id='" + index + "-0'>" + 
                        createEmailForm(title, value.in_time_submissions, courseid, "assign.php") +
                        "</div>";
                }
                if (typeof value.latesubmissions != 'undefined')
                {
            	 	title = <?php echo json_encode($this->coursename); ?> +
				        "</h3>" +
				        <?php echo json_encode(get_string('late_submission', 'block_analytics_graphs')); ?> +
                        " - " +  nome ;
                    div += "<div class='div_nomes' id='" + index + "-1'>" +
                        createEmailForm(title, value.latesubmissions, courseid, "assign.php") +
                        "</div>";
                }
        	    if (typeof value.no_submissions != 'undefined')
                {
            		title = <?php echo json_encode($this->coursename); ?> +
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

<?php
    }
}
