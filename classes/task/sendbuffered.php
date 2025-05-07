<?php
// This file is part of the mod_certifygen plugin for Moodle - http://moodle.org/
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
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
// Project implemented by the "Recovery, Transformation and Resilience Plan.
// Funded by the European Union - Next GenerationEU".
//
// Produced by the UNIMOODLE University Group: Universities of
// Valladolid, Complutense de Madrid, UPV/EHU, León, Salamanca,
// Illes Balears, Valencia, Rey Juan Carlos, La Laguna, Zaragoza, Málaga,
// Córdoba, Extremadura, Vigo, Las Palmas de Gran Canaria y Burgos.

namespace message_appcrue\task;

use coding_exception;
use core\task\scheduled_task;
use message_output_appcrue;
use moodle_exception;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../message_output_appcrue.php');

/**
 * There is a task, Sendbufered.
 * It is responsible for searching buffered messages, sending them, and clearing the buffer.
 * @package    message_appcrue
 * @copyright  2025 Juan Pablo de Castro
 * @author     Juan Pablo de Castro <juan.pablo.de.castro@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sendbuffered extends scheduled_task {
    // Use the logging trait to get some nice, juicy, logging.
    use \core\task\logging_trait;
    /**
     * get_name
     * @return string
     * @throws coding_exception
     */
    public function get_name(): string {
        return get_string('sendbufferedtask', 'message_appcrue');
    }

    /**
     * Get messages in the buffer, ordered by timestamp.
     * Send them in bunches.
     * Remove them from the buffer.
     * @return void
     * @throws coding_exception
     * @throws dml_exception
     */
    public function execute() {
        global $DB;
        // Get appcrue message output.
        $messageoutput = new message_output_appcrue();
        // Get the buffered messages.
        $messages = $DB->get_records('message_appcrue_buffered',
                        ['status' => message_output_appcrue::MESSAGE_READY],
                        'created_at ASC', '*',
                        0, 500);
        // If there are no messages, return.
        if (empty($messages)) {
            return;
        }
        // Accumulate errored messages.
        $globalerrored = [];
        $errorcondition = [];
        // Iterate.
        foreach ($messages as $message) {
            // Get the message.
            $message = (object) $message;
            // Get the recipients. Rename the field recipient_id to id for use in the send_api_message function.
            $recipients = $DB->get_records(
                'message_appcrue_recipients',
                ['message_id' => $message->id],
                null,
                'recipient_id as id'
            );
            // If there are no recipients, continue.
            if (empty($recipients)) {
                continue;
            }
            $unreachable = [];
            try {
                // Send the message.
                $unreachable = $messageoutput->send_api_message( $recipients, $message->subject, $message->body, $message->url);
            } catch (moodle_exception $e) {
                // Error sending the message.
                // Log the error and leave untouched recipients in buffer for retrying.
                debugging($e->getMessage(), DEBUG_DEVELOPER);
                $errorcondition[$message->id] = $e->getMessage();
                continue;
            }
            // If there are no unreachable recipients or want clear buffer, delete all the recipients.
            if (empty($unreachable) || get_config('message_appcrue', 'preserveundeliverable') == false) {
                // Delete all the recipients of $message fron the buffer.
                $DB->delete_records('message_appcrue_recipients', ['message_id' => $message->id]);
            } else {
                // Keep the unreachable recipients in the buffer.
                $globalerrored[$message->id] = $unreachable;
                $errorids = array_keys($unreachable);
                $this->log_no_ajax('Message ' . $message->id . ' not sent to users: ' . implode(',', $errorids));
                [$insql, $params] = $DB->get_in_or_equal($errorids, SQL_PARAMS_QM, null, false);
                // Delete all recipients from the buffer table *except errored users*.
                $DB->delete_records_select(
                    'message_appcrue_recipients',
                    'message_id = ? AND recipient_id ' . $insql,
                    array_merge([$message->id], $params)
                );
                // Mark the message as failed.
                $DB->set_field(
                    'message_appcrue_buffered',
                    'status',
                    message_output_appcrue::MESSAGE_FAILED,
                    ['id' => $message->id]
                );
            }
        }
        // Delete fully sent messages (with no pending recipients).
        $sqlwhere = "id NOT IN (SELECT DISTINCT message_id FROM {message_appcrue_recipients})";
        $DB->delete_records_select('message_appcrue_buffered', $sqlwhere);
        if (!empty($errorcondition)) {
            throw new moodle_exception('sendbufferedtaskerror', 'message_appcrue', '', json_encode($errorcondition));
        }
    }
    /**
     * Only log if not in AJAX mode.
     * @param mixed $message
     * @return void
     */
    protected function log_no_ajax($message) {
        if (!defined('AJAX_SCRIPT') || !AJAX_SCRIPT) {
            $this->log($message);
        }
    } // log_no_ajax
}
