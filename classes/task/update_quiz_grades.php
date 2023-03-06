<?php

namespace qtype_appstester\task;

use core\check\performance\debugging;

defined('MOODLE_INTERNAL') || die();

class update_quiz_grades extends \core\task\scheduled_task
{
    public function get_name()
    {
        return get_string('task:update_quiz_grades', 'qtype_appstester');
    }

    public function execute()
    {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/quiz/locallib.php');
        require_once($CFG->dirroot . '/mod/quiz/lib.php');

        $sql = "
            SELECT q.*
            FROM {qtype_appstester_quizupdate} q
            WHERE q.status = 'waiting'
        ";
        $quizes_to_update = $DB->get_records_sql($sql);
        foreach ($quizes_to_update as $quiz) {
            $quiz_cm = $DB->get_record('quiz', ['id' => $quiz->quiz_id]);
            if ($quiz_cm) {
                \quiz_update_all_attempt_sumgrades($quiz_cm);
                \quiz_update_all_final_grades($quiz_cm);
                \quiz_update_grades($quiz_cm);
                mtrace("Updated grades for quiz \"" . $quiz_cm->name . "\".");

                // delete records or change status?
                $update_object = new \stdClass();
                $update_object->id = $quiz->id;
                $update_object->quiz_id = $quiz->quiz_id;
                $update_object->status = 'updated';
                $update_object->timemodified = time();
                $DB->update_record('qtype_appstester_quizupdate', $update_object);
            } else {
                debugging('Invalid quiz passed to task');
            }
        }
    }
}