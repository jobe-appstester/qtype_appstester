<?php

use qtype_appstester\checker_definitions\checker_definitions_registry;
use qtype_appstester\checker_definitions\parameters\database_parameter;

/**
 * @throws ddl_exception
 */
function ensure_database(): bool
{
    global $DB;

    $db_manager = $DB->get_manager();

    if (!$db_manager->table_exists('qtype_appstester_parameters')) {
        $table = new xmldb_table('qtype_appstester_parameters');

        $table->add_field('id', XMLDB_TYPE_INTEGER, 10, null, true, true);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        $table->add_field('questionid', XMLDB_TYPE_INTEGER, 10, null, true, false);
        $table->add_key('questionid', XMLDB_KEY_FOREIGN, array('questionid'), 'question', array('id'));

        $table->add_field('checker_system_name', XMLDB_TYPE_TEXT, null, null, true);

        $db_manager->create_table($table);
    }

    $checkers = checker_definitions_registry::get_all_definitions();
    foreach ($checkers as $checker) {
        $teacher_parameters = $checker->get_teacher_parameters();
        foreach ($teacher_parameters as $teacher_parameter) {
            if (!($teacher_parameter instanceof database_parameter)) {
                continue;
            }

            if ($db_manager->field_exists('qtype_appstester_parameters', $teacher_parameter->get_parameter_name())) {
                continue;
            }

            $db_manager->add_field(new xmldb_table('qtype_appstester_parameters'), $teacher_parameter->get_xmldb_field());
        }

        $columns = $DB->get_columns('qtype_appstester_parameters');
        foreach ($columns as $column) {
            $should_drop_field = false;

            if ($column->name == 'id'
                || $column->name == 'questionid'
                || $column->name == 'checker_system_name'
//                || $column->name == 'hide_test_results'
            )
                continue;

            foreach ($teacher_parameters as $teacher_parameter) {
                if (!($teacher_parameter instanceof database_parameter)) {
                    continue;
                }

                if ($column->name == $teacher_parameter->get_parameter_name())
                    $should_drop_field = false;
            }

            if ($should_drop_field) {
                $db_manager->drop_field(new xmldb_table('qtype_appstester_parameters'), new xmldb_field($column->name));
            }
        }
    }

    return true;
}
