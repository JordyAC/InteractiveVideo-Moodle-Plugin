<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_vidinteractivo\interaction;

defined('MOODLE_INTERNAL') || die();

/**
 * Contract implemented by every interactive learning element.
 */
interface element_interface {
    public function get_type(): string;

    public function get_name(): string;

    public function is_gradable(array $config): bool;

    public function normalize_config(array $config): array;

    public function grade_response(array $config, $response, float $maxscore, int $attemptnumber = 1, float $penalty = 0.0): grade_result;
}
