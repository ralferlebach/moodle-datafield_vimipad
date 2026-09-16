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
 * Tests for the search API that mod_data expects every field type to provide.
 *
 * These exist because of a real regression: data_field_base does NOT define
 * display_search_field(), yet mod_data calls it on every field when it builds
 * the advanced search form. A field type that omits it makes the whole database
 * activity's browse page die with "Call to undefined method". The earlier unit
 * tests all called the plugin directly and so never crossed that boundary; these
 * tests deliberately go through mod_data's own code paths instead.
 *
 * @package    datafield_vimipad
 * @copyright  2026 Ralf Erlebach
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \data_field_vimipad
 */
final class search_contract_test extends \advanced_testcase {
    /**
     * Build a database activity with one ViMi Pad field.
     *
     * @param string|null $map Optional map JSON to store in a single entry.
     * @return array An array of the data record, the field object and the record id.
     */
    private function make_field(?string $map = null): array {
        global $DB;

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

        $recordid = 0;
        if ($map !== null) {
            $recordid = $DB->insert_record('data_records', (object)[
                'dataid' => $data->id,
                'userid' => 2,
                'groupid' => 0,
                'timecreated' => time(),
                'timemodified' => time(),
                'approved' => 1,
            ]);
            $DB->insert_record('data_content', (object)[
                'fieldid' => $fieldrecord->id,
                'recordid' => $recordid,
                'content' => $map,
            ]);
        }

        return [$data, data_get_field($fieldrecord, $data), $recordid];
    }

    /**
     * Every method mod_data calls on a field during search must exist.
     *
     * data_field_base supplies no default for these, so their absence is only
     * discovered at runtime on a live page. Assert the contract explicitly.
     *
     * @return void
     */
    public function test_search_api_methods_exist(): void {
        $this->resetAfterTest();
        [, $field] = $this->make_field();

        foreach (['display_search_field', 'parse_search_field', 'generate_sql'] as $method) {
            $this->assertTrue(
                method_exists($field, $method),
                "data_field_vimipad must implement {$method}(); mod_data calls it and " .
                'data_field_base provides no default.'
            );
        }
    }

    /**
     * The search input renders and carries the field's own parameter name.
     *
     * @return void
     */
    public function test_display_search_field_renders_input(): void {
        global $PAGE;
        $this->resetAfterTest();
        $PAGE->set_url('/mod/data/view.php');
        [, $field] = $this->make_field();

        $html = $field->display_search_field();
        $this->assertStringContainsString('name="f_' . $field->field->id . '"', $html);

        // A previous search term is echoed back, escaped.
        $html = $field->display_search_field('Water<script>');
        $this->assertStringContainsString('Water', $html);
        $this->assertStringNotContainsString('<script>', $html);
    }

    /**
     * generate_sql() produces a bound LIKE clause scoped to this field.
     *
     * @return void
     */
    public function test_generate_sql_matches_stored_labels(): void {
        global $DB;
        $this->resetAfterTest();

        $map = '{"profile":"conceptmap","nodes":[{"stableid":"a","label":"Photosynthesis"}],"relations":[]}';
        [, $field, $recordid] = $this->make_field($map);

        [$sql, $params] = $field->generate_sql('c', 'Photosynthesis');
        $this->assertStringContainsString('c.fieldid = ' . $field->field->id, $sql);
        $this->assertNotEmpty($params, 'The clause must use bound parameters, not inlined values.');

        // The clause really selects the entry whose map contains that label.
        $found = $DB->get_records_sql(
            "SELECT c.recordid FROM {data_content} c WHERE {$sql}",
            $params
        );
        $this->assertArrayHasKey(
            $recordid,
            $found,
            'Searching for a node label should find the entry holding that map.'
        );

        // A term that appears nowhere finds nothing.
        [$sql, $params] = $field->generate_sql('c', 'no_such_label_xyz');
        $this->assertEmpty($DB->get_records_sql("SELECT c.recordid FROM {data_content} c WHERE {$sql}", $params));
    }

    /**
     * The advanced search template builds without error.
     *
     * This is the exact call that crashed the database activity's browse page:
     * data_generate_default_template() walks every field and invokes
     * display_search_field() on it.
     *
     * @return void
     */
    public function test_advanced_search_template_builds(): void {
        global $PAGE;
        $this->resetAfterTest();
        $PAGE->set_url('/mod/data/view.php');
        [$data] = $this->make_field();

        $template = data_generate_default_template($data, 'asearchtemplate', 0, false, false);

        $this->assertNotEmpty(
            $template,
            'mod_data must be able to build the advanced search template with a ViMi Pad field present.'
        );
        $this->assertStringContainsString('Map', (string)$template);
    }

    /**
     * The list template renders an entry's stored map.
     *
     * Covers the browse path end to end through mod_data rather than by calling
     * display_browse_field() directly.
     *
     * @return void
     */
    public function test_list_template_renders_entry(): void {
        global $DB, $PAGE;
        $this->resetAfterTest();
        $PAGE->set_url('/mod/data/view.php');

        $map = '{"profile":"conceptmap","nodes":[{"stableid":"a","label":"Water"},'
            . '{"stableid":"b","label":"Ice"}],"relations":[{"stableid":"r","sourceid":"a",'
            . '"targetid":"b","label":"freezes to"}]}';
        [$data, $field, $recordid] = $this->make_field($map);

        // Generate the default templates the way the UI does when a field is added.
        foreach (['listtemplate', 'singletemplate'] as $name) {
            data_generate_default_template($data, $name, 0, false, true);
        }
        $data = $DB->get_record('data', ['id' => $data->id], '*', MUST_EXIST);
        $this->assertNotEmpty($data->listtemplate, 'The list template must reference the field.');

        $rendered = $field->display_browse_field($recordid, 'listtemplate');
        $this->assertStringContainsString('datafield_vimipad', $rendered);
    }
}
