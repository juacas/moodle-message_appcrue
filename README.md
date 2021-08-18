# AppCRUENotify message output #

Enable Moodle to send messages through the notification service of AppCrue application using TwinPush API.
## What is AppCRUE? ##

AppCRUE (https://tic.crue.org/app-crue/) is a mobile App develop by the CRUE (Conference of Rectors of Spanish Universities) and Santander Bank. It is used by 44 spanish universities and more than 150 000 students.

## What is TwinPush? ##

AppCRUE uses as notification platform TwinPush (https://twinpush.com/). TwinPush is a Marketing Mobile Platform that allows sending Push notifications to Smartphones. It offers Android SDK, IOS SDK, REST API.

This plugin can be used for any other application using TwinPush or a similar REST API.

# Functionality #

This message output plugin provides the following services:
- Allow admins to send only messages from the "News" forum of the course.
- Format the messages from the forum, instant messaging to fit better in the mobile notifications.
- Select which field in the user's profile is used to match the user in TwinPush platform.
- Sends every message that pass the former conditions to the recipient via TwinPush.

The notifications can be enabled/disabled/forced using Moodle's core notifications settings and user's preferences.

## Privacy ##

This plugin does not storage any user information. It just sends to TwinPush the field used to match the user and the content of the message.

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
