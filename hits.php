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
$legacy = required_param('legacy', PARAM_INT);
$startdate = optional_param('from', '***', PARAM_TEXT);
global $DB;

/* Access control */
require_login($course);
$context = context_course::instance($course);
require_capability('block/analytics_graphs:viewpages', $context);

/*For future tests*/
// $PAGE->set_url(new moodle_url('/blocks/analytics_graphs/hits.php', array('id' => $course, 'legacy' => 0)));
// $PAGE->set_context(context_course::instance($course));
// $PAGE->set_pagelayout('print');
// echo $OUTPUT->header();

$courseparams = get_course($course);
if ($startdate === '***') {
	$startdate = $courseparams->startdate;
} else {
	$datetoarray = explode('-', $startdate);
	$starttime = new DateTime("now", core_date::get_server_timezone_object());
	$starttime->setDate((int)$datetoarray[0], (int)$datetoarray[1], (int)$datetoarray[2]);
	$starttime->setTime(0, 0, 0);
	$startdate = $starttime->getTimestamp();
}
$coursename = get_string('course', 'block_analytics_graphs') . ": " . $courseparams->fullname;
$students = block_analytics_graphs_get_students($course);

$numberofstudents = count($students);
if ($numberofstudents == 0) {
    echo(get_string('no_students', 'block_analytics_graphs'));
    exit;
}
foreach ($students as $tuple) {
        $arrayofstudents[] = array('userid' => $tuple->id ,
                                'nome' => $tuple->firstname.' '.$tuple->lastname,
                                'email' => $tuple->email);
}

/* Get the number of days with access by week */
$resultado = block_analytics_graphs_get_number_of_days_access_by_week($course, $students, $startdate, $legacy); // A

/* Get the students that have no access */
$maxnumberofweeks = 0;
foreach ($resultado as $tuple) {
    $arrayofaccess[] = array('userid' => $tuple->userid ,
                            'nome' => $tuple->firstname.' '.$tuple->lastname,
                            'email' => $tuple->email);
    if ($tuple->week > $maxnumberofweeks) {
        $maxnumberofweeks = $tuple->week;
    }
}

if ($maxnumberofweeks) {
    $studentswithnoaccess = block_analytics_graphs_subtract_student_arrays($arrayofstudents, $arrayofaccess);
} else {
    $studentswithnoaccess = $arrayofstudents;
}

/* Get the number of modules accessed by week */
$accessresults = block_analytics_graphs_get_number_of_modules_access_by_week($course, $students, $startdate, $legacy); // B
$maxnumberofresources = 0;
foreach ($accessresults as $tuple) {
    if ( $tuple->number > $maxnumberofresources) {
        $maxnumberofresources = $tuple->number;
    }
}


/* Discover groups/groupings and members */
$groupmembers = block_analytics_graphs_get_course_group_members($course);
$groupingmembers = block_analytics_graphs_get_course_grouping_members($course);
$groupmembers = array_merge($groupmembers,$groupingmembers);
$groupmembersjson = json_encode($groupmembers);


/* Get the total number of modules accessed */
$numberofresourcesresult = block_analytics_graphs_get_number_of_modules_accessed($course, $students, $startdate, $legacy);

/* Convert results to javascript */
$resultado = json_encode($resultado);
$studentswithnoaccess = json_encode($studentswithnoaccess);
$accessresults = json_encode($accessresults);
$numberofresourcesresult = json_encode($numberofresourcesresult);

/* Log */
$event = \block_analytics_graphs\event\block_analytics_graphs_event_view_graph::create(array(
    'objectid' => $course,
    'context' => $context,
    'other' => "hits.php",
));
$event->trigger();
?>




<html>
<head>

<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title><?php echo get_string('hits_distribution', 'block_analytics_graphs'); ?></title>

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
.totalgraph {
    width: 55%;
    display: block;
    margin-left: auto;
    margin-right: auto;
    margin-top: 50px;
    border-radius: 0px;
    padding: 10px;
    border-top: 1px solid silver;
    border-bottom: 1px solid silver;
    border-right: 1px solid silver;
}
</style>


