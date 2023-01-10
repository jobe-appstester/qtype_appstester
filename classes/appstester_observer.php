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
        $cmid = $event->contextinstanceid;
        $regradedquizid = $event->other['quizid'];
        $test = 123;
    }
}
