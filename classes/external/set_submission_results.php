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

        $updated_submission_step = new \stdClass();
        $updated_submission_step->id = $id;
        $updated_submission_step->fraction = $fraction;
        $updated_submission_step->state = 'invalid';

        $result_step_data = new \stdClass();
        $result_step_data->attemptstepid = $submission_step->id;
        $result_step_data->name = '-result';
        $result_step_data->value = $result;

        //getting last step by its behaviour var "-finish" to verify that attempt was finished (when regrading, we need to update finishing step manually)
        $finishing_step = $DB->get_record_sql("
            SELECT qas.*
            FROM {question_attempt_steps} qas
            JOIN {question_attempt_step_data} qasd ON qasd.attemptstepid = qas.id
            WHERE qas.questionattemptid = :qa_id
              AND qasd.name = '-finish' 
              AND qasd.value = '1'
        ", ['qa_id' => $submission_step->questionattemptid]);

        $step_with_result = $DB->get_record_sql("
            SELECT qasd.*
            FROM {question_attempt_step_data} qasd
            WHERE qasd.attemptstepid = :subm_id
              AND qasd.name = '-result'
            ORDER BY qasd.id DESC
            LIMIT 1
        ", ['subm_id' => $submission_step->id]);

        if ($step_with_result) {
            $result_step_data->id = $step_with_result->id;
        }

        if ($finishing_step) { // if finishing step is found, usual regrade is happening
            $updated_finishing_step = new \stdClass();
            $updated_finishing_step->id = $finishing_step->id;

            $updated_finishing_step->fraction = max($fraction, $finishing_step->fraction);
            if ($updated_finishing_step->fraction < 0.000001) {
                $updated_finishing_step->state = 'gradedwrong';
            } else if ($updated_finishing_step->fraction > 0.999999) {
                $updated_finishing_step->state = 'gradedright';
            } else {
                $updated_finishing_step->state = 'gradedpartial';
            }

            $DB->update_record(
                'question_attempt_steps',
                $updated_finishing_step
            );
        } else {
            // if finishing step isn't found, verify that the submission doesn't have results yet
            // (attempts from old version plugin don't have any separate finishing step)

            if ($step_with_result) {
                /* if we found submission step WITH results in it but there is no finishing step, 2 things can be happening:
                   1. Regrading of old attempt, which don't have separate finishing step
                      - we should "make" graded finishing step out of submission step, because
                        we can't run process_finish on finished attempts and make a separate finishing step via API.
                   2. Attempt is still in progress (regrading a bunch of attempts)
                      - in this case attempt is saved just as usual, with "invalid" state, but we take max grade from old and new result
                */
                // TODO: check if regrade of old attempt is happening, rather than this is an attempt in progress

                // CASE 1
//                $updated_submission_step->fraction = max($fraction, $submission_step->fraction);
//                if ($updated_submission_step->fraction < 0.000001) {
//                    $updated_submission_step->state = 'gradedwrong';
//                } else if ($updated_submission_step->fraction > 0.999999) {
//                    $updated_submission_step->state = 'gradedright';
//                } else {
//                    $updated_submission_step->state = 'gradedpartial';
//                }

                // CASE 2
                $updated_submission_step->fraction = max($fraction, $submission_step->fraction);
                $updated_submission_step->state = 'invalid';
            } // else test attempt is still active and QBehaviour will take care of grading the step
        }

        $DB->update_record(
            'question_attempt_steps',
            $updated_submission_step
        );

        if(isset($result_step_data->id)) {
            $DB->update_record('question_attempt_step_data', $result_step_data);
        } else {
            $DB->insert_record('question_attempt_step_data', $result_step_data);
        }

        $incheck_step_data = $DB->get_record('question_attempt_step_data', array('attemptstepid' => $submission_step->id, 'name' => '-incheck'));
        if ($incheck_step_data){
            $updated_incheck_step = new \stdClass();
            $updated_incheck_step->id = $incheck_step_data->id;
            $updated_incheck_step->value = 0;
            $DB->update_record('question_attempt_step_data', $updated_incheck_step);
        } else {
            $incheck_step_insert = new \stdClass();
            $incheck_step_insert->attemptstepid = $submission_step->id;
            $incheck_step_insert->name = '-incheck';
            $incheck_step_insert->value = 0;
            $DB->insert_record('question_attempt_step_data', $incheck_step_insert);
        }

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