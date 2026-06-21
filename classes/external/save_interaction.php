<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_vidinteractivo\external;

use context_module;
use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use mod_vidinteractivo\interaction\registry;
use stdClass;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

/**
 * Save or update an interaction.
 */
class save_interaction extends external_api {
    public static function execute_parameters() {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module id'),
            'interactionid' => new external_value(PARAM_INT, 'Interaction id, 0 to create'),
            'timestamp' => new external_value(PARAM_INT, 'Start time in seconds'),
            'type' => new external_value(PARAM_ALPHANUMEXT, 'Interaction type'),
            'content' => new external_value(PARAM_RAW, 'JSON interaction configuration'),
            'title' => new external_value(PARAM_TEXT, 'Interaction title', VALUE_DEFAULT, ''),
            'timeend' => new external_value(PARAM_INT, 'Optional end time in seconds', VALUE_DEFAULT, 0),
            'maxscore' => new external_value(PARAM_FLOAT, 'Maximum score', VALUE_DEFAULT, 1.0),
            'attemptsallowed' => new external_value(PARAM_INT, 'Allowed attempts, 0 means unlimited', VALUE_DEFAULT, 0),
            'penalty' => new external_value(PARAM_FLOAT, 'Penalty ratio per extra attempt', VALUE_DEFAULT, 0.0),
            'required' => new external_value(PARAM_BOOL, 'Must be completed before continuing', VALUE_DEFAULT, true),
            'pause' => new external_value(PARAM_BOOL, 'Pause video when reached', VALUE_DEFAULT, true),
            'visible' => new external_value(PARAM_BOOL, 'Visible to learners', VALUE_DEFAULT, true),
        ]);
    }

    public static function execute($cmid, $interactionid, $timestamp, $type, $content, $title = '',
            $timeend = 0, $maxscore = 1.0, $attemptsallowed = 0, $penalty = 0.0, $required = true,
            $pause = true, $visible = true) {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
            'interactionid' => $interactionid,
            'timestamp' => $timestamp,
            'type' => $type,
            'content' => $content,
            'title' => $title,
            'timeend' => $timeend,
            'maxscore' => $maxscore,
            'attemptsallowed' => $attemptsallowed,
            'penalty' => $penalty,
            'required' => $required,
            'pause' => $pause,
            'visible' => $visible,
        ]);

        list($course, $cm) = get_course_and_cm_from_cmid($params['cmid'], 'vidinteractivo');
        $context = context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/vidinteractivo:author', $context);

        $vidinteractivo = $DB->get_record('vidinteractivo', ['id' => $cm->instance], '*', MUST_EXIST);
        $type = registry::normalize_type($params['type']);
        $element = registry::get($type);

        $config = [];
        if (trim($params['content']) !== '') {
            $decoded = json_decode($params['content'], true);
            $config = is_array($decoded) ? $decoded : ['html' => $params['content']];
        }
        $config = $element->normalize_config($config);

        $now = time();
        $interaction = new stdClass();
        $interaction->vidinteractivo = $vidinteractivo->id;
        $interaction->timestamp = max(0, (int)$params['timestamp']);
        $interaction->timeend = $params['timeend'] > 0 ? (int)$params['timeend'] : null;
        $interaction->type = $type;
        $interaction->title = $params['title'];
        $interaction->content = json_encode($config);
        $interaction->maxscore = max(0.0, (float)$params['maxscore']);
        $interaction->attemptsallowed = max(0, (int)$params['attemptsallowed']);
        $interaction->penalty = min(1.0, max(0.0, (float)$params['penalty']));
        $interaction->required = $params['required'] ? 1 : 0;
        $interaction->pause = $params['pause'] ? 1 : 0;
        $interaction->visible = $params['visible'] ? 1 : 0;
        $interaction->timemodified = $now;

        if ((int)$params['interactionid'] === 0) {
            $interaction->timecreated = $now;
            $interaction->sortorder = $DB->count_records('vidinteractivo_interactions',
                ['vidinteractivo' => $vidinteractivo->id]);
            $id = $DB->insert_record('vidinteractivo_interactions', $interaction);
        } else {
            $existing = $DB->get_record('vidinteractivo_interactions', [
                'id' => $params['interactionid'],
                'vidinteractivo' => $vidinteractivo->id,
            ], '*', MUST_EXIST);

            $interaction->id = $existing->id;
            $interaction->timecreated = $existing->timecreated;
            $interaction->sortorder = $existing->sortorder ?? 0;
            $DB->update_record('vidinteractivo_interactions', $interaction);
            $id = $interaction->id;
        }

        return ['id' => $id, 'status' => true];
    }

    public static function execute_returns() {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Saved interaction id'),
            'status' => new external_value(PARAM_BOOL, 'Operation status'),
        ]);
    }
}