<script type="text/javascript">
    var courseid = <?php echo json_encode($course); ?>;
    var coursename = <?php echo json_encode($coursename); ?>;
    var geral = <?php echo $resultado; ?>;
    var moduleaccess = <?php echo $accessresults; ?>;
    var numberofresources = <?php echo $numberofresourcesresult; ?>;
    var studentswithnoaccess = <?php echo $studentswithnoaccess; ?>;
    var groups = <?php echo $groupmembersjson; ?>;
    var legacy = <?php echo json_encode($legacy); ?>;
    var weekBeginningOffset = 1; //added to each loop making charts start from WEEK#1 instead of WEEK#0
    var nomes = [];
    var totalResourceAccessData = [];
    var totalWeekDaysAccessData = [];
    $.each(geral, function(ind, val){   
        var nome = val.firstname+" "+val.lastname;
        if (nomes.indexOf(nome) === -1)
            nomes.push(nome);

    });
    
    nomes.sort();
    var students = [];
    
    // Organize data to generate right graph.
    $.each(geral, function(ind, val){
        if (students[val.userid]){
            var student = students[val.userid];
            student.semanas[val.week] = Number(val.week);
            student.acessos[val.week] = Number(val.number);
            student.totalofaccesses += Number(val.number);
            student.pageViews += Number(val.numberofpageviews);
            students[val.userid] = student;
        }else{
            // Nessa parte criamos um obj que contera um array com a semana (indice) e outro com o number (valor)
            // os dois tendo a mesma chave que ÃÂ© o numero dasemana.
            var student = {};
            student.userid = Number(val.userid);
            student.nome = val.firstname+" "+val.lastname;
            student.email = val.email;
            student.semanas = [];
            student.semanas[val.week] = Number(val.week);
            student.acessos = [];
            student.acessos[val.week] = Number(val.number);
            student.totalofaccesses = Number(val.number);
            student.pageViews = Number(val.numberofpageviews);
            if (numberofresources[val.userid])
                student.totalofresources = numberofresources[val.userid].number ;
            else
                student.totalofresources = 0;
            students[val.userid] = student;
        }
    });

    $.each(moduleaccess, function(index, value){
        if (students[value.userid]){
            var student = students[value.userid];
            if (student.semanasModulos === undefined)
                student.semanasModulos = [];                
            student.semanasModulos[value.week] = Number(value.week);
            if (student.acessosModulos === undefined)
                student.acessosModulos = [];
            student.acessosModulos[value.week] = (value.number>0 ? Number(value.number) : 0 );
            students[value.userid] = student;
        }
    });

    for (i = 0; i <= <?php echo $maxnumberofweeks; ?>; i++) {
        totalResourceAccessData[i] = 0;
        $.each(students, function(index, item) {
            if (item !== undefined && item.acessosModulos !== undefined && item.acessosModulos[i] != undefined) {
                totalResourceAccessData[i] += item.acessosModulos[i];
            }
        });
        totalWeekDaysAccessData[i] = 0;
        $.each(students, function(index, item) {
            if (item !== undefined && item.acessos !== undefined && item.acessos[i] != undefined) {
                totalWeekDaysAccessData[i] += item.acessos[i];
            }
        });
    }
    totalResourceAccessData = pan_array_to_max_number_of_weeks(totalResourceAccessData);
    totalWeekDaysAccessData = pan_array_to_max_number_of_weeks(totalWeekDaysAccessData);

    function trata_array(array){
        var novo = [];
        $.each(array, function(ind, value){
            if (!value)
                novo[ind] = 0;
            else
                novo[ind] = value;
        });
        if (novo.length <= <?php echo $maxnumberofweeks; ?>) {
            novo = pan_array_to_max_number_of_weeks(novo);
        }
        return novo;
    }

    function pan_array_to_max_number_of_weeks(array) {
        for (i = array.length; i <= (<?php echo $maxnumberofweeks; ?>); i++ ) {
        if (array[i] === undefined)
            array[i] = 0;
        }
        return array;
    }

    function gerar_grafico_modulos(student){
        if (student.acessosModulos !== undefined){
                $("#modulos-"+student.userid).highcharts({

                chart: {
                        borderWidth: 0,
                        type: 'area',
                        margin: [0, 0, 0, 0],
                        spacingBottom: 0,
                        width: 250,
                        height: 60,
                        style: {
                                overflow: 'visible'
                        },
                        skipClone: true,
                },

                xAxis: {
                        labels: {
                                enabled: false
                        },
                        title: {
                                text: null
                        },
                        startOnTick: false,
                        endOnTick: false,
                        tickPositions: [],
                        tickInterval: 1,
                        minTickInterval: 24,
                        min: (<?php echo $maxnumberofweeks; ?> + weekBeginningOffset) - 15,
                        max: <?php echo $maxnumberofweeks; ?> + weekBeginningOffset
                 },

                navigator: {
                    enabled: false,
                    margin: 5
                },

                scrollbar: {
                    enabled: true,
                    height: 10
                },

                yAxis: {
                        minorTickInterval: 5,
                        endOnTick: false,
                        startOnTick: false,
                        labels: {
                                enabled: false
                        },
                        title: {
                                text: null
                        },
                        tickPositions: [0],
                        max: <?php echo $maxnumberofresources;?>,
                        tickInterval: 5
                },

                title: {
                        text: null
                },

                credits: {
                          enabled: false
                 },

                legend: {
                        enabled: false
                },

                tooltip: {
                        backgroundColor: null,
                        borderWidth: 0,
                        shadow: false,
                        useHTML: true,
                        hideDelay: 0,
                        shared: true,
                        padding: 0,
                        headerFormat: '',
                        pointFormat: <?php echo "'".get_string('week_number', 'block_analytics_graphs').": '"; ?> +
                                        '{point.x}<br>' +  
                                        <?php echo "'".get_string('resources_with_access', 'block_analytics_graphs').": '"; ?> +
                                        '{point.y}',
                        positioner: function (w, h, point) { return { x: point.plotX - w / 2, y: point.plotY - h};}
                },
                plotOptions: {
                        series: {
                                animation:  { 
                                        duration: 4000
                                },
                                lineWidth: 1,
                                shadow: false,
                                states: {
                                        hover: {
                                                lineWidth: 1
                                                }
                                        },
                                marker: {
                                        radius: 2,
                                        states: {
                                                hover: {
                                                        radius: 4
                                                        }
                                                }                                        },
                                fillOpacity: 0.25
                        },
                },
                series: [{
                    pointStart: weekBeginningOffset,
                    data: trata_array(student.acessosModulos)
                }],
                
                
                exporting: {
                    enabled: false
                },
                
                    });                    
                    last_week = <?php echo $maxnumberofweeks; ?>;
                    if(!(last_week in student.acessosModulos)){
                        $("#" + student.userid + "-1-img").css("visibility", "visible");
                    }
                }else{
                        $("#" + student.userid + "-2-img").css("visibility", "visible");
                        // $("#modulos-"+student.userid).text("Este usuário não acessou nenhum material ainda.");
                        // $("#modulos-"+student.userid).text(":(");
                }
        }

    function gerar_grafico(student){
        $("#acessos-"+student.userid).highcharts({

            chart: {
                borderWidth: 0,
                type: 'area',
                margin: [0, 0, 0, 0],
                spacingBottom: 0,
                width: 250,
                height: 60,
                style: {
                    overflow: 'visible'
                },
                skipClone: true,
            },


            xAxis: {
                labels: {
                    enabled: false
                },
                title: {
                    text: null
                },
                startOnTick: false,
                endOnTick: false,
                tickPositions: [],
                tickInterval: 1,
                minTickInterval: 24,
                min: (<?php echo $maxnumberofweeks; ?> + weekBeginningOffset) - 15,
                max: <?php echo $maxnumberofweeks; ?>  + weekBeginningOffset
            },

            navigator: {
                enabled: false,
                margin: 5
            },

            scrollbar: {
                enabled: true,
                height: 10
            },

            yAxis: {
                minorTickInterval: 1,
                endOnTick: false,
                startOnTick: false,
                labels: {
                    enabled: false
                },
                title: {
                    text: null
                },
                tickPositions: [0],
                max: 7, 
                tickInterval: 1
            },


            title: {
                text: null
            },


            credits: {
                enabled: false
            },


            legend: {
                enabled: false
            },


            tooltip: {
                backgroundColor: null,
                borderWidth: 0,
                shadow: false,
                useHTML: true,
                hideDelay: 0,
                shared: true,
                padding: 0,
                headerFormat: '',
                pointFormat: <?php echo "'".get_string('week_number', 'block_analytics_graphs').": '"; ?> +
                    '{point.x}<br>' +
                <?php echo "'".get_string('days_with_access', 'block_analytics_graphs').": '"; ?> +
                    '{point.y}',
                positioner: function (w, h, point) { return { x: point.plotX - w / 2, y: point.plotY - h}; }
            },


            plotOptions: {
                series: {
                    animation: {
                        duration: 2000
                    },
                    lineWidth: 1,
                    shadow: false,
                    states: {
                        hover: {
                            lineWidth: 1
                        }
                    },
                    marker: {
                        radius: 2,
                        states: {
                            hover: {
                                radius: 4                                                        }
                        }
                    },
                    fillOpacity: 0.25
                },
            },


            series: [{
                pointStart: weekBeginningOffset,
                data: trata_array(student.acessos)
            }],


            exporting: {
                enabled: false
            },

        });
    }


    function createRow(array, nomes){
        var red_excl = "images/warning-attention-road-sign-exclamation-mark.png";
        var yellow_excl = "images/exclamation_sign.png";
        var red_tooltip = <?php echo json_encode(get_string('red_tooltip', 'block_analytics_graphs')); ?>;
        var yellow_tooltip = <?php echo json_encode(get_string('yellow_tooltip', 'block_analytics_graphs')); ?>;
        $.each(nomes, function(ind,val){
            var nome = val;
            $.each(array, function(index, value){
                        if (value){
                            if (nome === value.nome){
                                    var linha = "<tr id='tr-student-"+value.userid+
                                        "'><th><span class='nome_student' style='cursor:hand'\
                                     id='linha-"+value.userid+"'>"+value.nome+"</span>"+
                                            "<div class='warnings'>\
                                                <div class='warning1' id='"+value.userid+"_1'>\
                                                    <img\
                                                        src='" + red_excl + "'\
                                                        title='" + red_tooltip + "'\
                                                        class='image-exclamation'\
                                                        id='" + value.userid + "-1-img'\
                                                    >\
                                                </div>\
                                                <div class='warning2' id='"+value.userid+"_2'>\
                                                    <img\
                                                        src='" + yellow_excl + "'\
                                                        title='" + yellow_tooltip +"'\
                                                        class='image-exclamation'\
                                                        id='" + value.userid + "-2-img'\
                                                    >\
                                                </div>\
                                            </div></th>" +
                                            "<td>"+
                                                    value.pageViews+
                                            "</td>"+
                                            "<td>"+
                                                    value.totalofaccesses+
                                            "</td>"+
                                            "<td width='250' id='acessos-"+value.userid+"'>"+
                                            "</td>"+
                                            "<td>"+                                                
                                            //(value.totalModulos>0? value.totalModulos : 0)+
                                            (numberofresources[value.userid]? numberofresources[value.userid].number : 0)+
                                            "</td>"+
                                            "<td id='modulos-"+value.userid+"'>"+
                                            "</td>"+
                                    "</tr>";
                                    $("table").append(linha);
                                    gerar_grafico(value);
                                    gerar_grafico_modulos(value);
                            }
                        }
            });
        });
    }
    
