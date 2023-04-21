<?php


namespace qtype_appstester\external;

use qtype_appstester\checker_definitions\checker_definition;
use qtype_appstester\checker_definitions\checker_definitions_registry;
use qtype_appstester\checker_definitions\parameters\file_parameter;
use qtype_appstester\checker_definitions\parameters\plain_parameter;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/engine/lib.php');

class get_submission extends \external_api
{
    /**
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \coding_exception
     */
    public static function get_submission($id, $included_file_hashes = ""): array
    {
        global $DB;

        self::validate_parameters(self::get_submission_parameters(), array('id' => $id, 'included_file_hashes' => $included_file_hashes));

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

        $question_usage = \question_engine::load_questions_usage_by_activity($submission_step->questionusageid);
        $question_attempt = $question_usage->get_question_attempt($submission_step->slot);

        $step_iterator = $question_attempt->get_step_iterator();
        while ($step_iterator->current() !== null && $step_iterator->current()->get_id() !== $submission_step->id) {
            $step_iterator->next();
        }

        $question_step = $step_iterator->current();
        if ($question_step === null) {
            throw new \coding_exception('Step not found');
        }

        $question_definition = $question_attempt->get_question();

        $included_file_hashes = explode(',', $included_file_hashes);

        return self::get_result($question_usage, $question_definition, $question_step, $included_file_hashes);
    }

    /**
     * @throws \coding_exception
     */
    private static function get_result(
        \question_usage_by_activity $question_usage_by_activity,
        \question_definition $question_definition,
        \question_attempt_step $question_attempt_step,
        array $included_file_hashes
    ): array
    {
        $checker_system_name = $question_definition->checker_system_name;
        $checker_definition = checker_definitions_registry::get_by_system_name($checker_system_name);

        $result = array('attempt_id' => $question_attempt_step->get_id(), 'checker_system_name' => $checker_system_name);

        $result['parameters'] = self::get_parameters(
            $checker_definition,
            $question_definition,
            $question_attempt_step
        );

        $result['files'] = self::get_files(
            $checker_definition,
            $question_usage_by_activity,
            $question_definition,
            $question_attempt_step,
            $included_file_hashes);

        return $result;
    }

    private static function get_parameters(
        checker_definition $checker_definition,
        \question_definition $question_definition,
        \question_attempt_step $question_attempt_step
    ): array
    {
        $parameters = array('associative' => 1);

        $student_parameters = $checker_definition->get_teacher_parameters();
        foreach ($student_parameters as $teacher_parameter) {
            if (!($teacher_parameter instanceof plain_parameter)) {
                continue;
            }

            $parameter_name = $teacher_parameter->get_parameter_name();
            $parameter_value = $teacher_parameter->get_value_from_question_definition($question_definition);

            $parameters[$parameter_name] = $parameter_value;
        }

        $student_parameters = $checker_definition->get_student_parameters();
        foreach ($student_parameters as $student_parameter) {
            if (!($student_parameter instanceof plain_parameter)) {
                continue;
            }

            $parameter_name = $student_parameter->get_parameter_name();
            $parameter_value = $student_parameter->get_value_from_question_step($question_attempt_step);

            $parameters[$parameter_name] = $parameter_value;
        }

        return $parameters;
    }

    private static function get_files(
        checker_definition $checker_definition,
        \question_usage_by_activity $question_usage_by_activity,
        \question_definition $question_definition,
        \question_attempt_step $question_attempt_step,
        array $included_file_hashes
    ): array
    {
        $files = array('associative' => 1);

        $student_parameters = $checker_definition->get_teacher_parameters();
        foreach ($student_parameters as $teacher_parameter) {
            if (!($teacher_parameter instanceof file_parameter)) {
                continue;
            }

            $parameter_name = $teacher_parameter->get_parameter_name();
            $parameter_value = $teacher_parameter->get_file_content_from_question_definition($question_definition);

            if ($parameter_value === "") {
                $files[$parameter_name . '_hash'] = "";
            } else {
                $file_content_hash = sha1($parameter_value);
                $files[$parameter_name . '_hash'] = $file_content_hash;

                if (in_array($file_content_hash, $included_file_hashes, true)) {
                    $files[$parameter_name] = base64_encode($parameter_value);
                }
            }
        }

        $student_parameters = $checker_definition->get_student_parameters();
        foreach ($student_parameters as $student_parameter) {
            if (!($student_parameter instanceof file_parameter)) {
                continue;
            }

            $parameter_name = $student_parameter->get_parameter_name();
            $parameter_value = $student_parameter->get_file_content_from_question_usage_and_step(
                $question_usage_by_activity,
                $question_attempt_step
            );

            if ($parameter_value === "") {
                $files[$parameter_name . '_hash'] = "";
            } else {
                $file_content_hash = sha1($parameter_value);
                $files[$parameter_name . '_hash'] = $file_content_hash;

                if (in_array($file_content_hash, $included_file_hashes, true)) {
                    $files[$parameter_name] = base64_encode($parameter_value);
                }
            }
        }

        return $files;
    }

    public static function get_submission_returns(): \external_single_structure
    {
        $checker_definitions = checker_definitions_registry::get_all_definitions();

        $parameters = array();
        $files = array();

        foreach ($checker_definitions as $checker_definition) {
            $definition_parameters = array_merge(
                $checker_definition->get_student_parameters(),
                $checker_definition->get_teacher_parameters()
            );
            foreach ($definition_parameters as $definition_parameter) {
                if ($definition_parameter instanceof plain_parameter) {
                    $parameters[$definition_parameter->get_parameter_name()] = new \external_value(
                        $definition_parameter->get_param_type(),
                        $definition_parameter->get_human_readable_name(),
                        VALUE_OPTIONAL
                    );
                }

                if ($definition_parameter instanceof file_parameter) {
                    $files[$definition_parameter->get_parameter_name()] = new \external_value(
                        PARAM_TEXT,
                        $definition_parameter->get_human_readable_name(),
                        VALUE_OPTIONAL
                    );

                    $files[$definition_parameter->get_parameter_name() . '_hash'] = new \external_value(
                        PARAM_TEXT,
                        $definition_parameter->get_human_readable_name(),
                        VALUE_OPTIONAL
                    );
                }
            }
        }

        return new \external_single_structure(
            array(
                'attempt_id' => new \external_value(PARAM_INT),
                'checker_system_name' => new \external_value(PARAM_TEXT),
                'parameters' => new \external_single_structure(
                    array_merge(
                        array(
                            'associative' => new \external_value(PARAM_INT),
                        ),
                        $parameters
                    )
                ),
                'files' => new \external_single_structure(
                    array_merge(
                        array(
                            'associative' => new \external_value(PARAM_INT),
                        ),
                        $files
                    )
                )
            )
        );
    }

    public static function get_submission_parameters(): \external_function_parameters
    {
        return new \external_function_parameters(
            array(
                'id' => new \external_value(PARAM_INT),
                'included_file_hashes' => new \external_value(PARAM_TEXT, "", VALUE_OPTIONAL, "")
            )
        );
    }
}