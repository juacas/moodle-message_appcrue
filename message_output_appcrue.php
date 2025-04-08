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
require_once($CFG->dirroot.'/user/profile/lib.php');

class message_output_appcrue extends \message_output {

    /**
     * Processes the message and sends a notification via appcrue
     *
     * @param stdClass $eventdata the event data submitted by the message sender plus $eventdata->savedmessageid
     * @return true if ok, false if error
     */
    public function send_message($eventdata) {
        global $CFG;
        $enabled = get_config('message_appcrue', 'enable_push');
        // Skip any messaging of suspended and deleted users.
        if (!$enabled or $eventdata->userto->auth === 'nologin'
            or $eventdata->userto->suspended
            or $eventdata->userto->deleted) {
            return true;
        }
        // Skip any messaging if suspended by admin system-wide.
        if ($eventdata->userto->email !== 'dummyuser@bademail.local' // Note: special user bypassed while testing.
            && !empty($CFG->noemailever)) {
            // Hidden setting for development sites, set in config.php if needed.
            debugging('$CFG->noemailever is active, no appcrue message sent.', DEBUG_MINIMAL);
            return true;
        }
        if ($this->is_system_configured() == false) {
            debugging('Appcrue endpoint is not configured in settings.', DEBUG_NORMAL);
            return true;
        }
        if ($this->skip_message($eventdata)) {
            return true;
        }
        $url = $eventdata->contexturl;
        $message  = $eventdata->fullmessage;
        $level = 3; // Default heading level.
        $subject = $eventdata->subject;
        $body = $eventdata->smallmessage;
        // Parse and format diferent message formats.
        if ($eventdata->component == 'mod_forum') {
            // Extract body.
            if (preg_match('/^-{50,}\n(.*)^-{50,}/sm', $message, $matches)) {
                $body = $matches[1];
            }
            // Remove empty lines.
            $body = preg_replace('/^\r?\n/m', '', $body);
            // Replace bullets+newlines.
            $body = preg_replace('/\*\s*/m', '* ', $body);
            // Replace natural end-of-paragraph new lines (.\n) with <p>.
            $body = '<p>' . preg_replace('/\.\r?\n/m', '</p><p>', $body) . '</p>';

        } else if ($eventdata->component == 'moodle' && $eventdata->name == 'instantmessage') {
            // Extract URL from body of fullmessage.
            $re = '/((https?:\/\/)[^\s.]+\.[:\w][^\s"]+)/m';
            if (preg_match($re, $eventdata->fullmessage, $matches)) {
                $url = $matches[1];
            }
            // And add text from Subject.
            $body = $eventdata->smallmessage;
            // Process message.
            // If first line is a MARKDOWN heading use it as subject.
            if (preg_match('/(#+)\s*(.*)\n([\S|\s]*)$/m', $body, $bodyparts)) {
                $level = strlen($bodyparts[1]);
                $subject = $bodyparts[2];
                $body = $bodyparts[3];
            }
            // Remove empty lines.
            // Best viewed in just one html paragraph.
            $body = "<p>" . preg_replace('/^\r?\n/m', '', $body) . "</p>";
        }

        $message = "<h{$level}>$subject</h{$level}>$body";
        // Create target url.
        $url = $this->get_target_url($url);
        if (get_config('message_appcrue', 'bufferedmode') == true) {
            // Buffer volume messages in a table an send them in bulk.
            return $this->buffer_message($eventdata->userto, $subject, $message, $url);
        } else {
            return $this->send_api_message([$eventdata->userto], $subject, $body, $url);
        }
    }
    /**
     * If module local_appcrue is installed and configured uses autologin.php to navigate.
     * @see local_appcrue plugin.
     */
    protected function get_target_url($url) {
        global $CFG;
        $urlpattern = get_config('message_appcrue', 'urlpattern');
        if (empty($urlpattern)) {
            return $url;
        }
        // Escape url.
        $url = urlencode($url);
        // Replace placeholders.
        $url = str_replace(['{url}', '{siteurl}'], [$url, $CFG->wwwroot], $urlpattern);
        return $url;
    }
    /**
     * Buffer messages (by title, message, url hash) and recipients in separate tables.
     * @param \stdClass $user The Moodle user record that is being sent to.
     * @param string $body The message content to send to push.
     * @param string $title The title of the message.
     * @param string $url url to see the details of the notification.
     */
    protected function buffer_message($user, $title, $body, $url) {
        global $DB;
        // Check if the message is already in the table.
        $hash = hash('sha256', $title . $body . $url);
        $message = $DB->get_record('message_appcrue_buffered', array('hash' => $hash));
        if (!$message) {
            // Insert message in the table.
            $message = new stdClass();
            $message->hash = $hash;
            $message->subject = $title;
            $message->body = $body;
            $message->url = $url;
            $message->created_at = time();
            $message->id = $DB->insert_record('message_appcrue_buffered', $message);
            if (!$message->id) {
                debugging("Error inserting message {$message} in buffer.", DEBUG_DEVELOPER);
                return false;
            }
        }
        // Insert user in the table.
        if (!$DB->insert_record('message_appcrue_recipients', array('recipient_id' => $user->id, 'message_id' => $message->id))) {
            debugging("Error inserting user {$user->id} in buffer.", DEBUG_DEVELOPER);
            return false;
        }
        return true;
    }
    /** 
     * Create bunches of 1000 users and send them to AppCrue.
     */
    public function send_api_message($users, $title, $body, $url='') {
         // Collect device aliases.
         $devicealiases = [];
         foreach ($users as $user) {
             $alias = $this->get_nick_name($user);
             if (empty($alias)) {
                 debugging("User {$user->id} has no device alias.", DEBUG_NORMAL);
                 continue;
             }
             $devicealiases[] = $alias;
         }
        // Split devicealiases in bunches of 1000.
        $chunks = array_chunk($devicealiases, 1000);
        foreach ($chunks as $chunk) {
            $this->send_api_message_chunk($chunk, $title, $body, $url);
        }
        return true;
    }
    /**
     * Send the message using TwinPush.
     * @param array $devicealiases The list of device aliases to send the message to.
     * @param string $title The title of the message.
     * @param string $body The message contect to send to AppCrue.
     * @param string $url url to see the details of the notification.
     * @return boolean false if message was not sent, true if sent.
     */
    public function send_api_message_chunk($devicealiases, $title, $body, $url='') {
       
        if (empty($devicealiases)) {
            return false;
        }

        $apicreator = get_config('message_appcrue', 'apikey');
        $appid = get_config('message_appcrue', 'appid');
        $data = new stdClass();
        $data->broadcast = false;
        $data->devices_aliases = $devicealiases;
        // Omit unused tags.
        if (false) {
            $data->devices_ids = array();
            $data->segments = array();
            $target = new stdClass();
            $target->name = array();
            $target->values = array();
            $data->target_property = $target;
        }
        $data->title = $title;
        $data->group_name = get_config('message_appcrue', 'group_name');
        $data->alert = $this->trim_alert_text($body);
        $data->url = $url;
        $data->inbox = true;
        // Ask to open the url in a webview and show a link in notification panel.
        $data->custom_properties = new stdClass();
        $data->custom_properties->target = 'webview';
        $data->custom_properties->target_id = $url;
        $jsonnotificacion = json_encode($data);
        $client = new curl();
        $client->setHeader(array('Content-Type:application/json', 'X-TwinPush-REST-API-Key-Creator:'.$apicreator));
        $options = [
            'CURLOPT_RETURNTRANSFER' => true,
            'CURLOPT_CONNECTTIMEOUT' => 5 // JPC: Limit impact on other scheduled tasks.
        ];
        $response = $client->post("https://appcrue.twinpush.com/api/v2/apps/{$appid}/notifications",
            $jsonnotificacion,
            $options);
        // Catch errors in response and log them.
        $respjson = json_decode($response);
        $aliasesstr = implode(', ', $devicealiases);
        if (isset($respjson->errors)) {
            debugging("Error sending message {$title} to {$aliasesstr}: {$respjson->errors}", DEBUG_NORMAL);
            return false;
        } else {
            debugging("Message {$title} sent to {$aliasesstr}.", DEBUG_DEVELOPER);
        }
        // Check if any error occurred.
        $info = $client->get_info();
        if ($client->get_errno() || $info['http_code'] != 200) {
            debugging('Curl error: ' . $client->get_errno(). ':' . $response , DEBUG_MINIMAL);
            return false;
        } else {
            return true;
        }
    }
    /** Limit lenght of text to 240 characters */
    protected function trim_alert_text($text) {
        if (strlen($text) > 240) {
            $trimmed = substr($text, 0, 240) . '…';
            return $trimmed;
        }
        return $text;
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
     * @return boolean true if API is configured
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
                debugging("This forum message is filtered out due to configuration.", DEBUG_DEVELOPER);
                return true;
            }
        }
        return false;
    }
}
