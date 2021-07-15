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

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configtext('message_appcrue_apikey', get_string('api_key', 'message_appcrue'),
                                                get_string('api_key_help', 'message_appcrue'), '', PARAM_TEXT));
    $settings->add(new admin_setting_configtext('message_appcrue_appid', get_string('app_id', 'message_appcrue'),
                                                get_string('app_id_help', 'message_appcrue'), '', PARAM_TEXT));
    $settings->add(new admin_setting_configcheckbox('message_appcrue_onlynewsforum', get_string('only_news_forum', 'message_appcrue'),
                                                get_string('only_news_forum_help', 'message_appcrue'), 1));
}
