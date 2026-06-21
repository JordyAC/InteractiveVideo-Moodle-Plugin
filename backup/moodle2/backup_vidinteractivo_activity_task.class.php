<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/vidinteractivo/backup/moodle2/backup_vidinteractivo_stepslib.php');

class backup_vidinteractivo_activity_task extends backup_activity_task {
    protected function define_my_settings() {
    }

    protected function define_my_steps() {
        $this->add_step(new backup_vidinteractivo_activity_structure_step('vidinteractivo_structure', 'vidinteractivo.xml'));
    }

    static public function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, "/");
        $search = "/(" . $base . "\/mod\/vidinteractivo\/index.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@VIDINTERACTIVOINDEX*$2@$', $content);

        $search = "/(" . $base . "\/mod\/vidinteractivo\/view.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@VIDINTERACTIVOVIEWBYID*$2@$', $content);

        return $content;
    }
}
