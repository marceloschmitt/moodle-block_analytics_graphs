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
//require('javascriptfunctions.php');
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
$availableModules = array();
foreach ( block_analytics_graphs_get_course_used_modules($course) as $result ) {
    array_push($availableModules, $result->name);
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
            //get_string('no_types_requested', 'block_analytics_graphs')
            echo "<h4 style='margin-bottom: 3px'>Activities:</h4>";
            if (in_array("activequiz", $availableModules)) { //from here used to check if specific module is available, otherwise it is not displayed
                echo block_analytics_graphs_generate_graph_startup_module_entry($legacypixurlbefore . $OUTPUT->pix_url('icon', 'mod_activequiz') . $legacypixurlafter, "mod" . $num, "activequiz",
                    get_string('typename_activequiz', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("assign", $availableModules)) { //from here used to check if specific module is available, otherwise it is not displayed
                echo block_analytics_graphs_generate_graph_startup_module_entry($legacypixurlbefore . $OUTPUT->pix_url('icon', 'mod_assign') . $legacypixurlafter, "mod" . $num, "assign",
                    get_string('typename_assign', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("attendance", $availableModules)) { //from here used to check if specific module is available, otherwise it is not displayed
                echo block_analytics_graphs_generate_graph_startup_module_entry($legacypixurlbefore . $OUTPUT->pix_url('icon', 'mod_attendance') . $legacypixurlafter, "mod" . $num, "attendance",
                    get_string('typename_attendance', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("bigbluebuttonbn", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($legacypixurlbefore . $OUTPUT->pix_url('icon', 'mod_bigbluebuttonbn') . $legacypixurlafter, "mod" . $num, "bigbluebuttonbn",
                    get_string('typename_bigbluebuttonbn', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("booking", $availableModules)) { //from here used to check if specific module is available, otherwise it is not displayed
                echo block_analytics_graphs_generate_graph_startup_module_entry($legacypixurlbefore . $OUTPUT->pix_url('icon', 'mod_booking') . $legacypixurlafter, "mod" . $num, "booking",
                    get_string('typename_booking', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("certificate", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($legacypixurlbefore . $OUTPUT->pix_url('icon', 'mod_certificate') . $legacypixurlafter, "mod" . $num, "certificate",
                    get_string('typename_certificate', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("chat", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($legacypixurlbefore . $OUTPUT->pix_url('icon', 'mod_chat') . $legacypixurlafter, "mod" . $num, "chat",
                    get_string('typename_chat', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("checklist", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($legacypixurlbefore . $OUTPUT->pix_url('icon', 'mod_checklist') . $legacypixurlafter, "mod" . $num, "checklist",
                    get_string('typename_checklist', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("choice", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($legacypixurlbefore . $OUTPUT->pix_url('icon', 'mod_choice') . $legacypixurlafter, "mod" . $num, "choice",
                    get_string('typename_choice', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("icontent", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($legacypixurlbefore . $OUTPUT->pix_url('icon', 'mod_icontent') . $legacypixurlafter, "mod" . $num, "icontent",
                    get_string('typename_icontent', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("customcert", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($legacypixurlbefore . $OUTPUT->pix_url('icon', 'mod_customcert') . $legacypixurlafter, "mod" . $num, "customcert",
                    get_string('typename_customcert', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("data", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($legacypixurlbefore . $OUTPUT->pix_url('icon', 'mod_data') . $legacypixurlafter, "mod" . $num, "data",
                    get_string('typename_data', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("dataform", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($legacypixurlbefore . $OUTPUT->pix_url('icon', 'mod_dataform') . $legacypixurlafter, "mod" . $num, "dataform",
                    get_string('typename_dataform', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("lti", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($legacypixurlbefore . $OUTPUT->pix_url('icon', 'mod_lti') . $legacypixurlafter, "mod" . $num, "lti",
                    get_string('typename_lti', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("feedback", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($legacypixurlbefore . $OUTPUT->pix_url('icon', 'mod_feedback') . $legacypixurlafter, "mod" . $num, "feedback",
                    get_string('typename_feedback', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("forum", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($legacypixurlbefore . $OUTPUT->pix_url('icon', 'mod_forum') . $legacypixurlafter, "mod" . $num, "forum",
                    get_string('typename_forum', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("game", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($legacypixurlbefore . $OUTPUT->pix_url('icon', 'mod_game') . $legacypixurlafter, "mod" . $num, "game",
                    get_string('typename_game', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("glossary", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($legacypixurlbefore . $OUTPUT->pix_url('icon', 'mod_glossary') . $legacypixurlafter, "mod" . $num, "glossary",
                    get_string('typename_glossary', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("choicegroup", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($legacypixurlbefore . $OUTPUT->pix_url('icon', 'mod_choicegroup') . $legacypixurlafter, "mod" . $num, "choicegroup",
                    get_string('typename_choicegroup', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("groupselect", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($legacypixurlbefore . $OUTPUT->pix_url('icon', 'mod_groupselect') . $legacypixurlafter, "mod" . $num, "groupselect",
                    get_string('typename_groupselect', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("hotpot", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($legacypixurlbefore . $OUTPUT->pix_url('icon', 'mod_hotpot') . $legacypixurlafter, "mod" . $num, "hotpot",
                    get_string('typename_hotpot', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("hvp", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($legacypixurlbefore . $OUTPUT->pix_url('icon', 'mod_hvp') . $legacypixurlafter, "mod" . $num, "hvp",
                    get_string('typename_hvp', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("lesson", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($legacypixurlbefore . $OUTPUT->pix_url('icon', 'mod_lesson') . $legacypixurlafter, "mod" . $num, "lesson",
                    get_string('typename_lesson', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("openmeetings", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($legacypixurlbefore . $OUTPUT->pix_url('icon', 'mod_openmeetings') . $legacypixurlafter, "mod" . $num, "openmeetings",
                    get_string('typename_openmeetings', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("questionnaire", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($legacypixurlbefore . $OUTPUT->pix_url('icon', 'mod_questionnaire') . $legacypixurlafter, "mod" . $num, "questionnaire",
                    get_string('typename_questionnaire', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("quiz", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($legacypixurlbefore . $OUTPUT->pix_url('icon', 'mod_quiz') . $legacypixurlafter, "mod" . $num, "quiz",
                    get_string('typename_quiz', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("quizgame", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($legacypixurlbefore . $OUTPUT->pix_url('icon', 'mod_quizgame') . $legacypixurlafter, "mod" . $num, "quizgame",
                    get_string('typename_quizgame', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("scheduler", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($legacypixurlbefore . $OUTPUT->pix_url('icon', 'mod_scheduler') . $legacypixurlafter, "mod" . $num, "scheduler",
                    get_string('typename_scheduler', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("scorm", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($legacypixurlbefore . $OUTPUT->pix_url('icon', 'mod_scorm') . $legacypixurlafter, "mod" . $num, "scorm",
                    get_string('typename_scorm', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("subcourse", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($legacypixurlbefore . $OUTPUT->pix_url('icon', 'mod_subcourse') . $legacypixurlafter, "mod" . $num, "subcourse",
                    get_string('typename_subcourse', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("survey", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($legacypixurlbefore . $OUTPUT->pix_url('icon', 'mod_survey') . $legacypixurlafter, "mod" . $num, "survey",
                    get_string('typename_survey', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("vpl", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($legacypixurlbefore . $OUTPUT->pix_url('icon', 'mod_vpl') . $legacypixurlafter, "mod" . $num, "vpl",
                    get_string('typename_vpl', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("wiki", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($legacypixurlbefore . $OUTPUT->pix_url('icon', 'mod_wiki') . $legacypixurlafter, "mod" . $num, "wiki",
                    get_string('typename_wiki', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("workshop", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($legacypixurlbefore . $OUTPUT->pix_url('icon', 'mod_workshop') . $legacypixurlafter, "mod" . $num, "workshop",
                    get_string('typename_workshop', 'block_analytics_graphs'));
                $num++;
            }

            echo "<h4 style='margin-bottom: 3px'>Resources:</h4>";

            if (in_array("book", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($legacypixurlbefore . $OUTPUT->pix_url('icon', 'mod_book') . $legacypixurlafter, "mod" . $num, "book",
                    get_string('typename_book', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("resource", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($legacypixurlbefore . $OUTPUT->pix_url('icon', 'mod_resource') . $legacypixurlafter, "mod" . $num, "resource",
                    get_string('typename_resource', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("folder", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($legacypixurlbefore . $OUTPUT->pix_url('icon', 'mod_folder') . $legacypixurlafter, "mod" . $num, "folder",
                    get_string('typename_folder', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("imscp", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($legacypixurlbefore . $OUTPUT->pix_url('icon', 'mod_imscp') . $legacypixurlafter, "mod" . $num, "imscp",
                    get_string('typename_imscp', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("label", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($legacypixurlbefore . $OUTPUT->pix_url('icon', 'mod_label') . $legacypixurlafter, "mod" . $num, "label",
                    get_string('typename_label', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("lightboxgallery", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($legacypixurlbefore . $OUTPUT->pix_url('icon', 'mod_lightboxgallery') . $legacypixurlafter, "mod" . $num, "lightboxgallery",
                    get_string('typename_lightboxgallery', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("page", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($legacypixurlbefore . $OUTPUT->pix_url('icon', 'mod_page') . $legacypixurlafter, "mod" . $num, "page",
                    get_string('typename_page', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("poster", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($legacypixurlbefore . $OUTPUT->pix_url('icon', 'mod_poster') . $legacypixurlafter, "mod" . $num, "poster",
                    get_string('typename_poster', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("recordingsbn", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($legacypixurlbefore . $OUTPUT->pix_url('icon', 'mod_recordingsbn') . $legacypixurlafter, "mod" . $num, "recordingsbn",
                    get_string('typename_recordingsbn', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("url", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($legacypixurlbefore . $OUTPUT->pix_url('icon', 'mod_url') . $legacypixurlafter, "mod" . $num, "url",
                    get_string('typename_url', 'block_analytics_graphs'));
                $num++;
            }
			
			echo "<input type=\"hidden\" name=\"id\" value=\"$course\">";
			
			echo "<h4 style='margin-bottom: 3px'>Options:</h4>";
			
			echo "Start from: <input type=\"date\" name=\"from\" value=\"$startdate\"><br>";
			 
			echo "<input type=\"checkbox\" name=\"hidden\" value=\"true\"> Display hidden items";
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
