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
            if (in_array("activequiz", $availablemodules)) {
                // from here used to check if specific module is available, otherwise it is not displayed
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_icon("icon", "mod_activequiz", "mod_activequiz", array(
                    'width' => 24,
                    'height' => 24,
                    'title' => ''
                )), "mod" . $num, "activequiz", get_string('typename_activequiz', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("assign", $availablemodules)) {
                // from here used to check if specific module is available, otherwise it is not displayed
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_icon("icon", "mod_assign", "mod_assign", array(
                    'width' => 24,
                    'height' => 24,
                    'title' => ''
                )), "mod" . $num, "assign", get_string('typename_assign', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("attendance", $availablemodules)) {
                // from here used to check if specific module is available, otherwise it is not displayed
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_icon("icon", "mod_attendance", "mod_attendance", array(
                    'width' => 24,
                    'height' => 24,
                    'title' => ''
                )), "mod" . $num, "attendance", get_string('typename_attendance', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("bigbluebuttonbn", $availablemodules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_icon("icon", "mod_bigbluebuttonbn", "mod_bigbluebuttonbn", array(
                    'width' => 24,
                    'height' => 24,
                    'title' => ''
                )), "mod" . $num, "bigbluebuttonbn", get_string('typename_bigbluebuttonbn', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("booking", $availablemodules)) {
                // from here used to check if specific module is available, otherwise it is not displayed
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_icon("icon", "mod_booking", "mod_booking", array(
                    'width' => 24,
                    'height' => 24,
                    'title' => ''
                )), "mod" . $num, "booking", get_string('typename_booking', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("certificate", $availablemodules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_icon("icon", "mod_certificate", "mod_certificate", array(
                    'width' => 24,
                    'height' => 24,
                    'title' => ''
                )), "mod" . $num, "certificate", get_string('typename_certificate', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("chat", $availablemodules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_icon("icon", "mod_chat", "mod_chat", array(
                    'width' => 24,
                    'height' => 24,
                    'title' => ''
                )), "mod" . $num, "chat", get_string('typename_chat', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("checklist", $availablemodules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_icon("icon", "mod_checklist", "mod_checklist", array(
                    'width' => 24,
                    'height' => 24,
                    'title' => ''
                )), "mod" . $num, "checklist", get_string('typename_checklist', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("choice", $availablemodules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_icon("icon", "mod_choice", "mod_choice", array(
                    'width' => 24,
                    'height' => 24,
                    'title' => ''
                )), "mod" . $num, "choice", get_string('typename_choice', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("icontent", $availablemodules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_icon("icon", "mod_icontent", "mod_icontent", array(
                    'width' => 24,
                    'height' => 24,
                    'title' => ''
                )), "mod" . $num, "icontent", get_string('typename_icontent', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("customcert", $availablemodules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_icon("icon", "mod_customcert", "mod_customcert", array(
                    'width' => 24,
                    'height' => 24,
                    'title' => ''
                )), "mod" . $num, "customcert", get_string('typename_customcert', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("data", $availablemodules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_icon("icon", "mod_data", "mod_data", array(
                    'width' => 24,
                    'height' => 24,
                    'title' => ''
                )), "mod" . $num, "data", get_string('typename_data', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("dataform", $availablemodules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_icon("icon", "mod_dataform", "mod_dataform", array(
                    'width' => 24,
                    'height' => 24,
                    'title' => ''
                )), "mod" . $num, "dataform", get_string('typename_dataform', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("lti", $availablemodules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_icon("icon", "mod_lti", "mod_lti", array(
                    'width' => 24,
                    'height' => 24,
                    'title' => ''
                )), "mod" . $num, "lti", get_string('typename_lti', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("feedback", $availablemodules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_icon("icon", "mod_feedback", "mod_feedback", array(
                    'width' => 24,
                    'height' => 24,
                    'title' => ''
                )), "mod" . $num, "feedback", get_string('typename_feedback', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("forum", $availablemodules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_icon("icon", "mod_forum", "mod_forum", array(
                    'width' => 24,
                    'height' => 24,
                    'title' => ''
                )), "mod" . $num, "forum", get_string('typename_forum', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("game", $availablemodules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_icon("icon", "mod_game", "mod_game", array(
                    'width' => 24,
                    'height' => 24,
                    'title' => ''
                )), "mod" . $num, "game", get_string('typename_game', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("glossary", $availablemodules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_icon("icon", "mod_glossary", "mod_glossary", array(
                    'width' => 24,
                    'height' => 24,
                    'title' => ''
                )), "mod" . $num, "glossary", get_string('typename_glossary', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("choicegroup", $availablemodules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_icon("icon", "mod_choicegroup", "mod_choicegroup", array(
                    'width' => 24,
                    'height' => 24,
                    'title' => ''
                )), "mod" . $num, "choicegroup", get_string('typename_choicegroup', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("groupselect", $availablemodules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_icon("icon", "mod_groupselect", "mod_groupselect", array(
                    'width' => 24,
                    'height' => 24,
                    'title' => ''
                )), "mod" . $num, "groupselect", get_string('typename_groupselect', 'block_analytics_graphs'));
                $num++;
            }
            
            if (in_array("turnitintooltwo", $availablemodules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_icon("icon", "mod_turnitintooltwo", "mod_turnitintooltwo", array(
                    'width' => 24,
                    'height' => 24,
                    'title' => ''
                )), "mod" . $num, "turnitintooltwo", get_string('typename_turnitintooltwo', 'block_analytics_graphs'));
                $num++;
            }
            
            if (in_array("hotpot", $availablemodules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_icon("icon", "mod_hotpot", "mod_hotpot", array(
                    'width' => 24,
                    'height' => 24,
                    'title' => ''
                )), "mod" . $num, "hotpot", get_string('typename_hotpot', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("hvp", $availablemodules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_icon("icon", "mod_hvp", "mod_hvp", array(
                    'width' => 24,
                    'height' => 24,
                    'title' => ''
                )), "mod" . $num, "hvp", get_string('typename_hvp', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("lesson", $availablemodules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_icon("icon", "mod_lesson", "mod_lesson", array(
                    'width' => 24,
                    'height' => 24,
                    'title' => ''
                )), "mod" . $num, "lesson", get_string('typename_lesson', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("openmeetings", $availablemodules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_icon("icon", "mod_openmeetings", "mod_openmeetings", array(
                    'width' => 24,
                    'height' => 24,
                    'title' => ''
                )), "mod" . $num, "openmeetings", get_string('typename_openmeetings', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("questionnaire", $availablemodules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_icon("icon", "mod_questionnaire", "mod_questionnaire", array(
                    'width' => 24,
                    'height' => 24,
                    'title' => ''
                )), "mod" . $num, "questionnaire", get_string('typename_questionnaire', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("quiz", $availablemodules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_icon("icon", "mod_quiz", "mod_quiz", array(
                    'width' => 24,
                    'height' => 24,
                    'title' => ''
                )), "mod" . $num, "quiz", get_string('typename_quiz', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("quizgame", $availablemodules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_icon("icon", "mod_quizgame", "mod_quizgame", array(
                    'width' => 24,
                    'height' => 24,
                    'title' => ''
                )), "mod" . $num, "quizgame", get_string('typename_quizgame', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("scheduler", $availablemodules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_icon("icon", "mod_scheduler", "mod_scheduler", array(
                    'width' => 24,
                    'height' => 24,
                    'title' => ''
                )), "mod" . $num, "scheduler", get_string('typename_scheduler', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("scorm", $availablemodules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_icon("icon", "mod_scorm", "mod_scorm", array(
                    'width' => 24,
                    'height' => 24,
                    'title' => ''
                )), "mod" . $num, "scorm", get_string('typename_scorm', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("subcourse", $availablemodules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_icon("icon", "mod_subcourse", "mod_subcourse", array(
                    'width' => 24,
                    'height' => 24,
                    'title' => ''
                )), "mod" . $num, "subcourse", get_string('typename_subcourse', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("survey", $availablemodules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_icon("icon", "mod_survey", "mod_survey", array(
                    'width' => 24,
                    'height' => 24,
                    'title' => ''
                )), "mod" . $num, "survey", get_string('typename_survey', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("vpl", $availablemodules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_icon("icon", "mod_vpl", "mod_vpl", array(
                    'width' => 24,
                    'height' => 24,
                    'title' => ''
                )), "mod" . $num, "vpl", get_string('typename_vpl', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("wiki", $availablemodules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_icon("icon", "mod_wiki", "mod_wiki", array(
                    'width' => 24,
                    'height' => 24,
                    'title' => ''
                )), "mod" . $num, "wiki", get_string('typename_wiki', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("workshop", $availablemodules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_icon("icon", "mod_workshop", "mod_workshop", array(
                    'width' => 24,
                    'height' => 24,
                    'title' => ''
                )), "mod" . $num, "workshop", get_string('typename_workshop', 'block_analytics_graphs'));
                $num++;
            }

            echo "<h4 style='margin-bottom: 3px'>" . get_string('resources', 'block_analytics_graphs') . ":</h4>";

            if (in_array("book", $availablemodules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_icon("icon", "mod_book", "mod_book", array(
                    'width' => 24,
                    'height' => 24,
                    'title' => ''
                )), "mod" . $num, "book", get_string('typename_book', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("resource", $availablemodules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_icon("icon", "mod_resource", "mod_resource", array(
                    'width' => 24,
                    'height' => 24,
                    'title' => ''
                )), "mod" . $num, "resource", get_string('typename_resource', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("folder", $availablemodules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_icon("icon", "mod_folder", "mod_folder", array(
                    'width' => 24,
                    'height' => 24,
                    'title' => ''
                )), "mod" . $num, "folder", get_string('typename_folder', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("imscp", $availablemodules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_icon("icon", "mod_imscp", "mod_imscp", array(
                    'width' => 24,
                    'height' => 24,
                    'title' => ''
                )), "mod" . $num, "imscp", get_string('typename_imscp', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("label", $availablemodules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_icon("icon", "mod_label", "mod_label", array(
                    'width' => 24,
                    'height' => 24,
                    'title' => ''
                )), "mod" . $num, "label", get_string('typename_label', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("lightboxgallery", $availablemodules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_icon("icon", "mod_lightboxgallery", "mod_lightboxgallery", array(
                    'width' => 24,
                    'height' => 24,
                    'title' => ''
                )), "mod" . $num, "lightboxgallery", get_string('typename_lightboxgallery', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("page", $availablemodules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_icon("icon", "mod_page", "mod_page", array(
                    'width' => 24,
                    'height' => 24,
                    'title' => ''
                )), "mod" . $num, "page", get_string('typename_page', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("poster", $availablemodules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_icon("icon", "mod_poster", "mod_poster", array(
                    'width' => 24,
                    'height' => 24,
                    'title' => ''
                )), "mod" . $num, "poster", get_string('typename_poster', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("recordingsbn", $availablemodules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_icon("icon", "mod_recordingsbn", "mod_recordingsbn", array(
                    'width' => 24,
                    'height' => 24,
                    'title' => ''
                )), "mod" . $num, "recordingsbn", get_string('typename_recordingsbn', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("url", $availablemodules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_icon("icon", "mod_url", "mod_url", array(
                    'width' => 24,
                    'height' => 24,
                    'title' => ''
                )), "mod" . $num, "url", get_string('typename_url', 'block_analytics_graphs'));
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
