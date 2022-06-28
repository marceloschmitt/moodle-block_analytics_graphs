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

class block_analytics_graphs extends block_base {
    public function init() {
        $this->title = get_string('analytics_graphs', 'block_analytics_graphs');
    }
    // The PHP tag and the curly bracket for the class definition
    // will only be closed after there is another function added in the next section.
    public function get_content() {
        global $CFG;
        global $DB;

        $course = $this->page->course;
        $context = context_course::instance($course->id);
        $canview = has_capability('block/analytics_graphs:viewpages', $context);
        if (!$canview) {
            return;
        }
        if ($this->content !== null) {
            return $this->content;
        }

        $sql = "SELECT cm.module, md.name
            FROM {course_modules} cm
            LEFT JOIN {modules} md ON cm.module = md.id
            WHERE cm.course = ?
            GROUP BY cm.module, md.name";
        $params = array($course->id);
        $availablemodulestotal = $DB->get_records_sql($sql, $params);
        $availablemodules = array();
        foreach ($availablemodulestotal as $result) {
            array_push($availablemodules, $result->name);
        }

        $this->content = new stdClass;
        $this->content->text = "";
        $this->content->text .= "<li> <a href= {$CFG->wwwroot}/blocks/analytics_graphs/grades_chart.php?id={$course->id}
                          target=_blank>" . get_string('grades_chart', 'block_analytics_graphs') . "</a>";
        $this->content->text .= "<li> <a href= {$CFG->wwwroot}/blocks/analytics_graphs/graphresourcestartup.php?id={$course->id}
                          target=_blank>" . get_string('access_to_contents', 'block_analytics_graphs') . "</a>";
        $this->content->text .= "<li> <a href= {$CFG->wwwroot}/blocks/analytics_graphs/timeaccesseschart.php?id={$course->id}&days=7
                          target=_blank>" . get_string('timeaccesschart_title', 'block_analytics_graphs') . "</a>";
        if (in_array("assign", $availablemodules)) {
            $this->content->text .= "<li> <a href= {$CFG->wwwroot}/blocks/analytics_graphs/assign.php?id={$course->id}
                          target=_blank>" . get_string('submissions_assign', 'block_analytics_graphs') . "</a>";
        }
        if (in_array("quiz", $availablemodules)) {
            $this->content->text .= "<li> <a href= {$CFG->wwwroot}/blocks/analytics_graphs/quiz.php?id={$course->id}
                          target=_blank>" . get_string('submissions_quiz', 'block_analytics_graphs') . "</a>";
        }
        if (in_array("hotpot", $availablemodules)) {
            $this->content->text .= "<li> <a href= {$CFG->wwwroot}/blocks/analytics_graphs/hotpot.php?id={$course->id}
                target=_blank>" . get_string('submissions_hotpot', 'block_analytics_graphs') . "</a>";
        }
        if (in_array("turnitintooltwo", $availablemodules)) {
            $this->content->text .= "<li> <a href= {$CFG->wwwroot}/blocks/analytics_graphs/turnitin.php?id={$course->id}
            target=_blank>" . get_string('submissions_turnitin', 'block_analytics_graphs') . "</a>";
        }
        $this->content->text .= "<li> <a href= {$CFG->wwwroot}/blocks/analytics_graphs/hits.php?id={$course->id}
                          target=_blank>" . get_string('hits_distribution', 'block_analytics_graphs') . "</a>";
        $this->content->footer = '<hr/>';
        return $this->content;
    }
}  // Here's the closing bracket for the class definition.
