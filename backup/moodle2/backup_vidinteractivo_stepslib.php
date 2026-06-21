<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

class backup_vidinteractivo_activity_structure_step extends backup_activity_structure_step {
    protected function define_structure() {
        $userinfo = $this->get_setting_value('userinfo');

        $vidinteractivo = new backup_nested_element('vidinteractivo', ['id'], [
            'course', 'name', 'intro', 'introformat', 'videotype', 'videourl',
            'grade', 'passgrade', 'completionrequiresall', 'timecreated', 'timemodified'
        ]);

        $interactions = new backup_nested_element('interactions');
        $interaction = new backup_nested_element('interaction', ['id'], [
            'timestamp', 'timeend', 'sortorder', 'type', 'title', 'content',
            'maxscore', 'attemptsallowed', 'penalty', 'required', 'pause', 'visible',
            'timecreated', 'timemodified'
        ]);

        $attempts = new backup_nested_element('attempts');
        $attempt = new backup_nested_element('attempt', ['id'], [
            'interaction', 'userid', 'attemptnumber', 'response', 'iscorrect',
            'score', 'maxscore', 'timetaken', 'timecreated', 'timemodified'
        ]);

        $progresses = new backup_nested_element('progresses');
        $progress = new backup_nested_element('progress', ['id'], [
            'userid', 'lastposition', 'completed', 'timecreated', 'timemodified'
        ]);

        $vidinteractivo->add_child($interactions);
        $interactions->add_child($interaction);

        $vidinteractivo->add_child($attempts);
        $attempts->add_child($attempt);

        $vidinteractivo->add_child($progresses);
        $progresses->add_child($progress);

        $vidinteractivo->set_source_table('vidinteractivo', ['id' => backup::VAR_ACTIVITYID]);
        $interaction->set_source_table('vidinteractivo_interactions', ['vidinteractivo' => backup::VAR_PARENTID]);

        if ($userinfo) {
            $attempt->set_source_table('vidinteractivo_attempts', ['vidinteractivo' => backup::VAR_PARENTID]);
            $progress->set_source_table('vidinteractivo_progress', ['vidinteractivo' => backup::VAR_PARENTID]);
        }

        $vidinteractivo->annotate_ids('course', 'course');
        $attempt->annotate_ids('user', 'userid');
        $progress->annotate_ids('user', 'userid');

        $vidinteractivo->annotate_files('mod_vidinteractivo', 'intro', null);
        $vidinteractivo->annotate_files('mod_vidinteractivo', 'video', null);

        return $this->prepare_activity_structure($vidinteractivo);
    }
}
