<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_vidinteractivo\external;

use context_module;
use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

/**
 * Delete an interaction and its attempts.
 */
class delete_interaction extends external_api {
    public static function execute_parameters() {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module id'),
            'interactionid' => new external_value(PARAM_INT, 'Interaction id'),
        ]);
    }

    public static function execute($cmid, $interactionid) {
        global $DB, $CFG;

        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
            'interactionid' => $interactionid,
        ]);

        list($course, $cm) = get_course_and_cm_from_cmid($params['cmid'], 'vidinteractivo');
        $context = context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/vidinteractivo:author', $context);

        $vidinteractivo = $DB->get_record('vidinteractivo', ['id' => $cm->instance], '*', MUST_EXIST);
        $interaction = $DB->get_record('vidinteractivo_interactions', [
            'id' => $params['interactionid'],
            'vidinteractivo' => $vidinteractivo->id,
        ], '*', MUST_EXIST);

        $DB->delete_records('vidinteractivo_attempts', ['interaction' => $interaction->id]);
        $DB->delete_records('vidinteractivo_interactions', ['id' => $interaction->id]);

        require_once($CFG->dirroot . '/mod/vidinteractivo/lib.php');
        vidinteractivo_update_grades($vidinteractivo, 0);

        return ['status' => true];
    }

    public static function execute_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_BOOL, 'Operation status'),
        ]);
    }
}
