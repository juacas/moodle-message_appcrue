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
 * @author  Juan Pablo de Castro
 * @copyright  2021 onwards Juan Pablo de Castro (juanpablo.decastro@uva.es)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace message_appcrue;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot.'/lib/filelib.php');
/**
 * AppCrue helper manager class
 *
 * @author  Juan Pablo de Castro
 * @copyright  2017 onwards Juan Pablo de Castro (juanpablo.decastro@uva.es)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manager {
    /**
     * @var $curl The curl object used in this run. Avoids continuous creation of a curl object.
     */
    private $curl = null;
    private $config = new stdClass();

    /**
     * Constructor. Loads all needed data.
     */
    public function __construct() {
        $this->curl = curl_init;
        $this->config = get_config('message_appcrue');
    }



}