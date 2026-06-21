<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Teacher report for mod_vidinteractivo.
 *
 * @package    mod_vidinteractivo
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

$id = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('vidinteractivo', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$vidinteractivo = $DB->get_record('vidinteractivo', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/vidinteractivo:viewreports', $context);

$PAGE->set_url('/mod/vidinteractivo/report.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($vidinteractivo->name) . ': ' . get_string('report', 'mod_vidinteractivo'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($vidinteractivo->name) . ': ' . get_string('report', 'mod_vidinteractivo'), 2);

$users = get_enrolled_users($context, 'mod/vidinteractivo:view', 0, 'u.id, u.firstname, u.lastname, u.email');

$table = new html_table();
$table->head = [
    get_string('student', 'mod_vidinteractivo'),
    get_string('answered', 'mod_vidinteractivo'),
    get_string('correctcount', 'mod_vidinteractivo'),
    get_string('incorrectcount', 'mod_vidinteractivo'),
    get_string('score', 'mod_vidinteractivo'),
    get_string('percentage', 'mod_vidinteractivo'),
    get_string('finalgrade', 'mod_vidinteractivo'),
    get_string('progress', 'mod_vidinteractivo'),
];
$table->attributes['class'] = 'generaltable table table-striped';

foreach ($users as $user) {
    if (has_capability('mod/vidinteractivo:author', $context, $user->id)) {
        continue;
    }

    $summary = vidinteractivo_calculate_user_summary($vidinteractivo, $user->id);
    $grade = vidinteractivo_calculate_user_grade($vidinteractivo, $user->id);
    $status = get_string('notgraded', 'mod_vidinteractivo');
    if ($summary['passed'] === true) {
        $status = get_string('passed', 'mod_vidinteractivo');
    } else if ($summary['passed'] === false) {
        $status = get_string('failed', 'mod_vidinteractivo');
    }

    $table->data[] = [
        fullname($user),
        $summary['answered'],
        $summary['correct'],
        $summary['incorrect'],
        format_float($summary['obtained'], 2) . ' / ' . format_float($summary['possible'], 2),
        format_float($summary['percentage'], 2) . '%',
        $grade ? format_float($grade->rawgrade, 2) : '-',
        $status,
    ];
}

if (empty($table->data)) {
    echo $OUTPUT->notification(get_string('nothingtodisplay'), 'info');
} else {
    echo html_writer::table($table);
}

echo $OUTPUT->single_button(new moodle_url('/mod/vidinteractivo/view.php', ['id' => $cm->id]), get_string('back'), 'get');
echo $OUTPUT->footer();