</script>


</head>
<body>
    <?php if (count($groupmembers) > 0) { ?>
        <div style="margin: 20px;">
            <select id="group_select">
                <option value="-"><?php  echo json_encode(get_string('all_groups', 'block_analytics_graphs'));?></option>
            <?php    foreach ($groupmembers as $key => $value) { ?>
                <option value="<?php echo $key; ?>"><?php echo $value["name"]; ?></option>
<?php
}
?>
            </select>
        </div>
<?php
}
?>
<center>
<H2><?php  echo   get_string('hits_distribution', 'block_analytics_graphs');?></H2>
<H3><?php  echo $coursename;?> </H3>
<H3><?php  echo   get_string('begin_date', 'block_analytics_graphs') . ": "
                        . userdate($startdate, get_string('strftimerecentfull'));?> </H3>
</center>
    <table id="table-sparkline" >
        <thead>
            <tr>                
        <th><?php  echo   get_string('students', 'block_analytics_graphs');?></th>                
        <th width=50><?php echo get_string('hits', 'block_analytics_graphs');?></th>                
        <th width=50><?php echo get_string('days_with_access', 'block_analytics_graphs');?></th>                
        <th><center><?php echo get_string('days_by_week', 'block_analytics_graphs');
            echo "<br><i>(". get_string('number_of_weeks', 'block_analytics_graphs')
                    . ": " . ($maxnumberofweeks + 1).")</i>";?></center></th>
        <th width=50><?php  echo   get_string('resources_with_access', 'block_analytics_graphs');?></th>                
        <th><center><?php echo get_string('resources_by_week', 'block_analytics_graphs');?></center></th>
            </tr>
        </thead>
        <tbody  id='tbody-sparklines'>
            <script type="text/javascript">
                    createRow(students, nomes);            
            </script>
        </tbody>
    </table>

    <div class="nao-acessaram">
    <br><BR>
        <center>
        <h3><?php echo get_string('no_access', 'block_analytics_graphs');?></h3>
        <p>
            <script type="text/javascript">
        var title = <?php echo json_encode(get_string('no_access', 'block_analytics_graphs'));?> + " - " + coursename;
        $.each(studentswithnoaccess, function(i, v) {
                                document.write("<span class='span-name' id='span-name-"+v.userid+"'>"+v.nome+"</span><br>");
        });
                var form ="<div class='div_nomes' id='studentswithnoaccess'>" +
                            createEmailForm(title , studentswithnoaccess, courseid, 'hits.php',
                                <?php echo json_encode(get_string('info_coursetype', 'block_analytics_graphs') . ': ' . block_analytics_graphs_get_course_name($course)); ?>)+
                            "</div>";
                document.write(form);
            </script>


            <input type="button" value="<?php echo get_string('send_email', 'block_analytics_graphs');?>" class="button-fancy" />
        </p>
        </center>
    </div>

    <div class="totalgraph">
        <div id="containerTotalWeekDaysAccess"></div>
        <div id="containerTotalGraphModules"></div>
    </div>

