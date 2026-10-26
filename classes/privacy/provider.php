<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Privacy Subsystem implementation for message_appcrue.
 *
 * @package    message_appcrue
 * @copyright  2021 onwards Juan Pablo de Castro <juanpablo.decastro@uva.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace message_appcrue\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider for message_appcrue.
 *
 * @package    message_appcrue
 * @copyright  2021 onwards Juan Pablo de Castro <juanpablo.decastro@uva.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {

    /**
     * Returns meta data about this system.
     *
     * @param collection $collection The initialised collection to add items to.
     * @return collection A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('message_appcrue_recipients', [
            'message_id' => 'privacy:metadata:message_appcrue_recipients:message_id',
            'recipient_id' => 'privacy:metadata:message_appcrue_recipients:recipient_id',
        ], 'privacy:metadata:message_appcrue_recipients');

        $collection->add_database_table('message_appcrue_buffered', [
            'hash' => 'privacy:metadata:message_appcrue_buffered:hash',
            'subject' => 'privacy:metadata:message_appcrue_buffered:subject',
            'body' => 'privacy:metadata:message_appcrue_buffered:body',
            'url' => 'privacy:metadata:message_appcrue_buffered:url',
            'created_at' => 'privacy:metadata:message_appcrue_buffered:created_at',
            'status' => 'privacy:metadata:message_appcrue_buffered:status',
        ], 'privacy:metadata:message_appcrue_buffered');

        $collection->link_external_location('TwinPush API (AppCrue)', [
            'device_aliases' => 'privacy:metadata:twinpush:device_aliases',
            'title' => 'privacy:metadata:twinpush:title',
            'alert' => 'privacy:metadata:twinpush:alert',
            'target_id' => 'privacy:metadata:twinpush:target_id',
        ], 'privacy:metadata:twinpush');

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid The user to search.
     * @return contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $sql = "SELECT ctx.id
                  FROM {message_appcrue_recipients} r
                  JOIN {context} ctx ON ctx.instanceid = r.recipient_id AND ctx.contextlevel = :contextlevel
                 WHERE r.recipient_id = :userid";

        $params = [
            'contextlevel' => CONTEXT_USER,
            'userid' => $userid,
        ];

        $contextlist = new contextlist();
        $contextlist->add_from_sql($sql, $params);
        return $contextlist;
    }

    /**
     * Get the list of users within a specific context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!$context instanceof \context_user) {
            return;
        }

        $sql = "SELECT recipient_id
                  FROM {message_appcrue_recipients}
                 WHERE recipient_id = :userid";
        $params = ['userid' => $context->instanceid];
        $userlist->add_from_sql('recipient_id', $sql, $params);
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_user || $context->instanceid != $userid) {
                continue;
            }

            $sql = "SELECT b.id, b.subject, b.body, b.url, b.created_at, b.status
                      FROM {message_appcrue_recipients} r
                      JOIN {message_appcrue_buffered} b ON b.id = r.message_id
                     WHERE r.recipient_id = :userid
                  ORDER BY b.created_at ASC";

            $records = $DB->get_records_sql($sql, ['userid' => $userid]);
            if (empty($records)) {
                continue;
            }

            $data = [];
            foreach ($records as $record) {
                $data[] = (object) [
                    'subject' => $record->subject,
                    'body' => $record->body,
                    'url' => $record->url,
                    'created_at' => transform::datetime($record->created_at),
                    'status' => $record->status == 0 ? 'ready' : 'failed',
                ];
            }

            writer::with_context($context)->export_data(
                [get_string('privacy:subcontext', 'message_appcrue')],
                (object) ['messages' => $data]
            );
        }
    }

    /**
     * Delete all user data which matches the specified context.
     *
     * @param \context $context A context.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        if (!$context instanceof \context_user) {
            return;
        }

        static::delete_data_for_userid((int)$context->instanceid);
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        $context = $userlist->get_context();

        if (!$context instanceof \context_user) {
            return;
        }

        $userids = $userlist->get_userids();
        foreach ($userids as $userid) {
            if ($userid == $context->instanceid) {
                static::delete_data_for_userid((int)$userid);
            }
        }
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context instanceof \context_user && $context->instanceid == $userid) {
                static::delete_data_for_userid((int)$userid);
            }
        }
    }

    /**
     * Delete all records related to a userid and remove orphaned buffered messages.
     *
     * @param int $userid The user ID.
     */
    protected static function delete_data_for_userid(int $userid): void {
        global $DB;

        $DB->delete_records('message_appcrue_recipients', ['recipient_id' => $userid]);

        // Clean up orphaned buffered messages that no longer have any recipients.
        $sqlwhere = "id NOT IN (SELECT DISTINCT message_id FROM {message_appcrue_recipients})";
        $DB->delete_records_select('message_appcrue_buffered', $sqlwhere);
    }
}
