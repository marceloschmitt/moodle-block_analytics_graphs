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

/**
 * Activity type picker for content access graphs.
 *
 * @package    block_analytics_graphs
 * @copyright  2026 Marcelo Augusto Rauh Schmitt <marcelo.schmitt@poa.ifrs.edu.br>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// phpcs:disable moodle.Commenting.MissingDocblock -- Mixed HTML/PHP; each inline <?php open tag would otherwise require a file docblock.

require('../../config.php');
require_once('lib.php');
$course = required_param('id', PARAM_INT);
global $DB;
/* Access control */
require_login($course);
$context = context_course::instance($course);
require_capability('block/analytics_graphs:viewpages', $context);
require_capability('block/analytics_graphs:viewcontentaccesses', $context);
$courseparams = get_course($course);
$startdate = date("Y-m-d", $courseparams->startdate);

/* Initializing and filling array with available modules, to display only modules that are
 available on the server on the course */
$availablemodules = [];
foreach (block_analytics_graphs_get_course_used_modules($course) as $result) {
    array_push($availablemodules, $result->name);
}

?>
<!DOCTYPE html>
<html style="background-color: #f4f4f4;">
<head>
    <meta charset="utf-8">
    <title><?php echo get_string('access_graph', 'block_analytics_graphs'); ?></title>
    <script>
        function checkUncheck(setTo) {
            var c = document.getElementsByClassName('selectable');
            for (var i = 0; i < c.length; i++) {
                if (c[i].type == 'checkbox') {
                    c[i].checked = setTo;
                }
            }
        }
    </script>
    <style>
        .my_text {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 0.75rem;
        }
    </style>
</head>
<body>
<div class = "my_text" style="width: 15.625rem;height: 80%;position:absolute;left:0; right:0;top:0;
    bottom:0;margin:auto;max-width:100%;max-height:100%;
    overflow:auto;background-color: white;border-radius: 0;padding: 1.25rem;border: 2px solid darkgray;text-align: center;">
    <?php
    echo "<input type=\"hidden\" name=\"id\" value=\"$course\">";

    echo "<h1>" . get_string('access_graph', 'block_analytics_graphs') . "</h1>";
    echo "<h3>" . get_string('select_items_to_display', 'block_analytics_graphs') . ":</h3>";
    ?>
    <div style="text-align: left">
        <form action="graphresourceurl.php" method="get">
            <?php
            $num = 1;
            echo "<h4 style='margin-bottom: 0.1875rem'>" . get_string('activities', 'block_analytics_graphs') . ":</h4>";
            foreach ($availablemodules as $modulename) {
                $module = "mod_$modulename";
                $typename = "typename_$modulename";
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_icon("icon", $module,
                    $module, [
                        'width' => 24,
                        'height' => 24,
                        'title' => '',
                    ]), "mod" . $num, $modulename, get_string('pluginname', $module));
                $num++;
            }

            echo "<input type=\"hidden\" name=\"id\" value=\"$course\">";

            echo "<h4 style='margin-bottom: 0.1875rem'>" . get_string('options', 'block_analytics_graphs') . ":</h4>";

            echo "<label for=\"ag-startup-from\">" . get_string('startfrom', 'block_analytics_graphs') .
                "</label>: <input type=\"date\" id=\"ag-startup-from\" name=\"from\" value=\"$startdate\"><br>";

            echo "<input type=\"checkbox\" id=\"ag-startup-hidden\" name=\"hidden\" value=\"true\">" .
                "<label for=\"ag-startup-hidden\">" . get_string('displayhidden', 'block_analytics_graphs') . "</label>";
            ?>
    </div>
    <?php
    echo "<input type='button' value='" . get_string('btn_select_all', 'block_analytics_graphs') . "'
        onclick='checkUncheck(true);'>";
    echo "<input type='button' value='" . get_string('btn_deselect_all', 'block_analytics_graphs') . "'
        onclick='checkUncheck(false);'>";
    echo "<input type='submit' value='" . get_string('btn_submit', 'block_analytics_graphs') . "''>";
    ?>
    </form>
</div>
</body>
</html>
