<?php


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/message/output/lib.php');
require_once($CFG->dirroot.'/lib/filelib.php');

define('APPCRUE_APIKEY', 'message_appcrue_apikey');
define('APPCRUE_APPID', 'message_appcrue_appid');
define('APPCRUE_ONLYNEWSFORUM', 'message_appcrue_onlynewsforum');

class message_output_appcrue extends message_output {

    /**
     * Processes the message and sends a notification via appcrue
     *
     * @param stdClass $eventdata the event data submitted by the message sender plus $eventdata->savedmessageid
     * @return true if ok, false if error
     */
    public function send_message($eventdata) {
        global $CFG;
        global $DB;
        // Skip any messaging of suspended and deleted users.
        if ($eventdata->userto->auth === 'nologin' or $eventdata->userto->suspended or $eventdata->userto->deleted) {
            return true;
        }

        if (!empty($CFG->noemailever)) {
            // hidden setting for development sites, set in config.php if needed
            debugging('$CFG->noemailever is active, no appcrue message sent.', DEBUG_MINIMAL);
            return true;
        }
        $url = $eventdata->contexturl;
        $message  = $eventdata->fullmessage;
        // Parse and format forum post.
        if ($eventdata->component == 'mod_forum') {
            // Remove 2nd and last lines.
            $lines = explode("\n", $message);
            unset($lines[count($lines)-1]);
            unset($lines[1]);
            $messagelines = array();
            foreach ($lines as $line) {
                if ($line !== '') {
                    $messagelines[] = $line;
                }
            }
            $message = implode("\n", $messagelines);
        }
        $sendmessage = true;
        if($CFG->message_appcrue_onlynewsforum){
             if ($eventdata->component == 'mod_forum' && strpos($eventdata->contexturl, 'forum/discuss') !== false){//true
                    $splited= explode("?",$eventdata->contexturl);
                    preg_match_all('!\d+!', $splited[1], $matches);
                    $id =(int)$matches[0][0];
                    $forumid= $DB->get_field('forum_discussions', 'forum', array('id' => $id));
                    $forum = $DB->get_record("forum", array("id" => $forumid));
                    if($forum->type  !== "news"){
                        $sendmessage=false;
                        debugging("This forum message is filtered out due to configuration.");
                    }
                }
        }
        if($sendmessage ){
            return $this->send_api_message($eventdata->userto, $message, $url);
        }
        return true;
    }
    /**
     * Send the message to TwinPush.
     * @param string $message The message contect to send to AppCrue.
     * @param \stdClass $user The Moodle user record that is being sent to.
     */
    public function send_api_message($user, $message, $url='') {
        global $CFG;
        $apiCreator= $CFG->message_appcrue_apikey;
        $deviceId= $CFG->message_appcrue_appid;
        $data = new stdClass();
        $data->broadcast= false;
        $data->devices_aliases = array($user->username);
        $data->devices_ids= array();
        $data->segments= array();
        $target = new stdClass();
        $target->name = array();
        $target->values = array();
        $data->target_property = $target;
        $data->title = "Campus Virtual UVa";
        $data->group_name = "Campus Virtual";
        $data->alert =$message;
        $data->url = $url;
        $data->inbox =true;
        $jsonNotificacion = json_encode($data);
        $ch = curl_init();
        //attach encoded JSON string to the POST fields
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonNotificacion);
        //set the content type to application/json
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json','X-TwinPush-REST-API-Key-Creator:'.$apiCreator));
        curl_setopt($ch, CURLOPT_URL, "https://appcrue.twinpush.com/api/v2/apps/$deviceId/notifications");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); // JPC: Limit impact on other scheduled tasks.
        curl_exec($ch);
        // Catch errors and log them
        // Check if any error occurred
        if(curl_errno($ch))
        {
            debugging('Curl error: ' . curl_error($ch));
            curl_close($ch);
            return false;
        } else {
            curl_close($ch);
            return true;
        }
    }
    /**
     * Creates necessary fields in the messaging config form.
     *
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
        global $CFG;
        return (isset($CFG->message_appcrue_apikey) && isset($CFG->message_appcrue_appid));
    }
}