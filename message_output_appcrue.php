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
use message_appcrue\twinpush_client;

global $CFG;
require_once($CFG->dirroot . '/message/output/lib.php');

/**
 * Messaging system for AppCrue.
 */
class message_output_appcrue extends \message_output {
    // Use the logging trait to get some nice, juicy, logging.
    use \core\task\logging_trait;

    /** @var int Buffered message ready to be sent. */
    public const MESSAGE_READY = 0;
    /** @var int Buffered message which presented failures when it was sent. */
    public const MESSAGE_FAILED = 1;

    /**
     * Api client instance.
     * @var message_appcrue\twinpush_client
     */
    public $apiclient = null;
    /**
     * Constructor.
     */
    public function __construct() {
        $apicreator = get_config('message_appcrue', 'apikey');
        $appid = get_config('message_appcrue', 'appid');
        $this->apiclient = new twinpush_client($apicreator, $appid);
    }
    /**
     * Processes the message and sends a notification via AppCrue.
     *
     * @param stdClass $eventdata the event data submitted by the message sender plus $eventdata->savedmessageid
     * @return true if ok, false if error
     */
    public function send_message($eventdata) {
        global $CFG;
        $enabled = get_config('message_appcrue', 'enable_push');
        //
        // Skip any messaging of suspended and deleted users.
        if (
            !$enabled || $eventdata->userto->auth === 'nologin'
            || $eventdata->userto->suspended
            || $eventdata->userto->deleted
        ) {
            return true;
        }
        // Skip any messaging if suspended by admin system-wide.
        if (
            $eventdata->userto->email !== 'dummyuser@bademail.local' // Note: special user bypassed while testing.
            && !empty($CFG->noemailever)
        ) {
            // Hidden setting for development sites, set in config.php if needed.
            debugging('$CFG->noemailever is active, no appcrue message sent.', DEBUG_MINIMAL);
            return true;
        }
        if ($this->is_system_configured() == false) {
            debugging('Appcrue endpoint is not configured in settings.', DEBUG_NORMAL);
            return true;
        }
        if ($this->should_skip_message($eventdata)) {
            return true;
        }

        $message = $this->build_message($eventdata);

        if (get_config('message_appcrue', 'bufferedmode') == true) {
            // Buffer volume messages in a table an send them in bulk.
            return $this->buffer_message($eventdata->userto, $message->subject, $message->body, $message->url, self::MESSAGE_READY);
        } else {
            // Send message to pushAPI.
            $errors = $this->send_api_message([$eventdata->userto], $message->subject, $message->body, $message->url);
            if (get_config('message_appcrue', 'preserveundeliverable') == true) {
                // Buffer message if any error occurred.
                foreach ($errors as $userid => $devicealias) {
                    $user = new stdClass();
                    $user->id = $userid;
                    if (!$this->buffer_message($user, $message->subject, $message->body, $message->url, self::MESSAGE_FAILED)) {
                        debugging("Error buffering message " . json_encode($message) . " for user {$user->id}.", DEBUG_DEVELOPER);
                        return false;
                    }
                }
            }
            $this->log_no_ajax("Message in error '{$message->subject}' buffered for users: " . implode(', ', array_keys($errors)));
            return true;
        }
    }
    /**
     * Format the message body.
     * For forums, it extracts the body from the fullmessage, clean it and format it as HTML.
     * For instant messages, it uses the smallmessage and formats it as HTML. If the message
     * has a Markdown heading, it uses it as subject.
     *
     * @param stdClass $eventdata The message to format.
     * @return stdClass The formatted  message.
     */
    protected function build_message($eventdata) {
        $message = new stdClass();
        $level = 3; // Default heading level.

        $url = $eventdata->contexturl;

        $body = $eventdata->fullmessage;
        $subject = $eventdata->subject;

        // Parse and format diferent message formats.
        if ($eventdata->component == 'mod_forum') {
            [$body, $subject] = message_appcrue\message_helper::extract_forum_body_subject($eventdata);
        } else if ($eventdata->component == 'moodle' && $eventdata->name == 'instantmessage') {
            [$body, $subject, $url] = message_appcrue\message_helper::extract_instantmessage_body_subject($eventdata);
        } else if ($eventdata->component == 'local_mail') {
            [$body, $subject] = message_appcrue\message_helper::extract_localmail_body_subject($eventdata);
        }

        $message->body = strip_tags($body); // NOTE: Temporaly strip tags for push notification. Remove after fix in apps.
        // Create target url.
        $message->url = $url ? $this->get_target_url($url) : null;
        $message->subject = strip_tags($subject);
        return $message;
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
        $url = str_replace(
            ['{url}', '{siteurl}'],
            [$url, $CFG->wwwroot],
            $urlpattern
        );
        return $url;
    }
    /**
     * Buffer messages (by title, message, url hash) and recipients in separate tables.
     *
     * @param stdClass $user The Moodle user record that is being sent to.
     * @param string $body The message content to send to push.
     * @param string $title The title of the message.
     * @param string $url url to see the details of the notification.
     */
    protected function buffer_message($user, $title, $body, $url, $status = self::MESSAGE_READY) {
        global $DB;
        // Check if the message is already in the table.
        $hash = hash('sha256', $title . $body . $url . $status);
        $message = $DB->get_record('message_appcrue_buffered', ['hash' => $hash]);
        if (!$message) {
            // Insert message in the table.
            $message = new stdClass();
            $message->hash = $hash;
            $message->subject = $title;
            $message->body = $body;
            $message->url = $url;
            $message->created_at = time();
            $message->status = $status;
            $message->id = $DB->insert_record('message_appcrue_buffered', $message);
            if (!$message->id) {
                debugging("Error inserting message " . json_encode($message) . " in buffer.", DEBUG_DEVELOPER);
                return false;
            }
        }
        // Check if the user is already in the table.
        $recipient = $DB->get_record(
            'message_appcrue_recipients',
            ['recipient_id' => $user->id, 'message_id' => $message->id]
        );
        if ($recipient) {
            // User already in the table.
            return true;
        }
        // Insert user in the table.
        if (!$DB->insert_record('message_appcrue_recipients', ['recipient_id' => $user->id, 'message_id' => $message->id])) {
            debugging("Error inserting user {$user->id} in buffer.", DEBUG_DEVELOPER);
            return false;
        }
        return true;
    }

