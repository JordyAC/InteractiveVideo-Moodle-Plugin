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
 * Save a learner response and update grades.
 */
class save_attempt extends external_api {
    public static function execute_parameters() {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module id'),
            'interactionid' => new external_value(PARAM_INT, 'Interaction id'),
            'response' => new external_value(PARAM_RAW, 'Learner response'),
            'timetaken' => new external_value(PARAM_INT, 'Time taken in seconds', VALUE_DEFAULT, 0),
        ]);
    }

    public static function execute($cmid, $interactionid, $response, $timetaken = 0) {
        global $DB, $USER, $CFG;

        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
            'interactionid' => $interactionid,
            'response' => $response,
            'timetaken' => $timetaken,
        ]);

        if (isguestuser()) {
            throw new \moodle_exception('noguestattempt', 'mod_vidinteractivo');
        }

        list($course, $cm) = get_course_and_cm_from_cmid($params['cmid'], 'vidinteractivo');
        $context = context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/vidinteractivo:view', $context);

        $vidinteractivo = $DB->get_record('vidinteractivo', ['id' => $cm->instance], '*', MUST_EXIST);
        $interaction = $DB->get_record('vidinteractivo_interactions', [
            'id' => $params['interactionid'],
            'vidinteractivo' => $vidinteractivo->id,
        ], '*', MUST_EXIST);

        if (isset($interaction->visible) && empty($interaction->visible)) {
            throw new \moodle_exception('interactionnotavailable', 'mod_vidinteractivo');
        }

        $attemptcount = $DB->count_records('vidinteractivo_attempts', [
            'interaction' => $interaction->id,
            'userid' => $USER->id,
        ]);
        $attemptnumber = $attemptcount + 1;
        $isauthor = has_capability('mod/vidinteractivo:author', $context);
        $attemptsallowed = isset($interaction->attemptsallowed) ? (int)$interaction->attemptsallowed : 1;
        if (!$isauthor && $attemptsallowed > 0 && $attemptnumber > $attemptsallowed) {
            return [
                'status' => false,
                'iscorrect' => false,
                'score' => 0,
                'maxscore' => 0,
                'feedback' => get_string('attempts_exhausted', 'mod_vidinteractivo'),
                'attemptnumber' => $attemptnumber,
                'attemptsremaining' => 0,
            ];
        }

        $type = registry::normalize_type($interaction->type);
        $element = registry::get($type);
        require_once($CFG->dirroot . '/mod/vidinteractivo/lib.php');
        $config = vidinteractivo_decode_interaction_config($interaction);
        $maxscore = max(0.0, (float)($interaction->maxscore ?? 1));
        $penalty = min(1.0, max(0.0, (float)($interaction->penalty ?? 0)));
        $result = $element->grade_response($config, $params['response'], $maxscore, $attemptnumber, $penalty);

        $now = time();
        $attempt = new stdClass();
        $attempt->vidinteractivo = $vidinteractivo->id;
        $attempt->interaction = $interaction->id;
        $attempt->userid = $USER->id;
        $attempt->attemptnumber = $attemptnumber;
        $attempt->response = $params['response'];
        $attempt->iscorrect = $result->is_correct() === null ? null : ($result->is_correct() ? 1 : 0);
        $attempt->score = $result->get_score();
        $attempt->maxscore = $maxscore;
        $attempt->timetaken = max(0, (int)$params['timetaken']);
        $attempt->timecreated = $now;
        $attempt->timemodified = $now;
        $DB->insert_record('vidinteractivo_attempts', $attempt);

        vidinteractivo_update_grades($vidinteractivo, $USER->id);

        return [
            'status' => true,
            'iscorrect' => $result->is_correct() === true,
            'score' => $result->get_score(),
            'maxscore' => $maxscore,
            'feedback' => $result->get_feedback(),
            'attemptnumber' => $attemptnumber,
            'attemptsremaining' => $attemptsallowed > 0 ? max(0, $attemptsallowed - $attemptnumber) : -1,
        ];
    }

    public static function execute_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_BOOL, 'Operation status'),
            'iscorrect' => new external_value(PARAM_BOOL, 'Whether response is correct'),
            'score' => new external_value(PARAM_FLOAT, 'Awarded score'),
            'maxscore' => new external_value(PARAM_FLOAT, 'Maximum score'),
            'feedback' => new external_value(PARAM_RAW, 'Immediate feedback'),
            'attemptnumber' => new external_value(PARAM_INT, 'Attempt number'),
            'attemptsremaining' => new external_value(PARAM_INT, 'Remaining attempts, -1 means unlimited'),
        ]);
    }
}
