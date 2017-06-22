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
require('lib.php');
require('javascriptfunctions.php');
$course = required_param('id', PARAM_INT);
$days = required_param('days', PARAM_INT);
global $DB;
global $CFG;

$logstorelife = block_analytics_graphs_get_logstore_loglife();
$coursedayssincestart = block_analytics_graphs_get_course_days_since_startdate($course);
if ($logstorelife === null || $logstorelife == 0) {
    // 0, false and NULL are threated as null in case logstore setting not found and 0 is "no removal" logs.
    $maximumdays = $coursedayssincestart; // the chart should not break with value more than available
} else if ($logstorelife >= $coursedayssincestart) {
    $maximumdays = $coursedayssincestart;
} else {
    $maximumdays = $logstorelife;
}

if ($days > $maximumdays) { // sanitycheck
    $days = $maximumdays;
} else if ($days < 1) {
    $days = 1;
}

$students = block_analytics_graphs_get_students($course);
$daysaccess = block_analytics_graphs_get_accesses_last_days($course, $students, $days);
$daysaccess = json_encode($daysaccess);


?>

<html>
<head>

    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title><?php echo get_string('timeaccesschart_title', 'block_analytics_graphs'); ?></title>

    <link rel="stylesheet" href="externalref/jquery-ui-1.12.1/jquery-ui.css">
    <script src="externalref/jquery-1.12.2.js"></script>
    <script src="externalref/jquery-ui-1.12.1/jquery-ui.js"></script>
    <script src="externalref/highstock.js"></script>
    <script src="externalref/no-data-to-display.js"></script>
    <script src="externalref/exporting.js"></script>
    <script src="externalref/export-csv-master/export-csv.js"></script>

    <style>
        div.res_query {
            display:table;
            margin-right:auto;
            margin-left:auto;
        }
        .chart {
            float: left;
            display: block;
            margin: auto;
        }
        .ui-dialog {
            position: fixed;
        }
        #result {
            text-align: right;
            color: gray;
            min-height: 2em;
        }
        #table-sparkline {
            margin: 0 auto;
            border-collapse: collapse;
        }
        div.student_panel{
            font-size: 0.85em;
            min-height: 450px;
            margin-left: auto;
            margin-right: auto;
        }
        a.contentaccess, a.submassign, a.msgs, a.mail, a.quizchart, a.forumchart{
            font-size: 0.85em;
        }
        table.res_query {
            font-size: 0.85em;
        }
        .image-exclamation {
            width: 25px;
            height: 20px;
            vertical-align: middle;
            visibility: hidden;
        }
        .warnings {
            float: right;
            align: right;
            margin-left: 10px;
            display: inline-flex;
            flex-direction: row;
            justify-content: space-around;
            width: 55px;
        }
        .warning1, .warning2 {
            width: 25px;
        }
        .warning1 {
            order: 1;
            margin-right: 5px;
        }
        .warning2 {
            order: 2;
        }
        th {
            font-weight: bold;
            text-align: left;
        }
        td, th {
            padding: 5px;
            border-top: 1px solid silver;
            border-bottom: 1px solid silver;
            border-right: 1px solid silver;
            height: 60px;
        }
        thead th {
            border-top: 2px solid gray;
            border-bottom: 2px solid gray;
        }
        .highcharts-container {
            overflow: visible !important;
        }
        .highcharts-tooltip {
            pointer-events: all !important;
        }
        .highcharts-tooltip>span {
            background: white;
            border: 1px solid silver;
            border-radius: 3px;
            box-shadow: 1px 1px 2px #888;
            padding: 8px;
            max-height: 250px;
            width: auto;
            overflow: auto;
        }
        .scrollableHighchartsTooltipAddition {
            position: relative;
            z-index: 50;
            border: 2px solid rgb(0, 108, 169);
            border-radius: 5px;
            background-color: #ffffff;
            padding: 5px;
            font-size: 9pt;
            overflow: auto;
            height: 200px;
        }
    </style>

</head>

<div style="width: 300px; min-width: 325px; height: 65px;left:10px; top:5px; border-radius: 0px;padding: 5px;border: 2px solid silver;text-align: center;">
    <?php echo get_string('timeaccesschart_daysforstatistics', 'block_analytics_graphs'); ?>
    <input style="width: 50px;" id = "days" type="number" name="days" min="1" max="<?php echo $maximumdays; ?>" value="<?php echo $days ?>">
    <br>
    <button style="width: 225px;" id="apply"><?php echo get_string('timeaccesschart_button_apply', 'block_analytics_graphs'); ?></button>
    <br>
    <?php echo get_string('timeaccesschart_maxdays', 'block_analytics_graphs') . "<b>" . $maximumdays . "</b>"; ?>
