<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_vidinteractivo\interaction\types;

use mod_vidinteractivo\interaction\base_element;
use mod_vidinteractivo\interaction\grade_result;

defined('MOODLE_INTERNAL') || die();

final class shortanswer extends base_element {
    public function get_type(): string {
        return 'shortanswer';
    }

    public function get_name(): string {
        return get_string('type_shortanswer', 'mod_vidinteractivo');
    }

    public function grade_response(array $config, $response, float $maxscore, int $attemptnumber = 1, float $penalty = 0.0): grade_result {
        $answers = $config['answers'] ?? [];
        $casesensitive = !empty($config['casesensitive']);
        $submitted = trim((string)$response);
        $needle = $casesensitive ? $submitted : $this->normalize_text($submitted);

        $iscorrect = false;
        foreach ($answers as $answer) {
            $candidate = $casesensitive ? trim((string)$answer) : $this->normalize_text((string)$answer);
            if ($candidate !== '' && $candidate === $needle) {
                $iscorrect = true;
                break;
            }
        }

        $score = $iscorrect ? $this->apply_penalty($maxscore, $attemptnumber, $penalty) : 0.0;
        return new grade_result($iscorrect, $score, $iscorrect ? get_string('correct', 'mod_vidinteractivo') : get_string('incorrect', 'mod_vidinteractivo'));
    }
}
