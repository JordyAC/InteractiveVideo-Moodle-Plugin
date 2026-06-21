<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_vidinteractivo\external;

use context_module;
use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use mod_vidinteractivo\interaction\registry;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

/**
 * Return configured interactions for an activity.
 */
class get_interactions extends external_api {
    public static function execute_parameters() {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module id'),
        ]);
    }

    public static function execute($cmid) {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), ['cmid' => $cmid]);
        list($course, $cm) = get_course_and_cm_from_cmid($params['cmid'], 'vidinteractivo');
        $context = context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/vidinteractivo:view', $context);

        $vidinteractivo = $DB->get_record('vidinteractivo', ['id' => $cm->instance], '*', MUST_EXIST);
        $records = $DB->get_records('vidinteractivo_interactions',
            ['vidinteractivo' => $vidinteractivo->id], 'timestamp ASC, sortorder ASC, id ASC');

        $result = [];
        foreach ($records as $record) {
            $record->type = registry::normalize_type($record->type);

            $usedattempts = (int)$DB->count_records('vidinteractivo_attempts', [
                'interaction' => $record->id,
                'userid'      => $USER->id,
            ]);
            $hascorrect = $DB->record_exists('vidinteractivo_attempts', [
                'interaction' => $record->id,
                'userid'      => $USER->id,
                'iscorrect'   => 1,
            ]);

            $result[] = [
                'id' => (int)$record->id,
                'vidinteractivo' => (int)$record->vidinteractivo,
                'timestamp' => (int)$record->timestamp,
                'timeend' => isset($record->timeend) ? (int)$record->timeend : 0,
                'sortorder' => isset($record->sortorder) ? (int)$record->sortorder : 0,
                'type' => $record->type,
                'title' => $record->title ?? '',
                'content' => $record->content ?? '',
                'maxscore' => isset($record->maxscore) ? (float)$record->maxscore : 1.0,
                'attemptsallowed' => isset($record->attemptsallowed) ? (int)$record->attemptsallowed : 1,
                'penalty' => isset($record->penalty) ? (float)$record->penalty : 0.0,
                'required' => !empty($record->required),
                'pause' => !empty($record->pause),
                'visible' => !empty($record->visible),
                'timecreated' => (int)$record->timecreated,
                'timemodified' => (int)$record->timemodified,
                'usedattempts' => $usedattempts,
                'hascorrect'   => (bool)$hascorrect,
            ];
        }

        return $result;
    }

    public static function execute_returns() {
        return new external_multiple_structure(
            new external_single_structure([
                'id' => new external_value(PARAM_INT, 'Interaction id'),
                'vidinteractivo' => new external_value(PARAM_INT, 'Activity instance id'),
                'timestamp' => new external_value(PARAM_INT, 'Start time in seconds'),
                'timeend' => new external_value(PARAM_INT, 'Optional end time'),
                'sortorder' => new external_value(PARAM_INT, 'Sort order'),
                'type' => new external_value(PARAM_ALPHANUMEXT, 'Interaction type'),
                'title' => new external_value(PARAM_TEXT, 'Interaction title'),
                'content' => new external_value(PARAM_RAW, 'JSON configuration'),
                'maxscore' => new external_value(PARAM_FLOAT, 'Maximum score'),
                'attemptsallowed' => new external_value(PARAM_INT, 'Attempts allowed'),
                'penalty' => new external_value(PARAM_FLOAT, 'Penalty'),
                'required' => new external_value(PARAM_BOOL, 'Required'),
                'pause' => new external_value(PARAM_BOOL, 'Pause video'),
                'visible' => new external_value(PARAM_BOOL, 'Visible'),
                'timecreated' => new external_value(PARAM_INT, 'Created timestamp'),
                'timemodified' => new external_value(PARAM_INT, 'Modified timestamp'),
                'usedattempts' => new external_value(PARAM_INT, 'Attempts used by current user'),
                'hascorrect'   => new external_value(PARAM_BOOL, 'User has a correct attempt'),
            ])
        );
    }
}
