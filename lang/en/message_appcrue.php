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
$string['pluginname'] = 'Push notifications for Appcrue';
$string['api_key'] = 'APIKey for AppCrue Notification';
$string['api_key_help'] = 'APIKey for AppCrue Notification';
$string['app_id'] = 'AppId for AppCrue Notification';
$string['app_id_help'] = 'AppId for AppCrue Notification';
$string['only_news_forum'] = 'Filter forums notifications.';
$string['only_news_forum_help'] = 'Filter forums notifications and only send messages from the "News" forum.';
$string['match_user_by'] = 'Field for matching user in Push API';
$string['match_user_by_help'] = 'Each user is associated to a name in the push API that may not match with userid in Moodle.';