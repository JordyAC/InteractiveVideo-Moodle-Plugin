<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_vidinteractivo\interaction\types;

use mod_vidinteractivo\interaction\base_element;
use mod_vidinteractivo\interaction\grade_result;

defined('MOODLE_INTERNAL') || die();

final class html extends base_element {
    public function get_type(): string {
        return 'html';
    }

    public function get_name(): string {
        return get_string('type_html', 'mod_vidinteractivo');
    }

    public function is_gradable(array $config): bool {
        return false;
    }

    public function grade_response(array $config, $response, float $maxscore, int $attemptnumber = 1, float $penalty = 0.0): grade_result {
        return new grade_result(null, 0.0, '');
    }
}
