<?php


namespace qtype_appstester\external;

use qtype_appstester\checker_definitions\android_checker_definition;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/engine/lib.php');

require_once($CFG->dirroot . '/question/type/appstester/classes/checker_definitions/checker_definition.php');
require_once($CFG->dirroot . '/question/type/appstester/classes/checker_definitions/android_checker_definition.php');

class set_submission_results extends \external_api
{
    /**
     * @throws \invalid_parameter_exception
     * @throws \dml_exception
     * @throws \coding_exception
     */
    public static function set_submission_results($id, $result) {
        global $DB;

        self::validate_parameters(self::set_submission_results_parameters(), array('id' => $id, 'result' => $result));

        $submission_step = $DB->get_record_sql('
            SELECT
                qas.*, qa.questionusageid, qa.slot
            FROM {question_attempt_steps} qas
            LEFT JOIN {question_attempts} qa ON qas.questionattemptid = qa.id
            LEFT JOIN {question} q ON qa.questionid = q.id
            WHERE 
                q.qtype = \'appstester\' AND
                qas.state = \'needsgrading\' AND
                qas.id = ' . $id . '
            ORDER BY qas.id
        ', null, MUST_EXIST);

        if ($submission_step === null) {
            throw new \coding_exception('Submission step not found');
        }

        $result_json_data = json_decode($result, true);
        $fraction = (new android_checker_definition())->get_fraction_from_result($result_json_data);

        $updated_step = new \stdClass();
        $updated_step->id = $id;

        if ($fraction === 1) {
            $updated_step->state = 'gradedright';
        } else {
            $updated_step->state = 'invalid';
        }

        $updated_step->fraction = $fraction;

        $DB->update_record(
            'question_attempt_steps',
            $updated_step
        );

        $result_step_data = new \stdClass();
        $result_step_data->attemptstepid = $submission_step->id;
        $result_step_data->name = '-result';
        $result_step_data->value = $result;

        $DB->insert_record('question_attempt_step_data', $result_step_data);

        return true;
    }

    public static function set_submission_results_parameters(): \external_function_parameters
    {
        return new \external_function_parameters(
            array(
                'id' => new \external_value(PARAM_INT),
                'result' => new \external_value(PARAM_RAW)
            )
        );
    }

    public static function set_submission_results_returns(): \external_value
    {
        return new \external_value(PARAM_BOOL);
    }
}