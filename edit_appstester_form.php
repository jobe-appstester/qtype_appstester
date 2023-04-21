<?php

use qtype_appstester\checker_definitions\checker_definitions_registry;
use qtype_appstester\checker_definitions\parameters\file_parameter;

defined('MOODLE_INTERNAL') || die();

class qtype_appstester_edit_form extends question_edit_form {
    /**
     * @var qtype_appstester_question
     */
    public $question;

    public function qtype(): string
    {
        return 'appstester';
    }

    /**
     * @param MoodleQuickForm $mform
     */
    protected function definition_inner($mform)
    {
        $qtype = question_bank::get_qtype('appstester');
        $checkers = checker_definitions_registry::get_all_definitions();

        $checkers_selection = array();
        foreach ($checkers as $checker) {
            $checkers_selection[$checker->get_system_name()] = $checker->get_human_readable_name();
        }

        $mform->addElement('header', 'check_options', get_string('check_options', 'qtype_appstester'));
        $mform->addElement('select', 'checker_system_name', get_string('checker_system_name', 'qtype_appstester'), $checkers_selection);

        $mform->addElement('advcheckbox', 'hideresult_whileactive', get_string('hideresult_whileactive', 'qtype_appstester'));
        $mform->setDefault('hideresult_whileactive', 0);

        $mform->addElement('advcheckbox', 'hideresult_afterfinish', get_string('hideresult_afterfinish', 'qtype_appstester'));
        $mform->setDefault('hideresult_afterfinish', 0);

        $mform->addElement('select', 'maxbytes', get_string('maxbytes', 'qtype_appstester'), $qtype->max_file_size_options());
        $mform->setDefault('maxbytes', $this->get_default_value('maxbytes', 0));

        foreach ($checkers as $checker) {
            $teacher_parameters = $checker->get_teacher_parameters();
            foreach ($teacher_parameters as $teacher_parameter) {
                $teacher_parameter->add_parameter_as_form_element($mform);
                $mform->hideIf($teacher_parameter->get_parameter_name(), 'checker', 'neq', $checker->get_system_name());
            }
        }
    }

    protected function data_preprocessing($question)
    {
        $checkers = checker_definitions_registry::get_all_definitions();
        foreach ($checkers as $checker) {
            $teacher_parameters = $checker->get_teacher_parameters();
            foreach ($teacher_parameters as $teacher_parameter) {
                if (!($teacher_parameter instanceof file_parameter)) {
                    continue;
                }

                $parameter_name = $teacher_parameter->get_parameter_name();
                $draft_itemid = file_get_submitted_draft_itemid($parameter_name);

                file_prepare_draft_area(
                    $draft_itemid,
                    $this->context->id,
                    'qtype_appstester',
                    $parameter_name,
                    $question->id
                );
                $question->$parameter_name = $draft_itemid;
            }
        }

        return $question;
    }
}