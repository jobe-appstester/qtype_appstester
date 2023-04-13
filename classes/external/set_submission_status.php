<?php


namespace qtype_appstester\external;

use qtype_appstester\checker_definitions\android_checker_definition;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/engine/lib.php');

require_once($CFG->dirroot . '/question/type/appstester/classes/checker_definitions/checker_definition.php');
require_once($CFG->dirroot . '/question/type/appstester/classes/checker_definitions/android_checker_definition.php');

class set_submission_status extends \external_api
{
    /**
     * @throws \invalid_parameter_exception
     * @throws \dml_exception
     * @throws \coding_exception
     */
    public static function set_submission_status($id, $status) {
        global $DB;

        self::validate_parameters(self::set_submission_status_parameters(), array('id' => $id, 'status' => $status));

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

        $status_count = $DB->count_records('question_attempt_step_data', array('attemptstepid' => $submission_step->id, 'name' => '-status'));
        if ($status_count === 0) {
            $status_step_data = new \stdClass();
            $status_step_data->attemptstepid = $submission_step->id;
            $status_step_data->name = '-status';
            $status_step_data->value = $status;

            $DB->insert_record('question_attempt_step_data', $status_step_data);
        } else {
            $submission_step_data = $DB->get_record('question_attempt_step_data', array('attemptstepid' => $submission_step->id, 'name' => '-status'));

            $status_step_update_data = new \stdClass();
            $status_step_update_data->id = $submission_step_data->id;
            $status_step_update_data->value = $status;

            $DB->update_record('question_attempt_step_data', $status_step_update_data);
        }

        $incheck_count = $DB->count_records('question_attempt_step_data', array('attemptstepid' => $submission_step->id, 'name' => '-incheck'));
        if ($incheck_count === 0) {
            $incheck_step_insert = new \stdClass();
            $incheck_step_insert->attemptstepid = $submission_step->id;
            $incheck_step_insert->name = '-incheck';
            $incheck_step_insert->value = 1;
            $DB->insert_record('question_attempt_step_data', $incheck_step_insert);
        } else {
            $incheck_step_data = $DB->get_record('question_attempt_step_data', array('attemptstepid' => $submission_step->id, 'name' => '-incheck'));
            $updated_incheck_step = new \stdClass();
            $updated_incheck_step->id = $incheck_step_data->id;
            $updated_incheck_step->value = 1;
            $DB->update_record('question_attempt_step_data', $updated_incheck_step);
        }

        return true;
    }

    public static function set_submission_status_parameters() {
        return new \external_function_parameters(
            array(
                'id' => new \external_value(PARAM_INT),
                'status' => new \external_value(PARAM_RAW)
            )
        );
    }

    public static function set_submission_status_returns() {
        return new \external_value(PARAM_BOOL);
    }
}