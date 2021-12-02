<?php

/**
 * @throws ddl_exception
 */
function xmldb_qtype_appstester_uninstall($oldversion): bool
{
    global $DB;

    $db_manager = $DB->get_manager();

    if ($db_manager->table_exists('qtype_appstester_parameters')) {
        $db_manager->drop_table(new xmldb_table('qtype_appstester_parameters'));
    }

    return true;
}
