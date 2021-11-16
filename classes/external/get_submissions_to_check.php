<?php


namespace qtype_appstester\external;

defined('MOODLE_INTERNAL') || die();

class get_submissions_to_check extends \external_api {
    /**
     * @throws \dml_exception
     */
    public static function get_submissions_to_check() {
        global $DB;

        $submissions = $DB->get_records_sql('
            SELECT
                qas.id
            FROM {question_attempt_steps} qas
            LEFT JOIN {question_attempts} qa ON qas.questionattemptid = qa.id
            LEFT JOIN {question} q ON qa.questionid = q.id
            WHERE 
                q.qtype = \'appstester\' AND
                qas.state = \'needsgrading\'
        ');

        return array_keys($submissions);
    }

    public static function get_submissions_to_check_parameters(): \external_function_parameters
    {
        return new \external_function_parameters(
            array()
        );
    }

    public static function get_submissions_to_check_returns(): \external_multiple_structure
    {
        return new \external_multiple_structure(new \external_value(PARAM_INT));
    }
}