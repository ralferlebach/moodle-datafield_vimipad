# Changelog

All notable changes to datafield_vimipad are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/) and the project adheres to
semantic-ish plain patch numbering.

## 0.1.1 - 2026-08-10

### Fixed
- Added the missing `fieldtypelabel` language string; the field edit page no
  longer shows the raw `[[fieldtypelabel]]` placeholder.
- The field now ships and renders its own type icon (via an `image()` override),
  so the ViMi Pad glyph appears next to the field name in the field list and on
  the field edit page.

### Changed
- Field-type icon redrawn as a neutral monochrome glyph.

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
  the next step.## 0.1.5 - 2026-08-10

### Added
- Browsing a database entry now renders the stored map as a read-only embedded
  editor (mod_vimipad mountValue, readonly) instead of a text summary; the plain
  summary remains as a no-JS <noscript> fallback. Covered by a new PHPUnit test.

## 0.1.4 - 2026-08-10

### Added
- Behat scenario (tests/behat/field.feature): a ViMi Pad field created via the
  generator is listed in the database field management. Fills the previously
  empty behat CI job.

## 0.1.3 - 2026-08-10

### Fixed
- The embedded editor now receives the profile form config (via the new
  mod_vimipad 0.9.4 embed), so nodes and relations can be created and the arrange
  action no longer pushes new nodes off the canvas.
- The learner journal is hidden in the embedded editor; every edit is
  auto-captured as the field value (no separate snapshot step).
- Dependency raised to mod_vimipad 2026080804 (0.9.4).


