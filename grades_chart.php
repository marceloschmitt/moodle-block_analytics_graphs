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
require("lib.php");
require('javascriptfunctions.php');
global $DB;
require_once($CFG->dirroot.'/lib/moodlelib.php');

$courseid = required_param('id', PARAM_INT);
require_login($courseid);
$context = context_course::instance($courseid);
require_capability('block/analytics_graphs:viewpages', $context);

/* Log */
$event = \block_analytics_graphs\event\block_analytics_graphs_event_view_graph::create(array(
    'objectid' => $courseid,
    'context' => $context,
    'other' => "grades_chart.php",
));
$event->trigger();


/*
$PAGE->set_url(new moodle_url('/blocks/analytics_graphs/grades_chart.php', array('id' => $courseid)));
$PAGE->set_context(context_course::instance($courseid));
$PAGE->set_pagelayout('print');
echo $OUTPUT->header();
*/

$sql = "SELECT gi.id, categoryid, fullname, itemname, gradetype, grademax, grademin
			FROM {grade_categories} gc
			LEFT JOIN {grade_items} gi ON gc.courseid = gi.courseid AND gc.id = gi.categoryid
			WHERE gc.courseid = ? AND categoryid IS NOT NULL AND EXISTS (
			    SELECT *
			        FROM mdl_grade_grades AS gg
			        WHERE gg.itemid = gi.id AND gg.rawgrade IS NOT NULL )
        ORDER BY fullname, itemname";

$result = $DB->get_records_sql($sql, array($courseid));

$groupmembers = block_analytics_graphs_get_course_group_members($courseid);
$groupmembersjson = json_encode($groupmembers);
?>

<html>
	<head>
		<meta charset=utf-8>
		<title><?php echo get_string('grades_chart', 'block_analytics_graphs'); ?></title>

<!--		
        <script src="http://code.jquery.com/jquery-1.11.3.min.js"></script>
		<link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
		<script src="http://code.jquery.com/ui/1.11.4/jquery-ui.min.js"></script>
		<script src="http://code.highcharts.com/highcharts.js"></script>
		<script src="http://code.highcharts.com/modules/exporting.js"></script>
		<script src="http://code.highcharts.com/highcharts-more.js"></script>
		<script src="http://code.highcharts.com/modules/no-data-to-display.js"></script>
