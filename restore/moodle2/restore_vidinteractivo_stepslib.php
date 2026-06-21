<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

class restore_vidinteractivo_activity_structure_step extends restore_activity_structure_step {
    protected function define_structure() {
        $paths = [];
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('vidinteractivo', '/activity/vidinteractivo');
        $paths[] = new restore_path_element('vidinteractivo_interaction', '/activity/vidinteractivo/interactions/interaction');

        if ($userinfo) {
            $paths[] = new restore_path_element('vidinteractivo_attempt', '/activity/vidinteractivo/attempts/attempt');
            $paths[] = new restore_path_element('vidinteractivo_progress', '/activity/vidinteractivo/progresses/progress');
        }

        return $this->prepare_activity_structure($paths);
    }

    protected function process_vidinteractivo($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        $newitemid = $DB->insert_record('vidinteractivo', $data);
        $this->apply_activity_instance($newitemid);
    }

    protected function process_vidinteractivo_interaction($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->vidinteractivo = $this->get_new_parentid('vidinteractivo');

        $newitemid = $DB->insert_record('vidinteractivo_interactions', $data);
        $this->set_mapping('vidinteractivo_interaction', $oldid, $newitemid);
    }

    protected function process_vidinteractivo_attempt($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->vidinteractivo = $this->get_new_parentid('vidinteractivo');
        $data->interaction = $this->get_mappingid('vidinteractivo_interaction', $data->interaction);
        $data->userid = $this->get_mappingid('user', $data->userid);

        $newitemid = $DB->insert_record('vidinteractivo_attempts', $data);
    }

    protected function process_vidinteractivo_progress($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->vidinteractivo = $this->get_new_parentid('vidinteractivo');
        $data->userid = $this->get_mappingid('user', $data->userid);

        $newitemid = $DB->insert_record('vidinteractivo_progress', $data);
    }

    protected function after_execute() {
        $this->add_related_files('mod_vidinteractivo', 'intro', null);
        $this->add_related_files('mod_vidinteractivo', 'video', null);
    }
}
