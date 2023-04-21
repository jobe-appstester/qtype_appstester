<?php

use qtype_appstester\checker_definitions\android_checker_definition;
use qtype_appstester\checker_definitions\checker_definitions_registry;
use qtype_appstester\checker_definitions\parameters\database_parameter;
use qtype_appstester\checker_definitions\parameters\file_parameter;

class qtype_appstester extends question_type
{

    public function extra_question_fields(): array
    {
        $fields = array(
            'qtype_appstester_parameters',
            'checker_system_name',
            'hideresult_whileactive',
            'hideresult_afterfinish',
            'maxbytes',
        );

        $checkers = checker_definitions_registry::get_all_definitions();
        foreach ($checkers as $checker) {
            $teacher_parameters = $checker->get_teacher_parameters();
            foreach ($teacher_parameters as $teacher_parameter) {
                if (!($teacher_parameter instanceof database_parameter)) {
                    continue;
                }

                $parameter_name = $teacher_parameter->get_parameter_name();
                $fields[] = $parameter_name;
            }
        }

        return $fields;
    }

    public function save_question_options($question)
    {
        parent::save_question_options($question);

        $checkers = checker_definitions_registry::get_all_definitions();
        foreach ($checkers as $checker) {
            $teacher_parameters = $checker->get_teacher_parameters();
            foreach ($teacher_parameters as $teacher_parameter) {
                if (!($teacher_parameter instanceof file_parameter)) {
                    continue;
                }

                $parameter_name = $teacher_parameter->get_parameter_name();
                file_save_draft_area_files(
                    $question->$parameter_name,
                    $question->context->id,
                    'qtype_appstester',
                    $parameter_name,
                    (int)$question->id,
                    $this->fileoptions
                );
            }
        }
    }

    /**
     * Return array of the choices that should be offered for the maximum file sizes.
     * @return array|lang_string[]|string[]
     */
    public function max_file_size_options() {
        global $CFG, $COURSE;
        return get_max_upload_sizes($CFG->maxbytes, $COURSE->maxbytes,0, array('31457280', '41943040'));
    }

    public function move_files($questionid, $oldcontextid, $newcontextid)
    {
        parent::move_files($questionid, $oldcontextid, $newcontextid);

        $fs = get_file_storage();

        $checkers = array(new android_checker_definition());
        foreach ($checkers as $checker) {
            $teacher_parameters = $checker->get_teacher_parameters();
            foreach ($teacher_parameters as $teacher_parameter) {
                if (!($teacher_parameter instanceof file_parameter)) {
                    continue;
                }

                $parameter_name = $teacher_parameter->get_parameter_name();
                $fs->move_area_files_to_new_context(
                    $oldcontextid, $newcontextid, 'qtype_appstester', $parameter_name, $questionid);
            }
        }
    }

    public function response_file_areas(): array
    {
        $file_areas = array();

        $checkers = checker_definitions_registry::get_all_definitions();
        foreach ($checkers as $checker) {
            $student_parameters = $checker->get_student_parameters();
            foreach ($student_parameters as $student_parameter) {
                if (!($student_parameter instanceof file_parameter)) {
                    continue;
                }

                $parameter_name = $student_parameter->get_parameter_name();
                $file_areas[] = $parameter_name;
            }
        }

        return $file_areas;
    }
}