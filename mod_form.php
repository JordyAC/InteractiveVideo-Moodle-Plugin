<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Activity settings form.
 *
 * @package    mod_vidinteractivo
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Moodle module form for mod_vidinteractivo.
 */
class mod_vidinteractivo_mod_form extends moodleform_mod {
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('vidinteractivoname', 'mod_vidinteractivo'), ['size' => '64']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', 'mod_vidinteractivo', 255), 'maxlength', 255, 'client');

        $this->standard_intro_elements();

        $mform->addElement('header', 'videosettings', get_string('videosettings', 'mod_vidinteractivo'));

        $mform->addElement('select', 'videotype', get_string('videotype', 'mod_vidinteractivo'), [
            'url' => get_string('videotype_url', 'mod_vidinteractivo'),
            'file' => get_string('videotype_file', 'mod_vidinteractivo'),
        ]);
        $mform->setDefault('videotype', 'url');

        $mform->addElement('text', 'videourl', get_string('videourl', 'mod_vidinteractivo'), ['size' => '80']);
        $mform->setType('videourl', PARAM_RAW_TRIMMED);
        $mform->hideIf('videourl', 'videotype', 'eq', 'file');
        $mform->addHelpButton('videourl', 'videourl', 'mod_vidinteractivo');

        global $CFG;
        $mform->addElement('filemanager', 'video', get_string('video', 'mod_vidinteractivo'), null,
            ['subdirs' => 0, 'maxbytes' => $CFG->maxbytes, 'maxfiles' => 1, 'accepted_types' => ['video']]);
        $mform->hideIf('video', 'videotype', 'eq', 'url');

        $mform->addElement('header', 'gradesettings', get_string('gradesettings', 'mod_vidinteractivo'));

        $mform->addElement('text', 'passgrade', get_string('passgrade', 'mod_vidinteractivo'), ['size' => '8']);
        $mform->setType('passgrade', PARAM_FLOAT);
        $mform->setDefault('passgrade', 0);
        $mform->addRule('passgrade', null, 'numeric', null, 'client');

        $mform->addElement('advcheckbox', 'completionrequiresall',
            get_string('completionrequiresall', 'mod_vidinteractivo'));
        $mform->setDefault('completionrequiresall', 0);

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    public function data_preprocessing(&$default_values) {
        if ($this->current->instance) {
            $draftitemid = file_get_submitted_draft_itemid('video');
            file_prepare_draft_area($draftitemid, $this->context->id, 'mod_vidinteractivo', 'video', 0, ['subdirs' => 0, 'maxfiles' => 1]);
            $default_values['video'] = $draftitemid;
        }
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if ($data['videotype'] === 'url') {
            if (empty($data['videourl'])) {
                $errors['videourl'] = get_string('required');
            } elseif (!filter_var($data['videourl'], FILTER_VALIDATE_URL)) {
                $errors['videourl'] = get_string('videourl', 'mod_vidinteractivo') . ': URL no valida.';
            }
        } elseif ($data['videotype'] === 'file') {
            if (empty($data['video'])) {
                $errors['video'] = get_string('required');
            }
        }

        if (isset($data['passgrade']) && ((float)$data['passgrade'] < 0 || (float)$data['passgrade'] > 100)) {
            $errors['passgrade'] = get_string('passgrade', 'mod_vidinteractivo') . ': 0-100.';
        }

        return $errors;
    }
}
