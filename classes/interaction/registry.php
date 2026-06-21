<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_vidinteractivo\interaction;

defined('MOODLE_INTERNAL') || die();

/**
 * Registry for built-in interaction element types.
 */
final class registry {
    /** @var array<string, element_interface> */
    private static $instances = [];

    /**
     * @return array<string, element_interface>
     */
    public static function all(): array {
        if (!self::$instances) {
            self::$instances = [
                'multiplechoice' => new types\multiplechoice(),
                'truefalse' => new types\truefalse(),
                'imageselection' => new types\imageselection(),
                'dragdrop' => new types\dragdrop(),
                'shortanswer' => new types\shortanswer(),
                'html' => new types\html(),
                'capture' => new types\capture(),
            ];
        }

        return self::$instances;
    }

    public static function get(string $type): element_interface {
        $type = self::normalize_type($type);
        $all = self::all();

        if (!isset($all[$type])) {
            throw new \moodle_exception('unknowninteractiontype', 'mod_vidinteractivo', '', $type);
        }

        return $all[$type];
    }

    public static function normalize_type(string $type): string {
        $type = strtolower(trim($type));
        $aliases = [
            'question' => 'multiplechoice',
            'mcq' => 'multiplechoice',
            'true_false' => 'truefalse',
            'image_selection' => 'imageselection',
            'drag_drop' => 'dragdrop',
            'short_answer' => 'shortanswer',
            'flashcard' => 'capture',
        ];

        return $aliases[$type] ?? $type;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function types_for_external(): array {
        $types = [];
        foreach (self::all() as $type => $element) {
            $types[] = [
                'type' => $type,
                'name' => $element->get_name(),
                'gradable' => $element->is_gradable([]),
            ];
        }
        return $types;
    }
}
