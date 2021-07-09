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
 * Plugin administration pages are defined here.
 *
 * @package     message_appcrue
 * @category    admin
 * @copyright   josemanuel.lorenzo@ticarum.es
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once('message_output_appcrue.php');

if ($ADMIN->fulltree) {
   // TODO: Define the plugin settings page.
   // https://docs.moodle.org/dev/Admin_settings
    $settings->add(new admin_setting_configtext(APPCRUE_APIKEY,'APIKEYCREATOR','token del API-Key-Creator','',PARAM_TEXT));
    $settings->add(new admin_setting_configtext(APPCRUE_APPID,'app_id','token de la universidad','',PARAM_TEXT));
    $settings->add(new admin_setting_configcheckbox(APPCRUE_ONLYNEWSFORUM,'Aplicar filtro en foros','Aplicar filtro en foros',1));
}
