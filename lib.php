<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Library callbacks for mod_vidinteractivo.
 *
 * @package    mod_vidinteractivo
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Declare module feature support.
 *
 * @param string $feature
 * @return mixed
 */
function vidinteractivo_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
        case FEATURE_GRADE_HAS_GRADE:
        case FEATURE_BACKUP_MOODLE2:
        case FEATURE_COMPLETION_TRACKS_VIEWS:
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        default:
            return null;
    }
}

/**
 * Add a new activity instance.
 *
 * @param stdClass $data
 * @param mod_vidinteractivo_mod_form|null $mform
 * @return int
 */
function vidinteractivo_add_instance($data, $mform = null) {
    global $DB;

    $data->timecreated = time();
    $data->timemodified = $data->timecreated;
    $data->videotype = $data->videotype ?? 'url';
    if ($data->videotype === 'file') {
        $data->videourl = '';
    }
    $data->grade = isset($data->grade) ? $data->grade : 100;

    $data->id = $DB->insert_record('vidinteractivo', $data);

    if ($data->videotype === 'file' && isset($data->video)) {
        $context = context_module::instance($data->coursemodule);
        file_save_draft_area_files($data->video, $context->id, 'mod_vidinteractivo', 'video', 0, ['subdirs' => 0, 'maxfiles' => 1]);
    }

    vidinteractivo_grade_item_update($data);

    return $data->id;
}

/**
 * Update an existing activity instance.
 *
 * @param stdClass $data
 * @param mod_vidinteractivo_mod_form|null $mform
 * @return bool
 */
function vidinteractivo_update_instance($data, $mform = null) {
    global $DB;

    $data->timemodified = time();
    $data->id = $data->instance;
    $data->videotype = $data->videotype ?? 'url';
    if ($data->videotype === 'file') {
        $data->videourl = '';
    }
    $data->grade = isset($data->grade) ? $data->grade : 100;

    $result = $DB->update_record('vidinteractivo', $data);

    if ($data->videotype === 'file' && isset($data->video)) {
        $context = context_module::instance($data->coursemodule);
        file_save_draft_area_files($data->video, $context->id, 'mod_vidinteractivo', 'video', 0, ['subdirs' => 0, 'maxfiles' => 1]);
    }

    vidinteractivo_grade_item_update($data);

    return $result;
}

/**
 * Delete an activity instance and related data.
 *
 * @param int $id
 * @return bool
 */
function vidinteractivo_delete_instance($id) {
    global $DB;

    if (!$instance = $DB->get_record('vidinteractivo', ['id' => $id])) {
        return false;
    }

    $DB->delete_records('vidinteractivo_attempts', ['vidinteractivo' => $id]);
    $DB->delete_records('vidinteractivo_progress', ['vidinteractivo' => $id]);
    $DB->delete_records('vidinteractivo_interactions', ['vidinteractivo' => $id]);
    $DB->delete_records('vidinteractivo', ['id' => $id]);

    vidinteractivo_grade_item_update($instance, 'reset');

    return true;
}

/**
 * Create or update the Gradebook item for this activity.
 *
 * @param stdClass $vidinteractivo
 * @param mixed $grades
 * @return int
 */
function vidinteractivo_grade_item_update($vidinteractivo, $grades = null) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    $params = [
        'itemname' => clean_param($vidinteractivo->name, PARAM_NOTAGS),
        'gradetype' => GRADE_TYPE_VALUE,
        'grademax' => $vidinteractivo->grade,
        'grademin' => 0,
    ];

    if ($grades === 'reset') {
        $params['reset'] = true;
        $grades = null;
    }

    return grade_update('mod/vidinteractivo', $vidinteractivo->course, 'mod', 'vidinteractivo',
        $vidinteractivo->id, 0, $grades, $params);
}

/**
 * Recalculate and push grades to Moodle's Gradebook.
 *
 * @param stdClass $vidinteractivo
 * @param int $userid 0 means all users with attempts.
 */
function vidinteractivo_update_grades($vidinteractivo, $userid = 0) {
    global $DB;

    $grades = [];
    if ($userid) {
        $grade = vidinteractivo_calculate_user_grade($vidinteractivo, $userid);
        if ($grade !== null) {
            $grades[$userid] = $grade;
        }
    } else {
        $userids = $DB->get_fieldset_select(
            'vidinteractivo_attempts',
            'DISTINCT userid',
            'vidinteractivo = ?',
            [$vidinteractivo->id]
        );
        foreach ($userids as $uid) {
            $grade = vidinteractivo_calculate_user_grade($vidinteractivo, $uid);
            if ($grade !== null) {
                $grades[$uid] = $grade;
            }
        }
    }

    if ($grades) {
        vidinteractivo_grade_item_update($vidinteractivo, $grades);
    }
}

