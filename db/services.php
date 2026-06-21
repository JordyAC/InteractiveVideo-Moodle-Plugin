<?php
// This file is part of Moodle - http://moodle.org/

/**
 * AJAX external functions for mod_vidinteractivo.
 *
 * @package    mod_vidinteractivo
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'mod_vidinteractivo_save_interaction' => [
        'classname' => 'mod_vidinteractivo\external\save_interaction',
        'methodname' => 'execute',
        'description' => 'Save or update an interaction.',
        'type' => 'write',
        'ajax' => true,
    ],
    'mod_vidinteractivo_get_interactions' => [
        'classname' => 'mod_vidinteractivo\external\get_interactions',
        'methodname' => 'execute',
        'description' => 'Get configured interactions for an activity.',
        'type' => 'read',
        'ajax' => true,
    ],
    'mod_vidinteractivo_delete_interaction' => [
        'classname' => 'mod_vidinteractivo\external\delete_interaction',
        'methodname' => 'execute',
        'description' => 'Delete an interaction.',
        'type' => 'write',
        'ajax' => true,
    ],
    'mod_vidinteractivo_save_attempt' => [
        'classname' => 'mod_vidinteractivo\external\save_attempt',
        'methodname' => 'execute',
        'description' => 'Save a learner response and update grades.',
        'type' => 'write',
        'ajax' => true,
    ],
    'mod_vidinteractivo_reset_attempts' => [
        'classname'   => 'mod_vidinteractivo\external\reset_attempts',
        'methodname'  => 'execute',
        'description' => 'Reset learner attempts for one or more interactions.',
        'type'        => 'write',
        'ajax'        => true,
    ],
    'mod_vidinteractivo_get_summary' => [
        'classname' => 'mod_vidinteractivo\external\get_summary',
        'methodname' => 'execute',
        'description' => 'Get the grade summary for the current user.',
        'type' => 'read',
        'ajax' => true,
    ],
];
