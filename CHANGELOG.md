# Changelog

All notable changes to datafield_vimipad are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/) and the project adheres to
semantic-ish plain patch numbering.

## 0.1.0 — 2026-08-10

First stub of the ViMi Pad database field.

### Added

- Field-type skeleton `data_field_vimipad` (extends `data_field_base`): value
  entry, storage into core `{data_content}`, browse display, text export and
  simple search.
- Field-definition form (`mod.html`) offering the full ViMi Pad diagram-profile
  list, sourced from the `mod_vimipad` public profile API
  (`\mod_vimipad\profile\profiles`).
- Privacy provider implementing `null_provider` and mod_data's
  `datafield_provider` (the map lives in core-owned tables).
- English and German language packs at key parity.
- PHPUnit tests for the cross-plugin profile-API contract, profile clamping, the
  map summary, and an `update_content` round-trip against a real Database
  activity.
- Declared dependency on `mod_vimipad` (>= 0.9.0 / 2026080800).

### Known limitations

- The value area is a plain-text field carrying the serialised map. The
  interactive editor embed (a ViMi Pad transport bound to the field value) is
  the next step.
