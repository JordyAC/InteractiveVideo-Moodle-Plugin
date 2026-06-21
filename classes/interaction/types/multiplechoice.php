<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_vidinteractivo\interaction\types;

use mod_vidinteractivo\interaction\base_element;
use mod_vidinteractivo\interaction\grade_result;

defined('MOODLE_INTERNAL') || die();

/**
 * Multiple choice interaction. Supports single and multi-select answers.
 */
final class multiplechoice extends base_element {
    public function get_type(): string {
        return 'multiplechoice';
    }

    public function get_name(): string {
        return get_string('type_multiplechoice', 'mod_vidinteractivo');
    }

    public function normalize_config(array $config): array {
        if (!isset($config['questions']) || !is_array($config['questions'])) {
            $q = $config;
            if (isset($q['question']) && !isset($q['prompt'])) {
                $q['prompt'] = $q['question'];
            }
            if (isset($q['correct']) && !isset($q['correctanswers'])) {
                $q['correctanswers'] = is_array($q['correct']) ? $q['correct'] : [(int)$q['correct']];
            }
            $q['options'] = array_values(array_filter($q['options'] ?? [], static function($value) {
                return trim((string)$value) !== '';
            }));
            $q['correctanswers'] = array_map('intval', $q['correctanswers'] ?? []);
            $q['multiple'] = !empty($q['multiple']);
            $config['questions'] = [$q];
        } else {
            foreach ($config['questions'] as &$q) {
                if (isset($q['question']) && !isset($q['prompt'])) {
                    $q['prompt'] = $q['question'];
                }
                if (isset($q['correct']) && !isset($q['correctanswers'])) {
                    $q['correctanswers'] = is_array($q['correct']) ? $q['correct'] : [(int)$q['correct']];
                }
                $q['options'] = array_values(array_filter($q['options'] ?? [], static function($value) {
                    return trim((string)$value) !== '';
                }));
                $q['correctanswers'] = array_map('intval', $q['correctanswers'] ?? []);
                $q['multiple'] = !empty($q['multiple']);
            }
        }
        return $config;
    }

    public function grade_response(array $config, $response, float $maxscore, int $attemptnumber = 1, float $penalty = 0.0): grade_result {
        $config = $this->normalize_config($config);
        $questions = $config['questions'];

        $submitted = $this->json_to_array($response);
        if (!$submitted || !is_array($submitted[0] ?? null)) {
            // Fallback for old single responses
            $sub = $this->json_to_array($response);
            if (!$sub) $sub = is_scalar($response) ? [(int)$response] : [];
            $submitted = [$sub];
        }

        $correct_count = 0;
        $total_questions = count($questions);
        
        foreach ($questions as $i => $q) {
            $sub = $submitted[$i] ?? [];
            $sub = array_map('intval', is_array($sub) ? $sub : [$sub]);
            sort($sub);
            
            $correct = $q['correctanswers'];
            sort($correct);
            
            if ($sub === $correct) {
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
