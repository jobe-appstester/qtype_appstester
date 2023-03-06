<?php

defined('MOODLE_INTERNAL') || die();

$tasks = array(
    array(
        'classname' => '\qtype_appstester\task\update_quiz_grades',
        'blocking' => 0,
        'minute' => '*',
        'hour' => '*/12',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*',
        'disabled' => 0
    ),
);
