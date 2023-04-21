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
        $_question = $qa->get_question();
        $_state = $qa->get_state();
        $render_result = true;
        if (!has_capability('moodle/grade:viewhidden', $options->context) &&
                (($_state->is_active() && $_question->hideresult_whileactive)
                || ($_state->is_finished() && $_question->hideresult_afterfinish))
        ) {
            $render_result = false;
        }

        $checker_system_name = $qa->get_question()->checker_system_name;
        $checker = checker_definitions_registry::get_by_system_name($checker_system_name);

        if ($_state->is_active()) {
            $qa_step = $qa->get_last_step();
            if ($qa_step->has_behaviour_var('result')) {
                if ($render_result) {
                    $result = json_decode($qa->get_last_step()->get_qt_var('-result'), true);
                    return $checker->render_result_feedback($result);
                } else {
                    return get_string('results_are_hidden', 'qtype_appstester')
                         . get_string('app_is_tested', 'qtype_appstester');
                }
            }

            if ($qa_step->has_behaviour_var('status')) {
                $status = json_decode($qa->get_last_step()->get_qt_var('-status'), true);
                return $checker->render_status_feedback($status);
            }

            // No status or result means server didn't acknowledge this attempt yet
            if ($_state === question_state::$invalid) {
                return get_string('submission_is_in_queue', 'qtype_appstester');
            }
        } else {
            $laststepwithresult = $qa->get_last_step_with_behaviour_var('result');
            $laststepwithstatus = $qa->get_last_step_with_behaviour_var('status');

            if ($laststepwithresult === $laststepwithstatus) { // if "result" and "status" steps are the same, last step is already checked, we can render the latest result
                if ($laststepwithresult->get_state() !== question_state::$unprocessed) {
                    if ($render_result) {
                        $result = json_decode($laststepwithresult->get_qt_var('-result'), true);
                        return $checker->render_result_feedback($result);
                    } else {
                        return get_string('results_are_hidden', 'qtype_appstester')
                             . get_string('app_is_tested', 'qtype_appstester');
                    }
                }
            } else { // else "status" step should be the latest step, which is waiting for results, so we render status feedback
                if ($laststepwithstatus->get_state() !== question_state::$unprocessed) {
                    $status = json_decode($laststepwithstatus->get_qt_var('-status'), true);
                    return $checker->render_status_feedback($status);
                }
            }
        }

        return '';
    }
}