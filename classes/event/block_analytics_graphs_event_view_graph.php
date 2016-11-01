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
 * The EVENTNAME event.
 *
 * @package    analytics_graphs
 * @copyright  2014 Marcelo Augusto Rauh Schmitt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace block_analytics_graphs\event;
defined('MOODLE_INTERNAL') || die();
/**
 * The block_analytics_graphs_event_view_graph event class.
 *
 * @property-read array $other {
 *      Extra information about event.
 *
 *      - Whenever a teacher views a graph
 * }
 *
 * @since     Moodle MOODLEVERSION
 * @copyright 2014 YOUR NAME
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/
class block_analytics_graphs_event_view_graph extends \core\event\base {
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
        $this->data['objecttable'] = 'course';
    }

    public static function get_name() {
        return get_string('event_view_graph', 'block_analytics_graphs');
    }

    public function get_description() {
        return "User: {$this->userid} - Course: {$this->objectid} - Graph: {$this->other}";
    }

    public function get_url() {
        return new \moodle_url('/blocks/analytics_graphs/' . $this->other, array('id' => $this->objectid, 'legacy' => '0'));
    }

}