    /**
     * Create bunches of 1000 users and send them to AppCrue.
     * @param array $users The list of users to send the message to.
     * @param string $title The title of the message.
     * @param string $body The message content to send to AppCrue.
     * @param string $url url to see the details of the notification.
     * @return array The list of userid=>alias that errored while sending the message [$user->id => $devicealias].
     * @throws moodle_exception if API can't be reached.
     */
    public function send_api_message($users, $title, $body, $url = '') {
        global $DB;
        // Accumulate errors.
        $errors = [];
        // Split users in bunches of 1000.
        $chunks = array_chunk($users, 1000);
        foreach ($chunks as $chunk) {
            // Load full user records for get_nick_name.
            $ids = array_map(
                function ($e) {
                    return $e->id;
                },
                $chunk
            );
            $users = $DB->get_records_list('user', 'id', $ids);
            // Collect device aliases.
            $devicealiases = [];
            foreach ($users as $user) {
                $alias = $this->get_nick_name($user);
                if (empty($alias)) {
                    $this->log_no_ajax("User {$user->id} has no device alias.");
                    continue;
                }
                $devicealiases[$user->id] = $alias;
            }
            $errored = $this->apiclient->send_api_message_chunk($devicealiases, $title, $body, $url);

            // Add to error list preserving keys.
            foreach ($errored as $userid => $alias) {
                $errors[$userid] = $alias;
            }
        }
        return $errors;
    }

    /**
     * Limit text length to 240 characters.
     *
     * @param string $text body message.
     */
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
            // Load profile data.
            profile_load_data($user);
        }
        return $user->$fieldname;
    }
    /**
     * Search user fields and get the user
     * @param string $fieldname the name of the field to search into
     * @param string $matchvalue the value to search for.
     * @return stdClass user structure
     */
    public function find_user($fieldname, $matchvalue) {
        global $DB, $CFG;
        if (empty($matchvalue)) {
            throw new Exception("Empty matchvalue to search by: {$fieldname}");
        }
        // First check in standard fieldnames.
        $fields = get_user_fieldnames();
        if (array_search($fieldname, $fields) !== false) {
            $user = $DB->get_record('user', [$fieldname => $matchvalue], '*');
        } else {
            require_once($CFG->dirroot . '/user/profile/lib.php');
            $customfields = profile_get_custom_fields();
            $fieldname = substr($fieldname, 14); // Trim prefix 'profile_field'.
            $fieldid = null;
            // Find custom field id.
            foreach ($customfields as $field) {
                if ($field->shortname == $fieldname) {
                    $fieldid = $field->id;
                    break;
                }
            }
            // Query user.
            $sql = 'fieldid = ? AND ' . $DB->sql_compare_text('data') . ' = ?';
            $userid = $DB->get_record_select('user_info_data', $sql, [$fieldid, $matchvalue], 'userid');
            if ($userid) {
                $user = $DB->get_record('user', ['id' => $userid->userid], '*');
            } else {
                $user = false;
                debugging("No match with: fieldid:{$fieldid} and data {$matchvalue}", DEBUG_NORMAL);
            }
        }
        if ($user == false) {
            throw new Exception("No match with: {$fieldname} => {$matchvalue}");
        }
        return $user;
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

    /**
     * Tells whether the user has been configured on the TwinPush API.
     *
     * @param stdClass $user
     * @return true Always true right now.
     */
    public function is_user_configured($user = null) {
        return true;
    }

    /**
     * Tests whether the AppCrue settings have been configured.
     *
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
    protected function should_skip_message($eventdata) {
        global $DB;
        // If configured, skip forum messages not from "news" special forum.
        if (
            get_config('message_appcrue', 'onlynewsforum') == true &&
            $eventdata->component == 'mod_forum' &&
            $eventdata->contexturl &&
            preg_match('/\Wd=(\d+)/', $eventdata->contexturl, $matches)
        ) {
            $id = (int) $matches[1];
            $forumid = $DB->get_field('forum_discussions', 'forum', ['id' => $id]);
            $forum = $DB->get_record("forum", ["id" => $forumid]);
            if ($forum->type !== "news") {
                debugging("This forum message is filtered out due to configuration.", DEBUG_DEVELOPER);
                return true;
            }
        }
        return false;
    }

    /**
     * Logs the given message only when it is not called via AJAX nor WEBSERVICES.
     *
     * @param string $message message to log during the messages processing.
     * @return void
     */
    protected function log_no_ajax($message) {
        // If headers are already sent, we cannot use mtrace.
        if (!headers_sent()) {
            return;
        }
        if (!defined('AJAX_SCRIPT') || !AJAX_SCRIPT) {
            $this->log($message);
        }
    }
}
