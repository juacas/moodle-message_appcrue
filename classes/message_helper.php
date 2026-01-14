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

namespace message_appcrue;

/**
 * Class message_helper
 *
 * @package    message_appcrue
 * @copyright  2025 Juan Pablo de Castro <juan.pablo.de.castro@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class message_helper {
    /**
     * Extract body and subject from local_mail messages.
     *
     * Local mail notifies with the format:
     *
     * From: Admin User
     * Subject:  RE: Mensaje con localmail
     * Course: Test Voting
     * Date: 28 August 2025, 12:17 PM
     *
     * BODY MESSAGE
     *
     * ---------
     * View message: https://moodle3.local/moodle405/local/mail/view.php?t=inbox&m=168"
     *
     * @param \stdClass $eventdata The message object.
     * @return array An array with body, subject.
     */
    public static function extract_localmail_body_subject($eventdata): array {
        $messagetext = $eventdata->fullmessage;
        $lines = explode("\n", $messagetext);
        $body = '';
        $subject = '';

        // Extract subject from second line.
        if (isset($lines[1]) && str_starts_with($lines[1], 'Subject:')) {
            $subject = trim(substr($lines[1], 9));
        }
        // Body in from line 5 to end -3.
        if (count($lines) > 5) {
            $body = implode("\n", array_slice($lines, 5, -3));
        } else {
            $body = $messagetext;
        }
        return [$body, "📧 {$subject}"];
    }

    /**
     * Extract body and subject from forum notification.
     * @param \stdClass $eventdata The message object.
     * @return array An array with body, subject.
     */
    public static function extract_forum_body_subject($eventdata): array {
        $subject = $eventdata->subject;
        $body = $eventdata->fullmessage;
        // Extract body.
        if (preg_match('/^-{50,}\n(.*)^-{50,}/sm', $body, $matches)) {
            $body = $matches[1];
        }
        // Remove empty lines.
        $body = preg_replace('/^\r?\n/m', '', $body);
        // Replace bullets+newlines.
        $body = preg_replace('/\*\s*/m', '* ', $body);
        // Replace natural end-of-paragraph new lines (.\n) with <p>.
        $body = '<p>' . preg_replace('/\.\r?\n/m', '</p><p>', $body) . '</p>';

        return [$body, "📢 {$subject}"];
    }
    /**
     * Extract body and subject for instant messages.
     * @param \stdClass $eventdata The message object.
     * @return array An array with body, subject.
     */
    public static function extract_instantmessage_body_subject($eventdata): array {
        $body = $eventdata->fullmessage;
        $subject = $eventdata->subject;

        $messagetxt = $eventdata->fullmessage == "" ? $eventdata->smallmessage : $eventdata->fullmessage;
        // Link to conversation.
        $url = new \moodle_url('/message/index.php', ['id' => $eventdata->userfrom->id]);

        // And add text from Subject.
        $body = $eventdata->smallmessage;
        // Process message.
        // If first line is a MARKDOWN heading use it as subject.
        if (preg_match('/(#+)\s*(.*)\n([\S|\s]*)$/m', $body, $bodyparts)) {
            $level = strlen($bodyparts[1]);
            $subject = $bodyparts[2];
            $body = $bodyparts[3];
        } else {
            $subject = $eventdata->subject;
        }
        // Remove empty lines.
        // Best viewed in just one html paragraph.
        $body = "<p>" . preg_replace('/^\r?\n/m', '', $body) . "</p>";
        return [$body, "💬 {$subject}", $url];
    }
}
