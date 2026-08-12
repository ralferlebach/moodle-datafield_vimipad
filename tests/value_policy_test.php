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

namespace datafield_vimipad;

use mod_vimipad\api\value;

/**
 * Boundary tests for what the field accepts as a stored map value.
 *
 * @package    datafield_vimipad
 * @copyright  2026 Ralf Erlebach
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \data_field_vimipad
 */
final class value_policy_test extends \advanced_testcase {
    /**
     * Build a database activity with a ViMi Pad field and one record.
     *
     * @param string $profile The field's diagram profile.
     * @return array [field object, record id]
     */
    private function make(string $profile = 'conceptmap'): array {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $data = $this->getDataGenerator()->create_module('data', ['course' => $course->id]);
        $fieldrecord = (object) [
            'dataid' => $data->id,
            'type' => 'vimipad',
            'name' => 'Map',
            'description' => '',
            'param1' => $profile,
        ];
        $fieldrecord->id = $DB->insert_record('data_fields', $fieldrecord);
        $recordid = $DB->insert_record('data_records', (object) [
            'dataid' => $data->id,
            'userid' => 2,
            'groupid' => 0,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        return [data_get_field($fieldrecord, $data), $recordid];
    }

    /**
     * A map with the given nodes.
     *
     * @param array $nodes The node definitions.
     * @param string $profile The profile key.
     * @return string The map JSON.
     */
    private function map(array $nodes, string $profile = 'conceptmap'): string {
        return (string) json_encode([
            'profile' => $profile,
            'nodes' => $nodes,
            'relations' => [],
        ]);
    }

    /**
     * A valid map is stored.
     *
     * @return void
     */
    public function test_valid_value_is_stored(): void {
        global $DB;
        $this->resetAfterTest();
        [$field, $recordid] = $this->make();

        $map = $this->map([['stableid' => 'n1', 'label' => 'Cat']]);
        $field->update_content($recordid, $map);

        $this->assertSame($map, $DB->get_field('data_content', 'content', ['recordid' => $recordid]));
    }

    /**
     * An empty value is allowed: the field was simply left blank.
     *
     * @return void
     */
    public function test_empty_value_is_allowed(): void {
        global $DB;
        $this->resetAfterTest();
        [$field, $recordid] = $this->make();

        $field->update_content($recordid, '   ');

        $this->assertSame('', $DB->get_field('data_content', 'content', ['recordid' => $recordid]));
    }

    /**
     * An oversized value is refused rather than written to the database.
     *
     * @return void
     */
    public function test_oversized_value_is_refused(): void {
        $this->resetAfterTest();
        [$field, $recordid] = $this->make();

        $this->expectException(\moodle_exception::class);
        $field->update_content($recordid, str_repeat('x', value::MAX_BYTES + 1));
    }

    /**
     * A value that parses as JSON but is not a map is refused.
     *
     * @return void
     */
    public function test_malformed_map_is_refused(): void {
        $this->resetAfterTest();
        [$field, $recordid] = $this->make();

        $this->expectException(\moodle_exception::class);
        $field->update_content($recordid, '{"nodes":[{}]}');
    }

    /**
     * A map from a different profile than the field is configured for is
     * refused, so a forged post cannot mix diagram types in one field.
     *
     * @return void
     */
    public function test_profile_mismatch_is_refused(): void {
        $this->resetAfterTest();
        [$field, $recordid] = $this->make('conceptmap');

        $this->expectException(\moodle_exception::class);
        $field->update_content($recordid, $this->map([['stableid' => 'n1', 'label' => 'One']], 'mindmap'));
    }

    /**
     * Nothing is written when a value is refused.
     *
     * @return void
     */
    public function test_refused_value_writes_nothing(): void {
        global $DB;
        $this->resetAfterTest();
        [$field, $recordid] = $this->make();

        try {
            $field->update_content($recordid, '{"nodes":[{}]}');
        } catch (\moodle_exception $e) {
            $this->assertNotEmpty($e->getMessage());
        }

        $this->assertEquals(0, $DB->count_records('data_content', ['recordid' => $recordid]));
    }
}
