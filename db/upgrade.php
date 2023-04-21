<?php

require_once(__DIR__.'/common.php');

/**
 * @throws ddl_exception
 */
function xmldb_qtype_appstester_upgrade($oldversion): bool
{
    global $CFG, $DB;
    $db_ensured = ensure_database();
    if (!$db_ensured) {
        return false;
    }
    $dbman = $DB->get_manager();

    if ($oldversion < 2023012700) {
        $table = new xmldb_table('qtype_appstester_quizupdate');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('quiz_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('status', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, 'waiting');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2023012700, 'qtype', 'appstester');
    }

    if ($oldversion < 2023020600) {
        $table = new xmldb_table('qtype_appstester_parameters');
        if ($dbman->table_exists($table)) {
            $field = new xmldb_field('hideresult_whileactive', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, 0);
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
            $field = new xmldb_field('hideresult_afterfinish', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, 0);
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        upgrade_plugin_savepoint(true, 2023020600, 'qtype', 'appstester');
    }

    if ($oldversion < 2023042000) {
        $table = new xmldb_table('qtype_appstester_parameters');
        if ($dbman->table_exists($table)) {
            $field = new xmldb_field('maxbytes', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        upgrade_plugin_savepoint(true, 2023042000, 'qtype', 'appstester');
    }
    return true;
}
