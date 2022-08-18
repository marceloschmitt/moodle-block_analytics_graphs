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

namespace block_analytics_graphs\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\writer;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;

/**
 * Privacy Subsystem implementation for block_analytics_graphs.
 *
 * @package    block_analytics_graphs
 * @author     Dmitrii Metelkin <dmitriim@catalyst-au.net>
 * @copyright  2022 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {


    /**
     * Returns metadata.
     *
     * @param \core_privacy\local\metadata\collection $collection
     * @return \core_privacy\local\metadata\collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'block_analytics_graphs_msg',
            [
                'fromid' => 'privacy:metadata:block_analytics_graphs_msg:fromid',
                'subject' => 'privacy:metadata:block_analytics_graphs_msg:subject',
            ],
            'privacy:metadata:block_analytics_graphs_msg'
        );

        $collection->add_database_table(
            'block_analytics_graphs_dest',
            [
                'toid' => 'privacy:metadata:block_analytics_graphs_dest:toid',
                'messageid' => 'privacy:metadata:block_analytics_graphs_dest:messageid',
            ],
            'privacy:metadata:block_analytics_graphs_dest'
        );

        return  $collection;
    }

    /**
     * Gets context for provided user ID.
     *
     * @param int $userid User ID.
     * @return \core_privacy\local\request\contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        $sql = "SELECT c.id
                  FROM {context} ctx
            INNER JOIN {course} c ON c.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
            INNER JOIN {block_analytics_graphs_msg} agm ON agm.courseid = c.id
                 WHERE agm.fromid = :userid
        ";

        $contextlist->add_from_sql($sql, [
            'contextlevel' => CONTEXT_COURSE,
            'userid'  => $userid,
        ]);

        $sql = "SELECT c.id
                  FROM {context} ctx
            INNER JOIN {course} c ON c.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
            INNER JOIN {block_analytics_graphs_msg} agm ON agm.courseid = c.id
            INNER JOIN {block_analytics_graphs_dest} agd ON agd.messageid = agm.id
                 WHERE agd.toid = :userid
        ";

        $contextlist->add_from_sql($sql, [
            'contextlevel' => CONTEXT_COURSE,
            'userid'  => $userid,
        ]);

        return $contextlist;
    }

    /**
     * Get users in the provided context.
     *
     * @param \core_privacy\local\request\userlist $userlist
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!$context instanceof \context_course) {
            return;
        }

        $params = ['courseid' => $context->instanceid];

        $sql = "SELECT fromid FROM {block_analytics_graphs_msg} WHERE courseid = :courseid";
        $userlist->add_from_sql('fromid', $sql, $params);

        $sql = "SELECT agd.toid
                  FROM {block_analytics_graphs_dest} agd
            INNER JOIN {block_analytics_graphs_msg} agm ON agd.messageid = agm.id
                 WHERE agm.courseid = :courseid";
        $userlist->add_from_sql('toid', $sql, $params);
    }

    /**
     * Export user data in the provided context.
     *
     * @param \core_privacy\local\request\approved_contextlist $contextlist
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $user = $contextlist->get_user();

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel === CONTEXT_COURSE) {
                // Data export is organised in: {Context}/{Plugin Name}/{Table name}/{index}/data.json.
                $courseids[] = $context->instanceid;

                // Messages.
                $messages = $DB->get_records('block_analytics_graphs_msg', [
                    'courseid' => $context->instanceid,
                    'fromid' => $user->id,
                ]);
                $index = 0;
                foreach ($messages as $message) {
                    // Data export is organised in: {Context}/{Plugin Name}/{Table name}/{index}/data.json.
                    $index++;
                    $subcontext = [
                        get_string('pluginname', 'block_analytics_graphs'),
                        'block_analytics_graphs_msg',
                        $index
                    ];

                    $data = (object) [
                        'fromid' => $message->fromid,
                        'subject' => $message->subject,
                    ];

                    writer::with_context($context)->export_data($subcontext, $data);
                }

                // Destinations.
                $sql = "SELECT agd.toid, agd.messageid
                          FROM {block_analytics_graphs_dest} agd
                    INNER JOIN {block_analytics_graphs_msg} agm ON agd.messageid = agm.id
                         WHERE agm.courseid = :courseid AND agd.toid = :userid";

                $messages = $DB->get_records_sql($sql, [
                    'courseid' => $context->instanceid,
                    'userid' => $user->id,
                ]);
                $index = 0;
                foreach ($messages as $message) {
                    // Data export is organised in: {Context}/{Plugin Name}/{Table name}/{index}/data.json.
                    $index++;
                    $subcontext = [
                        get_string('pluginname', 'block_analytics_graphs'),
                        'block_analytics_graphs_dest',
                        $index
                    ];

                    $data = (object) [
                        'toid' => $message->toid,
                        'messageid' => $message->messageid,
                    ];

                    writer::with_context($context)->export_data($subcontext, $data);
                }
            }
        }
    }

    /**
     * Delete data for all users in the provided context.
     *
     * @param \context $context
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if ($context->contextlevel !== CONTEXT_COURSE) {
            return;
        }

        $messageids = $DB->get_field('block_analytics_graphs_msg', 'id', ['courseid' => $context->instanceid]);
        list($insql, $inparams) = $DB->get_in_or_equal($messageids);

        $DB->delete_records_select('block_analytics_graphs_dest',  " messageid $insql", $inparams);
        $DB->delete_records('block_analytics_graphs_msg', ['courseid' => $context->instanceid]);
    }

    /**
     * Delete data for user.
     *
     * @param \core_privacy\local\request\approved_contextlist $contextlist
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        // If the user has data, then only the User context should be present so get the first context.
        $contexts = $contextlist->get_contexts();
        if (count($contexts) == 0) {
            return;
        }

        $userid = $contextlist->get_user()->id;

        $DB->set_field_select('block_analytics_graphs_dest', 'toid', 0, "toid = :toid", ['toid' => $userid]);
        $DB->set_field_select('block_analytics_graphs_msg', 'fromid', 0, "fromid = :fromid", ['fromid' => $userid]);
    }

    /**
     * Delete data for users.
     *
     * @param \core_privacy\local\request\approved_userlist $userlist
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();

        if ($context->contextlevel !== CONTEXT_COURSE) {
            return;
        }

        $userids = $userlist->get_userids();
        list($insql, $inparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);

        $DB->set_field_select('block_analytics_graphs_dest', 'toid', 0, "toid {$insql}", $inparams);
        $DB->set_field_select('block_analytics_graphs_msg', 'fromid', 0, "fromid {$insql}", $inparams);
    }
}
