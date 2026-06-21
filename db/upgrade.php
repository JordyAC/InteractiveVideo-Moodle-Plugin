<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Upgrade steps for mod_vidinteractivo.
 *
 * @package    mod_vidinteractivo
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade this plugin's database schema.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_vidinteractivo_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026062000) {
        $table = new xmldb_table('vidinteractivo');

        $fields = [
            new xmldb_field('videotype', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'url', 'introformat'),
            new xmldb_field('passgrade', XMLDB_TYPE_NUMBER, '10, 5', null, XMLDB_NOTNULL, null, '0', 'grade'),
            new xmldb_field('completionrequiresall', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'passgrade'),
        ];
        foreach ($fields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        $field = new xmldb_field('videourl', XMLDB_TYPE_CHAR, '1333', null, null, null, null, 'videotype');
        if ($dbman->field_exists($table, $field)) {
            $dbman->change_field_precision($table, $field);
            $dbman->change_field_notnull($table, $field);
        }

        $table = new xmldb_table('vidinteractivo_interactions');
        $fields = [
            new xmldb_field('timeend', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'timestamp'),
            new xmldb_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'timeend'),
            new xmldb_field('title', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'type'),
            new xmldb_field('maxscore', XMLDB_TYPE_NUMBER, '10, 5', null, XMLDB_NOTNULL, null, '1', 'content'),
            new xmldb_field('attemptsallowed', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '1', 'maxscore'),
            new xmldb_field('penalty', XMLDB_TYPE_NUMBER, '10, 5', null, XMLDB_NOTNULL, null, '0', 'attemptsallowed'),
            new xmldb_field('required', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'penalty'),
            new xmldb_field('pause', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'required'),
            new xmldb_field('visible', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'pause'),
        ];
        foreach ($fields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        $oldindex = new xmldb_index('timestamp', XMLDB_INDEX_NOTUNIQUE, ['timestamp']);
        if ($dbman->index_exists($table, $oldindex)) {
            $dbman->drop_index($table, $oldindex);
        }
        $index = new xmldb_index('vid-time', XMLDB_INDEX_NOTUNIQUE, ['vidinteractivo', 'timestamp']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }
        $index = new xmldb_index('vid-type', XMLDB_INDEX_NOTUNIQUE, ['vidinteractivo', 'type']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        $table = new xmldb_table('vidinteractivo_attempts');
        $oldindex = new xmldb_index('interaction-userid', XMLDB_INDEX_UNIQUE, ['interaction', 'userid']);
        if ($dbman->index_exists($table, $oldindex)) {
            $dbman->drop_index($table, $oldindex);
        }

        $fields = [
            new xmldb_field('attemptnumber', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '1', 'userid'),
            new xmldb_field('maxscore', XMLDB_TYPE_NUMBER, '10, 5', null, XMLDB_NOTNULL, null, '0', 'score'),
            new xmldb_field('timetaken', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'maxscore'),
        ];
        foreach ($fields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }
        $index = new xmldb_index('interaction-userid', XMLDB_INDEX_NOTUNIQUE, ['interaction', 'userid']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }
        $index = new xmldb_index('vid-user', XMLDB_INDEX_NOTUNIQUE, ['vidinteractivo', 'userid']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        $table = new xmldb_table('vidinteractivo_progress');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('vidinteractivo', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('lastposition', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('completed', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('vidinteractivo', XMLDB_KEY_FOREIGN, ['vidinteractivo'], 'vidinteractivo', ['id']);
            $table->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
            $table->add_index('vid-user', XMLDB_INDEX_UNIQUE, ['vidinteractivo', 'userid']);
            $dbman->create_table($table);
        }

        upgrade_mod_savepoint(true, 2026062000, 'vidinteractivo');
    }

    return true;
}
