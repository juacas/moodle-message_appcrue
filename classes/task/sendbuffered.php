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
use moodle_exception;
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
        // If buffering is disabled, return.
        if (!get_config('message_appcrue', 'bufferedmode')) {
            return;
        }
        // If the task is not enabled, return.
        // Get the buffered messages.
        $messages = $DB->get_records('message_appcrue_buffered', [], 'created_at ASC', '*', 0, 100);
        // If there are no messages, return.
        if (empty($messages)) {
            return;
        }
        // Get appcrue message output.
        $messageoutput = new \message_output_appcrue();
        // Accumulate errored messages.
        $globalerrored = [];
        // Iterate.
        foreach ($messages as $message) {
            // Get the message.
            $message = (object) $message;
            // Get the recipients. Rename the field recipient_id to id for use in the send_api_message function.
            $recipients = $DB->get_records('message_appcrue_recipients', ['message_id' => $message->id], null, 'recipient_id as id');
            // If there are no recipients, continue.
            if (empty($recipients)) {
                continue;
            }
            $errored = [];
            // Send the message.
            try {
                $errored = $messageoutput->send_api_message( $recipients, $message->subject, $message->body, $message->url);
            } catch (moodle_exception $e) {
                // Log the error.
                debugging($e->getMessage(), DEBUG_DEVELOPER);
                continue;
            }
            // Delete the recipients of $message->id and in $sent array.
            if (empty($errored)) {
                // If there are no errors, delete the recipients.
                $DB->delete_records('message_appcrue_recipients', ['message_id' => $message->id]);
            } else {
                $globalerrored[$message->id] = $errored;
                $errorids = array_keys($errored);
                $this->log_no_ajax('Message ' . $message->id . ' not sent to users: ' . implode(',', $errorids));
                [$insql, $params] = $DB->get_in_or_equal($errorids, SQL_PARAMS_QM, null, false);
                // Delete all recipients from the buffer table except errored users.
                $DB->delete_records_select('message_appcrue_recipients', 'message_id = ? AND recipient_id ' . $insql, array_merge([$message->id], $params));
            }
        }
        // Delete message orphan messages with no recipients.
        $sqlwhere = "id NOT IN (SELECT DISTINCT message_id FROM {message_appcrue_recipients})";
        $DB->delete_records_select('message_appcrue_buffered', $sqlwhere);
        if (!empty($globalerrored)) {
            throw new moodle_exception('sendbufferedtaskerror', 'message_appcrue', '', json_encode($globalerrored));
        }
    }
    /**
     * Only log if not in AJAX mode.
     * @param mixed $message
     * @return void
     */
    protected function log_no_ajax($message) {
        if (!defined('AJAX_SCRIPT')) {
            $this->log($message);
        }
    } // log_no_ajax
}
