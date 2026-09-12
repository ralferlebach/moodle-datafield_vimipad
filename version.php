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
 * Plugin version definition for datafield_vimipad (ViMi Pad database field).
 *
 * @package    datafield_vimipad
 * @copyright  2026 Ralf Erlebach
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component    = 'datafield_vimipad';
$plugin->version      = 2026091101;
$plugin->requires     = 2024100700;   // Moodle 4.5.0 — hard minimum, mirrors mod_vimipad.
$plugin->supported    = [405, 502];   // Tested on Moodle 4.5–5.2, like the activity it depends on.
$plugin->maturity     = MATURITY_RC;
$plugin->release      = '1.0.0-RC1';

// A ViMi Pad database field embeds the shared editor as the value of a database
// activity entry. It reuses the public API (\mod_vimipad\profile\*) and the
// embeddable editor; the map JSON is stored in core's {data_content} table via
// a field-value adapter. The dependency is declared at the build that
// introduced the public map-value API this field validates its values with.
$plugin->dependencies = [
    'mod_vimipad' => 2026091101,
];
