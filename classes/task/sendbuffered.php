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
            // Send the message.
            try {
                $messageoutput->send_api_message( $recipients, $message->subject, $message->body, $message->url);
            } catch (moodle_exception $e) {
                // Log the error.
                debugging($e->getMessage(), DEBUG_DEVELOPER);
                continue;
            }
            // Delete the message from the buffer.
            $DB->delete_records('message_appcrue_recipients', ['message_id' => $message->id]);
            $DB->delete_records('message_appcrue_buffered', ['id' => $message->id]);
        }
    }
}
