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


function xmldb_block_analytics_graphs_upgrade($oldversion, $block) {
    global $CFG, $DB;

    $dbman = $DB->get_manager(); // Loads ddl manager and xmldb classes.

    if ($oldversion < 2015042003) {
                // Define table block_analytics_graphs_msg to be created.
        $table = new xmldb_table('block_analytics_graphs_msg');

        // Adding fields to table block_analytics_graphs_msg.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('fromid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('subject', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('message', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table block_analytics_graphs_msg.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('fromid', XMLDB_KEY_FOREIGN, array('fromid'), 'user', array('id'));
        $table->add_key('courseid', XMLDB_KEY_FOREIGN, array('courseid'), 'course', array('id'));

        // Conditionally launch create table for block_analytics_graphs_msg.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table block_analytics_graphs_dest to be created.
        $table = new xmldb_table('block_analytics_graphs_dest');

        // Adding fields to table block_analytics_graphs_dest.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('messageid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('toid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        // Adding keys to table block_analytics_graphs_dest.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('messageid', XMLDB_KEY_FOREIGN, array('messageid'), 'block_analytics_graphs_msg', array('id'));
        $table->add_key('toid', XMLDB_KEY_FOREIGN, array('toid'), 'user', array('id'));

        // Conditionally launch create table for block_analytics_graphs_dest.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
    } else if ($oldversion < 2015051302) {
        $table = new xmldb_table('block_analytics_graphs_msg');

        // Define field courseid to be added to block_analytics_graphs_msg.
        $field = new xmldb_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '1', 'message');
        // Conditionally launch add field courseid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
            $key = new xmldb_key('courseid', XMLDB_KEY_FOREIGN, array('courseid'), 'course', array('id'));
            $dbman->add_key($table, $key);
        }

        // Define field timecreated to be added to block_analytics_graphs_msg.
        $field = new xmldb_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, '0', 'courseid');
        // Conditionally launch add field timecreated.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
            $index = new xmldb_index('timecreated', XMLDB_INDEX_NOTUNIQUE, array('timecreated'));
            if (!$dbman->index_exists($table, $index)) {
                $dbman->add_index($table, $index);
            }
        }
    }
    // Analytics_graphs savepoint reached.
    upgrade_block_savepoint(true, 2016102101, 'analytics_graphs');
}