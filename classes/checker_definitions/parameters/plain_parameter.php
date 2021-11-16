<?php


namespace qtype_appstester\checker_definitions\parameters;


interface plain_parameter extends parameter
{
    public function get_value_from_question_definition(\question_definition $question_definition);
    public function get_value_from_question_step(\question_attempt_step $question_step);
}