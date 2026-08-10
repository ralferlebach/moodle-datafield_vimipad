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
 * Privacy provider for datafield_vimipad.
 *
 * The field stores no personal data of its own: the map value lives in core's
 * {data_content} table, owned by mod_data. The plugin implements
 * mod_data's datafield_provider so the map is included when a user's database
 * entries are exported.
 *
 * @package    datafield_vimipad
 * @copyright  2026 Ralf Erlebach
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace datafield_vimipad\privacy;

use core_privacy\local\request\writer;
use mod_data\privacy\datafield_provider;

/**
 * Privacy provider for datafield_vimipad implementing null_provider.
 */
class provider implements \core_privacy\local\metadata\null_provider, datafield_provider {
    /**
     * Explain why this plugin stores no data of its own.
     *
     * @return string
     */
    public static function get_reason(): string {
        return 'privacy:metadata';
    }

    /**
     * Export the map stored for one record in the {data_content} table.
     *
     * @param \context_module $context The activity context.
     * @param \stdClass $recordobj Record from {data_records}.
     * @param \stdClass $fieldobj Record from {data_fields}.
     * @param \stdClass $contentobj Record from {data_content}.
     * @param \stdClass $defaultvalue Pre-populated default value.
     * @return void
     */
    public static function export_data_content($context, $recordobj, $fieldobj, $contentobj, $defaultvalue) {
        $subcontext = [$recordobj->id, $contentobj->id];
        $defaultvalue->field['profile'] = $fieldobj->param1;
        writer::with_context($context)->export_data($subcontext, $defaultvalue);
    }

    /**
     * The plugin keeps no data outside {data_content}, which mod_data deletes.
     *
     * @param \context_module $context The activity context.
     * @param \stdClass $recordobj Record from {data_records}.
     * @param \stdClass $fieldobj Record from {data_fields}.
     * @param \stdClass $contentobj Record from {data_content}.
     * @return void
     */
    public static function delete_data_content($context, $recordobj, $fieldobj, $contentobj) {
    }
}
