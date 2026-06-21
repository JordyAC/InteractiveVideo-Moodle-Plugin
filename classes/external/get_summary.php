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
 * Get the summary of grades for the current user.
 */
class get_summary extends external_api {
    public static function execute_parameters() {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module id'),
        ]);
    }

    public static function execute($cmid) {
        global $DB, $USER, $CFG;

        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
        ]);

        list($course, $cm) = get_course_and_cm_from_cmid($params['cmid'], 'vidinteractivo');
        $context = context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/vidinteractivo:view', $context);

        $vidinteractivo = $DB->get_record('vidinteractivo', ['id' => $cm->instance], '*', MUST_EXIST);
        
        require_once($CFG->dirroot . '/mod/vidinteractivo/lib.php');
        $summary = vidinteractivo_calculate_user_summary($vidinteractivo, $USER->id);

        return [
            'obtained' => $summary['obtained'],
            'possible' => $summary['possible'],
            'percentage' => $summary['percentage'],
            'correct' => $summary['correct'],
            'incorrect' => $summary['incorrect'],
            'total' => $summary['total'],
        ];
    }

    public static function execute_returns() {
        return new external_single_structure([
            'obtained' => new external_value(PARAM_FLOAT, 'Obtained score'),
            'possible' => new external_value(PARAM_FLOAT, 'Possible score'),
            'percentage' => new external_value(PARAM_FLOAT, 'Percentage'),
            'correct' => new external_value(PARAM_INT, 'Correct answers'),
            'incorrect' => new external_value(PARAM_INT, 'Incorrect answers'),
            'total' => new external_value(PARAM_INT, 'Total interactions'),
        ]);
    }
}
