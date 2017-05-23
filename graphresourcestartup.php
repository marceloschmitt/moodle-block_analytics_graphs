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
$legacy = required_param('legacy', PARAM_INT);
global $DB;
/* Access control */
require_login($course);
$context = context_course::instance($course);
require_capability('block/analytics_graphs:viewpages', $context);


/* Initializing and filling array with available modules, to display only modules that are
 available on the server on the course */
$availableModules = array();
foreach ( block_analytics_graphs_get_course_used_modules($course) as $result ) {
    array_push($availableModules, $result->name);
}
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
    echo "<input type=\"hidden\" name=\"legacy\" value=\"$legacy\">";

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
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_url('icon', 'mod_activequiz'), "mod" . $num, "activequiz",
                    get_string('typename_activequiz', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("assign", $availableModules)) { //from here used to check if specific module is available, otherwise it is not displayed
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_url('icon', 'mod_assign'), "mod" . $num, "assign",
                    get_string('typename_assign', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("attendance", $availableModules)) { //from here used to check if specific module is available, otherwise it is not displayed
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_url('icon', 'mod_attendance'), "mod" . $num, "attendance",
                    get_string('typename_attendance', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("bigbluebuttonbn", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_url('icon', 'mod_bigbluebuttonbn'), "mod" . $num, "bigbluebuttonbn",
                    get_string('typename_bigbluebuttonbn', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("booking", $availableModules)) { //from here used to check if specific module is available, otherwise it is not displayed
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_url('icon', 'mod_booking'), "mod" . $num, "booking",
                    get_string('typename_booking', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("certificate", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_url('icon', 'mod_certificate'), "mod" . $num, "certificate",
                    get_string('typename_certificate', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("chat", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_url('icon', 'mod_chat'), "mod" . $num, "chat",
                    get_string('typename_chat', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("checklist", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_url('icon', 'mod_checklist'), "mod" . $num, "checklist",
                    get_string('typename_checklist', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("choice", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_url('icon', 'mod_choice'), "mod" . $num, "choice",
                    get_string('typename_choice', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("icontent", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_url('icon', 'mod_icontent'), "mod" . $num, "icontent",
                    get_string('typename_icontent', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("customcert", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_url('icon', 'mod_customcert'), "mod" . $num, "customcert",
                    get_string('typename_customcert', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("data", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_url('icon', 'mod_data'), "mod" . $num, "data",
                    get_string('typename_data', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("dataform", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_url('icon', 'mod_dataform'), "mod" . $num, "dataform",
                    get_string('typename_dataform', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("lti", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_url('icon', 'mod_lti'), "mod" . $num, "lti",
                    get_string('typename_lti', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("feedback", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_url('icon', 'mod_feedback'), "mod" . $num, "feedback",
                    get_string('typename_feedback', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("forum", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_url('icon', 'mod_forum'), "mod" . $num, "forum",
                    get_string('typename_forum', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("game", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_url('icon', 'mod_game'), "mod" . $num, "game",
                    get_string('typename_game', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("glossary", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_url('icon', 'mod_glossary'), "mod" . $num, "glossary",
                    get_string('typename_glossary', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("choicegroup", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_url('icon', 'mod_choicegroup'), "mod" . $num, "choicegroup",
                    get_string('typename_choicegroup', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("groupselect", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_url('icon', 'mod_groupselect'), "mod" . $num, "groupselect",
                    get_string('typename_groupselect', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("hotpot", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_url('icon', 'mod_hotpot'), "mod" . $num, "hotpot",
                    get_string('typename_hotpot', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("hvp", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_url('icon', 'mod_hvp'), "mod" . $num, "hvp",
                    get_string('typename_hvp', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("lesson", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_url('icon', 'mod_lesson'), "mod" . $num, "lesson",
                    get_string('typename_lesson', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("openmeetings", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_url('icon', 'mod_openmeetings'), "mod" . $num, "openmeetings",
                    get_string('typename_openmeetings', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("questionnaire", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_url('icon', 'mod_questionnaire'), "mod" . $num, "questionnaire",
                    get_string('typename_questionnaire', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("quiz", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_url('icon', 'mod_quiz'), "mod" . $num, "quiz",
                    get_string('typename_quiz', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("quizgame", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_url('icon', 'mod_quizgame'), "mod" . $num, "quizgame",
                    get_string('typename_quizgame', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("scheduler", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_url('icon', 'mod_scheduler'), "mod" . $num, "scheduler",
                    get_string('typename_scheduler', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("scorm", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_url('icon', 'mod_scorm'), "mod" . $num, "scorm",
                    get_string('typename_scorm', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("subcourse", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_url('icon', 'mod_subcourse'), "mod" . $num, "subcourse",
                    get_string('typename_subcourse', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("survey", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_url('icon', 'mod_survey'), "mod" . $num, "survey",
                    get_string('typename_survey', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("vpl", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_url('icon', 'mod_vpl'), "mod" . $num, "vpl",
                    get_string('typename_vpl', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("wiki", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_url('icon', 'mod_wiki'), "mod" . $num, "wiki",
                    get_string('typename_wiki', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("workshop", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_url('icon', 'mod_workshop'), "mod" . $num, "workshop",
                    get_string('typename_workshop', 'block_analytics_graphs'));
                $num++;
            }

            echo "<h4 style='margin-bottom: 3px'>Resources:</h4>";

            if (in_array("book", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_url('icon', 'mod_book'), "mod" . $num, "book",
                    get_string('typename_book', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("resource", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_url('icon', 'mod_resource'), "mod" . $num, "resource",
                    get_string('typename_resource', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("folder", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_url('icon', 'mod_folder'), "mod" . $num, "folder",
                    get_string('typename_folder', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("imscp", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_url('icon', 'mod_imscp'), "mod" . $num, "imscp",
                    get_string('typename_imscp', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("label", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_url('icon', 'mod_label'), "mod" . $num, "label",
                    get_string('typename_label', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("lightboxgallery", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_url('icon', 'mod_lightboxgallery'), "mod" . $num, "lightboxgallery",
                    get_string('typename_lightboxgallery', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("page", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_url('icon', 'mod_page'), "mod" . $num, "page",
                    get_string('typename_page', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("poster", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_url('icon', 'mod_poster'), "mod" . $num, "poster",
                    get_string('typename_poster', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("recordingsbn", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_url('icon', 'mod_recordingsbn'), "mod" . $num, "recordingsbn",
                    get_string('typename_recordingsbn', 'block_analytics_graphs'));
                $num++;
            }
            if (in_array("url", $availableModules)) {
                echo block_analytics_graphs_generate_graph_startup_module_entry($OUTPUT->pix_url('icon', 'mod_url'), "mod" . $num, "url",
                    get_string('typename_url', 'block_analytics_graphs'));
                $num++;
            }
			 echo "<input type=\"hidden\" name=\"id\" value=\"$course\">";
    echo "<input type=\"hidden\" name=\"legacy\" value=\"$legacy\">";
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
