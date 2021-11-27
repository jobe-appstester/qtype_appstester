<?php

namespace qtype_appstester\checker_definitions\parameters;

abstract class base_parameter implements parameter
{
    /**
     * @var string
     */
    private $system_name;
    /**
     * @var string
     */
    private $human_readable_name;

    public function __construct(string $system_name, string $human_readable_name)
    {
        $this->system_name = $system_name;
        $this->human_readable_name = $human_readable_name;
    }

    public function get_parameter_name(): string
    {
        return $this->system_name;
    }

    public function get_human_readable_name(): string
    {
        return $this->human_readable_name;
    }
}