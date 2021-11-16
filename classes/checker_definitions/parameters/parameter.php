<?php

namespace qtype_appstester\checker_definitions\parameters;

use MoodleQuickForm;

interface parameter
{
    public function get_parameter_name(): string;

    public function get_human_readable_name(): string;

    public function add_parameter_as_form_element(MoodleQuickForm $form);

    public function get_parameter_as_html_element(
        \moodle_page $moodle_page,
        \question_attempt $question_attempt,
        \question_display_options $question_display_options
    ): string;

    public function get_param_type(): string;
}