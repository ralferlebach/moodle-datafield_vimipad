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
  the next step.

## 0.2.2 - 2026-08-12

### Fixed
- The load seed pointed one directory too high when including config.php, so it
  could never bootstrap Moodle from mod/data/field/vimipad/tests/load.
- The load targets in the makefile carried doubled line-continuation backslashes,
  which made the shell fail with "unexpected end of file".
- `make load-seed` now fails when the seed script fails, instead of reporting
  success and writing an empty .load-env.

## 0.2.1 - 2026-08-12

### Added
- Load-test harness (tests/load): seed, JMeter plan and k6 script, plus a README.
  This plugin exposes no web service, so the runs are session-based: they log in
  (handling Moodle one-time logintoken) and request the pages a learner actually
  meets. Not distributed (export-ignore), downloads and results gitignored.

## 0.2.0 - 2026-08-12

First beta. Maturity raised from ALPHA to BETA.

### Tests
- Boundary coverage for stored values: valid map, empty value allowed, oversized
  refused, malformed refused, profile mismatch refused, and nothing written when
  a value is refused.
- A behat scenario covering the entry form showing the embedded editor.

## 0.1.8 - 2026-08-12

### Fixed
- Field values are validated against the public ViMi Pad map policy, with the
  configured field profile as the expected profile. A forged record POST could
  previously write an arbitrarily large or structurally invalid document, or one
  from a different diagram profile, straight into data_content.
- Corrected the docblock that still described a plain-text input with the editor
  arriving later.

## 0.1.7 - 2026-08-10

### Changed
- Browse view mounts the read-only editor lazily (IntersectionObserver): each
  entry's editor is created only when it scrolls near the viewport, keeping
  entry lists with many ViMi Pad fields light. The editable add/edit form still
  mounts immediately. Falls back to an immediate mount where IntersectionObserver
  is unavailable.

## 0.1.6 - 2026-08-10

### Fixed
- Behat: tagged tests/behat/field.feature with the plugin-type tag @datafield so
  moodle-plugin-ci validate passes (it requires @datafield in addition to
  @datafield_vimipad).

## 0.1.5 - 2026-08-10

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


