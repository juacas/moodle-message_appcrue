<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

// Project implemented by the "Recovery, Transformation and Resilience Plan.
// Funded by the European Union - Next GenerationEU\".
//
// Produced by the UNIMOODLE University Group: Universities of
// Valladolid, Complutense de Madrid, UPV/EHU, León, Salamanca,
// Illes Balears, Valencia, Rey Juan Carlos, La Laguna, Zaragoza, Málaga,
// Córdoba, Extremadura, Vigo, Las Palmas de Gran Canaria y Burgos.

/**
 * Version details
 *
 * @package    message_appcrue
 * @copyright  2024 Juan Pablo de Castro
 * @author     Juan Pablo de Castro <juanpablo.de.castro@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace message_appcrue\tests;

/**
 * Testing send_message
 *
 * @group message_appcrue
 */
final class send_message_test extends \advanced_testcase {
    use \core\message\send_message_test_trait;
    
    /**
     * Plugin object.
     * @var \message_output_appcrue
     */
    public $appcrueoutput;
    /**
     * Users.
     * @var array
     */
    protected $users;

    final public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        // Instantiate the message output provider.
        $this->appcrueoutput = new \message_output_appcrue();
        // Set mockup for the TwinPush client.
        $this->appcrueoutput->apiclient = new \message_appcrue\test\twinpush_client();

        self::$course = self::getDataGenerator()->create_course();
        // Create 10 users
        for ($i = 0; $i < 10; $i++) {
            $this->users[$i] = self::getDataGenerator()->create_user(['idnumber' => $i . '']);
        }

        self::getDataGenerator()->enrol_user(self::$user->id, self::$course->id);
    }


    /**
     * Test send_message to a single user.
     *
     * @dataProvider launched_provider
     * @param int $launched
     * @param int $expected
     * @param bool $expectedresult
     */
    public function test_send_instant_message_to_single_user(int $launched, int $expected, bool $expectedresult): void {
        // Created eventdata.
        $eventdata = new \core\message\message();
        $eventdata->component = 'moodle';
        $eventdata->name = 'instantmessage';

        $eventdata->userfrom = self::$user;
        $eventdata->userto = $this->users[$launched];
        $eventdata->subject = 'Test Subject';
        $eventdata->fullmessage = 'This is a test message.';
        $eventdata->fullmessageformat = FORMAT_PLAIN;
        $eventdata->smallmessage = 'Test message';
        $eventdata->contexturl = '';
        $eventdata->contexturlname = '';

        $result = \core\message\message::send_instant_messages([$eventdata]);

        // Check that the message was sent.
        $this->assertEquals($expectedresult, $result);
    }
}
