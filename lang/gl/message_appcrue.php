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
$string['pluginname'] = 'Notificacións push para Appcrue';
$string['api_key'] = 'APIKey do servizo de notificación AppCrue';
$string['api_key_help'] = 'APIKey do servizo de notificación AppCrue';
$string['app_id'] = 'AppId para AppCrue Notification';
$string['app_id_help'] = 'Token da universidade para notificacións';
$string['only_news_forum'] = 'Só foros taboleiro de anuncios.';
$string['only_news_forum_help'] = 'Filtra as notificacións dos foros e só envía as do foro de anuncios dos cursos.';
$string['match_user_by'] = 'Campo para identificar ao usuario en Twin Push API';
$string['match_user_by_help'] = 'Cada usuario está asociado a un nome no API de TwinPush que pode non coincidir co userid en Moodle.';
$string['privacy:metadata'] = 'O engadido "Notificacións push para Appcrue" non almacena ningún dato persoal.';
$string['url_pattern_help'] = 'Patrón da URL para as notificacións push. As seguintes variables están dispoñibles: {url}: a url da mensaxe, {siteurl}: a url base do servidor. por exemplo: {siteurl}/local/appcrue/autologin.php?urltogo={url}&fallback=continue&<bearer>';
$string['url_pattern'] = 'Patrón da URL para as ligazóns dos eventos.';