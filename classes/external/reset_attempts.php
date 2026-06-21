<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_vidinteractivo\external;

use context_module;
use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

/**
 * Delete a learner's attempts for given interactions so they can retry.
 */
class reset_attempts extends external_api {
    public static function execute_parameters() {
        return new external_function_parameters([
            'cmid'           => new external_value(PARAM_INT, 'Course module id'),
            'interactionids' => new external_multiple_structure(
                new external_value(PARAM_INT, 'Interaction id')
            ),
        ]);
    }

    public static function execute($cmid, $interactionids) {
        global $DB, $USER, $CFG;

        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid'           => $cmid,
            'interactionids' => $interactionids,
        ]);

        if (isguestuser()) {
            throw new \moodle_exception('noguestattempt', 'mod_vidinteractivo');
        }

        list($course, $cm) = get_course_and_cm_from_cmid($params['cmid'], 'vidinteractivo');
        $context = context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/vidinteractivo:view', $context);

        $vidinteractivo = $DB->get_record('vidinteractivo', ['id' => $cm->instance], '*', MUST_EXIST);

        foreach ($params['interactionids'] as $interactionid) {
            $exists = $DB->record_exists('vidinteractivo_interactions', [
                'id'             => (int)$interactionid,
                'vidinteractivo' => $vidinteractivo->id,
            ]);
            if ($exists) {
                $DB->delete_records('vidinteractivo_attempts', [
                    'interaction' => (int)$interactionid,
                    'userid'      => $USER->id,
                ]);
            }
        }

        require_once($CFG->dirroot . '/mod/vidinteractivo/lib.php');
        vidinteractivo_update_grades($vidinteractivo, $USER->id);

        return ['status' => true];
    }

    public static function execute_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_BOOL, 'Operation status'),
        ]);
    }
}
