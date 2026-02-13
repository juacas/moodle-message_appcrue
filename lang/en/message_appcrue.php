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
$string['api_callerror'] = 'Error calling AppCrue API: {$a}';
$string['api_key'] = 'CreatorKey for AppCrue Notification';
$string['api_key_help'] = 'APIKey for AppCrue Notification';
$string['app_id'] = 'AppId for AppCrue Notification';
$string['app_id_help'] = 'AppId for AppCrue Notification';
$string['bufferedmode'] = 'Asynchronous mode';
$string['bufferedmode_help'] = 'Messages are buffered and sent in chunks in background. In "Buffered mode" the plugin sends the message to TwinPush API using a request for each 1000 users. This means that if you send a message to 5000 users, the plugin will send 5 requests to TwinPush API. The plugin will wait for the response of each request before sending the next one. This is done to avoid overloading the TwinPush API and to avoid sending too many requests in a short period of time. See README.md';
$string['enable_push'] = 'Enable push message service';
$string['group_name'] = 'Group name for AppCrue Notification';
$string['group_name_help'] = 'Group name for AppCrue Notification';
$string['match_user_by'] = 'Field for matching user in Push API';
$string['match_user_by_help'] = 'Each user is associated to a name in the push API that may not match with userid in Moodle.';
$string['only_news_forum'] = 'Filter forums notifications.';
$string['only_news_forum_help'] = 'Filter forums notifications and only send messages from the "News" forum.';
$string['openinwebview'] = 'Open links in webview';
$string['openinwebview_help'] = 'If enabled, links in push notifications will be opened inside the app using a webview. If disabled, links will be opened in the device\'s default browser.';
$string['pluginname'] = 'Push notifications for Appcrue';
$string['preserveundeliverable'] = 'Buffer undeliverable messages';
$string['preserveundeliverable_help'] = 'If the message is rejected by TwinPush (mainly because unknown device_alias), it will be stored in the database marked as failed. This is useful if you want to debug delivery problems.';
$string['privacy:metadata'] = 'The "Push Notifications for AppCrue" plugin does not store any personal data.';
$string['sendbufferedtask'] = 'Send buffered messages';
$string['sendbufferedtaskerror'] = 'Some recipients were not reached for messages: {$a}';
$string['sendtestmessage'] = 'Send test message';
$string['testconfigurationmissing'] = 'Configure the AppCrue API key and App ID before sending test messages.';
$string['testconnection'] = 'Test AppCrue connection';
$string['testconnectiondesc'] = 'Send a test notification to a user to verify the TwinPush credentials and device alias.';
$string['testmessagebody'] = '✅Test notification triggered by {$a}.';
$string['testmessageerroritem'] = 'User ID {$a->userid} (alias: {$a->alias})';
$string['testmessageerrors'] = 'TwinPush reported delivery issues for: {$a}';
$string['testmessageexception'] = 'TwinPush call failed: {$a}';
$string['testmessagenoalias'] = 'Missing device aliases in TwinPush response.';
$string['testmessagesent'] = 'Test message sent to {$a}.';
$string['testmessagesubject'] = '🧑🏻‍🔧{$a}: AppCrue connection test';
$string['testusername'] = 'Username';
$string['testusernotfound'] = 'No user found with the username "{$a}".';
$string['url_pattern'] = 'URL pattern for the event\'s links.';
$string['url_pattern_help'] = 'URL pattern for push notifications. The following variables are available: {url}:the message\'s url, {siteurl}:base url of the server. Example: {siteurl}/local/appcrue/autologin.php?urltogo={url}&fallback=continue&<bearer>';
