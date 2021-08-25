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
// require('javascriptfunctions.php');
$course = required_param('id', PARAM_INT);
global $DB;
/* Access control */
require_login($course);
$context = context_course::instance($course);
require_capability('block/analytics_graphs:viewpages', $context);
$courseparams = get_course($course);
$startdate = date("Y-m-d", $courseparams->startdate);

/* Initializing and filling array with available modules, to display only modules that are
 available on the server on the course */
$availablemodules = array();
foreach (block_analytics_graphs_get_course_used_modules($course) as $result) {
    array_push($availablemodules, $result->name);
}

$legacypixurlbefore = "<img style='display: table-cell; vertical-align: middle;' src='";
$legacypixurlafter = "'width='24' height='24'>";

?>

<script>
    function checkUncheck(setTo) {
        var c = document.getElementsByTagName('input');
        for (var i = 0; i < c.length; i++) {
            if (c[i].type == 'checkbox') {
                c[i].checked = setTo;
            }
        }
    }
</script>

<html style="background-color: #f4f4f4;">
<div style="width: 250px;height: 80%;position:absolute;left:0; right:0;top:0; bottom:0;margin:auto;max-width:100%;max-height:100%;
overflow:auto;background-color: white;border-radius: 0px;padding: 20px;border: 2px solid darkgray;text-align: center;">
    <?php
    echo "<input type=\"hidden\" name=\"id\" value=\"$course\">";

    echo "<h1>" . get_string('access_graph', 'block_analytics_graphs') . "</h1>";
    echo "<h3>" . get_string('select_items_to_display', 'block_analytics_graphs') . ":</h3>";
    ?>
    <div style="text-align: left">
        <form action="graphresourceurl.php" method="get">
            <?php
            /* Checking and displaying available choices based on installed modules and specific order and formatting,
            if order does not matter, then can be exchanged with a simple for loop */
            $num = 1;
            // get_string('no_types_requested', 'block_analytics_graphs')
            echo "<h4 style='margin-bottom: 3px'>" . get_string('activities', 'block_analytics_graphs') . ":</h4>";
            foreach($availablemodules AS $modulename) {
                    $module = "mod_$modulename";
                    $typename = "typename_$modulename";
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_icon("icon", $module, $module, array(
                    'width' => 24,
                    'height' => 24,
                    'title' => ''
                )), "mod" . $num, $modulename, get_string('pluginname', $module));
                $num++;
            }

            echo "<input type=\"hidden\" name=\"id\" value=\"$course\">";

			echo "<h4 style='margin-bottom: 3px'>" . get_string('options', 'block_analytics_graphs') . ":</h4>";

			echo get_string('startfrom', 'block_analytics_graphs') . ": <input type=\"date\" name=\"from\" value=\"$startdate\"><br>";

			echo "<input type=\"checkbox\" name=\"hidden\" value=\"true\">" . get_string('displayhidden', 'block_analytics_graphs');
            ?>
    </div>
    <?php
    echo "<input type='button' value='" . get_string('btn_select_all', 'block_analytics_graphs') . "' onclick='checkUncheck(true);'>";
    echo "<input type='button' value='" . get_string('btn_deselect_all', 'block_analytics_graphs') . "' onclick='checkUncheck(false);'>";
    echo "<input type='submit' value='" . get_string('btn_submit', 'block_analytics_graphs') . "''>";
    ?>
    </form>
</div>
</html>
