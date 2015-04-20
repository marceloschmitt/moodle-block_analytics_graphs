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

    $dbman = $DB->get_manager(); // loads ddl manager and xmldb classes

    if ($oldversion < 2015042002) {

        // Define table block_analytics_graphs_msg to be created.
        $table = new xmldb_table('block_analytics_graphs_msg');

        // Adding fields to table block_analytics_graphs_msg.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('fromid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('message', XMLDB_TYPE_TEXT, null, null, null, null, null);

        // Adding keys to table block_analytics_graphs_msg.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('fromid', XMLDB_KEY_FOREIGN, array('fromid'), 'user', array('id'));

        // Conditionally launch create table for block_analytics_graphs_msg.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Analytics_graphs savepoint reached.
        upgrade_block_savepoint(true, 2015042002, 'analytics_graphs');
    }
}