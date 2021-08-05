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
 * AppCrue message plugin version information.
 *
 * @package message_appcrue
 * @category admin
 * @author Jose Manuel Lorenzo
 * @author  Juan Pablo de Castro
 * @copyright 2021 onwards josemanuel.lorenzo@ticarum.es, juanpablo.decastro@uva.es
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/message/output/lib.php');
require_once($CFG->dirroot.'/lib/filelib.php');

class message_output_appcrue extends \message_output {

    /**
     * Processes the message and sends a notification via appcrue
     *
     * @param stdClass $eventdata the event data submitted by the message sender plus $eventdata->savedmessageid
     * @return true if ok, false if error
     */
    public function send_message($eventdata) {
        global $CFG;
        // Skip any messaging of suspended and deleted users.
        if ($eventdata->userto->auth === 'nologin' or $eventdata->userto->suspended or $eventdata->userto->deleted) {
            return true;
        }
        // Skip any messaging if suspended by admin system-wide.
        if (!empty($CFG->noemailever)) {
            // Hidden setting for development sites, set in config.php if needed.
            debugging('$CFG->noemailever is active, no appcrue message sent.', DEBUG_MINIMAL);
            return true;
        }
        if ($this->skip_message($eventdata)) {
            return true;
        }
        $url = $eventdata->contexturl;
        $message  = $eventdata->fullmessage;
        // Parse and format diferent message formats.
        if ($eventdata->component == 'mod_forum') {
            // Extract body.
            if (preg_match('/^-{50,}\n(.*)^-{50,}/sm', $message, $matches)) {
                $body = $matches[1];
                $subject = $eventdata->subject;
                $message = $subject . "\n" . $body;
            }

        } else if ($eventdata->component == 'moodle' && $eventdata->name == 'instantmessage') {
            // Extract URL from body of fullmessage.
            $re = '/((https:\/\/)[^\s.]+\.[\w][^\s]+)/m';
            if (preg_match($re, $eventdata->fullmessage, $matches)) {
                $url = $matches[1];
            }
            // And add text from Subject.
            $message = $eventdata->subject . "\n" . $eventdata->smallmessage;
        }

        // Remove empty lines.
        $message = preg_replace('/^\r?\n/m', '', $message);
        // Replace new lines with <p>.
        $message = '<p>' . preg_replace('/\r?\n/m', '</p><p>', $message) . '</p>';

        return $this->send_api_message($eventdata->userto, $message, $url);
    }
    /**
     * Send the message using TwinPush.
     * @param string $message The message contect to send to AppCrue.
     * @param \stdClass $user The Moodle user record that is being sent to.
     * @param string $url url to see the details of the notification.
     */
    public function send_api_message($user, $message, $url='') {
        $apicreator = get_config('message_appcrue', 'apikey');
        $appid = get_config('message_appcrue', 'appid');
        $data = new stdClass();
        $data->broadcast = false;
        $data->devices_aliases = array($this->get_nick_name($user));
        $data->devices_ids = array();
        $data->segments = array();
        $target = new stdClass();
        $target->name = array();
        $target->values = array();
        $data->target_property = $target;
        $data->title = get_site()->fullname;
        $data->group_name = "Moodle message";
        $data->alert = $message;
        $data->url = $url;
        $data->inbox = true;
        $jsonnotificacion = json_encode($data);
        $ch = curl_init();
        // Attach encoded JSON string to the POST fields.
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonnotificacion);
        // Set the content type to application/json.
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json', 'X-TwinPush-REST-API-Key-Creator:'.$apicreator));
        curl_setopt($ch, CURLOPT_URL, "https://appcrue.twinpush.com/api/v2/apps/{$appid}/notifications");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); // JPC: Limit impact on other scheduled tasks.
        $response = curl_exec($ch);
        // Catch errors and log them.
        debugging("Push API Response:{$response}", DEBUG_DEVELOPER);
        // Check if any error occurred.
        if (curl_errno($ch)) {
            debugging('Curl error: ' . curl_error($ch));
            curl_close($ch);
            return false;
        } else {
            curl_close($ch);
            return true;
        }
    }
    /**
     * Returns the target nickname of the user in the Push API
     */
    public function get_nick_name($user) {
        $fieldname = get_config('message_appcrue', 'match_user_by');
        if (!isset($user->$fieldname)) {
            profile_load_data($user);
        }
        return $user->$fieldname;
    }
    /**
     * Creates necessary fields in the messaging config form.
     * @param array $preferences An object of user preferences
     */
    public function config_form($preferences) {
        return null;
    }

    /**
     * Parses the submitted form data and saves it into preferences array.
     *
     * @param stdClass $form preferences form class
     * @param array $preferences preferences array
     */
    public function process_form($form, &$preferences) {
        return true;
    }

    /**
     * Loads the config data from database to put on the form during initial form display.
     *
     * @param object $preferences preferences object
     * @param int $userid the user id
     */
    public function load_data(&$preferences, $userid) {
        return true;
    }

    public function is_user_configured($user = null) {
        return true;
    }

    /**
     * Tests whether the AppCrue settings have been configured
     * @return boolean true if Telegram is configured
     */
    public function is_system_configured() {
        return (get_config('message_appcrue', 'apikey') && get_config('message_appcrue', 'appid'));
    }
    /**
     * Check wheter to skip this message or not.
     * @param stdClass $eventdata the event data submitted by the message sender
     * @return boolean should be skiped?
     */
    protected function skip_message($eventdata) {
        global $DB;
        // If configured, skip forum messages not from "news" special forum.
        if (get_config('message_appcrue', 'onlynewsforum') == true &&
            $eventdata->component == 'mod_forum' &&
            preg_match('/\Wd=(\d+)/', $eventdata->contexturl, $matches) ) {

            $id = (int) $matches[1];
            $forumid = $DB->get_field('forum_discussions', 'forum', array('id' => $id));
            $forum = $DB->get_record("forum", array("id" => $forumid));
            if ($forum->type !== "news") {
                debugging("This forum message is filtered out due to configuration.");
                return true;
            }
        }
        return false;
    }
}
