<?php
require_once("../../config.php");
require('javascriptfunctions.php');
global $DB;
require_once($CFG->dirroot.'/lib/moodlelib.php');

$course_id = required_param('id', PARAM_INT);
require_login($course_id);
$context = context_course::instance($course_id);
require_capability('block/analytics_graphs:viewpages', $context);

$sql = "SELECT gi.id, categoryid, fullname, itemname, gradetype, grademax, grademin
	        FROM {grade_categories} AS gc
	        LEFT JOIN {grade_items} AS gi ON gc.courseid = gi.courseid AND gc.id = gi.categoryid
	        WHERE gc.courseid = ? AND categoryid IS NOT NULL AND EXISTS (
                SELECT * 
	                FROM mdl_grade_grades AS gg
	                WHERE gg.itemid = gi.id AND gg.rawgrade IS NOT NULL )
        ORDER BY fullname, itemname";

$result = $DB->get_records_sql($sql, array($course_id));
?>

<html>
	<head>
		<meta charset=utf-8>
		<title><?php echo get_string('grades_chart', 'block_analytics_graphs'); ?></title>
		<script src="http://code.jquery.com/jquery-1.11.3.min.js"></script>
		<script src="http://code.jquery.com/ui/1.11.4/jquery-ui.min.js"></script>
		<script src="http://code.highcharts.com/highcharts.js"></script>
		<script src="http://code.highcharts.com/modules/exporting.js"></script>
		<script src="http://code.highcharts.com/highcharts-more.js"></script>
		<script src="http://code.highcharts.com/modules/no-data-to-display.js"></script>

		<style>
			#chart_div {
			    width: 100%;
			    margin-left: auto;
			    margin-right: auto;
			}

			#chart_outerdiv {
			    width: 95%;
			    margin-left: auto;
			    margin-right: auto;
			    margin-bottom: 5px;
			}

			#tasklist_div {
			    width: 95%;
			    margin: 0px auto 0px auto;
			}

			.individual_task_div {
			    flex-grow: 1;
			    margin: 5px;
			}

			.task_button{
			    width: 100%;
			}

			#taskbuttons_outerdiv{
			    margin-left: auto;
			    margin-right: auto;
			    margin-top: 5px;
			    margin-bottom: 5px;
			    width: 95%;
			}

			#taskbuttons_div {
			    width: 100%;
			    margin-left: auto;
			    margin-right: auto;
			    display: inline-flex;
			    flex-direction: row;
			    flex-wrap: wrap;
			    justify-content: flex-start;
			    align-content: center;
			}
		</style>
	</head>
	<body>
		<div id='chart_outerdiv'>
			<h1>Grades chart</h1>
			<div id='chart_div'></div>
		</div>
		<div id="tasklist_div"></div>
		<div id='taskbuttons_outerdiv'>
			<div id="taskbuttons_div"></div>
		</div>
		<script>			
			function mail_dialog(task_name, quartile){
				var taskgrades = tasksinfo[tasknameid[task_name]];
				quartile = parseInt(quartile);
				$("#" + tasknameid[task_name] + ".mail_dialog").dialog("open");
				$("#" + tasknameid[task_name] + ".mail_dialog").dialog("option", "position", {
		            my:"center top",
		            at:"center top+" + 10,
		            of:window
		        });
		        $("#" + tasknameid[task_name] + ".mail_dialog").dialog("option", "width", 1000);
		        $("#" + tasknameid[task_name] + ".mail_dialog").dialog("option", "height", 600);
		        var index;
		        if(quartile == 25){
		        	index = "q1_index";
		        }
		        else if(quartile == 50){
		        	index = "median";
		        }
		        else{
		        	index = "q3_index";
		        }
		        var title = "Students with grades smaller than " + taskgrades.grades[index];
		        var students;
		        if(quartile == 25){
		        	students = taskgrades.grades.slice(0, taskgrades.q1_index+1);
		        }
		        else if(quartile == 50){
		        	students = taskgrades.grades.slice(0, taskgrades.median_index+1);
		        }
		        else{
		        	students = taskgrades.grades.slice(0, taskgrades.q3_index+1);
		        }
		        for(var s=0; s<students.length; s++){
		        	students[s]['nome'] = students[s].name;
		        }
		        $("#" + tasknameid[task_name] + ".mail_dialog").append(createEmailForm(title, students, <?php echo json_encode($course_id); ?>, 'grades_chart.php'));
		        $("#" + tasknameid[task_name] + ".mail_dialog form").submit(function(event){
                    event.preventDefault();
                    var $form = this;
                    var otherval = $form.find( "input[name='other']" ).val();
                    var idsval = $form.find( "input[name='ids[]']" ).val();
                    var subjectval = $form.find( "input[name='subject']" ).val();
                    var textoval = $form.find( "textarea[name='texto']" ).val();
                    var url = $form.attr( "action" );

                    var posting = $.post( url, {
                    						other: otherval,
                    						ids: idsval,
                    						subject: subjectval,
                    						texto: textoval});

                    posting.done(function( data ){
	                    if(data){
	                        $("#" + tasknameid[task_name] + ".mail_dialog").dialog("close");
	                        alert("<?php echo get_string('sent_message', 'block_analytics_graphs');?>");
	                    }
	                    else {
	                        alert("<?php echo get_string('not_sent_message', 'block_analytics_graphs');?>");
	                    }
                	});
            	});
			};

			var base_chart_options = {
		        chart: {
		            type: 'boxplot',
		            borderWidth: 1,
		            events: {
                        load: function(){
                            this.mytooltip = new Highcharts.Tooltip(this, this.options.tooltip);
                        }
                    }
		        },

		        title: {
		            text: 'Grades distribution'
		        },

		        legend: {
		            enabled: false
		        },

		        credits: {
		        	enabled: false
		        },

		        lang: {
		        	noData: "Use the buttons below to toggle the tasks displayed on the chart"
		        },

		        xAxis: {
		        	categories: [],
		            title: {
		                text: 'Task name',
		                style: {
		                	fontWeight: 'bold',
		                	fontSize: 12
		                }
		            },
		            labels: {
		            	style:{
		            		fontSize: 12,
		            	}
		            }
		        },

		        yAxis: {
		        	min: 0,
		        	max: 1,
		            title: {
		                text: 'Grades',
		                style: {
		                	fontWeight: 'bold',
		                	fontSize: 12
		                }
		            }		        
		        },

		        tooltip:{
		        	enabled: false,
		        	useHTML: true,
		        	backgroundColor: "rgba(255,255,255,1.0)",
		        	formatter: function(){
		        		var str = "";
		        		str += "<b>Task " + this.point.category + "</b><br/>";
		        		str += "Total grades: " + this.point.num_grades + "<br/>";
		        		str += "Lowest grade: " + this.point.low.toFixed(2) + "<br/>";
		        		str += "Largest grade: " + this.point.high.toFixed(2) + "<br/>";
		        		str += "75% of all \
		        			<a class='mail_link' \
		        				id='" + this.point.category + "-75' \
		        				href='#' onclick='mail_dialog('" + this.point.category + "', 25); return false;'>students</a> \
		        				achieved grades larger than " + this.point.q1.toFixed(2) + "<br/>";
		        		
		        		str += "50% of all \
		        			<a class='mail_link' \
		        				id='" + this.point.category + "-50' \
		        				href='#' onclick='mail_dialog('" + this.point.category + "', 50); return false;'>students</a> \
		        				achieved grades larger than " + this.point.median.toFixed(2) + "<br/>";
		        		
		        		str += "25% of all \
		        			<a class='mail_link' \
		        				id='" + this.point.category + "-25' \
		        				href='#' onclick='mail_dialog('" + this.point.category + "', 75); return false;'>students</a> \
		        				achieved grades larger than " + this.point.q3.toFixed(2) + "<br/>";
		        		return str;
		        	}
		        },

		        plotOptions: {
		            boxplot: {
		            	pointWidth: 50,
		                fillColor: '#F0F0E0',
		                lineWidth: 2,
		                medianColor: '#3333FF',
		                medianWidth: 4,
		                stemColor: '#434348',
		                stemDashStyle: 'dot',
		                stemWidth: 1.5,
		                whiskerColor: '#669999',
		                whiskerLength: '20%',
		                whiskerWidth: 3
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
		        }]
		    };
		    
			var tasks = <?php echo json_encode($result); ?>;
			var tasksinfo = {};
			var totaltasks = tasks.length;
			var tasks_toggle = {};
			var taskidname = {};
			var tasknameid = {};
			var active_tasks = 0;
			var cont = 1;			
			$("#tasklist_div").empty().append("<h1>Task list:<h1>");
			for(elem in tasks){
				$("#tasklist_div").append(cont + " - " + tasks[elem]['itemname'] + "<br/>");
				$("#taskbuttons_div").append("<div class='individual_task_div' id='div_task_" + tasks[elem]['id'] + "'>" + 
										"<button type='button' class=task_button id='" +  tasks[elem]['id'] + "'>" + 
										cont + "</button></div>");
				document.write("<div id='" + tasks[elem]['id'] + "' class='mail_dialog' title='" + tasks[elem]['itemname'] + "'></div>");
				tasks_toggle[tasks[elem]['id']] = false;
				taskidname[tasks[elem]['id']] = tasks[elem]['itemname'];
				tasknameid[tasks[elem]['itemname']] = tasks[elem]['id'];
				cont++;
			}
			$("#chart_div").highcharts(base_chart_options);
			$('.task_button').click(function(){
				var task_name = this.id;
				var send_data = [];
				if(tasks_toggle[task_name] === true){
					tasks_toggle[task_name] = false;
					active_tasks--;
				}
				else{
					tasks_toggle[task_name] = true;
					active_tasks ++;
				}
				$('#chart_div').highcharts().xAxis[0].categories = [];
				if(active_tasks > 0){
					for(var field in tasks_toggle){
						if(tasks_toggle[field] === true){
							send_data.push(field.toString());
							$('#chart_div').highcharts().xAxis[0].categories.push(taskidname[field.toString()]);
						}
					}
					$.ajax({
						type: "POST",
						dataType: "JSON",
						url: "query_grades.php",
						data: {
							"form_data": send_data,
							"course_id": <?php echo json_encode($course_id); ?>
						},
						success: function(grades_info){
							var grades_stats = [];
							function sort_func(a, b){
								return a['grade'] - b['grade'];
							};
							function median_func(data){
								var data_size = data.length;
								if(data_size % 2){
									return {
										idx: Math.floor(data_size/2),
										val: data[Math.floor(data_size/2)]['grade']
									};
								}
								else{
									return {
										idx: data_size/2,
										val: 0.5 * (data[data_size/2]['grade'] + data[data_size/2 - 1]['grade'])
									};
								}
							};
							for(var task_i in grades_info){
								grades_info[task_i].sort(sort_func);
								var num_grades = grades_info[task_i].length;
								var task_data = null;
								var min_grade = grades_info[task_i][0]['grade'];
								var max_grade = grades_info[task_i][num_grades-1]['grade'];
								var stats = median_func(grades_info[task_i]);
								var median_grade = stats.val;
								var median_idx = stats.idx;
								var q1_grade = null, q3_grade = null;
								var q1_index, q3_index;
								if(num_grades%2){
									stats = median_func(grades_info[task_i].slice(0,Math.max(Math.floor(num_grades/2), 1)));
									q1_grade = stats.val;
									q1_index = stats.idx;
									stats = median_func(grades_info[task_i].slice(Math.min(Math.floor(num_grades/2) + 1, num_grades-1), Math.max(num_grades, Math.floor(num_grades/2) + 1)));
									q3_grade = stats.val;
									q3_index = stats.idx + Math.min(Math.floor(num_grades/2) + 1, num_grades-1);
								}
								else{
									stats = median_func(grades_info[task_i].slice(0,num_grades/2));
									q1_grade = stats.val;
									q1_index = stats.idx;
									stats = median_func(grades_info[task_i].slice(num_grades/2, num_grades));
									q3_grade = stats.val;
									q3_index = stats.idx + num_grades/2;
								}
								task_data = {
								    low: min_grade,
								    q1: q1_grade,
								    median: median_grade,
								    q3: q3_grade,
								    high: max_grade,
								    name: task_i,
								    num_grades: num_grades,
								    grades_stats : {
								    	median_index : median_idx,
								    	q1_index : q1_index,
								    	q3_index : q3_index,
								    	grades: grades_info[task_i]
								    }
								};
								tasksinfo[task_i] = {
									median_index : median_idx,
							    	q1_index : q1_index,
							    	q3_index : q3_index,
							    	grades: grades_info[task_i]
								};
								grades_stats.push(task_data);
							}
							$('#chart_div').highcharts().series[0].setData(grades_stats);
						}
					});
				}
				else{
					$('#chart_div').highcharts().series[0].setData([]);
				}
				return false;
			});			
		</script>
	</body>
</html>