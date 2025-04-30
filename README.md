# AppCRUENotify message output #

Enable Moodle to send messages through the notification service of AppCrue application using TwinPush API.
## What is AppCRUE? ##

AppCRUE (https://tic.crue.org/app-crue/) is a mobile App develop by the CRUE (Conference of Rectors of Spanish Universities) and Santander Bank. It is used by more than 44 spanish universities and more than 150 000 students.

## What is TwinPush? ##

AppCRUE uses as notification platform TwinPush (https://twinpush.com/). TwinPush is a Marketing Mobile Platform that allows sending Push notifications to Smartphones. It offers Android SDK, IOS SDK, REST API.

This plugin can be used for any other application using TwinPush or a similar REST API.

# Functionality #

This message output plugin provides the following services:
- Allow admins to send only messages from the "News" forum of the course.
- Format the messages from the forum, instant messaging to fit better in the mobile notifications.
- Select which field in the user's profile is used to match the user in TwinPush platform.
- Sends every message that pass the former conditions to the recipient via TwinPush.
- Can add messages (with same subject, body, url) to a buffer allowing to send them to multiple users in a single request.

The notifications can be enabled/disabled/forced using Moodle's core notifications settings and user's preferences.

# Known issues #
- The plugin is not able to send messages to users that are not registered in TwinPush. This is a limitation of the TwinPush API.
- In "Buffered mode" the plugin sends the message to TwinPush API using a request for each 1000 users. This means that if you send a message to 5000 users, the plugin will send 5 requests to TwinPush API. The plugin will wait for the response of each request before sending the next one. This is done to avoid overloading the TwinPush API and to avoid sending too many requests in a short period of time.
- The TwinPush API does not report recipient errors unless all the recipients are invalid. This means that if a message is sent to 10 users and only 1 of them is valid, the API will return a success response. This plugin treat the message as correctly sent.
- If all the recipients are invalid, the plugin will mark the message as failed in the Moodle database. The message will not be sent to TwinPush API and the plugin will not retry to send it again. An error message will be shown in the scheduler task log.
- If an admin wants to retry failed messages, i.e. because the TwinPush API was down or the users has corrected their profile, the admin can do it resetting the status of the message to 0 in the Moodle database. There is no user interface for this, still.
- The admin can bypass the retention of failed messages in settings.

 ## Privacy ##

This plugin does not store any user information. It just sends to TwinPush the field used to match the user and the content of the message.

## License ##

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <http://www.gnu.org/licenses/>.
