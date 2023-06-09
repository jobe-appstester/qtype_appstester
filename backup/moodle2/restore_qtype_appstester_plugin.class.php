<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/type/appstester/questiontype.php');

/**
 * Restore plugin class for appstester questions.
 */
class restore_qtype_appstester_plugin extends restore_qtype_plugin
{

    /**
     * Returns the paths to be handled by the plugin at question level.
     */
    public function define_question_plugin_structure() {
        $paths = array();

        // Add own qtype stuff.
        $elename = 'appstester';
        // We used get_recommended_name() so this works.
        $elepath = $this->get_pathfor('/appstester');
        $paths[] = new restore_path_element($elename, $elepath);

        return $paths; // And return the paths.
    }

    /*
     * Called during restore to process the testcases within the
     * backup element.
     */
    public function process_appstester($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        // Detect if the question is created or mapped.
        $oldquestionid = $this->get_old_parentid('question');
        $newquestionid = $this->get_new_parentid('question');
        $questioncreated = $this->get_mappingid('question_created', $oldquestionid) ? true : false;

        // If the question has been created by restore, insert the new testcase.
        if ($questioncreated) {
            $data->questionid = $newquestionid;

//            if (!$DB->record_exists('qtype_appstester_parameters', array('questionid' => $data->questionid))) {
                $newitemid = $DB->insert_record('qtype_appstester_parameters', $data);
                $this->set_mapping('qtype_appstester_parameters', $oldid, $newitemid);
//            }
        }
        // Nothing to remap if the question already existed.
    }
}
