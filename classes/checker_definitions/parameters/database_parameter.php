<?php

namespace qtype_appstester\checker_definitions\parameters;

interface database_parameter extends plain_parameter
{
    public function get_xmldb_field(): \xmldb_field;
}