<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_vidinteractivo\interaction\types;

use mod_vidinteractivo\interaction\base_element;
use mod_vidinteractivo\interaction\grade_result;

defined('MOODLE_INTERNAL') || die();

final class dragdrop extends base_element {
    public function get_type(): string {
        return 'dragdrop';
    }

    public function get_name(): string {
        return get_string('type_dragdrop', 'mod_vidinteractivo');
    }

    public function grade_response(array $config, $response, float $maxscore, int $attemptnumber = 1, float $penalty = 0.0): grade_result {
        $submitted = $this->json_to_array($response);
        $mapping   = $config['mapping'] ?? [];

        if (empty($mapping)) {
            return new grade_result(null, 0.0, get_string('correct', 'mod_vidinteractivo'));
        }

        // Check each required item individually to avoid PHP array order sensitivity.
        $iscorrect = true;
        foreach ($mapping as $item => $correctzone) {
            if (!array_key_exists($item, $submitted) || $submitted[$item] !== $correctzone) {
                $iscorrect = false;
                break;
            }
        }

        // Check that no extra distractors were dragged.
        if ($iscorrect) {
            foreach ($submitted as $item => $submittedzone) {
                if (!array_key_exists($item, $mapping) || $mapping[$item] !== $submittedzone) {
                    $iscorrect = false;
                    break;
                }
            }
        }

        $score = $iscorrect ? $this->apply_penalty($maxscore, $attemptnumber, $penalty) : 0.0;
        $msg   = $iscorrect ? get_string('correct', 'mod_vidinteractivo') : get_string('incorrect', 'mod_vidinteractivo');
        return new grade_result($iscorrect, $score, $msg);
    }
}
