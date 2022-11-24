<?php

use qtype_appstester\checker_definitions\checker_definitions_registry;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/behaviour/appstester/behaviour.php');

class qtype_appstester_question extends question_graded_automatically
{
    /**
     * @param array $response
     * @return array
     */
    public function grade_response(array $response): array
    {
        $fraction = 0;
        $state = question_state::$complete;
        return array($fraction, $state);
    }

    public function get_expected_data(): array
    {
        $checker_system_name = $this->checker_system_name;
        $checker = checker_definitions_registry::get_by_system_name($checker_system_name);

        $expected_data = array();

        foreach ($checker->get_student_parameters() as $student_parameter) {
            $expected_data[$student_parameter->get_parameter_name()] = $student_parameter->get_param_type();
        }

        return $expected_data;
    }

    public function get_correct_response()
    {
    }

    /**
     * @param array $response
     * @return bool
     */
    public function is_complete_response(array $response): bool
    {
        return true;
    }

    /**
     * @param array $prevresponse
     * @param array $newresponse
     * @return false
     */
    public function is_same_response(array $prevresponse, array $newresponse): bool
    {
        return false;
    }

    public function summarise_response(array $response): string
    {
        return 'решение задачи';
    }

    public function get_validation_error(array $response)
    {
    }

    /**
     * @throws coding_exception
     */
    public function make_behaviour(question_attempt $qa, $preferredbehaviour): qbehaviour_appstester
    {
        return new qbehaviour_appstester($qa, $preferredbehaviour);
    }

    public function check_file_access($qa, $options, $component, $filearea, $args, $forcedownload): bool
    {
        return true;
    }
}