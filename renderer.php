<?php

use qtype_appstester\checker_definitions\checker_definitions_registry;

require_once($CFG->dirroot . '/question/type/appstester/classes/checker_definitions/checker_definition.php');
require_once($CFG->dirroot . '/question/type/appstester/classes/checker_definitions/android_checker_definition.php');
require_once($CFG->dirroot . '/lib/form/filemanager.php');

class qtype_appstester_renderer extends qtype_renderer {
    /**
     * @throws coding_exception
     */
    public function formulation_and_controls(question_attempt $qa, question_display_options $options)
    {
        $checker_system_name = $qa->get_question()->checker_system_name;
        $checker = checker_definitions_registry::get_by_system_name($checker_system_name);

        $result_html = parent::formulation_and_controls($qa, $options);

        foreach ($checker->get_student_parameters() as $student_parameter) {
            $result_html .= $student_parameter->get_parameter_as_html_element($this->page, $qa, $options);
        }

        return $result_html;
    }

    /**
     * @throws coding_exception
     */
    public function feedback(question_attempt $qa, question_display_options $options): string
    {
        $checker_system_name = $qa->get_question()->checker_system_name;
        $checker = checker_definitions_registry::get_by_system_name($checker_system_name);

        if ($qa->get_state()->is_active()) {
            if ($qa->get_last_step()->has_behaviour_var('result')) {
                $result = json_decode($qa->get_last_step()->get_qt_var('-result'), true);
                return $checker->render_result_feedback($result);
            }

            if ($qa->get_last_step()->has_behaviour_var('status')) {
                $status = json_decode($qa->get_last_step()->get_qt_var('-status'), true);
                return $checker->render_status_feedback($status);
            }
        } else {
            $laststepwithresult = $qa->get_last_step_with_behaviour_var('result');
            if ($laststepwithresult->get_state() !== question_state::$unprocessed) {
                $result = json_decode($laststepwithresult->get_qt_var('-result'), true);
                return $checker->render_result_feedback($result);
            }

            $laststepwithstatus= $qa->get_last_step_with_behaviour_var('status');
            if ($laststepwithstatus->get_state() !== question_state::$unprocessed) {
                $status = json_decode($laststepwithstatus->get_qt_var('-status'), true);
                return $checker->render_status_feedback($status);
            }
        }

        return '';
    }
}