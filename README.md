# moodle-datafield_vimipad

[![Moodle Plugin CI](https://github.com/ralferlebach/moodle-datafield_vimipad/actions/workflows/moodle-ci.yml/badge.svg?branch=main)](https://github.com/ralferlebach/moodle-datafield_vimipad/actions?query=workflow%3A%22Moodle+Plugin+CI%22)

The ViMi Pad database field lets a Database activity hold a visual knowledge map
— a concept map, mind map, tree and more — as the value of a field. Each entry
in the database carries its own map, constrained to a diagram profile the field
defines.

## Status

**0.1.0 — alpha** (`MATURITY_ALPHA`). This is an early stub: it installs, adds a
field type to the Database activity, stores, displays, searches, exports and
backs up a map value, and offers the full ViMi Pad profile list in the field
configuration. The interactive editor embed — mounting the ViMi Pad editor with
a transport bound to the field value — replaces the plain-text value area in a
following iteration.

## Requirements

This plugin requires Moodle 4.5+ and the ViMi Pad activity module
(`mod_vimipad`) 0.9.0 or newer, which it depends on and which provides the shared
editor, the diagram profiles and the public API this field is built on.

It is developed and tested against Moodle 4.5, 5.0 and 5.2 on PHP 8.1–8.3 with
both PostgreSQL and MariaDB.

## Motivation for this plugin

The Database activity is Moodle's tool for structured, learner-contributed
collections. A ViMi Pad field extends it into visual knowledge construction: a
glossary of concepts where each entry is a small map, a collection of worked
arguments, a shared bank of process diagrams. The field reuses the ViMi Pad
editor and its diagram profiles through the module's public API
(`\mod_vimipad\profile\*`) and stores the map in core's own `data_content`
table, so it needs no schema or server of its own.

## Installation

Install the plugin into

    /mod/data/field/vimipad

See <http://docs.moodle.org/en/Installing_plugins> for details on installing
Moodle plugins. Because this plugin declares a dependency on `mod_vimipad`, that
activity must be installed first (or at the same time).

## Usage

In any Database activity, add a field of type **ViMi Pad**, choose the diagram
profile entries should use, and save. Users then build a map when they create or
edit an entry; the map is shown in the list and single-entry views.

## License

2026 Ralf Erlebach

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this
program. If not, see <https://www.gnu.org/licenses/>.
