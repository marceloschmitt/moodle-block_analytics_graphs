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
		WHERE gc.courseid = ? AND gc.depth > 1
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
	</head>
	<body>
		<h1>Choose which task(s) to use to build a grades distribution chart</h1>
		<div id="tasks"></div>
		<form id="tasks_form"></form>
		<div id='chart_div'></div>
		<script>
			var tasks = <?php echo json_encode($result); ?>;
			for(elem in taks){
				$("#tasks_form").append("<input type='checkbox' name='" + 
										tasks[elem]['id'] + "'>" +
										tasks[elem]['itemname'] + 
										"<br>");
			}
			$("#tasks_form").append("<input type='submit' value='" + 
										"See grades chart with selected tasks" + "'>");			
			
			$('#tasks_form').submit(function(){
				var form_data = $(this).serialize();
				jQuery.ajax({
					type: "POST",
					url: "query_grades.php",
					data: {
						"form_data": form_data,
						"course_id": <?php echo json_encode($course_id); ?>
					},
					success: display_chart('chart_div')
				});
				return false;
			});

			var display_chart = function(div_id){
				return function(display_chart_callback(data)){
					var grades = [];
					var num_grades = data.length;
					var statistics = {
						mean : 0.0, 
						variance : 0.0,
						std_dev: 0.0
					};
					for(elem in data){
						grades.push(data[elem]['grade']);
						statistics['mean'] += grades[i];
					}
					statistics['mean'] = statistics['mean']/num_grades;
					for(var i=0; i<num_grades; i++){
						statistics['variance'] += Math.pow(grades[i] - statistics['mean'], 2);
					}
					statistics['variance'] = statistics['variance']/(num_grades-1.0);
					statistics['std_dev'] = Math.sqrt(statistics['variance']);

					var min_limit = Math.max(statistics['mean'] - 3*statistics['std_dev'], 0.0);
					var max_limit = statistics['mean'] + 3*statistics['std_dev'];
					var num_points = Math.max(num_grades, 50);
					var point_incr = (max_limit - min_limit)/num_points;
					var point = min_limit;
					var p = null;
					while(point <= max_limit){
						if(Math.abs(point - statistics['mean']) < 1e-3){
							point = statistics['mean'];
						}
						p = Math.exp(-Math.pow(point - statistics['mean'], 2));
						p /= (2.0 * statistics['variance']);
						data.push([point, p/(statistics['std_dev'] * Math.sqrt(2.0 * Math.PI))]);
						point += point_incr;
					}
					$("#container").empty().highcharts({
						chart: {
					        type: 'area',
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
					        text: 'Grades Distribution'
					    },
					    tooltip:{
					    	enabled:false,
					    	useHTML:true,
					    	backgroundColor: "rgba(255, 255, 255, 1.0)",
					    	hideDelay: 250,
					    	formatter: function(){
					    		var tooltipStr = "<span style='font-size: 13px'><b>This grade</b></span>: <b>" + 
					    								this.point.x.toFixed(2) + "</b><br>" + 
					    								"<b>Probability: " + 
					    								this.point.y.toExponential(4) + "</b><br><br>";
					    		var smaller_grades_str = "<b>Grades smaller than this:</b><br>";
					    		var larger_grades_str = "<b>Grades larger than this:</b><br>";
					    		var smaller_grades = [];
					    		var larger_grades = [];
					    		for(var i=0; i<grades.length; i++){
					    			if(grades[i] < this.point.x){
					    				smaller_grades.push(grades[i]);
					    				smaller_grades_str += grades[i].toFixed(2).toString() + "<br>";
					    			}
					    			else{
					    				larger_grades.push(grades[i]);
					    				larger_grades_str += grades[i].toFixed(2).toString() + "<br>";
					    			}
					    		}
					    		if(larger_grades.length == 0){
					    			larger_grades_str += "There is no grade larger than this<br>";
					    		}
					    		if(smaller_grades.length == 0){
					    			smaller_grades_str += "There is no grade smaller than this<br>";
					    		}
					    		tooltipStr += smaller_grades_str + "<br>" + larger_grades_str;
					    		return tooltipStr;
							}
					    },
					    plotOptions:{
					    	area:{
					    		cursor: "pointer",
					    		allowPointSelect: true
					        },
					        series : {
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
					    xAxis:{
					    	min: min_limit,
					    	endOnTick: false,
					    	allowDecimals: true,
					    	plotLines: [{
					    		value: statistics['mean'],
					    		color: '#434348',
					    		width: 1,
					    		dashStyle: "ShortDash",
					    		label:{
					    			text: "Average grade: " + statistics['mean'].toFixed(2),
					    			rotation:0,
					    			style:{
					    				fontWeight: "bold",
					    				fontSize: "13px"
					    			}
					    		}
					    	},
					    	{
					    		value: statistics['mean'] + statistics['std_dev'],
					    		color: '#434348',
					    		width: 1,
					    		dashStyle: "ShortDash",
					    		label:{
					    			text: "1 Std",
					    			rotation:0,
					    			style:{
					    				fontWeight: "bold",
					    				fontSize: "12px"
					    			}
								}
							},
					    	{
					    		value: statistics['mean'] + 2*statistics['std_dev'],
					    		color: '#434348',
					    		width: 1,
					    		dashStyle: "ShortDash",
					    		label:{
					    			text: "2 Std",
					    			rotation:0,
					    			style:{
					    				fontWeight: "bold",
					    				fontSize: "12px"
					    			}
								}
					    	},
					    	{
					    		value: statistics['mean'] - statistics['std_dev'],
					    		color: '#434348',
					    		width: 1,
					    		dashStyle: "ShortDash",
					    		label:{
					    			text: "1 Std",
					    			rotation:0,
					    			style:{
					    				fontWeight: "bold",
					    				fontSize: "12px"
					    			}
								}
					    	},
					    	{
					    		value: statistics['mean'] - 2*statistics['std_dev'],
					    		color: '#434348',
					    		width: 1,
					    		dashStyle: "ShortDash",
					    		label:{
					    			text: "2 Std",
					    			rotation:0,
					    			style:{
					    				fontWeight: "bold",
					    				fontSize: "12px"
					    			}
								}
					    	}]
					    },
					    yAxis: {
					        title: {
					            text: 'Grade probability'
					        }
					    },
					    series: [{
					    	name: "Grades",
					        data: data
					    }]
					});
				};
			};			
		</script>
	</body>
</html>