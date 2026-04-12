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
 * Event when a teacher sends email to students via the analytics graphs block.
 *
 * @package    block_analytics_graphs
 * @copyright  2026 Marcelo Augusto Rauh Schmitt <marcelo.schmitt@poa.ifrs.edu.br>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_analytics_graphs\event;

/**
 * The block_analytics_graphs_event_send_email event class.
 *
 * @package    block_analytics_graphs
 * @property-read array $other {
 *      Extra information about event.
 *
 *      - Whenever a teacher sends email from a graph report
 * }
 */
class block_analytics_graphs_event_send_email extends \core\event\base {

    /**
     * Initialise the event data.
     */
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
        $this->data['objecttable'] = 'course';
    }

    /**
     * Returns a localised name for the event.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event_send_email', 'block_analytics_graphs');
    }

    /**
     * Returns a textual description of the event.
     *
     * @return string
     */
    public function get_description() {
        return "User: {$this->userid} - Course: {$this->objectid} - Graph: {$this->other}";
    }

    /**
     * Returns a URL related to the event.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/blocks/analytics_graphs/' . $this->other, [
            'id' => $this->objectid,
            'legacy' => '0',
        ]);
    }
}