<script type="text/javascript">

    var studentwithaccess = [];
    $.each(students, function(ind, val) {
        var div = "";
        if (val !== undefined){   
            var title = coursename + 
                "</h3><p style='font-size:small'>" + 
                <?php  echo json_encode(get_string('hits', 'block_analytics_graphs'));?> + ": "+
                val.pageViews + 
                ", "+ <?php  echo json_encode(get_string('days_with_access', 'block_analytics_graphs'));?> + ": "+
                val.totalofaccesses + 
                ", "+ <?php  echo json_encode(get_string('resources_with_access', 'block_analytics_graphs'));?> + ": "+
                val.totalofresources ; 
            studentwithaccess[0] = val;             
            div = 
                "<div class='div_nomes' id='" + val.userid + "' title='" + val.nome + "'>" +
                    "<div class='student_tabs'> \
                        <ul> \
                            <li class='navi_tab'><a href='#email_panel-" + val.userid +
                                "' class='mail' id='tab_link-" + val.userid + "'>" +
                        <?php  echo json_encode(get_string('new_message', 'block_analytics_graphs'));?> +
                                "</a></li> \
                            <li class='navi_tab'><a href='#student_tab_panel-" + val.userid +
                                "' class='msgs' id='tab_link-" + val.userid + "-" + courseid + "'>" +
                        <?php  echo json_encode(get_string('old_messages', 'block_analytics_graphs'));?> +
                                "</a></li> \
                            <li class='navi_tab'><a href='#student_tab_panel-" + val.userid +
                             "' class='contentaccess' id='tab_link-" + val.userid + "-" + courseid + "'>" +
                        <?php  echo json_encode(get_string('access_to_contents', 'block_analytics_graphs'));?> +
                            "</a></li> \
                        <li class='navi_tab'><a href='#student_tab_panel-" + val.userid +
                "' class='submassign' id='tab_link-" + val.userid + "-" + courseid + "'>" +
                <?php  echo json_encode(get_string('submissions_assign', 'block_analytics_graphs'));?> +
                    "</a></li> \
                                                <li class='navi_tab'><a href='#student_tab_panel-" + val.userid +
                "' class='forumchart' id='tab_link-" + val.userid + "-" + courseid + "'>" +
                <?php  echo json_encode(get_string('submissions_forumchart', 'block_analytics_graphs'));?> +
                    "</a></li> \
                             <li class='navi_tab'><a href='#student_tab_panel-" + val.userid +
                "' class='quizchart' id='tab_link-" + val.userid + "-" + courseid + "'>" +
                <?php  echo json_encode(get_string('submissions_quizchart', 'block_analytics_graphs'));?> +
                    "</a></li> \
</ul>" +
                        "<div class='student_panel' id='email_panel-" + val.userid + "'>" +
                        createEmailForm(title,studentwithaccess, courseid, 'hits.php',
                        <?php echo json_encode(get_string('info_coursetype', 'block_analytics_graphs') . ': ' . block_analytics_graphs_get_course_name($course)); ?>) + "</div>" +
                        "<div class='student_panel' id='student_tab_panel-" + val.userid + "'></div>" + 
                    "</div>" + 
                "</div>";
            document.write(div);     
        }
    });
        

    $("li.navi_tab a").click(function(){
        if($(this).hasClass("msgs")){
            $("#student_tab_panel-" + this.id.split("-")[1]).empty();
            $(this).removeClass('current');
            $(this).addClass('current');

            var fill_panel = function(panel_id){
                return function fill_panel_callback(data){
                    if(jQuery.isEmptyObject(data)){
                        $("#student_tab_panel-" + panel_id).empty().append("<div>" + 
                        <?php echo json_encode(get_string('no_messages', 'block_analytics_graphs')); ?> + 
                        "</div>");
                    }
                    else{
                        var date_string = <?php echo json_encode(get_string('date_sent', 'block_analytics_graphs')); ?>;
                        var sender_string = <?php echo json_encode(get_string('sender', 'block_analytics_graphs')); ?>;
                        var subject_string = <?php echo json_encode(get_string('subject', 'block_analytics_graphs')); ?>;
                        var message_text_string = <?php echo json_encode(get_string('message_text', 'block_analytics_graphs')); ?>;
                        var table = "<table class='res_query'><tr><th>" + date_string + 
                                            "</th><th>" + sender_string + 
                                            "</th><th>" + subject_string + 
                                            "</th><th>" + message_text_string + 
                                            "</th></tr>";
                        for(elem in data){
                            table += "<tr>";
                            // table += "<td>" + new Date(data[elem]['timecreated'] *1000) +  "</td>";
                            table += "<td>" + data[elem]['timecreated'] +  "</td>";
                            table += "<td>" + data[elem]['fromid'] +  "</td>";
                            table += "<td>" + data[elem]['subject'] +  "</td>";
                            table += "<td>" + data[elem]["message"] +  "</td>";
                            table += "</tr>";
                        }
                        table += "</table>";
                        $("#student_tab_panel-" + panel_id).empty().append('<div class="res_query">' + table + '</div>');
                    }
                }
            }; 
     
            $.ajax({
                method: "POST",
                url: "query_messages.php",
                dataType: "JSON",
                data: {
                    student_ids: this.id.split("-")[1],
                    course_id: this.id.split("-")[2]
                },
                success: fill_panel(this.id.split("-")[1])
            });
        }
        else if($(this).hasClass("contentaccess")){
            // $("#student_tab_panel-" + this.id.split("-")[1]).empty().append("<div id='test-info'>" +
            //     <?php  echo json_encode(get_string('under_development', 'block_analytics_graphs'));?> +
            //     "</div>");
            $(this).removeClass('current');
            $(this).addClass('current');

            var fill_panel = function(panel_id){
                return function fill_panel_callback(data){
                    var material_names = {
                        "accessed" : [],
                        "not_accessed" : []
                    };

                    var material_data = [];

                    var name;
                    for(elem in data["resources"]){
                        if(data["resources"][elem]["tipo"] === "activequiz"){
                            name = data["resources"][elem]["activequiz"];
                        }
                        else if(data["resources"][elem]["tipo"] === "assign"){
                            name = data["resources"][elem]["assign"];
                        }
                        else if(data["resources"][elem]["tipo"] === "attendance"){
                            name = data["resources"][elem]["attendance"];
                        }
                        else if(data["resources"][elem]["tipo"] === "bigbluebuttonbn"){
                            name = data["resources"][elem]["bigbluebuttonbn"];
                        }
                        else if(data["resources"][elem]["tipo"] === "booking"){
                            name = data["resources"][elem]["booking"];
                        }
                        else if(data["resources"][elem]["tipo"] === "certificate"){
                            name = data["resources"][elem]["certificate"];
                        }
                        else if(data["resources"][elem]["tipo"] === "chat"){
                            name = data["resources"][elem]["chat"];
                        }
                        else if(data["resources"][elem]["tipo"] === "checklist"){
                            name = data["resources"][elem]["checklist"];
                        }
                        else if(data["resources"][elem]["tipo"] === "choice"){
                            name = data["resources"][elem]["choice"];
                        }
                        else if(data["resources"][elem]["tipo"] === "icontent"){
                            name = data["resources"][elem]["icontent"];
                        }
                        else if(data["resources"][elem]["tipo"] === "customcert"){
                            name = data["resources"][elem]["customcert"];
                        }
                        else if(data["resources"][elem]["tipo"] === "data"){
                            name = data["resources"][elem]["data"];
                        }
                        else if(data["resources"][elem]["tipo"] === "dataform"){
                            name = data["resources"][elem]["dataform"];
                        }
                        else if(data["resources"][elem]["tipo"] === "lti"){
                            name = data["resources"][elem]["lti"];
                        }
                        else if(data["resources"][elem]["tipo"] === "feedback"){
                            name = data["resources"][elem]["feedback"];
                        }
                        else if(data["resources"][elem]["tipo"] === "forum"){
                            name = data["resources"][elem]["forum"];
                        }
                        else if(data["resources"][elem]["tipo"] === "game"){
                            name = data["resources"][elem]["game"];
                        }
                        else if(data["resources"][elem]["tipo"] === "glossary"){
                            name = data["resources"][elem]["glossary"];
                        }
                        else if(data["resources"][elem]["tipo"] === "choicegroup"){
                            name = data["resources"][elem]["choicegroup"];
                        }
                        else if(data["resources"][elem]["tipo"] === "groupselect"){
                            name = data["resources"][elem]["groupselect"];
                        }
                        else if(data["resources"][elem]["tipo"] === "hotpot"){
                            name = data["resources"][elem]["hotpot"];
                        }
                        else if(data["resources"][elem]["tipo"] === "hvp"){
                            name = data["resources"][elem]["hvp"];
                        }
                        else if(data["resources"][elem]["tipo"] === "lesson"){
                            name = data["resources"][elem]["lesson"];
                        }
                        else if(data["resources"][elem]["tipo"] === "openmeetings"){
                            name = data["resources"][elem]["openmeetings"];
                        }
                        else if(data["resources"][elem]["tipo"] === "questionnaire"){
                            name = data["resources"][elem]["questionnaire"];
                        }
                        else if(data["resources"][elem]["tipo"] === "quiz"){
                            name = data["resources"][elem]["quiz"];
                        }
                        else if(data["resources"][elem]["tipo"] === "quizgame"){
                            name = data["resources"][elem]["quizgame"];
                        }
                        else if(data["resources"][elem]["tipo"] === "scheduler"){
                            name = data["resources"][elem]["scheduler"];
                        }
                        else if(data["resources"][elem]["tipo"] === "scorm"){
                            name = data["resources"][elem]["scorm"];
                        }
                        else if(data["resources"][elem]["tipo"] === "subcourse"){
                            name = data["resources"][elem]["subcourse"];
                        }
                        else if(data["resources"][elem]["tipo"] === "survey"){
                            name = data["resources"][elem]["survey"];
                        }
                        else if(data["resources"][elem]["tipo"] === "vpl"){
                            name = data["resources"][elem]["vpl"];
                        }
                        else if(data["resources"][elem]["tipo"] === "wiki"){
                            name = data["resources"][elem]["wiki"];
                        }
                        else if(data["resources"][elem]["tipo"] === "workshop"){
                            name = data["resources"][elem]["workshop"];
                        }
                        else if(data["resources"][elem]["tipo"] === "book"){
                            name = data["resources"][elem]["book"];
                        }
                        else if(data["resources"][elem]["tipo"] === "resource"){
                            name = data["resources"][elem]["resource"];
                        }
                        else if(data["resources"][elem]["tipo"] === "folder"){
                            name = data["resources"][elem]["folder"];
                        }
                        else if(data["resources"][elem]["tipo"] === "imscp"){
                            name = data["resources"][elem]["imscp"];
                        }
                        else if(data["resources"][elem]["tipo"] === "label"){
                            name = data["resources"][elem]["label"];
                        }
                        else if(data["resources"][elem]["tipo"] === "lightboxgallery"){
                            name = data["resources"][elem]["lightboxgallery"];
                        }
                        else if(data["resources"][elem]["tipo"] === "page"){
                            name = data["resources"][elem]["page"];
                        }
                        else if(data["resources"][elem]["tipo"] === "poster"){
                            name = data["resources"][elem]["poster"];
                        }
                        else if(data["resources"][elem]["tipo"] === "recordingsbn"){
                            name = data["resources"][elem]["recordingsbn"];
                        }
                        else if(data["resources"][elem]["tipo"] === "url"){
                            name = data["resources"][elem]["url"];
                        }


                        if(data["resources"][elem]["userid"] !== "0"){
                            material_names["accessed"].push(name);
                        }
                        else{
                            material_names["not_accessed"].push(name);
                        }
                    }

                    material_data = [[<?php echo json_encode(get_string('access',
                        'block_analytics_graphs'))?>,
                        material_names["accessed"].length],
                        [<?php echo json_encode(get_string('no_access',
                            'block_analytics_graphs'))?>,
                            material_names["not_accessed"].length]];

                    $("#student_tab_panel-" + panel_id).empty().append("\
                        <div class='res_query'>\
                        <div class='chart' id='" + panel_id + "-1'></div>\
                        </div>");

                    var materials_chart_options = {
                        chart: {
                            plotBackgroundColor: null,
                            plotBorderWidth: null,
                            plotShadow: false,
                            events: {
                                load: function(){
                                    this.mytooltip = new Highcharts.Tooltip(this, this.options.tooltip);
                                }
                            }
                        },
                        credits: {
                            enabled: false
                        },
                        title: {
                            text: <?php echo json_encode(get_string('access_to_contents', 'block_analytics_graphs'))?>,
                            style: {
                                fontSize: '13px',
                                fontWeight: 'bold'
                            }
                        },
                        tooltip: {
                            enabled: false,
                            useHTML: true,
                            backgroundColor: "rgba(255, 255, 255, 1.0)",
                            formatter: function(){
                                var tooltipStr = "<span style='font-size: 13px'><b>" +
                                    this.point.name +
                                    "</b></span>:<br>";
                                if(this.point.name == <?php echo json_encode(get_string('access',
                                    'block_analytics_graphs'))?>){
                                    for(var i = 0; i< material_names["accessed"].length; i++){
                                        tooltipStr += material_names["accessed"][i];
                                        if(i+1 < material_names["accessed"].length){
                                            tooltipStr += "<br>";
                                        }
                                    }
                                }
                                else{
                                    for(var i = 0; i< material_names["not_accessed"].length; i++){
                                        tooltipStr += material_names["not_accessed"][i];
                                        if(i+1 < material_names["not_accessed"].length){
                                            tooltipStr += "<br>";
                                        }
                                    }
                                }
                                return "<div class='scrollableHighchartsTooltipAddition'>" + tooltipStr + "</div>";
                            }
                        },
                        plotOptions: {
                            pie: {
                                allowPointSelect: true,
                                cursor: 'pointer',
                                dataLabels: {
                                    enabled: true,
                                    format: '<b>{point.name}</b>:<br/>{point.percentage:.1f} %',
                                    style: {
                                        color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black',
                                        width: 100
                                    }
                                },
                                colors: ['#7cb5ec', '#FF1111']
                            },
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
                            type: 'pie',
                            data: material_data
                        }]
                    };

                    $("#" + panel_id + "-1.chart").empty().highcharts(materials_chart_options);
                    $("#" + panel_id + "-1.chart").highcharts().setSize(400, 400, true);
                }
            };

            $.ajax({
                method: "POST",
                url: "query_resources_access.php",
                dataType : "JSON",
                data: {
                    student_id: this.id.split("-")[1],
                    course_id: this.id.split("-")[2],
                    legacy: legacy
                },
                success: fill_panel(this.id.split("-")[1])
            });
        }
        else if($(this).hasClass("submassign")){
            // $("#student_tab_panel-" + this.id.split("-")[1]).empty().append("<div id='test-info'>" +
            //     <?php  echo json_encode(get_string('under_development', 'block_analytics_graphs'));?> +
            //     "</div>");
            $(this).removeClass('current');
            $(this).addClass('current');

            var fill_panel = function(panel_id){
                return function fill_panel_callback(data){
                    var ONTIMESTR = <?php echo json_encode(get_string('in_time_submission', 'block_analytics_graphs'))?>;
                    var LATESTR = <?php echo json_encode(get_string('late_submission', 'block_analytics_graphs'))?>;
                    var NOSUBMISSIONSTR = <?php echo json_encode(get_string('no_submission', 'block_analytics_graphs'))?>;
                    var NOSUBMISSIONONTIMESTR = <?php echo json_encode(get_string('no_submission_on_time',
                        'block_analytics_graphs'))?>;
                    var assign_status = {
                        "on_time" : [],
                        "no_submission" : [],
                        "late" : [],
                        "no_submission_on_time" : []
                    }
                    var assign_data = [];

                    var student_time, assign_time;
                    var current_time = new Date().getTime();
                    for(elem in data["assign"]){
                        name = data["assign"][elem]["name"];
                        student_time = data["assign"][elem]["timecreated"];
                        assign_time = data["assign"][elem]["duedate"];
                        if(assign_time === "0"){
                            if(student_time === "0"){
                                assign_status["no_submission_on_time"].push(name);
                            }
                            else{
                                assign_status["on_time"].push(name);
                            }
                        }
                        else if (assign_time !== "0"){
                            if(current_time > parseInt(assign_time)){
                                if(parseInt(student_time) <= parseInt(assign_time)){
                                    if(student_time === "0"){
                                        assign_status["no_submission"].push(name);
                                    }
                                    else {
                                        assign_status["on_time"].push(name);
                                    }
                                }
                                else if(parseInt(student_time) > parseInt(assign_time)){
                                    assign_status["late"].push(name);
                                }
                            }
                            else{
                                if(student_time === "0"){
                                    assign_status["no_submission_on_time"].push(name);
                                }
                                else {
                                    assign_status["on_time"].push(name);
                                }
                            }
                        }
                    }

                    assign_data = [[ONTIMESTR, assign_status["on_time"].length],
                        [LATESTR, assign_status["late"].length],
                        [NOSUBMISSIONSTR, assign_status["no_submission"].length],
                        [NOSUBMISSIONONTIMESTR, assign_status["no_submission_on_time"].length]];

                    $("#student_tab_panel-" + panel_id).empty().append("\
                        <div class='res_query'>\
                        <div class='chart' id='" + panel_id + "-1'></div>\
                        </div>");

                    var assign_chart_options = {
                        chart: {
                            plotBackgroundColor: null,
                            plotBorderWidth: null,
                            plotShadow: false,
                            events: {
                                load: function(){
                                    this.mytooltip = new Highcharts.Tooltip(this, this.options.tooltip);
                                }
                            }
                        },
                        credits: {
                            enabled: false
                        },
                        title: {
                            text: <?php echo json_encode(get_string('submissions_assign', 'block_analytics_graphs'))?>,
                            style: {
                                fontSize: '13px',
                                fontWeight: 'bold'
                            }
                        },
                        tooltip: {
                            enabled: false,
                            useHTML: true,
                            backgroundColor: "rgba(255, 255, 255, 1.0)",
                            formatter: function(){
                                var tooltipStr = "<span style='font-size: 13px'><b>" +
                                    this.point.name +
                                    "</b></span>:<br>";
                                if(this.point.name == ONTIMESTR){
                                    for(var i = 0; i< assign_status["on_time"].length; i++){
                                        tooltipStr += assign_status["on_time"][i];
                                        if(i+1 < assign_status["on_time"].length){
                                            tooltipStr += "<br>";
                                        }
                                    }
                                }
                                else if(this.point.name == LATESTR){
                                    for(var i = 0; i< assign_status["late"].length; i++){
                                        tooltipStr += assign_status["late"][i];
                                        if(i+1 < assign_status["late"].length){
                                            tooltipStr += "<br>";
                                        }
                                    }
                                }
                                else if(this.point.name == NOSUBMISSIONSTR){
                                    for(var i = 0; i< assign_status["no_submission"].length; i++){
                                        tooltipStr += assign_status["no_submission"][i];
                                        if(i+1 < assign_status["no_submission"].length){
                                            tooltipStr += "<br>";
                                        }
                                    }
                                }
                                else{
                                    for(var i = 0; i< assign_status["no_submission_on_time"].length; i++){
                                        tooltipStr += assign_status["no_submission_on_time"][i];
                                        if(i+1 < assign_status["no_submission_on_time"].length){
                                            tooltipStr += "<br>";
                                        }
                                    }
                                }
                                return "<div class='scrollableHighchartsTooltipAddition'>" + tooltipStr + "</div>";
                            }
                        },
                        plotOptions: {
                            pie: {
                                allowPointSelect: true,
                                cursor: 'pointer',
                                dataLabels: {
                                    enabled: true,
                                    format: '<b>{point.name}</b>:<br/>{point.percentage:.1f} %',
                                    style: {
                                        color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black',
                                        width: 100
                                    }
                                },
                                colors: ['#7cb5ec', '#434348', '#FF1111', '#2b908f']
                            },
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
                            type: 'pie',
                            data: assign_data
                        }]
                    };

                    $("#" + panel_id + "-1.chart").empty().highcharts(assign_chart_options);
                    $("#" + panel_id + "-1.chart").highcharts().setSize(450, 450, true);
                }
            };

            $.ajax({
                method: "POST",
                url: "query_resources_access.php",
                dataType : "JSON",
                data: {
                    student_id: this.id.split("-")[1],
                    course_id: this.id.split("-")[2],
                    legacy: legacy
                },
                success: fill_panel(this.id.split("-")[1])
            });
        }         else if($(this).hasClass("quizchart")){
            // $("#student_tab_panel-" + this.id.split("-")[1]).empty().append("<div id='test-info'>" +
            //     <?php  echo json_encode(get_string('under_development', 'block_analytics_graphs'));?> +
            //     "</div>");
            $(this).removeClass('current');
            $(this).addClass('current');

            var fill_panel = function(panel_id){
                return function fill_panel_callback(data){
                    var FAILEDSTR = <?php echo json_encode(get_string('subfailed', 'block_analytics_graphs'))?>;
                    var PASSEDSTR = <?php echo json_encode(get_string('subpassed', 'block_analytics_graphs'))?>;
                    var NOSUBMISSIONSTR = <?php echo json_encode(get_string('no_submission', 'block_analytics_graphs'))?>;
                    var quiz_status = {
                        "passed" : [],
                        "failed" : [],
                        "no_submission" : []
                    };
                    var quiz_data = [];

                    for(elem in data["quiz"]){
                        var temp = data["quiz"][elem];
                        if (elem == "passed") {
                            for (var i = 0; i < temp.length; i++) {
                                quiz_status["passed"].push(temp[i]);
                            }
                        }
                        if (elem == "failed") {
                            for (var i = 0; i < temp.length; i++) {
                                quiz_status["failed"].push(temp[i]);;
                            }
                        }
                        if (elem == "noaccess") {
                            for (var i = 0; i < temp.length; i++) {
                                quiz_status["no_submission"].push(temp[i]);
                            }
                        }
                    }

                    quiz_data = [[PASSEDSTR, quiz_status["passed"].length],
                        [NOSUBMISSIONSTR, quiz_status["no_submission"].length],
                        [FAILEDSTR, quiz_status["failed"].length]
                        ];

                    $("#student_tab_panel-" + panel_id).empty().append("\
                        <div class='res_query'>\
                        <div class='chart' id='" + panel_id + "-1'></div>\
                        </div>");

                    var quiz_chart_options = {
                        chart: {
                            plotBackgroundColor: null,
                            plotBorderWidth: null,
                            plotShadow: false,
                            events: {
                              load: function(){
                                    this.mytooltip = new Highcharts.Tooltip(this, this.options.tooltip);
                                }
                            }
                        },
                        credits: {
                            enabled: false
                        },
                        title: {
                            text: <?php echo json_encode(get_string('submissions_quizchart', 'block_analytics_graphs'))?>,
                            style: {
                                fontSize: '13px',
                                fontWeight: 'bold'
                            }
                        },
                        tooltip: {
                            enabled: false,
                            useHTML: true,
                            backgroundColor: "rgba(255, 255, 255, 1.0)",
                            formatter: function(){
                                var tooltipStr = "<span style='font-size: 13px'><b>" +
                                    this.point.name +
                                    "</b></span>:<br>";
                                if(this.point.name == PASSEDSTR){
                                    for(var i = 0; i< quiz_status["passed"].length; i++){
                                        tooltipStr += quiz_status["passed"][i];
                                        if(i+1 < quiz_status["passed"].length){
                                            tooltipStr += "<br>";
                                        }
                                    }
                                }
                                else if(this.point.name == FAILEDSTR){
                                    for(var i = 0; i< quiz_status["failed"].length; i++){
                                        tooltipStr += quiz_status["failed"][i];
                                        if(i+1 < quiz_status["failed"].length){
                                            tooltipStr += "<br>";
                                        }
                                    }
                                }
                                else {
                                    for(var i = 0; i< quiz_status["no_submission"].length; i++){
                                        tooltipStr += quiz_status["no_submission"][i];
                                        if(i+1 < quiz_status["no_submission"].length){
                                            tooltipStr += "<br>";
                                        }
                                    }
                                }
                                return "<div class='scrollableHighchartsTooltipAddition'>" + tooltipStr + "</div>";
                            }
                        },
                        plotOptions: {
                            pie: {
                                allowPointSelect: true,
                                cursor: 'pointer',
                                dataLabels: {
                                    enabled: true,
                                    format: '<b>{point.name}</b>:<br/>{point.percentage:.1f} %',
                                    style: {
                                        color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black',
                                        width: 100
                                    }
                                },
                                colors: ['#7cb5ec', '#434348', '#FF1111']
                            },
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
                            type: 'pie',
                            data: quiz_data
                        }]
                    };

                    $("#" + panel_id + "-1.chart").empty().highcharts(quiz_chart_options);
                    $("#" + panel_id + "-1.chart").highcharts().setSize(450, 450, true);
                }
            };

            $.ajax({
                method: "POST",
                url: "query_resources_access.php",
                dataType : "JSON",
                data: {
                    student_id: this.id.split("-")[1],
                    course_id: this.id.split("-")[2],
                    legacy: legacy
                },
                success: fill_panel(this.id.split("-")[1])
            });
        } else
        if($(this).hasClass("forumchart")){
            // $("#student_tab_panel-" + this.id.split("-")[1]).empty().append("<div id='test-info'>" +
            //     <?php  echo json_encode(get_string('under_development', 'block_analytics_graphs'));?> +
            //     "</div>");
            $(this).removeClass('current');
            $(this).addClass('current');

            var fill_panel = function(panel_id){
                return function fill_panel_callback(data){
                    var READSTR = <?php echo json_encode(get_string('forumchart_read', 'block_analytics_graphs'))?>;
                    var NOTREADSTR = <?php echo json_encode(get_string('forumchart_notread', 'block_analytics_graphs'))?>;
                    var POSTEDSTR = <?php echo json_encode(get_string('forumchart_posted', 'block_analytics_graphs'))?>;
                    var NOTPOSTEDSTR = <?php echo json_encode(get_string('forumchart_notposted',
                        'block_analytics_graphs'))?>;
                    var forum_status_a = {
                        "read" : [],
                        "notread" : []
                    }

                    var forum_status_b = {
                        "posted" : [],
                        "notposted" : []
                    }

                    var forum_data_a = [];
                    var forum_data_b = [];

                    for(elem in data["forum"]){
                        var temp = data["forum"][elem];
                        if (elem == "read") {
                            for (var i = 0; i < temp.length; i++) {
                                forum_status_a["read"].push(temp[i]);
                            }
                        }
                        if (elem == "notread") {
                            for (var i = 0; i < temp.length; i++) {
                                forum_status_a["notread"].push(temp[i]);;
                            }
                        }
                        if (elem == "posted") {
                            for (var i = 0; i < temp.length; i++) {
                                forum_status_b["posted"].push(temp[i]);
                            }
                        }
                        if (elem == "notposted") {
                            for (var i = 0; i < temp.length; i++) {
                                forum_status_b["notposted"].push(temp[i]);
                            }
                        }
                    }

                    forum_data_a = [[READSTR, forum_status_a["read"].length],
                        [NOTREADSTR, forum_status_a["notread"].length]];

                    forum_data_b = [[POSTEDSTR, forum_status_b["posted"].length],
                        [NOTPOSTEDSTR, forum_status_b["notposted"].length]];

                    $("#student_tab_panel-" + panel_id).empty().append("\
                        <div class='res_query'>\
                        <div class='chart' id='" + panel_id + "-1'></div>\
                        <div class='chart' id='" + panel_id + "-2'></div>\
                        </div>");

                    var forum_chart_options_a = {
                        chart: {
                            plotBackgroundColor: null,
                            plotBorderWidth: null,
                            plotShadow: false,
                            events: {
                                load: function(){
                                    this.mytooltip = new Highcharts.Tooltip(this, this.options.tooltip);
                                }
                            }
                        },
                        credits: {
                            enabled: false
                        },
                        title: {
                            text: <?php echo json_encode(get_string('submissions_forumchart_reads', 'block_analytics_graphs'))?>,
                            style: {
                                fontSize: '13px',
                                fontWeight: 'bold'
                            }
                        },
                        tooltip: {
                            enabled: false,
                            useHTML: true,
                            backgroundColor: "rgba(255, 255, 255, 1.0)",
                            formatter: function(){
                                var tooltipStr = "<span style='font-size: 13px'><b>" +
                                    this.point.name +
                                    "</b></span>:<br>";
                                if(this.point.name == READSTR){
                                    for(var i = 0; i< forum_status_a["read"].length; i++){
                                        tooltipStr += forum_status_a["read"][i];
                                        if(i+1 < forum_status_a["read"].length){
                                            tooltipStr += "<br>";
                                        }
                                    }
                                }
                                else {
                                    for(var i = 0; i< forum_status_a["notread"].length; i++){
                                        tooltipStr += forum_status_a["notread"][i];
                                        if(i+1 < forum_status_a["notread"].length){
                                            tooltipStr += "<br>";
                                        }
                                    }
                                }
                                return "<div class='scrollableHighchartsTooltipAddition'>" + tooltipStr + "</div>";
                            }
                        },
                        plotOptions: {
                            pie: {
                                allowPointSelect: true,
                                cursor: 'pointer',
                                dataLabels: {
                                    enabled: true,
                                    format: '<b>{point.name}</b>:<br/>{point.percentage:.1f} %',
                                    style: {
                                        color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black',
                                        width: 100
                                    }
                                },
                                colors: ['#0096FA', '#00194B']
                            },
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
                            type: 'pie',
                            data: forum_data_a
                        }]
                    };

                    var forum_chart_options_b = {
                        chart: {
                            plotBackgroundColor: null,
                            plotBorderWidth: null,
                            plotShadow: false,
                            events: {
                                load: function(){
                                    this.mytooltip = new Highcharts.Tooltip(this, this.options.tooltip);
                                }
                            }
                        },
                        credits: {
                            enabled: false
                        },
                        title: {
                            text: <?php echo json_encode(get_string('submissions_forumchart_posts', 'block_analytics_graphs'))?>,
                            style: {
                                fontSize: '13px',
                                fontWeight: 'bold'
                            }
                        },
                        tooltip: {
                            enabled: false,
                            useHTML: true,
                            backgroundColor: "rgba(255, 255, 255, 1.0)",
                            formatter: function(){
                                var tooltipStr = "<span style='font-size: 13px'><b>" +
                                    this.point.name +
                                    "</b></span>:<br>";
                                if(this.point.name == POSTEDSTR){
                                    for(var i = 0; i< forum_status_b["posted"].length; i++){
                                        tooltipStr += forum_status_b["posted"][i];
                                        if(i+1 < forum_status_b["posted"].length){
                                            tooltipStr += "<br>";
                                        }
                                    }
                                }
                                else{
                                    for(var i = 0; i< forum_status_b["notposted"].length; i++){
                                        tooltipStr += forum_status_b["notposted"][i];
                                        if(i+1 < forum_status_b["notposted"].length){
                                            tooltipStr += "<br>";
                                        }
                                    }
                                }
                                return "<div class='scrollableHighchartsTooltipAddition'>" + tooltipStr + "</div>";
                            }
                        },
                        plotOptions: {
                            pie: {
                                allowPointSelect: true,
                                cursor: 'pointer',
                                dataLabels: {
                                    enabled: true,
                                    format: '<b>{point.name}</b>:<br/>{point.percentage:.1f} %',
                                    style: {
                                        color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black',
                                        width: 100
                                    }
                                },
                                colors: ['#00FF00', '#004B00']
                            },
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
                            type: 'pie',
                            data: forum_data_b
                        }]
                    };

                    $("#" + panel_id + "-1.chart").empty().highcharts(forum_chart_options_a);
                    $("#" + panel_id + "-1.chart").highcharts().setSize(400, 400, true);
                    $("#" + panel_id + "-2.chart").empty().highcharts(forum_chart_options_b);
                    $("#" + panel_id + "-2.chart").highcharts().setSize(400, 400, true);
                }
            };

            $.ajax({
                method: "POST",
                url: "query_resources_access.php",
                dataType : "JSON",
                data: {
                    student_id: this.id.split("-")[1],
                    course_id: this.id.split("-")[2],
                    legacy: legacy
                },
                success: fill_panel(this.id.split("-")[1])
            });
        }
        return false;
    });

    $(".button-fancy").bind("click", function(){                
        $(".div_nomes").dialog("close");
        $("#studentswithnoaccess").dialog("open");
        $("#studentswithnoaccess").dialog("option", "position", {
            my:"center top",
            at:"center top+" + 10,
            of:window
        });

    });

    $(".nome_student").bind("click", function(){                
        $(".div_nomes").dialog("close");
        var val = $(this).attr('id');                
        val = val.split("-");
        $("#" + val[1]).dialog("open");
        $("#" + val[1]).dialog("option", "width", 1000);
        $("#" + val[1]).dialog("option", "height", 600);
        var offsetTop = window.innerHeight/2 - $("#" + val[1]).dialog("option", "height")/2;
        $("#" + val[1]).dialog("option", "position", {
            my:"center top",
            at:"center top+" + offsetTop,
            of:window
        });
    });


    sendEmail();
    $(".student_tabs").tabs({
        active: 0,
        heightStyle: "auto"
    });
    $(".div_nomes").dialog("close");

    /*group selection*/
    $( "#group_select" ).change(function() {
        $(".button-fancy").removeAttr('disabled');
        var group = $(this).val();
        if(group == "-"){
            $("tr").show();
            $(".span-name").show();
            if(studentswithnoaccessgroup.length==0){
                $(".button-fancy").attr('disabled', 'disabled');
            }else{
                $("#studentswithnoaccess").children().remove();
                var title = <?php echo json_encode(get_string('no_access', 'block_analytics_graphs'));?> + " - " + coursename;
                $("#studentswithnoaccess").append(createEmailForm(title , studentswithnoaccessgroup, courseid, 'hits.php',
                    <?php echo json_encode(get_string('info_coursetype', 'block_analytics_graphs') . ': ' . block_analytics_graphs_get_course_name($course)); ?>));
            }
        }else{
            $.each(groups, function(index, value){
                if(index == group){
                    $("tbody>tr").hide();
                    $(".span-name").hide();
                    var studentswithnoaccessgroup = [];
                    $.each(value.members, function(ind, val){
                        $("#tr-student-"+val).show();
                        $("#span-name-"+val).show();

                        $.each(studentswithnoaccess, function(i, v) {
                            if(v.userid == val)
                                studentswithnoaccessgroup.push(v);
                        });
                    });
                    if(studentswithnoaccessgroup.length==0){
                        $(".button-fancy").attr('disabled', 'disabled');
                    }else{
                        $("#studentswithnoaccess").children().remove();
                        var title = <?php echo json_encode(get_string('no_access', 'block_analytics_graphs'));?> + 
                            " - " + coursename;
                        $("#studentswithnoaccess").append(createEmailForm(title , studentswithnoaccessgroup, courseid, 'hits.php',
                            <?php echo json_encode(get_string('info_coursetype', 'block_analytics_graphs') . ': ' . block_analytics_graphs_get_course_name($course)); ?>));
                    }
                }
            });
        }
    });

    Highcharts.chart('containerTotalGraphModules', {
        chart: {
            //borderWidth: 0,
            type: 'area',
            //margin: [0, 0, 0, 0],
            //spacingBottom: 0,
            //width: 250,
            height: 250,
            style: {
                overflow: 'visible'
            },
            skipClone: true,
        },

        title: {
            text: '<?php echo get_string('hitschart_totalresourcechart', 'block_analytics_graphs'); ?>'
        },

        xAxis: {
            labels: {
                enabled: false
            },
            title: {
                text: null
            },
            startOnTick: false,
            endOnTick: false,
            tickPositions: [],
            tickInterval: 1,
            minTickInterval: 24,
            min: (<?php echo $maxnumberofweeks; ?> + weekBeginningOffset) - 31,
            max: <?php echo $maxnumberofweeks; ?>  + weekBeginningOffset
        },

        navigator: {
            enabled: false,
            margin: 5
        },

        scrollbar: {
            enabled: true,
            height: 10
        },

        yAxis: {
            minorTickInterval: 1,
            endOnTick: false,
            startOnTick: false,
            labels: {
                enabled: false
            },
            title: {
                text: null
            },
            tickPositions: [0],
            tickInterval: 1
        },


        credits: {
            enabled: false
        },


        legend: {
            enabled: false
        },


        tooltip: {
            backgroundColor: null,
            borderWidth: 0,
            shadow: false,
            useHTML: true,
            hideDelay: 0,
            shared: true,
            padding: 0,
            headerFormat: '',
            pointFormat: <?php echo "'".get_string('week_number', 'block_analytics_graphs').": '"; ?> +
                '{point.x}<br>' +
            <?php echo "'".get_string('resources_with_access', 'block_analytics_graphs').": '"; ?> +
                '{point.y}',
            positioner: function (w, h, point) { return { x: point.plotX - w / 2, y: point.plotY - h}; }
        },


        plotOptions: {
            series: {
                animation: {
                    duration: 2000
                },
                lineWidth: 1,
                shadow: false,
                states: {
                    hover: {
                        lineWidth: 1
                    }
                },
                marker: {
                    radius: 2,
                    states: {
                        hover: {
                            radius: 4                                                        }
                    }
                },
                fillOpacity: 0.25
            },
        },


        series: [{
            pointStart: weekBeginningOffset,
            data: trata_array(totalResourceAccessData)
        }],


        exporting: {
            enabled: false
        },

    });

    Highcharts.chart('containerTotalWeekDaysAccess', {
        chart: {
            //borderWidth: 0,
            type: 'area',
            //margin: [0, 0, 0, 0],
            //spacingBottom: 0,
            //width: 250,
            height: 250,
            style: {
                overflow: 'visible'
            },
            skipClone: true,
        },

        title: {
            text: '<?php echo get_string('hitschart_totalweekaccesseschart', 'block_analytics_graphs'); ?>'
        },

        xAxis: {
            labels: {
                enabled: false
            },
            title: {
                text: null
            },
            startOnTick: false,
            endOnTick: false,
            tickPositions: [],
            tickInterval: 1,
            minTickInterval: 24,
            min: (<?php echo $maxnumberofweeks; ?> + weekBeginningOffset) - 31,
            max: <?php echo $maxnumberofweeks; ?>  + weekBeginningOffset
        },

        navigator: {
            enabled: false,
            margin: 5
        },

        scrollbar: {
            enabled: true,
            height: 10
        },

        yAxis: {
            minorTickInterval: 1,
            endOnTick: false,
            startOnTick: false,
            labels: {
                enabled: false
            },
            title: {
                text: null
            },
            tickPositions: [0],
            tickInterval: 1
        },


        credits: {
            enabled: false
        },


        legend: {
            enabled: false
        },


        tooltip: {
            backgroundColor: null,
            borderWidth: 0,
            shadow: false,
            useHTML: true,
            hideDelay: 0,
            shared: true,
            padding: 0,
            headerFormat: '',
            pointFormat: <?php echo "'".get_string('week_number', 'block_analytics_graphs').": '"; ?> +
                '{point.x}<br>' +
            <?php echo "'".get_string('days_with_access', 'block_analytics_graphs').": '"; ?> +
                '{point.y}',
            positioner: function (w, h, point) { return { x: point.plotX - w / 2, y: point.plotY - h}; }
        },


        plotOptions: {
            series: {
                animation: {
                    duration: 2000
                },
                lineWidth: 1,
                shadow: false,
                states: {
                    hover: {
                        lineWidth: 1
                    }
                },
                marker: {
                    radius: 2,
                    states: {
                        hover: {
                            radius: 4                                                        }
                    }
                },
                fillOpacity: 0.25
            },
        },


        series: [{
            pointStart: weekBeginningOffset,
            data: trata_array(totalWeekDaysAccessData)
        }],


        exporting: {
            enabled: false
        },

    });
    </script>
</body>
</html>