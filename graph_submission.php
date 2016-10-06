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
    private $statistics;

    public function __construct($course, $title) {
        $this->course = $course;
        $this->title = $title;

        // Control access.
        require_login($course);
        $this->context = context_course::instance($course);

        require_capability('block/analytics_graphs:viewpages', $this->context);

        $courseparams = get_course($course);
        $this->startdate = $courseparams->startdate;
        $this->coursename = get_string('course', 'block_analytics_graphs') . ": " . $courseparams->fullname;
    }

    public function get_course() {
        return $this->course;
    }

    public function get_coursename() {
        return $this->coursename;
    }

    public function get_statistics() {
        return $this->statistics;
    }


    public function create_graph($result, $students) {
        if (empty($result)) {
            exit;
        }
        $numberofstudents = count($students);
        if ($numberofstudents == 0) {
            echo(get_string('no_students', 'block_analytics_graphs'));
            exit;
        }
        foreach ($students as $tuple) {
            $arrayofstudents[] = array('userid' => $tuple->id ,
                'nome' => $tuple->firstname.' '.$tuple->lastname, 'email' => $tuple->email);
        }
        $counter = 0;
        $numberofintimesubmissions = 0;
        $numberoflatesubmissions = 0;
        $assignmentid = 0;

        foreach ($result as $tuple) {
            if ($assignmentid == 0) { // First time in loop.
                $this->statistics[$counter]['assign'] = $tuple->name;
                $this->statistics[$counter]['duedate'] = $tuple->duedate;
                $this->statistics[$counter]['cutoffdate'] = $tuple->cutoffdate;
                if (isset($tuple->userid)) { // If a student submitted.
                    if ($tuple->duedate >= $tuple->timecreated || $tuple->duedate == 0) { // In the right time.
                        $this->statistics[$counter]['in_time_submissions'][] = array('userid'  => $tuple->userid,
                            'nome'  => $tuple->firstname." ".$tuple->lastname,
                            'email'  => $tuple->email, 'timecreated'  => $tuple->timecreated);
                        $numberofintimesubmissions++;
                    } else { // Late.
                        $this->statistics[$counter]['latesubmissions'][] = array('userid'  => $tuple->userid,
                            'nome'  => $tuple->firstname." ".$tuple->lastname, 'email'  => $tuple->email,
                            'timecreated'  => $tuple->timecreated);
                        $numberoflatesubmissions++;
                    }
                }
                $assignmentid = $tuple->assignment;
            } else { // Not first time in loop.
                if ($assignmentid == $tuple->assignment and $tuple->userid) { // Same task -> add student.
                    if ($tuple->duedate >= $tuple->timecreated || $tuple->duedate == 0) { // Right time.
                        $this->statistics[$counter]['in_time_submissions'][] = array('userid'  => $tuple->userid,
                            'nome'  => $tuple->firstname." ".$tuple->lastname,
                            'email'  => $tuple->email, 'timecreated'  => $tuple->timecreated);
                        $numberofintimesubmissions++;
                    } else { // Late.
                        $this->statistics[$counter]['latesubmissions'][] = array('userid'  => $tuple->userid,
                            'nome'  => $tuple->firstname." ".$tuple->lastname,
                            'email'  => $tuple->email, 'timecreated'  => $tuple->timecreated);
                        $numberoflatesubmissions++;
                    }
                }

                if ($assignmentid != $tuple->assignment) { // Another task -> finish previous and start.
                    $this->statistics[$counter]['numberofintimesubmissions'] = $numberofintimesubmissions;
                    $this->statistics[$counter]['numberoflatesubmissions'] = $numberoflatesubmissions;
                    $this->statistics[$counter]['numberofnosubmissions'] =
                            $numberofstudents - $numberofintimesubmissions - $numberoflatesubmissions;
                    if ($this->statistics[$counter]['numberofnosubmissions'] > 0) {
                        if ($this->statistics[$counter]['numberofnosubmissions'] == $numberofstudents) {
                            $this->statistics[$counter]['no_submissions'] = $arrayofstudents;
                        } else if ($numberoflatesubmissions == 0) {
                            $this->statistics[$counter]['no_submissions'] =
                                block_analytics_graphs_subtract_student_arrays($arrayofstudents,
                                $this->statistics[$counter]['in_time_submissions']);
                        } else if ($numberofintimesubmissions == 0) {
                            $this->statistics[$counter]['no_submissions'] =
                                block_analytics_graphs_subtract_student_arrays($arrayofstudents,
                                $this->statistics[$counter]['latesubmissions']);
                        } else {
                            $this->statistics[$counter]['no_submissions'] =
                                block_analytics_graphs_subtract_student_arrays(
                                block_analytics_graphs_subtract_student_arrays($arrayofstudents,
                                    $this->statistics[$counter]['in_time_submissions']),
                                $this->statistics[$counter]['latesubmissions']);
                        }
                    }
                    $counter++;
                    $numberofintimesubmissions = 0;
                    $numberoflatesubmissions = 0;
                    $this->statistics[$counter]['assign'] = $tuple->name;
                    $this->statistics[$counter]['duedate'] = $tuple->duedate;
                    $this->statistics[$counter]['cutoffdate'] = $tuple->cutoffdate;
                    $assignmentid = $tuple->assignment;
                    if ($tuple->userid) { // If a user has submitted
                        if ($tuple->duedate >= $tuple->timecreated || $tuple->duedate == 0) { // Right time.
                            $this->statistics[$counter]['in_time_submissions'][] = array('userid'  => $tuple->userid,
                                'nome' => $tuple->firstname." ".$tuple->lastname,
                                'email' => $tuple->email, 'timecreated'  => $tuple->timecreated);
                            $numberofintimesubmissions = 1;
                        } else { // Late.
                            $this->statistics[$counter]['latesubmissions'][] = array('userid'  => $tuple->userid,
                                'nome'  => $tuple->firstname." ".$tuple->lastname,
                                'email'  => $tuple->email, 'timecreated'  => $tuple->timecreated);
                            $numberoflatesubmissions = 1;
                        }
                    }
                }
            }
        }

        // Finishing of last access.
        $this->statistics[$counter]['numberofintimesubmissions'] = $numberofintimesubmissions;
        $this->statistics[$counter]['numberoflatesubmissions'] = $numberoflatesubmissions;
        $this->statistics[$counter]['numberofnosubmissions'] = $numberofstudents - $numberofintimesubmissions -
            $numberoflatesubmissions;
        if ($this->statistics[$counter]['numberofnosubmissions'] > 0) {
            if ($this->statistics[$counter]['numberofnosubmissions'] == $numberofstudents) {
                $this->statistics[$counter]['no_submissions'] = $arrayofstudents;
            } else if ($numberoflatesubmissions == 0) {
                $this->statistics[$counter]['no_submissions'] =
                    block_analytics_graphs_subtract_student_arrays($arrayofstudents,
                    $this->statistics[$counter]['in_time_submissions']);
            } else if ($numberofintimesubmissions == 0) {
                $this->statistics[$counter]['no_submissions'] =
                    block_analytics_graphs_subtract_student_arrays($arrayofstudents,
                    $this->statistics[$counter]['latesubmissions']);
            } else {
                $this->statistics[$counter]['no_submissions'] =
                    block_analytics_graphs_subtract_student_arrays(
                    block_analytics_graphs_subtract_student_arrays($arrayofstudents,
                        $this->statistics[$counter]['in_time_submissions']),
                    $this->statistics[$counter]['latesubmissions']);
            }
        }

        foreach ($this->statistics as $tuple) {
            $arrayofassignments[] = $tuple['assign'];
            $arrayofintimesubmissions[] = $tuple['numberofintimesubmissions'];
            $arrayoflatesubmissions[] = $tuple['numberoflatesubmissions'];
            $arrayofnosubmissions[] = $tuple['numberofnosubmissions'];
            $arrayofduedates[] = $tuple['duedate'];
            $arrayofcutoffdates[] = $tuple['cutoffdate']; // For future use.
        }
        $this->statistics = json_encode($this->statistics);

        $chart = 'options = {
                chart: {
                    zoomType: "x",
                    alignTicks: false
                },
                title: {
                    text: "' . $this->title . '",
                    margin: 60
                },
                subtitle: {
                    text: "' . $this->coursename . '<br>' .
                             get_string("begin_date", "block_analytics_graphs") . ': ' .
                             userdate($this->startdate, get_string("strftimerecentfull")) . '",
                },
                legend: {
                    layout: "vertical",
                    align: "right",
                    verticalAlign: "top",
                    x: -40,
                    y: 5,
                    floating: true,
                    borderWidth: 1,
                    backgroundColor: (Highcharts.theme && Highcharts.theme.legendBackgroundColor || "#FFFFFF"),
                    shadow: true
                },
                credits: {
                    enabled: false
                },
                xAxis: [
                    {
                        categories: [';
        $arrlength = count($arrayofassignments);
        for ($x = 0; $x < $arrlength; $x++) {
            $chart .= '"<b>';
            $chart .= substr($arrayofassignments[$x], 0, 35);
            if ($arrayofduedates[$x]) {
                $chart .= '</b><br>'. userdate($arrayofduedates[$x], get_string("strftimerecentfull")) . '",';
            } else {
                $chart .= '</b><br>'.get_string("no_deadline", "block_analytics_graphs") . '",';
            }
        }
        $chart .= '],
                        labels: {
                            rotation: -45,
                        }
                    }
                ],
                yAxis: [
                    { // Primary yAxis
                        max: 1,
                        min: 0,
                        tickInterval: 0.25,
                        labels: {
                            format: "{value}",
                            style: {
                                color: Highcharts.getOptions().colors[1]
                            }
                        },
                        title: {
                            text: "' . get_string("ratio", "block_analytics_graphs") . '",
                            style: {
                                color: Highcharts.getOptions().colors[1]
                            }
                        }
                    }, { // Secondary yAxis
                        min: 0,
                        ceiling: ' . json_encode($numberofstudents) . ',
                        tickInterval: ' . json_encode($numberofstudents / 4) . ',
                        opposite: true,
                        labels: {
                            format: "{value}",
                            style: {
                                color: Highcharts.getOptions().colors[1]
                            }
                        },
                        title: {
                            text: "' . get_string("number_of_students", "block_analytics_graphs") . '",
                            style: {
                                color: Highcharts.getOptions().colors[1]
                            }
                        }
                    }
                ],
                tooltip: {
                    crosshairs: true
                },
                plotOptions: {
                    series: {
                        cursor: "pointer",
                        point: {
                            events: {
                                click: function() {
                                            var nome_conteudo = this.x + "-" + this.series.index;
                                            var group = $("#group_select").val();
                                            $(".div_nomes").dialog("close");
                                            if(group !== undefined && group != "-")
                                                nome_conteudo +=  "-" + group;
                                            $("#" + nome_conteudo).dialog("open");
                                }
                            }
                        }
                    }
                },
                bar: {
                    dataLabels: {
                        useHTML: this,
                        enabled: true
                    }
                },
                series: [
                    {
                        yAxis: 1,
                        name: "' . get_string("in_time_submission", "block_analytics_graphs") . '",
                        type: "column",
                        data: [';
        $arrlength = count($arrayofassignments);
        for ($x = 0; $x < $arrlength; $x++) {
            $chart .= $arrayofintimesubmissions[$x];
            $chart .= ',';
        }
        $chart .= '],
                        tooltip: {
                            valueSuffix: " ' . get_string("students", "block_analytics_graphs") . '"
                        }
                    }, {
                        yAxis: 1,
                        name: "' . get_string("late_submission", "block_analytics_graphs") . '",
                        type: "column",
                        data: [';
        $arrlength = count($arrayofassignments);
        for ($x = 0; $x < $arrlength; $x++) {
            $chart .= $arrayoflatesubmissions[$x];
            $chart .= ',';
        }
        $chart .= '],
                        tooltip: {
                            valueSuffix: " ' . get_string("students", "block_analytics_graphs") . '"
                        }
                    }, {
                        yAxis: 1,
                        name: "' . get_string("no_submission", "block_analytics_graphs") . '",
                        type: "column",
                        color: "#FF1111", //cor
                        data: [';
        $arrlength = count($arrayofassignments);
        for ($x = 0; $x < $arrlength; $x++) {
            $chart .= $arrayofnosubmissions[$x];
            $chart .= ',';
        }
        $chart .= '],
                        tooltip: {
                            valueSuffix: " ' . get_string("students", "block_analytics_graphs") . '"
                        }
                    }, {
                        yAxis: 0,
                        name: "' . get_string("submission_ratio", "block_analytics_graphs") . '",
                        type: "spline",
                        lineWidth: 2,
                        lineColor: Highcharts.getOptions().colors[2],
                        data: [';
        $arrlength = count($arrayofassignments);
        for ($x = 0; $x < $arrlength; $x++) {
            $chart .= sprintf("%.2f", ($arrayofintimesubmissions[$x] + $arrayoflatesubmissions[$x]) /
                ($arrayofintimesubmissions[$x] + $arrayoflatesubmissions[$x] + $arrayofnosubmissions[$x]));
            $chart .= ',';
        }
        $chart .= '],
                        marker: {
                            lineWidth: 2,
                            lineColor: Highcharts.getOptions().colors[2],
                            fillColor: "white"
                        }
                    }, {
                        yAxis: 0,
                        name: "' . get_string("in_time_ratio", "block_analytics_graphs") . '",
                        type: "spline",
                        lineWidth: 2,
                        lineColor: Highcharts.getOptions().colors[1],
                        data: [';
        $arrlength = count($arrayofassignments);
        for ($x = 0; $x < $arrlength; $x++) {
            if ($arrayofduedates[$x] == 0 || $arrayofduedates[$x] > time()) {
                // If no duedate or duedate has not passed.
                $chart .= 1;
            } else {
                $chart .= sprintf("%.2f", $arrayofintimesubmissions[$x] /
                    ($arrayofintimesubmissions[$x] + $arrayoflatesubmissions[$x] + $arrayofnosubmissions[$x]));
            }
            $chart .= ',';
        }
        $chart .= '],
                        marker: {
                            lineWidth: 2,
                            lineColor: Highcharts.getOptions().colors[1],
                            fillColor: "white"
                        }
                    }
                ]
            }';

        $event = \block_analytics_graphs\event\block_analytics_graphs_event_view_graph::create(array(
            'objectid' => $this->course,
            'context' => $this->context,
            'other' => "assign.php",
        ));
        $event->trigger();

        return $chart;
    }

}