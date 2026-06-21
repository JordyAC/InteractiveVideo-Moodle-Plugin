<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_vidinteractivo\interaction\types;

use mod_vidinteractivo\interaction\base_element;
use mod_vidinteractivo\interaction\grade_result;

defined('MOODLE_INTERNAL') || die();

final class imageselection extends base_element {
    public function get_type(): string {
        return 'imageselection';
    }

    public function get_name(): string {
        return get_string('type_imageselection', 'mod_vidinteractivo');
    }

    public function normalize_config(array $config): array {
        $config['areas'] = array_values($config['areas'] ?? []);
        return $config;
    }

    public function grade_response(array $config, $response, float $maxscore, int $attemptnumber = 1, float $penalty = 0.0): grade_result {
        $config = $this->normalize_config($config);
        $submitted = $this->json_to_array($response);
        $x = (float)($submitted['x'] ?? -1);
        $y = (float)($submitted['y'] ?? -1);

        $iscorrect = false;
        foreach ($config['areas'] as $area) {
            if (empty($area['correct'])) {
                continue;
            }
            $left = (float)($area['x'] ?? 0);
            $top = (float)($area['y'] ?? 0);
            $width = (float)($area['width'] ?? 0);
            $height = (float)($area['height'] ?? 0);
            if ($x >= $left && $x <= ($left + $width) && $y >= $top && $y <= ($top + $height)) {
                $iscorrect = true;
                break;
            }
        }

        $score = $iscorrect ? $this->apply_penalty($maxscore, $attemptnumber, $penalty) : 0.0;
        return new grade_result($iscorrect, $score, $iscorrect ? get_string('correct', 'mod_vidinteractivo') : get_string('incorrect', 'mod_vidinteractivo'));
    }
}
