<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/analytics_graphs/lib.php');

/**
 * Form for editing lock instances.
 *
 * @package    block_analytics_graphs
 * @copyright  2024 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_analytics_graphs_edit_form extends block_edit_form {

    /**
     * Block config form definition.
     *
     * @param \MoodleQuickForm $mform Block form.
     *
     * @return void
     */
    protected function specific_definition($mform) {
        // Adding a new "only active" setting only of allowed for individual blocks.
        if (get_config('block_analytics_graphs', 'overrideonlyactive')) {
            $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));
            $mform->addElement('advcheckbox', 'config_onlyactive', get_string('settings:onlyactive', 'block_analytics_graphs'));
            $mform->addHelpButton('config_onlyactive', 'settings:onlyactive', 'block_analytics_graphs');
            $mform->setDefault('config_onlyactive', (bool) get_config('block_analytics_graphs', 'onlyactive'));
        }
    }

    /**
     * Display the configuration form when block is being added to the page
     *
     * @return bool
     */
    public static function display_form_when_adding(): bool {
        // Makes sense to display block's setting when adding a block only if allowed extra setting.
        return (bool) get_config('block_analytics_graphs', 'overrideonlyactive');
    }

    /**
     * Process the form submission, used if form was submitted via AJAX
     */
    public function process_dynamic_submission() {
        parent::process_dynamic_submission();

        // If allowed to have 'only active' setting per block,
        // then we would like to update it based on the curren block data.
        if (get_config('block_analytics_graphs', 'overrideonlyactive')) {
            $needupdate = false;
            $onlyactivecourses = block_analytics_graphs_get_onlyactivecourses();
            $courseid = $this->page->course->id;
            $data = $this->get_data();

            if(!empty($data->config_onlyactive)) {
                if (!in_array($courseid, $onlyactivecourses)) {
                    $onlyactivecourses[] = $courseid;
                    $needupdate = true;
                }
            } else {
                if (($key = array_search($courseid, $onlyactivecourses)) !== false) {
                    unset($onlyactivecourses[$key]);
                    $needupdate = true;
                }
            }

            // Conditionally save the settings only if updated.
            if ($needupdate) {
                set_config('onlyactivecourses', implode(',', $onlyactivecourses), 'block_analytics_graphs');
            }
        }
    }
}
