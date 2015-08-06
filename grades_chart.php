<?php
require_once("../../config.php");
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
		<script src="http://code.highcharts.com/highcharts-more.js"></script>
		<script src="http://code.highcharts.com/modules/no-data-to-display.js"></script>
	</head>
	<body>
		<form id="tasks_form"></form>
		<div id='chart_div'></div>
		<script>
			var base_chart_options = {
		        chart: {
		            type: 'boxplot'
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
		        	noData: "Select at least one task and click on 'Submit' to see its grade distribution"
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
		        	max: 100,
		            title: {
		                text: 'Grades',
		                style: {
		                	fontWeight: 'bold',
		                	fontSize: 12
		                }
		            }		        
		        },

		        tooltip:{
		        	backgroundColor: "rgba(255,255,255,1.0)",
		        	formatter: function(){
		        		var str = "";
		        		str += "<b>Task " + this.point.category + "</b><br/>";
		        		str += "Total grades: " + this.point.num_grades + "<br/>";
		        		str += "Lowest grade: " + this.point.low.toFixed(2) + "<br/>";
		        		str += "Largest grade: " + this.point.high.toFixed(2) + "<br/>";
		        		str += "75% of all grades are greater than " + this.point.q1.toFixed(2) + "<br/>";
		        		str += "50% of all grades are greater than " + this.point.median.toFixed(2) + "<br/>";
		        		str += "25% of all grades are greater than " + this.point.q3.toFixed(2) + "<br/>";
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
		            }
		        },

		        series: [{
		        }]
		    };
			var tasks = <?php echo json_encode($result); ?>;
			for(elem in tasks){
				$("#tasks_form").append("<input type='checkbox' name='" + tasks[elem]['id'] + "'>" +
										tasks[elem]['itemname'] + "<br>");
			}
			$("#tasks_form").append("<input type='submit' value='Submit'>");
			$("#chart_div").highcharts(base_chart_options);
			$('#tasks_form').submit(function(){
				var form_data_raw = $(this).serializeArray();
				var form_data_clean = [];
				$('#chart_div').highcharts().xAxis[0].categories = [];
				for(var field in form_data_raw){
					form_data_clean.push(form_data_raw[field]['name']);
					$('#chart_div').highcharts().xAxis[0].categories.push(tasks[form_data_raw[field]['name']]['itemname']);
				}
				$.ajax({
					type: "POST",
					dataType: "JSON",
					url: "query_grades.php",
					data: {
						"form_data": form_data_clean,
						"course_id": <?php echo json_encode($course_id); ?>
					},
					success: function(grades_info){
						var grades_stats = [];
						var sort_func = function(a, b){
							return a - b;
						};
						for(var task_i in grades_info){
							grades_info[task_i]['grades'].sort(sort_func);
							var num_grades = grades_info[task_i]['grades'].length;
							var min_grade = Math.min.apply(null, grades_info[task_i]['grades']);
							var max_grade = Math.max.apply(null, grades_info[task_i]['grades']);
							var median_grade = null;
							if(num_grades % 2 != 0){
								median_grade = grades_info[task_i]['grades'][Math.ceil(num_grades/2)];
							}
							else{
								median_grade = 0.5 * (grades_info[task_i]['grades'][Math.floor(num_grades/2)] + grades_info[task_i]['grades'][Math.ceil(num_grades/2)]);
							}
							var task_data = {
							    low: min_grade,
							    q1: grades_info[task_i]['grades'][Math.round(0.25 * (num_grades + 1))],
							    median: median_grade,
							    q3: grades_info[task_i]['grades'][Math.round(0.75 * (num_grades + 1))],
							    high: max_grade,
							    name: task_i,
							    num_grades: grades_info[task_i]['grades'].length
							};
							grades_stats.push(task_data);
						}
						$('#chart_div').highcharts().series[0].setData(grades_stats);
					}
				});
				return false;
			});			
		</script>
	</body>
</html>