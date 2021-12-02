<?php

require_once(__DIR__.'/common.php');

/**
 * @throws ddl_exception
 */
function xmldb_qtype_appstester_install($oldversion): bool
{
    return ensure_database();
}
