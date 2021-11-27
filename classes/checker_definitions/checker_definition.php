<?php

namespace qtype_appstester\checker_definitions;

interface checker_definition
{
    public function get_system_name(): string;
    public function get_human_readable_name(): string;

    public function get_teacher_parameters(): array;
    public function get_student_parameters(): array;

    public function render_result_feedback(array $result): string;
    public function render_status_feedback(array $status): string;

    public function get_fraction_from_result(array $result): float;
}