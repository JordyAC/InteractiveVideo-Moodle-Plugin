<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/vidinteractivo/restore/moodle2/restore_vidinteractivo_stepslib.php');

class restore_vidinteractivo_activity_task extends restore_activity_task {
    protected function define_my_settings() {
    }

    protected function define_my_steps() {
        $this->add_step(new restore_vidinteractivo_activity_structure_step('vidinteractivo_structure', 'vidinteractivo.xml'));
    }

    static public function define_decode_contents() {
        $contents = [];

        $contents[] = new restore_decode_content('vidinteractivo', ['intro'], 'vidinteractivo');

        return $contents;
    }

    static public function define_decode_rules() {
        $rules = [];

        $rules[] = new restore_decode_rule('VIDINTERACTIVOINDEX', '/mod/vidinteractivo/index.php?id=$1', 'course');
        $rules[] = new restore_decode_rule('VIDINTERACTIVOVIEWBYID', '/mod/vidinteractivo/view.php?id=$1', 'course_module');

        return $rules;
    }
}
