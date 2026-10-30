# Changelog

## v0.5.9 - 2026-10-30

## Connection test improvements

- Send administrator test notifications through a dedicated method that checks plugin configuration and recipient device alias before calling TwinPush.
- Show separate results for missing configuration, recipients without an alias, delivery errors, and unexpected exceptions.
- Use the plugin's configured URL pattern for the test notification link.

## v0.5.8 - 2026-10-26

### Privacy and reliability

- Implement Moodle's privacy API for buffered notifications, recipients, and data sent to the TwinPush service.
- Protect API keys in settings, add a request timeout, and improve delivery error handling so failed pushes are not retried as failed Moodle tasks.
- Improve notification URL handling and update the Moodle Marketplace release workflow.

## v0.5.7 - 2026-05-06

### Fixed

- Do not report the notification task as failed when some push deliveries fail.

## v0.5.6 - 2026-04-29

### Fixed

- Improve compatibility with AppCRUE iOS app 7.24 by removing the `url` field that took precedence over `target_id`.

## v0.5.5 - 2026-04-24

### Fixed

- Add a workaround for iOS app 7.24 URL handling.

## v0.5.4 - 2026-03-30

### Fixed

- Use relative deep links in events to fit TwinPush API length limits.

## v0.5.3 - 2026-02-13

### Added

- Add a connection test button for administrators.

## v0.5.2 - 2026-02-10

### Fixed

- Log message delivery errors.

## v0.5.1 - 2026-01-14

### Fixed

- Correct URLs in notifications.

## v0.4.4 - 2026-01-14

### Fixed

- Update handling for recent TwinPush validation rules.
