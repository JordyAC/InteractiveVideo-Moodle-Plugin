<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_vidinteractivo\interaction;

defined('MOODLE_INTERNAL') || die();

/**
 * Immutable result returned by an interaction element after grading.
 */
final class grade_result {
    /** @var bool|null */
    private $correct;

    /** @var float */
    private $score;

    /** @var string */
    private $feedback;

    public function __construct(?bool $correct, float $score, string $feedback = '') {
        $this->correct = $correct;
        $this->score = $score;
        $this->feedback = $feedback;
    }

    public function is_correct(): ?bool {
        return $this->correct;
    }

    public function get_score(): float {
        return $this->score;
    }

    public function get_feedback(): string {
        return $this->feedback;
    }
}
