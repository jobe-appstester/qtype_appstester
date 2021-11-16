<?php

namespace qtype_appstester\checker_definitions\parameters;

use MoodleQuickForm;

class string_parameter extends base_parameter implements database_parameter
{

    public function add_parameter_as_form_element(MoodleQuickForm $form)
    {
        $form->addElement('text', $this->get_parameter_name(), $this->get_human_readable_name());
    }

    public function get_parameter_as_html_element(
        \moodle_page $moodle_page,
        \question_attempt $question_attempt,
        \question_display_options $question_display_options
    ): string
    {
        return '';
    }

    public function get_param_type(): string
    {
        return PARAM_TEXT;
    }

    public function get_value_from_question_definition(\question_definition $question_definition)
    {
        $parameter_name = $this->get_parameter_name();
        return $question_definition->$parameter_name;
    }

    public function get_value_from_question_step(\question_attempt_step $question_step)
    {
        $parameter_name = $this->get_parameter_name();
        $question_step->get_qt_var($parameter_name);
    }

    public function get_xmldb_field(): \xmldb_field
    {
        return new \xmldb_field($this->get_parameter_name(), XMLDB_TYPE_TEXT);
    }

}