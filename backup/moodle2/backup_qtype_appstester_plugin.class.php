<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/type/appstester/questiontype.php');

/**
 * Provides the information to backup AppsTester questions.
 */
class backup_qtype_appstester_plugin extends backup_qtype_plugin {

    // Legacy code, for supporting a subclassing of coderunner.
    protected function qtype() {
        return 'appstester';
    }


    /**
     * Returns the qtype information to attach to question element.
     */
    protected function define_question_plugin_structure() {

        // Define the virtual plugin element with the condition to fulfill.
        $plugin = $this->get_plugin_element(null, '../../qtype', 'appstester');

        // Create one standard named plugin element (the visible container).
        $pluginwrapper = new backup_nested_element($this->get_recommended_name());

        // Connect the visible container ASAP.
        $plugin->add_child($pluginwrapper);

        // Now create the qtype own structures.
        $appstester = new backup_nested_element(
            'appstester',
            array('id'),
            array('checker_system_name', 'hideresult_whileactive', 'hideresult_afterfinish')
        );

        // Now the own qtype tree.
        $pluginwrapper->add_child($appstester);

        // Set source to populate the data.
        $appstester->set_source_table('qtype_appstester_parameters',
            array('questionid' => backup::VAR_PARENTID));

        return $plugin;
    }


    /**
     * Returns one array with filearea => mappingname elements for the qtype.
     *
     * Used by {@link get_components_and_fileareas} to know about all the qtype
     * files to be processed both in backup and restore.
     */
    public static function get_qtype_fileareas() {
        return array('template' => 'question_created');
    }
}
