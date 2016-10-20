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
        $course = $this->page->course;
        $context = context_course::instance($course->id);
        $canview = has_capability('block/analytics_graphs:viewpages', $context);
        if (!$canview) {
            return;
        }
        if ($this->content !== null) {
            return $this->content;
        }
        $this->content = new stdClass;
        if (floatval($CFG->release) >= 2.7) {
            $this->content->text = get_string('graphs', 'block_analytics_graphs')
                . "<li> <a href= {$CFG->wwwroot}/blocks/analytics_graphs/grades_chart.php?id={$course->id}
                              target=_blank>" . get_string('grades_chart', 'block_analytics_graphs') . "</a>"
                . "<li> <a href= {$CFG->wwwroot}/blocks/analytics_graphs/graphresourceurl.php?id={$course->id}&legacy=0
                              target=_blank>" . get_string('access_to_contents', 'block_analytics_graphs') . "</a>"
                . "<li> <a href= {$CFG->wwwroot}/blocks/analytics_graphs/assign.php?id={$course->id}
                              target=_blank>" . get_string('submissions_assign', 'block_analytics_graphs') . "</a>"
                . "<li> <a href= {$CFG->wwwroot}/blocks/analytics_graphs/quiz.php?id={$course->id}
                              target=_blank>" . get_string('submissions_quiz', 'block_analytics_graphs') . "</a>"
                . "<li> <a href= {$CFG->wwwroot}/blocks/analytics_graphs/hotpot.php?id={$course->id}
                              target=_blank>" . get_string('submissions_hotpot', 'block_analytics_graphs') . "</a>"
                . "<li> <a href= {$CFG->wwwroot}/blocks/analytics_graphs/hits.php?id={$course->id}&legacy=0
                              target=_blank>" . get_string('hits_distribution', 'block_analytics_graphs') . "</a>";
        } else {
            $this->content->text  = $this->content->text . '<br><br>'.get_string('legacy', 'block_analytics_graphs')
            .  "<li> <a href= {$CFG->wwwroot}/blocks/analytics_graphs/graphresourceurl.php?id={$course->id}&legacy=1
                          target=_blank>" . get_string('access_to_contents', 'block_analytics_graphs') . "</a>"
            . "<li> <a href= {$CFG->wwwroot}/blocks/analytics_graphs/hits.php?id={$course->id}&legacy=1
                          target=_blank>" . get_string('hits_distribution', 'block_analytics_graphs') . "</a>";
        }
        $this->content->footer = '---';
        return $this->content;
    }
}  // Here's the closing bracket for the class definition.
