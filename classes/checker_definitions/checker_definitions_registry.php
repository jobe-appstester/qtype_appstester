<?php


namespace qtype_appstester\checker_definitions;


class checker_definitions_registry
{
    public static function get_all_definitions(): array {
        return array(
            new android_checker_definition()
        );
    }

    /**
     * @throws \coding_exception
     */
    public static function get_by_system_name(string $system_name): checker_definition {
        $definitions = self::get_all_definitions();
        foreach ($definitions as $definition) {
            if ($definition->get_system_name() === $system_name) {
                return $definition;
            }
        }

        throw new \coding_exception('Checker with system name "' . $system_name . '" does not exists');
    }
}