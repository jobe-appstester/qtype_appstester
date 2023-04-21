<?php

namespace qtype_appstester\checker_definitions\parameters;

use form_filemanager;
use html_writer;
use MoodleQuickForm;
use question_display_options;
use stdClass;

class single_file_parameter extends base_parameter implements file_parameter
{
    /**
     * @var array
     */
    private $accepted_types;

    public function __construct(string $system_name, string $human_readable_name, array $accepted_types)
    {
        parent::__construct($system_name, $human_readable_name);

        $this->accepted_types = $accepted_types;
    }

    public function add_parameter_as_form_element(MoodleQuickForm $form)
    {
        $form->addElement(
            'filepicker',
            $this->get_parameter_name(),
            $this->get_human_readable_name(),
            null,
            array('accepted_types' => $this->accepted_types)
        );
    }

    /**
     * @throws \coding_exception
     */
    public function get_parameter_as_html_element(
        \moodle_page $moodle_page,
        \question_attempt $question_attempt,
        \question_display_options $question_display_options
    ): string
    {
        global $CFG, $COURSE;
        $draft_item_id = $question_attempt->prepare_response_files_draft_itemid($this->get_parameter_name(), $question_display_options->context->id);
        $usermaxbytes = get_user_max_upload_file_size(
            $moodle_page->context,
            $CFG->maxbytes,
            $COURSE->maxbytes,
            $question_attempt->get_question()->maxbytes
        );
        $file_manager = $this->create_file_manager(1, $usermaxbytes, $question_display_options, $draft_item_id);
        $files_renderer = $moodle_page->get_renderer('core', 'files');

        $files_html = $files_renderer->render($file_manager);

        $files_hidden_input_html = html_writer::empty_tag(
            'input',
            array(
                'type' => 'hidden',
                'name' => $question_attempt->get_qt_field_name($this->get_parameter_name()),
                'value' => $draft_item_id,
            )
        );

        return $files_html . $files_hidden_input_html;
    }

    public function create_file_manager(
        int $max_allowed_files,
        int $max_bytes_for_user,
        question_display_options $options,
        string $draft_item_id
    ): form_filemanager
    {
        $file_manager_options = new stdClass();
        $file_manager_options->mainfile = null;
        $file_manager_options->maxfiles = $max_allowed_files;
        $file_manager_options->maxbytes = $max_bytes_for_user;
        $file_manager_options->context = $options->context;
        $file_manager_options->return_types = FILE_INTERNAL | FILE_CONTROLLED_LINK;
        $file_manager_options->accepted_types = '.zip';
        $file_manager_options->itemid = $draft_item_id;
        return new form_filemanager($file_manager_options);
    }

    public function get_param_type(): string
    {
        return \question_attempt::PARAM_FILES;
    }

    /**
     * @throws \coding_exception
     */
    public function get_file_content_from_question_definition(\question_definition $question_definition): string
    {
        $file_storage = get_file_storage();

        $files = $file_storage->get_area_files(
            $question_definition->contextid,
            'qtype_appstester',
            $this->get_parameter_name(),
            $question_definition->id
        );

        if (empty($files)){
            return "";
        } else {
            return $files[array_keys($files)[1]]->get_content();
        }
    }

    public function get_file_content_from_question_usage_and_step(
        \question_usage_by_activity $question_usage_by_activity,
        \question_attempt_step $question_attempt_step
    ): string
    {
        $files = $question_attempt_step->get_qt_files($this->get_parameter_name(), $question_usage_by_activity->get_owning_context()->id);
        if (empty($files)){
            return "";
        } else {
            return $files[array_keys($files)[0]]->get_content();
        }
    }
}