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
                qa.id, string_agg(qas.id::character varying, \',\') as attemptstepsids
            FROM {question_attempts} qa
            LEFT JOIN {question_attempt_steps} qas ON qas.questionattemptid = qa.id
            LEFT JOIN {question} q ON qa.questionid = q.id
            WHERE 
                q.qtype = \'appstester\' AND
                qas.state = \'complete\'
            GROUP BY qa.id
        ');

//        $array = new \stdClass();
        $array = [];
        foreach ($submissions as $s) {
//            $array->{$s->id} = ["attemptid"=>$s->id, "stepids"=>array_map('intval', explode(',', $s->attemptstepsids))];
            $array[$s->id] = ["attemptId"=>$s->id, "attemptStepsIds"=>array_map('intval', explode(',', $s->attemptstepsids))];
        }

        return $array;
    }

    public static function get_submissions_to_check_parameters(): \external_function_parameters
    {
        return new \external_function_parameters(
            array()
        );
    }

    public static function get_submissions_to_check_returns(): \external_multiple_structure
    {
        return new \external_multiple_structure(new \external_single_structure(array(
                    "attemptId" => new \external_value(PARAM_INT),
                    "attemptStepsIds" => new \external_multiple_structure(new \external_value(PARAM_INT))
                )
            )
        );
    }
}