# AppCRUE message output v0.5.9 release notes

## Connection test improvements

- Send administrator test notifications through a dedicated method that checks plugin configuration and recipient device alias before calling TwinPush.
- Show separate results for missing configuration, recipients without an alias, delivery errors, and unexpected exceptions.
- Use the plugin's configured URL pattern for the test notification link.
- Default recipient matching to the Moodle username when no matching field is configured.
