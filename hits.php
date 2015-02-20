<?php
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
require('javascript_functions.php');
$course = required_param('id', PARAM_INT);
$legacy = required_param('legacy', PARAM_INT);
global $DB;

/* Access control */
require_login($course);
$context = get_context_instance(CONTEXT_COURSE, $course);
require_capability('block/analytics_graphs:viewpages', $context);

$courseparams = get_course($course);
$startdate = $courseparams->startdate;
$coursename = get_string('course', 'block_analytics_graphs') . ": " . $courseparams->fullname;
$students = block_analytics_graphs_get_students($course);

$numberofstudents = count($students);
if ($numberofstudents == 0) {
    error(get_string('no_students', 'block_analytics_graphs'));
}
foreach ($students as $tuple) {
        $arrayofstudents[] = array('userid' => $tuple->id ,
                                'nome' => $tuple->firstname.' '.$tuple->lastname,
                                'email' => $tuple->email);
}

/* Get the number of days with access by week */
$resultado = block_analytics_graphs_get_number_of_days_access_by_week($course, $students, $startdate);

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
$studentswithnoaccess = block_analytics_graphs_subtract_student_arrays($arrayofstudents, $arrayofaccess);

/* Get the number of modules accessed by week */
$accessresults = block_analytics_graphs_get_number_of_modules_access_by_week($course, $students, $startdate);
$maxnumberofresources = 0;
foreach ($accessresults as $tuple) {
    if ( $tuple->number > $maxnumberofresources) {
        $maxnumberofresources = $tuple->number;
    }
}

/* Get the total number of modules accessed */
$numberofresourcesresult = block_analytics_graphs_get_number_of_modules_accessed($course, $students, $startdate);

/* Conver results to javascript */
$resultado = json_encode($resultado);
$studentswithnoaccess = json_encode($studentswithnoaccess);
$accessresults = json_encode($accessresults);
$numberofresourcesresult = json_encode($numberofresourcesresult);

/* Log */
$event = \block_analytics_graphs\event\block_analytics_graphs_event_view_graph::create(array(
    'objectid' => $course,
    'context' => $PAGE->context,
    'other'=> "hits.php",
));
$event->trigger();
?>




<html>
<head>

<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title><?php echo get_string('hits_distribution', 'block_analytics_graphs'); ?></title>
<link rel="stylesheet" href="http://code.jquery.com/ui/1.11.1/themes/smoothness/jquery-ui.css">

<!--<script src="http://code.jquery.com/jquery-1.10.2.js"></script>-->
<script src="http://ajax.aspnetcdn.com/ajax/jQuery/jquery-1.11.1.js"></script>

<script src="http://code.jquery.com/ui/1.11.1/jquery-ui.js"></script>
<script src="http://code.highcharts.com/highcharts.js"></script>
<script src="http://code.highcharts.com/modules/no-data-to-display.js"></script>
<!--<script src="http://code.highcharts.com/modules/exporting.js"></script> -->

<style>
#result {
        text-align: right;
        color: gray;
        min-height: 2em;
}
#table-sparkline {
        margin: 0 auto;
    	border-collapse: collapse;
}
th {
    font-weight: bold;
    text-align: left;
}
td, th {
    padding: 5px;
    border-bottom: 1px solid silver;
    border-right: 1px solid silver;
    height: 60px;
}
thead th {
    border-top: 2px solid gray;
    border-bottom: 2px solid gray;
}
.highcharts-tooltip>span {
        background: white;
        border: 1px solid silver;
        border-radius: 3px;
        box-shadow: 1px 1px 2px #888;
        padding: 8px;
}
</style>


<script type="text/javascript">
    var courseid = <?php echo json_encode($course); ?>;
    var coursename = <?php echo json_encode($coursename); ?>;
    var geral = <?php echo $resultado; ?>;
    var moduleaccess = <?php echo $accessresults; ?>;
    var numberofresources = <?php echo $numberofresourcesresult; ?>;
    var studentswithnoaccess = <?php echo $studentswithnoaccess; ?>;

    var nomes = [];
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

    function trata_array(array){
        var novo = [];
        $.each(array, function(ind, value){
                if (!value)
                        novo[ind] = 0;
                else
                        novo[ind] = value;
        });
        return novo;
    }

    function gerar_grafico_modulos(student){
        if (student.acessosModulos !== undefined){
                $("#modulos-"+student.userid).highcharts({

                chart: {                        borderWidth: 0,
                        type: 'area',
                        margin: [2, 0,2, 0],
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
			max: <?php echo $maxnumberofweeks; ?>
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
                    //data: trata_array(student.acessos)
                    data: trata_array(student.acessosModulos)
                    
                }]
                    });
                }else{
                        $("#modulos-"+student.userid).text(":(");
                }
        }

    function gerar_grafico(student){
        $("#acessos-"+student.userid).highcharts({

                chart: {
                        borderWidth: 0,
                        type: 'area',
                        margin: [2, 0,2, 0],
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
			max: <?php echo $maxnumberofweeks; ?>
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
                    data: trata_array(student.acessos)
                }]

            });
        }


    function createRow(array, nomes){
        $.each(nomes, function(ind,val){
            var nome = val;
            console.log(nome);
            $.each(array, function(index, value){
                        if (value){
                            if (nome === value.nome){
                                    var linha = "<tr>"+
                                            "<th><span class='nome_student' style='cursor:hand' id='linha-"+value.userid+"'>"+
                                                    value.nome+
                                            "</span></th>"+
                                            "<td>"+
                                                    value.pageViews+
                                            "</td>"+
                                            "<td>"+
                                                    value.totalofaccesses+
                                            "</td>"+
                                            "<td id='acessos-"+value.userid+"'>"+
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
                                document.write(v.nome+"<br>");
		});
                var form ="<div class='div_nomes' id='studentswithnoaccess'>" +
                            createEmailForm(title , studentswithnoaccess, courseid, 'hits.php')+
                            "</div>";
                document.write(form);
                </script>


            <input type="button" value="<?php echo get_string('send_email', 'block_analytics_graphs');?>" class="button-fancy" />
        </p>
        </center>
    </div>


<script type="text/javascript">
	var studentwithaccess = [];
    $.each(students, function(ind, val){
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
            div = "<div class='div_nomes' id='" + val.userid + "'>" +                        
				createEmailForm(title,studentwithaccess, courseid, 'hits.php') + "</div>";
            document.write(div);     
        }
    });
        

	$(".button-fancy").bind("click", function(){                
		$(".div_nomes").dialog("close");
                $("#studentswithnoaccess").dialog("open");
    });

    $(".nome_student").bind("click", function(){                
	    $(".div_nomes").dialog("close");
        var val = $(this).attr('id');                
		val = val.split("-");
        $("#" + val[1]).dialog("open");
    });

	sendEmail();

	$("div .highcharts-tooltip").css('top', '-9999990px');
    </script>
</body>
</html>
