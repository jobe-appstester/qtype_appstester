<?php

namespace qtype_appstester\checker_definitions\parameters;

interface file_parameter extends parameter
{
    public function get_file_content_from_question_definition(\question_definition $question_definition): string;

    public function get_file_content_from_question_usage_and_step(
        \question_usage_by_activity $question_usage_by_activity,
        \question_attempt_step $question_attempt_step
    ): string;
}