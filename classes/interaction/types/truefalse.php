<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_vidinteractivo\interaction\types;

use mod_vidinteractivo\interaction\base_element;
use mod_vidinteractivo\interaction\grade_result;

defined('MOODLE_INTERNAL') || die();

final class truefalse extends base_element {
    public function get_type(): string {
        return 'truefalse';
    }

    public function get_name(): string {
        return get_string('type_truefalse', 'mod_vidinteractivo');
    }

    public function normalize_config(array $config): array {
        if (!isset($config['questions']) || !is_array($config['questions'])) {
            $q = $config;
            $q['correct'] = !empty($q['correct']);
            $config['questions'] = [$q];
        } else {
            foreach ($config['questions'] as &$q) {
                $q['correct'] = !empty($q['correct']);
            }
        }
        return $config;
    }

    public function grade_response(array $config, $response, float $maxscore, int $attemptnumber = 1, float $penalty = 0.0): grade_result {
        $config = $this->normalize_config($config);
        $questions = $config['questions'];

        $submitted = $this->json_to_array($response);
        if (!is_array($submitted)) {
            $submitted = [$response];
        }

        $correct_count = 0;
        $total_questions = count($questions);

        foreach ($questions as $i => $q) {
            $sub = $submitted[$i] ?? null;
            $sub = filter_var($sub, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($sub === $q['correct']) {
                $correct_count++;
            }
        }

        if ($total_questions > 0) {
            $score = ($correct_count / $total_questions) * $maxscore;
        } else {
            $score = 0;
        }

        $score = $this->apply_penalty($score, $attemptnumber, $penalty);
        $is_correct = ($correct_count === $total_questions);
        
        if ($total_questions === 1) {
            $message = $is_correct ? get_string('correct', 'mod_vidinteractivo') : get_string('incorrect', 'mod_vidinteractivo');
        } else {
            $message = "Respuestas correctas: $correct_count de $total_questions.";
        }

        return new grade_result($is_correct, $score, $message);
    }
}
