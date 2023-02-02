<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Event observers supported by this question type
 *
 * @package    qtype_appstester
 * @copyright  2022 Lavrentev Semyon
 */

namespace qtype_appstester;
defined('MOODLE_INTERNAL') || die();

/**
 * Observer class.
 *
 * @copyright  2022 Lavrentev Semyon
 */
class appstester_observer {

    /** Some attempt was regraded
     * @param \core\event\base $event The event.
     * @return void
     */
    public static function attempt_regraded($event) {
        global $DB;
        $quiz_attempt_id = $event->objectid;
        $sql = "
            SELECT quiza.id, qa.behaviour
            FROM {quiz_attempts} quiza
            JOIN {question_usages} qu ON qu.id = quiza.uniqueid
            JOIN {question_attempts} qa ON qa.questionusageid = qu.id
            WHERE quiza.id = :quizaid AND qa.behaviour = 'appstester'";
        $params = ['quizaid' => $quiz_attempt_id];
        $result = $DB->get_record_sql($sql, $params);

        if ($result) {
            // Check if quiz is in table already
            $regraded_quiz_id = $event->other['quizid'];
            $sql = "
                SELECT q.*
                FROM {qtype_appstester_quizupdate} q
                WHERE q.quiz_id = :quizid AND q.status = 'waiting'
            ";
            $params = ['quizid' => $regraded_quiz_id];
            $quiz_in_table = $DB->get_record_sql($sql, $params);

            // Add quiz to table if there's no quiz with such id yet
            if (!$quiz_in_table) {
                $insert_object = new \stdClass();
                $insert_object->quiz_id = $regraded_quiz_id;
                $insert_object->status = 'waiting';
                $insert_object->timecreated = time();
                $DB->insert_record('qtype_appstester_quizupdate', $insert_object);
            }
        }
    }
}
