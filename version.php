<?php

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'qtype_appstester';
$plugin->version = 2023042000;
$plugin->maturity = MATURITY_BETA;
$plugin->release = 'v0.01';
$plugin->dependencies = array(
    'qbehaviour_appstester' => 2023042000
);