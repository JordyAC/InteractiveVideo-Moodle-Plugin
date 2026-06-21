<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_vidinteractivo\interaction;

defined('MOODLE_INTERNAL') || die();

/**
 * Shared helpers for interaction elements.
 */
abstract class base_element implements element_interface {
    public function is_gradable(array $config): bool {
        return true;
    }

    public function normalize_config(array $config): array {
        return $config;
    }

    protected function normalize_text(string $value): string {
        $value = \core_text::strtolower(trim($value));
        $value = preg_replace('/\s+/u', ' ', $value);
        return $value ?? '';
    }

    protected function apply_penalty(float $score, int $attemptnumber, float $penalty): float {
        if ($attemptnumber <= 1 || $score <= 0 || $penalty <= 0) {
            return $score;
        }
        $deduction = min(1.0, ($attemptnumber - 1) * $penalty);
        return max(0.0, $score * (1.0 - $deduction));
    }

    protected function json_to_array($response): array {
        if (is_array($response)) {
            return $response;
        }
        if (!is_string($response) || trim($response) === '') {
            return [];
        }
        $decoded = json_decode($response, true);
        return is_array($decoded) ? $decoded : [];
    }
}