-->
        <link rel="stylesheet" href="externalref/jquery-ui-1.11.4/jquery-ui.css">
        <script src="externalref/jquery-1.11.1.js"></script> 
        <script src="externalref/jquery-ui-1.11.4/jquery-ui.js"></script>
        <script src="externalref/highcharts.js"></script>
        <script src="externalref/highcharts-more.js"></script>
        <script src="externalref/no-data-to-display.js"></script>
        <script src="externalref/exporting.js"></script> 

		<style>
			body {
				height: 90%;
			}

			#chart_outerdiv {
			    width: 90%;
			    height: 50vh;
			    margin: 0px auto 0px auto;
			}

			#grades_chart_text, #tasklist_text {
				display: block;
			}

			#chart_div {
			    width: 100%;
			    height: 85%;
			    margin: 0px auto 0px auto;
			}

			#tasklist_outerdiv {
			    height: 40vh;
			    width: 90%;
			    margin: 0px auto 0px auto;
			}

			#tasklist_buttons {
			    display: -webkit-box;
			    display: -webkit-flex;
			    display: flex;
			    flex-direction: row;
			    -webkit-flex-direction: row;
			    -webkit-box-direction: row;
			    align-items: center;
			}

			#tasklist_text {
			    flex: 5;
			    -webkit-box-flex: 5;
			}

			#special_buttons {
			    flex: 1.5;
			    -webkit-box-flex: 1.5;
			}

			.sp_button {
			    width: 47%;
			}

			#tasklist_div {
			    height: 85%;
			    width: 100%;
			    overflow: auto;
			    margin: 0px auto 0px auto;
			}

			.individual_task_div {
			    margin: 10px 5px 10px 5px;
			    height: 40px;
			    background-color: #f3f3f3;
			    display: -webkit-box;
			    display: -webkit-flex;
			    display: flex;
			    flex-direction: row;
			    -webkit-flex-direction: row;
			    -webkit-box-direction: row;
			    align-items: center;
			}

			.task_button{
			    flex: 1.5;
			    -webkit-box-flex: 1.5;
			    height: 100%;
			    border: 0px;
			}

			.task_name {
			    margin: auto 0px auto 5px;
			    flex: 5;
			    -webkit-box-flex: 5;
			}

			.deactivated {
			    background-color: #9EFFAC;
			}

			.activated {
			    background-color: #FCD7D7;
			}

			.no_student_img {
			    width: 20px;
			    height: 18px;
			    vertical-align: middle;
			    margin: 0px 0px 0px 10px;
			}
		</style>
	</head>
	<body>
	    <?php if (count($groupmembers) > 0) : ?>
		    <div style="margin: 20px;">
		        <select id="group_select">
		            <option value="-">
		            	<?php echo json_encode(get_string('all_groups', 'block_analytics_graphs')); ?>
		            </option>
		        	<?php   foreach ($groupmembers as $key => $value) : ?>
		            	<option value="<?php echo $key; ?>">
		            		<?php echo $value["name"]; ?>
		            	</option>
					<?php endforeach;?>
		        </select>
		    </div>
		<?php endif;?>
		<div id='chart_outerdiv'>
			<div id='chart_div'></div>
		</div>
		<div id="tasklist_outerdiv">
			<div id='tasklist_buttons'>
				<span id='tasklist_text'><h2><?php echo get_string('task_list', 'block_analytics_graphs'); ?></h2></span>
				<div id='special_buttons'>
					<button type='button' class='sp_button' id='add_all'>
						<?php echo get_string('add_all', 'block_analytics_graphs'); ?>
					</button>
					<button type='button' class='sp_button' id='remove_all'>
						<?php echo get_string('remove_all', 'block_analytics_graphs'); ?>
					</button>
				</div>
			</div>
			<div id="tasklist_div"></div>
		</div>
		<script>
			function mail_dialog(task_name, quartile){
				var taskgrades = tasksinfo[tasknameid[task_name]];
		        var index;
		        var title = <?php echo json_encode(get_string('grades_mail_dialog_title', 'block_analytics_graphs')); ?> + " ";
		        var students;
				
				quartile = parseInt(quartile);
				$("#" + tasknameid[task_name] + ".mail_dialog").dialog("open");
				$("#" + tasknameid[task_name] + ".mail_dialog").dialog("option", "position", {
		            my:"center top",
		            at:"center top+" + 10,
		            of:window
		        });
		        $("#" + tasknameid[task_name] + ".mail_dialog").dialog("option", "width", 900);
		        $("#" + tasknameid[task_name] + ".mail_dialog").dialog("option", "height", 600);
		        
		        if(quartile == 25){
		        	index = taskgrades.q1_index;
		        	title += taskgrades.q1_grade.toFixed(2);
		        }
		        else if(quartile == 50){
		        	index = taskgrades.median_index;
		        	title += taskgrades.median_grade.toFixed(2);
		        }
		        else{
		        	index = taskgrades.q3_index;
		        	title += taskgrades.q3_grade.toFixed(2);
		        }

		        students = taskgrades.grades.slice(0, parseInt(index)+1);		        

		        for(var s=0; s<students.length; s++){
		        	students[s]['nome'] = students[s].name;
		        }

		        $("#" + tasknameid[task_name] + ".mail_dialog").empty().append(
		        	createEmailForm(title, students, <?php echo json_encode($courseid); ?>, 'grades_chart.php'));
		        $("#" + tasknameid[task_name] + ".mail_dialog form").submit(function(event){
                    event.preventDefault();
                    var $form = $(this);
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

			function update_chart(grades_info){
				var grades_stats = [];
				function get_group_grades(groups, students){
					var grades = [];
					for(var student_i=0, students_len = students.length; student_i<students_len; student_i++){
						for(var member_i=0, members_len = groups[current_group].members.length; member_i < members_len; member_i++){
							if(students[student_i].userid === groups[current_group].members[member_i]){
								grades.push(students[student_i]);
								break;
							}
						}
					}
					return grades;
				};
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
							idx: (data_size-1)/2,
							val: 0.5 * (data[data_size/2]['grade'] + data[data_size/2 - 1]['grade'])
						};
					}
				};
				for(var task_i in grades_info){
					grades_info[task_i].sort(sort_func);
					var group_grades = current_group === "-"? grades_info[task_i] : get_group_grades(groups, grades_info[task_i]);
					if(group_grades.length > 0){
						$('#chart_div').highcharts().xAxis[0].categories.push(taskidname[task_i]);
						var num_grades = group_grades.length;
						var min_grade = group_grades[0]['grade'];
						var max_grade = group_grades[num_grades-1]['grade'];
						var stats = median_func(group_grades);
						var median_grade = stats.val;
						var median_idx = stats.idx;
						var q1_grade = null, q3_grade = null;
						var q1_index, q3_index;
						if(num_grades%2){
							stats = median_func(group_grades.slice(0,Math.max(Math.floor(num_grades/2), 1)));
							q1_grade = stats.val;
							q1_index = stats.idx;
							stats = median_func(group_grades.slice(Math.min(Math.floor(num_grades/2) + 1, num_grades-1),
													Math.max(num_grades, Math.floor(num_grades/2) + 1)));
							q3_grade = stats.val;
							q3_index = stats.idx + Math.min(Math.floor(num_grades/2) + 1, num_grades-1);
						}
						else{
							stats = median_func(group_grades.slice(0,num_grades/2));
							q1_grade = stats.val;
							q1_index = stats.idx;
							stats = median_func(group_grades.slice(num_grades/2, num_grades));
							q3_grade = stats.val;
							q3_index = stats.idx + num_grades/2;
						}
						tasksinfo[task_i] = {
							median_index : median_idx,
							median_grade : median_grade,
					    	q1_index : q1_index,
					    	q1_grade : q1_grade,
					    	q3_index : q3_index,
					    	q3_grade : q3_grade,
					    	grades: group_grades
						};
						grades_stats.push({
						    low: min_grade,
						    q1: q1_grade,
						    median: median_grade,
						    q3: q3_grade,
						    high: max_grade,
						    name: taskidname[task_i],
						    num_grades: num_grades
						});
						$("#img-" + task_i).hide();
					}
					else{
						$("#" + task_i + ".task_button")
							.removeClass("activated")
							.addClass("deactivated")
							.empty()
							.append(<?php echo json_encode(get_string('add_task', 'block_analytics_graphs')); ?>);
						$("#img-" + task_i).show();
						active_tasks--;
						tasks_toggle[task_i] = false;
					}
				}
				$('#chart_div').highcharts().series[0].setData(grades_stats);
			};

			function make_grades_query(){
				var send_data = [];
				$('#chart_div').highcharts().xAxis[0].categories = [];
				if(active_tasks > 0){
					for(var field in tasks_toggle){
						if(tasks_toggle[field] === true){
							send_data.push(field.toString());
						}
					}
					$.ajax({
						type: "POST",
						dataType: "JSON",
						url: "query_grades.php",
						data: {
							"form_data": send_data,
							"course_id": <?php echo json_encode($courseid); ?>
						},
						success: update_chart
					});
				}
				else{
					$('#chart_div').highcharts().series[0].setData([]);
				}
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
		            text: <?php echo json_encode(get_string('grades_distribution', 'block_analytics_graphs')); ?>
		        },

		        legend: {
		            enabled: false
		        },

		        credits: {
		        	enabled: false
		        },

		        lang: {
		        	noData: <?php echo json_encode(get_string('grades_chart_no_data', 'block_analytics_graphs')); ?>
		        },

		        xAxis: {
		        	categories: [],
		            title: {
		                text: <?php echo json_encode(get_string('task_name', 'block_analytics_graphs')); ?>,
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
		                text: <?php echo json_encode(get_string('grades', 'block_analytics_graphs')); ?>,
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
		        		str += "<b>" + this.point.category + "</b><br/>";
		        		str += <?php echo json_encode(get_string('total_grades', 'block_analytics_graphs')); ?> +
		        				": " + this.point.num_grades + "<br/>";
		        		str += <?php echo json_encode(get_string('lowest_grade', 'block_analytics_graphs')); ?> +
		        				": " + this.point.low.toFixed(2) + "<br/>";
		        		str += <?php echo json_encode(get_string('largest_grade', 'block_analytics_graphs')); ?> +
		        				": " + this.point.high.toFixed(2) + "<br/>";
		        		if(this.point.num_grades >= 5){
		        			str += "<a class='mail_link' id='" + this.point.category + "-25' \
			        				href='#' onclick='mail_dialog(\"" + this.point.category + "\", 25); return false;'>" +
			        				parseInt(tasksinfo[tasknameid[this.point.category]].q1_index + 1) + " " +
			        				<?php echo json_encode(get_string('students', 'block_analytics_graphs')); ?> + "</a> " +
			        				<?php echo json_encode(get_string('tooltip_grade_achievement', 'block_analytics_graphs')); ?> +
			        				" " + this.point.q1.toFixed(2) + " (25%)<br/>";

			        		str += "<a class='mail_link' id='" + this.point.category + "-50' \
			        				href='#' onclick='mail_dialog(\"" + this.point.category + "\", 50); return false;'>" +
			        				parseInt(tasksinfo[tasknameid[this.point.category]].median_index + 1) + " " +
			        				<?php echo json_encode(get_string('students', 'block_analytics_graphs')); ?> + "</a> " +
			        				<?php echo json_encode(get_string('tooltip_grade_achievement', 'block_analytics_graphs')); ?> +
			        				" " + this.point.median.toFixed(2) + " (50%)<br/>";

							str += "<a class='mail_link' id='" + this.point.category + "-75' \
			        				href='#' onclick='mail_dialog(\"" + this.point.category + "\", 75); return false;'>" +
			        				parseInt(tasksinfo[tasknameid[this.point.category]].q3_index + 1) + " " +
			        				<?php echo json_encode(get_string('students', 'block_analytics_graphs')); ?> + "</a> " +
			        				<?php echo json_encode(get_string('tooltip_grade_achievement', 'block_analytics_graphs')); ?> +
			        				" " + this.point.q3.toFixed(2) + " (75%)<br/>";
	        			}
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
	                    stickyTracking: true,
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
			var groups = <?php echo $groupmembersjson; ?>;
			var tasksinfo = {};
			var totaltasks = tasks.length;
			var tasks_toggle = {};
			var taskidname = {};
			var tasknameid = {};
			var active_tasks = 0;
			var cont = 1;
			var current_group = "-";
			for(var elem in tasks){
				$("#tasklist_div").append("<div class='individual_task_div' id='div_task_" + tasks[elem]['id'] + "'>" + 
										"<span class='task_name'>" + cont + " - " + tasks[elem]['itemname'] +
										"<img src='images/exclamation_sign.png' title='" + 
										<?php echo json_encode(get_string('no_student_task', 'block_analytics_graphs')); ?> +
										"' class='no_student_img' id='img-" + tasks[elem]['id'] + "'></span>" +
										"<button type='button' class='task_button deactivated' id='" +  tasks[elem]['id'] + "'>" + 
										cont + "</button></div>");
				document.write("<div id='" + tasks[elem]['id'] + "' class='mail_dialog' title='" + tasks[elem]['itemname'] + "'></div>");
				$("#" + tasks[elem]['id'] + ".mail_dialog").dialog({
	                modal: true,
	                autoOpen: false
            	});            	
				tasks_toggle[tasks[elem]['id']] = false;
				taskidname[tasks[elem]['id']] = tasks[elem]['itemname'];
				tasknameid[tasks[elem]['itemname']] = tasks[elem]['id'];
				cont++;
			}
			$(".deactivated").empty().append(<?php echo json_encode(get_string('add_task', 'block_analytics_graphs')); ?>);
			$(".no_student_img").hide();
			$("#chart_div").highcharts(base_chart_options);
			$("#group_select").change(function(){
				var group = $(this).val();
				if(group != current_group){
					current_group = group;
					for(var tid in tasks_toggle){
						if(tasks_toggle[tid] === false){
							$("#img-" + tid).hide();
						}
					}
					make_grades_query();
				}
			});
			$("#add_all.sp_button").click(function(){
				var task_added = false;
				for(var task_id in tasks_toggle){
					if(tasks_toggle[task_id] === false){
						tasks_toggle[task_id] = true;
						active_tasks++;
						$("#" + task_id + ".task_button").removeClass("deactivated");
						$("#" + task_id + ".task_button").addClass("activated");
						$("#" + task_id + ".task_button").empty()
							.append(<?php echo json_encode(get_string('remove_task', 'block_analytics_graphs')); ?>);
						task_added = true;
					}
				}
				if(task_added){
					make_grades_query();
				}
			});
			$("#remove_all.sp_button").click(function(){
				var task_removed = false;
				for(var task_id in tasks_toggle){
					if(tasks_toggle[task_id] === true){
						tasks_toggle[task_id] = false;
						active_tasks--;
						$("#" + task_id + ".task_button").removeClass("activated");
						$("#" + task_id + ".task_button").addClass("deactivated");
						$("#" + task_id + ".task_button").empty()
							.append(<?php echo json_encode(get_string('add_task', 'block_analytics_graphs')); ?>);
						task_removed = true;
					}
				}
				if(task_removed){
					make_grades_query();
				}
				$(".no_student_img").hide();
			});
			$('.task_button').click(function(){
				var task_name = this.id;
				if(tasks_toggle[task_name] === true){
					tasks_toggle[task_name] = false;
					$(this).removeClass("activated");
					$(this).addClass("deactivated");
					$(this).empty().append(<?php echo json_encode(get_string('add_task', 'block_analytics_graphs')); ?>);
					active_tasks--;
				}
				else{
					tasks_toggle[task_name] = true;
					$(this).removeClass("deactivated");
					$(this).addClass("activated");
					$(this).empty().append(<?php echo json_encode(get_string('remove_task', 'block_analytics_graphs')); ?>);
					active_tasks ++;
				}
				make_grades_query();
				return false;
			});			
		</script>
	</body>
</html>