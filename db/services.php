<?php

$functions = array(
    'local_qtype_get_submissions_to_check' => array(
        'classname' => 'qtype_appstester\external\get_submissions_to_check',
        'methodname' => 'get_submissions_to_check',
        'description' => 'Returns submissions to check',
        'type' => 'read',
    ),
    'local_qtype_get_submission' => array(
        'classname' => 'qtype_appstester\external\get_submission',
        'methodname' => 'get_submission',
        'description' => 'Returns submission\'s info',
        'type' => 'read',
    ),
    'local_qtype_set_submission_results' => array(
        'classname' => 'qtype_appstester\external\set_submission_results',
        'methodname' => 'set_submission_results',
        'description' => 'Set submission\'s result',
        'type' => 'write',
    ),
    'local_qtype_set_submission_status' => array(
        'classname' => 'qtype_appstester\external\set_submission_status',
        'methodname' => 'set_submission_status',
        'description' => 'Set submission\'s status',
        'type' => 'write',
    )
);

$services = array(
    'submissions' => array(
        'functions' => array(
            'local_qtype_get_submissions_to_check',
            'local_qtype_get_submission',
            'local_qtype_set_submission_results',
            'local_qtype_set_submission_status'
        ),
        'enabled' => 1
    )
);
