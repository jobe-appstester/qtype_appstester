<?php

namespace qtype_appstester\checker_definitions;

use html_table;
use html_writer;

use qtype_appstester\checker_definitions\parameters\single_file_parameter;
use qtype_appstester\checker_definitions\parameters\string_parameter;

class android_checker_definition implements checker_definition
{

    public function get_system_name(): string
    {
        return 'android';
    }

    public function get_human_readable_name(): string
    {
        return 'Android';
    }

    public function get_teacher_parameters(): array
    {
        return array(
            new single_file_parameter('template', 'ZIP файл с шаблоном', array('zip')),
        );
    }

    public function get_student_parameters(): array
    {
        return array(
            new single_file_parameter('submission', 'ZIP архив с решением', array('zip'))
        );
    }

    public function render_result_feedback(array $result): string
    {
        if ($result["CompilationError"]) {
            return "<pre><code class='language-gradle'>" . $result["CompilationError"] . "</code></pre>";
        }

        if ($result["ValidationError"]) {
            return "<pre>" . $result["ValidationError"] . "</pre>";
        }

        $html = '<p>Набрано ' . $result['Grade'] . ' баллов из ' . $result['TotalGrade'] . '</p>';

        $table = new html_table();
        $table->attributes = array(
            'style' => 'display: inline-block; overflow: auto; max-height: 60vh;'
        );
        $table->head = array('Название теста', 'Результат');

        $test_results = [];
        foreach ($result['TestResults'] as $test_result) {
            $test_results[] = array($test_result['Test'], '<pre><code class="language-java">' . ($test_result['ResultCode'] === 0 ? 'OK' : $test_result['Stream']) . '</code></pre>');
        }

        $table->data = $test_results;

        $html .= html_writer::table($table);

        return $html;
    }

    public function render_status_feedback(array $status): string
    {
        switch ($status['Status']) {
            case 'checking_started':
                return 'Началась проверка работы';
            case 'unzip_files':
                return 'Производится распаковка решения';
            case 'validate_submission':
                return 'Проверяется целостность решения';
            case 'gradle_build':
                return 'Приложение собирается';
            case 'install_application':
                return 'Приложение устанавливается для последующего тестирования';
            case 'test':
                return 'Приложение тестируется';
            default:
                return 'Статус проверки неизвестен';
        }
    }

    public function get_fraction_from_result(array $result): float
    {
        $grade = $result['Grade'];
        $total_grade = $result['TotalGrade'];

        if (!$total_grade || !$grade) {
            return 0;
        }

        return $grade / $total_grade;
    }
}