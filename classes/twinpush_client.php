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
namespace message_appcrue;
defined('MOODLE_INTERNAL') || die();
use stdClass;
use curl;

class twinpush_client {
    // Use the logging trait to get some nice, juicy, logging.
    use \core\task\logging_trait;
    /**
     * @var string apikey
     */
    protected $apikey;
    /**
     * @var string appid
     */
    protected $appid;
    

    /**
     * Constructor.
     * @param $apikey
     * @param $appid
     */
    public function __construct($apikey, $appid) {
        $this->apikey = $apikey;
        $this->appid = $appid;
    }
    /**
     * Send the message to TwinPush using curl client.
     * @param array $devicealiases The list of device aliases to send the message to. userid=> devicealias.
     * @param string $title The title of the message.
     * @param string $body The message contect to send to AppCrue.
     * @param string $url url to see the details of the notification.
     * @return array userid=>aliases not sent.
     * @throws moodle_exception if API can't be reached.
     */
    public function send_api_message_chunk($devicealiases, $title, $body, $url='') {
       
        if (empty($devicealiases)) {
            return [];
        }

      
        $data = new stdClass();
        $data->broadcast = false;
        $data->devices_aliases = array_values($devicealiases);
        $data->title = $title;
        $data->group_name = get_config('message_appcrue', 'group_name');
        $data->alert = $this->trim_alert_text($body);
        $data->inbox = true;
        // Ask to open the url in a webview and show a link in notification panel.
        $data->url = $url;
        $data->custom_properties = new stdClass();
        $data->custom_properties->target = 'webview';
        $data->custom_properties->target_id = $url;

        $jsonnotificacion = json_encode($data);
        $client = new curl();
        $client->setHeader(array('Content-Type:application/json', 'X-TwinPush-REST-API-Key-Creator: ' . $this->apikey));
        $options = [
            'CURLOPT_RETURNTRANSFER' => true,
            'CURLOPT_CONNECTTIMEOUT' => 5 // JPC: Limit impact on other scheduled tasks.
        ];
        $apiurl = 'https://appcrue.twinpush.com/api/v2/apps/' . $this->appid . '/notifications';
        $response = $client->post($apiurl,
            $jsonnotificacion,
            $options);
        // Catch errors in response and log them.
        $respjson = json_decode($response);
        $aliasesstr = implode(', ', $devicealiases);
        if (isset($respjson->errors)) {
            if ($respjson->errors->type == 'AppNotFound') {
                throw new \moodle_exception('apicallerror', 'message_appcrue', '', 'App not found. Check App ID.');
            } else if (isset($respjson->type) && $respjson->type == 'NotificationNotCreated') {
                $this->log_no_ajax("Error sending message '{$title}' to {$aliasesstr}: {$respjson->errors->message}");
                return $devicealiases;
            } else if (isset($respjson->type) && $respjson->type == 'DeviceAliasNotFound') {
                // Device alias not found. Remove it from the list.
                foreach ($respjson->errors->device_aliases as $alias) {
                    $key = array_search($alias, $devicealiases);
                    if ($key !== false) {
                        unset($devicealiases[$key]);
                    }
                }
            } else {
                // Unknown error.
                $this->log_no_ajax("Error sending message '{$title}' to {$aliasesstr}: {$respjson->errors->message}");
                return $devicealiases;
            }
            $this->log_no_ajax("Error sending message '{$title}' to {$aliasesstr}: {$response}");
            return $devicealiases;
        } else {
            $this->log_no_ajax("Message '{$title}' sent to {$aliasesstr}");
        }
        // Check if any error occurred.
        $info = $client->get_info();
        if ($client->get_errno() || $info['http_code'] != 200) {
            debugging('Curl error: ' . $client->get_errno(). ':' . $response , DEBUG_MINIMAL);
            throw new \moodle_exception('apicallerror', 'message_appcrue', '', $client->error);
        } else {
            return [];
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

    protected function log_no_ajax($message) {
        if (!defined('AJAX_SCRIPT') || !AJAX_SCRIPT) {
            $this->log($message);
        }
    }
}