</div>

<div id="containerA" style="min-width: 300px; height: 400px; margin: 0 auto"></div>
<br>
<hr/>
<br>
<div id="containerB" style="min-width: 300px; height: 400px; margin: 0 auto"></div>

<script type="text/javascript">
    var data = <?php echo $daysaccess; ?>;
    var houraccesses = [];
    var houractivities = [];

    for (var i = 0; i < 24; i++)
    {
        var hourbegin = i * 10000;
        var hourend = i * 10000 + 9999;
        var countedIds = [];
        var numActiveStudents = 0;
        var numActivitiesHour = 0;
        var maximumDays = <?php echo $maximumdays; ?>;

        for(var j in data)
        {
            if (data[j].timecreated >= hourbegin && data[j].timecreated <= hourend) {
                if (jQuery.inArray(data[j].userid, countedIds) == -1) {
                countedIds.push(data[j].userid);
                numActiveStudents++;
                }
                numActivitiesHour++;
            }
        }

        houraccesses[i] = numActiveStudents;
        houractivities[i] = numActivitiesHour;
    }

    $('#apply').click(function() {
        if (maximumDays < $('#days').val()) {
            window.location.href = '<?php echo $CFG->wwwroot . "/blocks/analytics_graphs/timeaccesseschart.php?id=" . $course . "&days="; ?>' + maximumDays;
        } else {
            window.location.href = '<?php echo $CFG->wwwroot . "/blocks/analytics_graphs/timeaccesseschart.php?id=" . $course . "&days="; ?>' + $('#days').val();
        }
        return false;
    });

    Highcharts.chart('containerA', {
        chart: {
            type: 'column',
            events: {
                load: function(){
                    this.mytooltip = new Highcharts.Tooltip(this, this.options.tooltip);
                }
            }
        },
        title: {
            text: '<?php echo get_string('timeaccesschart_title', 'block_analytics_graphs'); ?>'
        },
        xAxis: {
            type: 'category',
            labels: {
                rotation: -45,
                style: {
                    fontSize: '13px',
                    fontFamily: 'Verdana, sans-serif'
                }
            }
        },
        yAxis: {
            min: 0,
            title: {
                text: '<?php echo get_string('timeaccesschart_tip', 'block_analytics_graphs'); ?>'
            }
        },
        legend: {
            enabled: false
        },
        tooltip: {
            enabled: false,
            useHTML: true,
            backgroundColor: "rgba(255, 255, 255, 1.0)",
            formatter: function(){
                var hour = this.point.name.replace(":00", "");
                var hourbegin = hour * 10000;
                var hourend = hour * 10000 + 9999;
                var countedIds = [];

                var tooltipStr = "<span style='font-size: 13px'><b>" +
                    this.point.name +
                    "</b></span>:<br>";

                for(var j in data)
                {
                    if (data[j].timecreated >= hourbegin && data[j].timecreated <= hourend) {
                        if (jQuery.inArray(data[j].userid, countedIds) == -1) {
                            countedIds.push(data[j].userid);
                            tooltipStr += data[j].firstname + " " + data[j].lastname + "<br>";
                        }
                    }
                }

                return "<div class='scrollableHighchartsTooltipAddition'>" + tooltipStr + "</div>";
            }
        },
        credits: {
            enabled: false
        },
        plotOptions: {
            series : {
                stickyTracking: false,
                events: {
                    click : function(evt){
                        this.chart.mytooltip.refresh(evt.point, evt);
                    },
                    mouseOut : function(){
                        this.chart.mytooltip.hide();
                    }
                }
            }
        },
        series: [{
            name: 'Time',
            data: [
                ['00:00', houraccesses[0]],
                ['01:00', houraccesses[1]],
                ['02:00', houraccesses[2]],
                ['03:00', houraccesses[3]],
                ['04:00', houraccesses[4]],
                ['05:00', houraccesses[5]],
                ['06:00', houraccesses[6]],
                ['07:00', houraccesses[7]],
                ['08:00', houraccesses[8]],
                ['09:00', houraccesses[9]],
                ['10:00', houraccesses[10]],
                ['11:00', houraccesses[11]],
                ['12:00', houraccesses[12]],
                ['13:00', houraccesses[13]],
                ['14:00', houraccesses[14]],
                ['15:00', houraccesses[15]],
                ['16:00', houraccesses[16]],
                ['17:00', houraccesses[17]],
                ['18:00', houraccesses[18]],
                ['19:00', houraccesses[19]],
                ['20:00', houraccesses[20]],
                ['21:00', houraccesses[21]],
                ['22:00', houraccesses[22]],
                ['23:00', houraccesses[23]]
            ],
            dataLabels: {
                enabled: true,
                rotation: -90,
                color: '#FFFFFF',
                align: 'right',
                format: '{point.y:.1f}', // one decimal
                y: 10, // 10 pixels down from the top
                style: {
                    fontSize: '13px',
                    fontFamily: 'Verdana, sans-serif'
                }
            }
        }]
    });

    Highcharts.chart('containerB', {
        chart: {
            type: 'column',
            events: {
                load: function(){
                    this.mytooltip = new Highcharts.Tooltip(this, this.options.tooltip);
                }
            }
        },
        title: {
            text: '<?php echo get_string('timeaccesschart_activities_title', 'block_analytics_graphs'); ?>'
        },
        xAxis: {
            type: 'category',
            labels: {
                rotation: -45,
                style: {
                    fontSize: '13px',
                    fontFamily: 'Verdana, sans-serif'
                }
            }
        },
        yAxis: {
            min: 0,
            title: {
                text: '<?php echo get_string('timeaccesschart_tip', 'block_analytics_graphs'); ?>'
            }
        },
        legend: {
            enabled: false
        },
        tooltip: {
            enabled: false,
            useHTML: true,
            backgroundColor: "rgba(255, 255, 255, 1.0)",
            formatter: function(){
                var hour = this.point.name.replace(":00", "");
                var hourbegin = hour * 10000;
                var hourend = hour * 10000 + 9999;

                var tooltipStr = "<span style='font-size: 13px'><b>" +
                    this.point.name +
                    "</b></span>:<br>";

                var previousStr = "";
                var sameStrCount = 0;
                for(var j in data)
                {
                    if (data[j].timecreated >= hourbegin && data[j].timecreated <= hourend) {
                        var tempstr = data[j].firstname + " " + data[j].lastname
                            + "->" + data[j].action + ":" + data[j].target;

                        if (tempstr != previousStr && sameStrCount == 0) {
                            if (previousStr != "") {
                                tooltipStr += previousStr + "<br>";
                            }
                            previousStr = tempstr;
                        } else if (tempstr == previousStr) {
                            sameStrCount++;
                        } else if (tempstr != previousStr && sameStrCount > 0) {
                            tooltipStr += previousStr + ":" + (sameStrCount+1) +"<br>";
                            sameStrCount = 0;
                            previousStr = tempstr;
                        }
                    }
                }

                if (sameStrCount > 0) {
                    tooltipStr += previousStr + ":" + (sameStrCount+1) +"<br>";
                } else {
                    tooltipStr += previousStr + "<br>";
                }

                return "<div class='scrollableHighchartsTooltipAddition'>" + tooltipStr + "</div>";
            }
        },
        credits: {
            enabled: false
        },
        plotOptions: {
            series : {
                stickyTracking: false,
                events: {
                    click : function(evt){
                        this.chart.mytooltip.refresh(evt.point, evt);
                    },
                    mouseOut : function(){
                        this.chart.mytooltip.hide();
                    }
                }
            }
        },
        series: [{
            name: 'Time',
            data: [
                ['00:00', houractivities[0]],
                ['01:00', houractivities[1]],
                ['02:00', houractivities[2]],
                ['03:00', houractivities[3]],
                ['04:00', houractivities[4]],
                ['05:00', houractivities[5]],
                ['06:00', houractivities[6]],
                ['07:00', houractivities[7]],
                ['08:00', houractivities[8]],
                ['09:00', houractivities[9]],
                ['10:00', houractivities[10]],
                ['11:00', houractivities[11]],
                ['12:00', houractivities[12]],
                ['13:00', houractivities[13]],
                ['14:00', houractivities[14]],
                ['15:00', houractivities[15]],
                ['16:00', houractivities[16]],
                ['17:00', houractivities[17]],
                ['18:00', houractivities[18]],
                ['19:00', houractivities[19]],
                ['20:00', houractivities[20]],
                ['21:00', houractivities[21]],
                ['22:00', houractivities[22]],
                ['23:00', houractivities[23]]
            ],
            dataLabels: {
                enabled: true,
                rotation: -90,
                color: '#FFFFFF',
                align: 'right',
                format: '{point.y:.1f}', // one decimal
                y: 10, // 10 pixels down from the top
                style: {
                    fontSize: '13px',
                    fontFamily: 'Verdana, sans-serif'
                }
            }
        }]
    });

</script>




</html>