/**
 * Calculate one learner's final grade.
 *
 * Formula: (points obtained / points possible) * activity grade max.
 *
 * @param stdClass $vidinteractivo
 * @param int $userid
 * @return stdClass|null
 */
function vidinteractivo_calculate_user_grade($vidinteractivo, $userid) {
    $summary = vidinteractivo_calculate_user_summary($vidinteractivo, $userid);
    if ($summary['possible'] <= 0) {
        return null;
    }

    $grade = new stdClass();
    $grade->userid = $userid;
    $grade->rawgrade = round(($summary['obtained'] / $summary['possible']) * $vidinteractivo->grade, 2);

    return $grade;
}

/**
 * Build the grade/report summary for a learner.
 *
 * @param stdClass $vidinteractivo
 * @param int $userid
 * @return array<string, mixed>
 */
function vidinteractivo_calculate_user_summary($vidinteractivo, $userid) {
    global $DB;

    $interactions = $DB->get_records('vidinteractivo_interactions',
        ['vidinteractivo' => $vidinteractivo->id, 'visible' => 1], 'timestamp ASC, sortorder ASC, id ASC');

    $possible = 0.0;
    $obtained = 0.0;
    $correct = 0;
    $incorrect = 0;
    $answered = 0;

    foreach ($interactions as $interaction) {
        $type = \mod_vidinteractivo\interaction\registry::normalize_type($interaction->type);
        try {
            $element = \mod_vidinteractivo\interaction\registry::get($type);
        } catch (moodle_exception $e) {
            continue;
        }

        $config = vidinteractivo_decode_interaction_config($interaction);
        if (!$element->is_gradable($config)) {
            continue;
        }

        $maxscore = max(0.0, (float)($interaction->maxscore ?? 1));
        $possible += $maxscore;

        $attempts = $DB->get_records('vidinteractivo_attempts',
            ['interaction' => $interaction->id, 'userid' => $userid], 'attemptnumber DESC, id DESC');
        if (!$attempts) {
            continue;
        }

        $answered++;
        $best = null;
        foreach ($attempts as $attempt) {
            if ($best === null || (float)$attempt->score > (float)$best->score) {
                $best = $attempt;
            }
        }

        $obtained += (float)$best->score;
        if ((int)$best->iscorrect === 1) {
            $correct++;
        } else {
            $incorrect++;
        }
    }

    $percentage = $possible > 0 ? round(($obtained / $possible) * 100, 2) : 0.0;
    $passgrade = (float)($vidinteractivo->passgrade ?? 0);

    return [
        'obtained' => round($obtained, 5),
        'possible' => round($possible, 5),
        'percentage' => $percentage,
        'passed' => $passgrade <= 0 ? null : ($percentage >= $passgrade),
        'correct' => $correct,
        'incorrect' => $incorrect,
        'answered' => $answered,
        'total' => count($interactions),
    ];
}

/**
 * Decode interaction JSON configuration while preserving legacy records.
 *
 * @param stdClass $interaction
 * @return array<string, mixed>
 */
function vidinteractivo_decode_interaction_config($interaction): array {
    $content = $interaction->content ?? '';
    $config = [];
    if (is_string($content) && trim($content) !== '') {
        $decoded = json_decode($content, true);
        $config = is_array($decoded) ? $decoded : ['html' => $content, 'capture' => $content];
    }

    if (($interaction->type ?? '') === 'question') {
        $config = \mod_vidinteractivo\interaction\registry::get('multiplechoice')->normalize_config($config);
    }

    return $config;
}

/**
 * Serves the video files.
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return bool false if file not found, does not return if found - just send the file
 */
function vidinteractivo_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()) {
    global $DB, $CFG;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_login($course, true, $cm);

    if (!has_capability('mod/vidinteractivo:view', $context)) {
        return false;
    }

    if ($filearea !== 'video') {
        return false;
    }

    $itemid = (int)array_shift($args);
    if ($itemid != 0) {
        return false;
    }

    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = "/$context->id/mod_vidinteractivo/video/0/$relativepath";

    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        return false;
    }

    // Serve the file.
    send_stored_file($file, 0, 0, $forcedownload, $options);
}
