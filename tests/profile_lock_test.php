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

/**
 * Tests for the profile-change guard on a field that already holds maps.
 *
 * @package    datafield_vimipad
 * @copyright  2026 Ralf Erlebach
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \data_field_vimipad::validate
 */
final class profile_lock_test extends \advanced_testcase {
    /**
     * Build a database activity with a ViMi Pad field.
     *
     * @param string $profile The field's diagram profile.
     * @return array [field object, data instance, field record]
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

        return [data_get_field($fieldrecord, $data), $data, $fieldrecord];
    }

    /**
     * Store a map in one entry of the field.
     *
     * @param object $data The database instance.
     * @param object $fieldrecord The field record.
     * @param string $content The stored value.
     * @return void
     */
    private function add_entry($data, $fieldrecord, string $content): void {
        global $DB;

        $recordid = $DB->insert_record('data_records', (object) [
            'dataid' => $data->id,
            'userid' => 2,
            'groupid' => 0,
            'timecreated' => time(),
            'timemodified' => time(),
            'approved' => 1,
        ]);
        $DB->insert_record('data_content', (object) [
            'fieldid' => $fieldrecord->id,
            'recordid' => $recordid,
            'content' => $content,
        ]);
    }

    /**
     * A valid map for a given profile.
     *
     * @param string $profile The profile key.
     * @return string The map JSON.
     */
    private function map(string $profile): string {
        return (string) json_encode([
            'profile' => $profile,
            'nodes' => [['stableid' => 'n1', 'label' => 'Cat']],
            'relations' => [],
        ]);
    }

    /**
     * On an empty field the profile can still be changed freely.
     *
     * @return void
     */
    public function test_profile_change_allowed_while_empty(): void {
        $this->resetAfterTest();
        [$field] = $this->make('conceptmap');

        $errors = $field->validate((object) ['param1' => 'mindmap']);

        $this->assertSame([], $errors);
    }

    /**
     * Once an entry holds a map, changing the profile is refused: the stored
     * maps would no longer match the field, and their authors could not save
     * them again.
     *
     * @return void
     */
    public function test_profile_change_refused_with_entries(): void {
        $this->resetAfterTest();
        [$field, $data, $fieldrecord] = $this->make('conceptmap');
        $this->add_entry($data, $fieldrecord, $this->map('conceptmap'));

        $errors = $field->validate((object) ['param1' => 'mindmap']);

        $this->assertArrayHasKey('param1', $errors);
        $this->assertNotEmpty($errors['param1']);
    }

    /**
     * Saving the field without touching the profile stays possible, so a teacher
     * can still rename it or edit its description.
     *
     * @return void
     */
    public function test_other_settings_remain_editable(): void {
        $this->resetAfterTest();
        [$field, $data, $fieldrecord] = $this->make('conceptmap');
        $this->add_entry($data, $fieldrecord, $this->map('conceptmap'));

        $errors = $field->validate((object) ['param1' => 'conceptmap', 'name' => 'Renamed']);

        $this->assertSame([], $errors);
    }

    /**
     * An entry whose value is empty does not lock the profile.
     *
     * @return void
     */
    public function test_empty_entries_do_not_lock(): void {
        $this->resetAfterTest();
        [$field, $data, $fieldrecord] = $this->make('conceptmap');
        $this->add_entry($data, $fieldrecord, '');

        $errors = $field->validate((object) ['param1' => 'mindmap']);

        $this->assertSame([], $errors);
    }
}
