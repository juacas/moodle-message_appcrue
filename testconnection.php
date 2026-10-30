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
 * Test connection page for AppCrue message plugin.
 *
 * @package message_appcrue
 * @category admin
 * @author  Juan Pablo de Castro
 * @copyright 2021 onwards
 */
require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/message/output/appcrue/message_output_appcrue.php');
require_login();
$systemcontext = context_system::instance();
require_capability('moodle/site:config', $systemcontext);

$PAGE->set_url(new moodle_url('/message/output/appcrue/testconnection.php'));
$PAGE->set_context($systemcontext);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('testconnection', 'message_appcrue'));
$PAGE->set_heading(get_string('testconnection', 'message_appcrue'));
/**
 * Form for the device alias to test.
 * @package message_appcrue
 * @copyright 2026 Juan Pablo
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class message_appcrue_testconnection_form extends moodleform {
    /**
     * Form for nickname.
     * @return void
     */
    public function definition() {
        $mform = $this->_form;
        $mform->addElement('static', 'intro', '', get_string('testconnectiondesc', 'message_appcrue'));
        $mform->addElement('text', 'username', get_string('testusername', 'message_appcrue'));
        $mform->setType('username', PARAM_RAW_TRIMMED);
        $mform->addRule('username', get_string('required'), 'required', null, 'client');
        $this->add_action_buttons(false, get_string('sendtestmessage', 'message_appcrue'));
    }
    /**
     * Validate test form.
     * @param mixed $data
     * @param mixed $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $username = trim($data['username'] ?? '');
        if ($username == '') {
            $errors['username'] = get_string('required');
        }
        return $errors;
    }
}

$notifications = [];
$mform = new message_appcrue_testconnection_form();

if ($data = $mform->get_data()) {
    try {
        $mform->set_data($data);
        $sender = new message_output_appcrue();
        $recipient = $sender->find_user('username', trim($data->username));
        $testresult = $sender->send_test_notification($recipient);
        if ($testresult['status'] === 'sent') {
            $notifications[] = [
                get_string('testmessagesent', 'message_appcrue', fullname($recipient)),
                core\output\notification::NOTIFY_SUCCESS,
                ];
        } else if ($testresult['status'] === 'missingconfig') {
            $notifications[] = [
                get_string('testconfigurationmissing', 'message_appcrue'),
                core\output\notification::NOTIFY_ERROR,
                ];
        } else if ($testresult['status'] === 'noalias') {
            $notifications[] = [
                get_string(
                    'testmessageerrors',
                    'message_appcrue',
                    get_string(
                        'testmessageerroritem',
                        'message_appcrue',
                        (object)[
                            'userid' => $recipient->id,
                            'alias' => get_string('testmessagenoalias', 'message_appcrue'),
                        ]
                    )
                ),
                core\output\notification::NOTIFY_ERROR,
                ];
        } else if ($testresult['status'] === 'exception') {
            $notifications[] = [
                get_string('testmessageexception', 'message_appcrue', s($testresult['message'])),
                core\output\notification::NOTIFY_ERROR,
                ];
        } else {
            $failures = [];
            foreach ($testresult['errors'] as $userid => $alias) {
                $failures[] = get_string(
                    'testmessageerroritem',
                    'message_appcrue',
                    (object) [
                        'userid' => $userid,
                        'alias' => $alias !== '' ? s($alias) : get_string('testmessagenoalias', 'message_appcrue'),
                    ]
                );
            }
            $failures = implode('; ', $failures);
            $notifications[] = [
                get_string('testmessageerrors', 'message_appcrue', $failures),
                core\output\notification::NOTIFY_ERROR,
                ];
        }
    } catch (Throwable $exception) {
        $notifications[] = [
            get_string('testmessageexception', 'message_appcrue', s($exception->getMessage())),
            core\output\notification::NOTIFY_ERROR,
            ];
    }
}

echo $OUTPUT->header();

foreach ($notifications as [$message, $type]) {
    echo $OUTPUT->notification($message, $type);
}

$mform->display();

echo $OUTPUT->footer();
