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

if ($hassiteconfig) {
    // Collect user fields.
    $fields = get_user_fieldnames();
    global $CFG;
    require_once($CFG->dirroot . '/user/profile/lib.php');
    $customfields = profile_get_custom_fields();
    $userfields = [];
    // Make the keys string values and not indexes.
    foreach ($fields as $field) {
        $userfields[$field] = $field;
    }
    foreach ($customfields as $field) {
        $userfields["profile_field_{$field->shortname}"] = $field->name;
    }

    /** @var admin_settingpage $settings */ // phpcs:ignore
    $settings->add(
        new admin_setting_configcheckbox(
            'message_appcrue/enable_push',
            get_string('enable_push', 'message_appcrue'),
            null,
            true
        )
    );

    $settings->add(new admin_setting_configtext(
        'message_appcrue/apikey',
        get_string('api_key', 'message_appcrue'),
        get_string('api_key_help', 'message_appcrue'),
        '',
        PARAM_TEXT
    ));
    $settings->add(new admin_setting_configtext(
        'message_appcrue/appid',
        get_string('app_id', 'message_appcrue'),
        get_string('app_id_help', 'message_appcrue'),
        '',
        PARAM_TEXT
    ));
    $settings->add(new admin_setting_configtext(
        'message_appcrue/group_name',
        get_string('group_name', 'message_appcrue'),
        get_string('group_name_help', 'message_appcrue'),
        'Moodle message',
        PARAM_TEXT
    ));
    $settings->add(new admin_setting_configcheckbox(
        'message_appcrue/onlynewsforum',
        get_string('only_news_forum', 'message_appcrue'),
        get_string('only_news_forum_help', 'message_appcrue'),
        1
    ));

    $settings->add(new admin_setting_configselect(
        'message_appcrue/match_user_by',
        get_string('match_user_by', 'message_appcrue'),
        get_string('match_user_by_help', 'message_appcrue'),
        'id',
        $userfields
    ));

    // Configure url pattern for generating the urls to the events.
    $settings->add(new admin_setting_configtext(
        'message_appcrue/urlpattern',
        get_string('url_pattern', 'message_appcrue'),
        get_string('url_pattern_help', 'message_appcrue'),
        '',
        PARAM_RAW_TRIMMED,
        100
    ));
    // Select if the messages are buffered.
    $settings->add(new admin_setting_configcheckbox(
        'message_appcrue/bufferedmode',
        get_string('bufferedmode', 'message_appcrue'),
        get_string('bufferedmode_help', 'message_appcrue'),
        0
    ));
    // Preserve undeliverable messages in buffered mode.
    $settings->add(new admin_setting_configcheckbox(
        'message_appcrue/preserveundeliverable',
        get_string('preserveundeliverable', 'message_appcrue'),
        get_string('preserveundeliverable_help', 'message_appcrue'),
        0
    ));
    $settings->hide_if(
        'message_appcrue/preserveundeliverable',
        'message_appcrue/bufferedmode',
        'eq',
        0
    );
}
