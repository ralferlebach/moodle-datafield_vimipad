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

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/data/lib.php');
require_once($CFG->dirroot . '/mod/data/field/vimipad/field.class.php');

/**
 * Unit tests for the ViMi Pad database field stub.
 *
 * @package    datafield_vimipad
 * @copyright  2026 Ralf Erlebach
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \data_field_vimipad
 */
final class field_test extends \advanced_testcase {
    /**
     * The dependency on mod_vimipad's public profile API is present and usable.
     *
     * @return void
     */
    public function test_public_profile_api_available(): void {
        $this->assertTrue(
            class_exists('\mod_vimipad\profile\profiles'),
            'datafield_vimipad depends on the mod_vimipad public profile API.'
        );
        $options = \data_field_vimipad::profile_options();
        $this->assertIsArray($options);
        $this->assertArrayHasKey(
            'conceptmap',
            $options,
            'The conceptmap profile is expected to be offered by mod_vimipad.'
        );
    }

    /**
     * An unknown profile is clamped to the safe default; a known one is kept.
     *
     * @return void
     */
    public function test_clamp_profile(): void {
        $this->assertSame('conceptmap', \data_field_vimipad::clamp_profile('no_such_profile'));
        $this->assertSame('conceptmap', \data_field_vimipad::clamp_profile(null));
        $this->assertSame('conceptmap', \data_field_vimipad::clamp_profile('conceptmap'));
        if (\mod_vimipad\profile\profiles::exists('mindmap')) {
            $this->assertSame('mindmap', \data_field_vimipad::clamp_profile('mindmap'));
        }
    }

    /**
     * The map summary reports node and relation counts from the stored JSON.
     *
     * @return void
     */
    public function test_map_summary(): void {
        $json = json_encode([
            'nodes' => [['id' => 'n1'], ['id' => 'n2'], ['id' => 'n3']],
            'relations' => [['id' => 'r1'], ['id' => 'r2']],
        ]);
        $summary = \data_field_vimipad::map_summary($json);
        $this->assertStringContainsString('3', $summary);
        $this->assertStringContainsString('2', $summary);

        // Non-JSON content falls back to a shortened plain-text preview.
        $this->assertSame('plain text', \data_field_vimipad::map_summary('plain text'));
        $this->assertSame('', \data_field_vimipad::map_summary(null));
    }

    /**
     * update_content stores the map value in {data_content} and updates in place.
     *
     * @return void
     */
    public function test_update_content_roundtrip(): void {
        global $DB;
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $data = $generator->create_module('data', ['course' => $course->id]);

        // Create a ViMi Pad field on the database activity.
        $fieldrecord = (object)[
            'dataid' => $data->id,
            'type' => 'vimipad',
            'name' => 'Map',
            'description' => '',
            'param1' => 'conceptmap',
        ];
        $fieldrecord->id = $DB->insert_record('data_fields', $fieldrecord);

        // Create a record to attach content to.
        $recordid = $DB->insert_record('data_records', (object)[
            'dataid' => $data->id,
            'userid' => 2,
            'groupid' => 0,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $field = data_get_field($fieldrecord, $data);
        $this->assertInstanceOf(\data_field_vimipad::class, $field);

        $map = '{"nodes":[{"id":"n1"}],"relations":[]}';
        $this->assertTrue((bool)$field->update_content($recordid, $map));

        $stored = $DB->get_field(
            'data_content',
            'content',
            ['fieldid' => $fieldrecord->id, 'recordid' => $recordid]
        );
        $this->assertSame($map, $stored);

        // Update in place (no duplicate row).
        $map2 = '{"nodes":[{"id":"n1"},{"id":"n2"}],"relations":[{"id":"r1"}]}';
        $field->update_content($recordid, $map2);
        $rows = $DB->count_records(
            'data_content',
            ['fieldid' => $fieldrecord->id, 'recordid' => $recordid]
        );
        $this->assertSame(1, $rows);
        $this->assertSame($map2, $DB->get_field(
            'data_content',
            'content',
            ['fieldid' => $fieldrecord->id, 'recordid' => $recordid]
        ));
    }

    /**
     * display_browse_field renders the read-only embedded editor for a stored
     * map, and nothing for an empty record.
     *
     * @covers \data_field_vimipad::display_browse_field
     * @return void
     */
    public function test_display_browse_field_renders_readonly_editor(): void {
        global $DB, $PAGE;
        $this->resetAfterTest();
        $PAGE->set_url('/mod/data/view.php');

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $data = $generator->create_module('data', ['course' => $course->id]);

        $fieldrecord = (object)[
            'dataid' => $data->id,
            'type' => 'vimipad',
            'name' => 'Map',
            'description' => '',
            'param1' => 'conceptmap',
        ];
        $fieldrecord->id = $DB->insert_record('data_fields', $fieldrecord);
        $recordid = $DB->insert_record('data_records', (object)[
            'dataid' => $data->id,
            'userid' => 2,
            'groupid' => 0,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
        $field = data_get_field($fieldrecord, $data);

        // An empty record renders nothing.
        $this->assertSame('', $field->display_browse_field($recordid, ''));

        // A stored map renders the read-only editor container and the value.
        $map = '{"profile":"conceptmap","nodes":[{"stableid":"a","label":"Water"}],"relations":[]}';
        $field->update_content($recordid, $map);
        $html = $field->display_browse_field($recordid, '');

        $containerid = 'field_' . $fieldrecord->id . '_r' . $recordid . '_editor';
        $this->assertStringContainsString($containerid, $html);
        $this->assertStringContainsString('datafield_vimipad_browse', $html);
        $this->assertStringContainsString('Water', $html);
        $this->assertStringContainsString('<noscript>', $html);
    }
}
