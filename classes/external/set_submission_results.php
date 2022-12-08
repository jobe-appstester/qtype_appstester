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
                qas.state = \'complete\' AND
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
        $updated_step->fraction = $fraction;
        $updated_step->state = 'invalid';

        //getting last step by its behaviour var to verify that attempt was finished (when regrading, we need to update finishing step manually)
        $finishing_step = $DB->get_record_sql("
            SELECT qas.*
            FROM mdl_question_attempt_steps qas
            JOIN mdl_question_attempt_step_data qasd ON qasd.attemptstepid = qas.id
            WHERE qas.questionattemptid = :qa_id
              AND qasd.name = '-finish' 
              AND qasd.value = '1'
        ", ['qa_id' => $submission_step->questionattemptid]);

        if ($finishing_step) {
            $updated_finishing_step = new \stdClass();
            $updated_finishing_step->id = $finishing_step->id;
            if ($fraction == 1.0) {
                $updated_finishing_step->state = 'gradedright';
            } else if ($fraction == 0.0) {
                $updated_finishing_step->state = 'gradedwrong';
            } else {
                $updated_finishing_step->state = 'gradedpartial';
            }
            $updated_finishing_step->fraction = $fraction;

            $DB->update_record(
                'question_attempt_steps',
                $updated_finishing_step
            );
        } else { // if finishing step is not found, verify that test is NOT active (regrading for old attempts without any separate finishing step)

            $step_with_result = $DB->get_record('question_attempt_step_data', ['attemptstepid' => $submission_step->id, 'name' => '-result']);

            if ($step_with_result) {
                // if we found step data with results in it, regrading is happening,
                // and we should "make" graded finishing step out of submission step.
                // We can't run process_finish on finished attempts and make a separate finishing step.
                if ($fraction == 1.0) {
                    $updated_step->state = 'gradedright';
                } else if ($fraction == 0.0) {
                    $updated_step->state = 'gradedwrong';
                } else {
                    $updated_step->state = 'gradedpartial';
                }
            } // else test attempt is still active and QBehaviour will take care of grading the step
        }

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