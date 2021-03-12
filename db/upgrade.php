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
* @package    local_komettranslator
* @copyright  2020 Center for Learningmanagement (https://www.lernmanagement.at)
* @author     Robert Schrenk
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

defined('MOODLE_INTERNAL') || die;

function xmldb_local_komettranslator_upgrade($oldversion=0) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2021031200) {
        $table = new xmldb_table('local_komettranslator');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('type', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('sourceid', XMLDB_TYPE_CHAR, '200', null, XMLDB_NOTNULL, null, null);
        $table->add_field('itemid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('internalid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table local_komettranslator.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Adding indexes to table local_komettranslator.
        $table->add_index('idx_sourceid', XMLDB_INDEX_NOTUNIQUE, ['sourceid']);
        $table->add_index('idx_sourceid_itemid', XMLDB_INDEX_NOTUNIQUE, ['sourceid', 'itemid']);

        // Conditionally launch create table for local_komettranslator.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        upgrade_plugin_savepoint(true, 2021031200, 'local', 'komettranslator');
    }

    return true;
}
