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

use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/data/lib.php');

/**
 * Privacy tests that drive mod_data's own export and deletion.
 *
 * This plugin implements mod_data's datafield_provider, so core calls into it
 * while answering a data subject request. A signature mismatch or an error in
 * those methods only surfaces when an administrator actually runs an export or
 * a deletion, which is the worst possible moment to find out. The plugin had no
 * privacy test at all, so these drive the real core code path.
 *
 * @package    datafield_vimipad
 * @copyright  2026 Ralf Erlebach
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \datafield_vimipad\privacy\provider
 */
final class privacy_provider_test extends \advanced_testcase {
    /**
     * Build a database activity with a ViMi Pad field and one learner entry.
     *
     * @return array An array of the user, the course module and the record id.
     */
    private function make_entry(): array {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $data = $this->getDataGenerator()->create_module('data', ['course' => $course->id]);

        $field = (object) [
            'dataid' => $data->id,
            'type' => 'vimipad',
            'name' => 'Map',
            'description' => '',
            'param1' => 'conceptmap',
        ];
        $field->id = $DB->insert_record('data_fields', $field);

        $recordid = $DB->insert_record('data_records', (object) [
            'dataid' => $data->id,
            'userid' => $user->id,
            'groupid' => 0,
            'timecreated' => time(),
            'timemodified' => time(),
            'approved' => 1,
        ]);
        $DB->insert_record('data_content', (object) [
            'fieldid' => $field->id,
            'recordid' => $recordid,
            'content' => '{"profile":"conceptmap","nodes":[{"stableid":"a","label":"Photosynthesis"}],'
                . '"relations":[]}',
        ]);

        $cm = get_coursemodule_from_instance('data', $data->id);
        return [$user, $cm, $recordid];
    }

    /**
     * The reason string a null provider points at must exist.
     *
     * Otherwise the site's privacy registry renders a raw [[string]] placeholder.
     *
     * @return void
     */
    public function test_reason_string_exists(): void {
        $this->resetAfterTest();

        $reason = \datafield_vimipad\privacy\provider::get_reason();
        $this->assertTrue(
            get_string_manager()->string_exists($reason, 'datafield_vimipad'),
            "get_reason() points at '{$reason}', which datafield_vimipad does not define."
        );
    }

    /**
     * A full export through mod_data includes the learner's map.
     *
     * @return void
     */
    public function test_export_includes_the_map(): void {
        $this->resetAfterTest();
        [$user, $cm] = $this->make_entry();

        $context = \context_module::instance($cm->id);
        $this->setUser($user);

        $contextlist = new approved_contextlist($user, 'mod_data', [$context->id]);
        \mod_data\privacy\provider::export_user_data($contextlist);

        $writer = writer::with_context($context);
        $this->assertTrue(
            $writer->has_any_data(),
            'Exporting a database entry that holds a ViMi Pad map must produce data.'
        );
    }

    /**
     * Deleting a user's data through mod_data clears the stored map.
     *
     * @return void
     */
    public function test_delete_removes_the_map(): void {
        global $DB;
        $this->resetAfterTest();
        [$user, $cm, $recordid] = $this->make_entry();

        $this->assertSame(1, $DB->count_records('data_content', ['recordid' => $recordid]));

        $context = \context_module::instance($cm->id);
        $contextlist = new approved_contextlist($user, 'mod_data', [$context->id]);
        \mod_data\privacy\provider::delete_data_for_user($contextlist);

        // Core owns data_content, so a deletion request removes the row
        // outright. The plugin's delete_data_content() is intentionally empty
        // because this plugin keeps no storage of its own - this test is what
        // proves that assumption still holds.
        $this->assertSame(
            0,
            $DB->count_records('data_content', ['recordid' => $recordid]),
            'A deletion request must not leave the learner\'s map behind.'
        );
        $this->assertSame(
            0,
            $DB->count_records('data_records', ['id' => $recordid]),
            'The entry itself must be gone too.'
        );
    }

    /**
     * Deleting everything in the context leaves no map behind.
     *
     * @return void
     */
    public function test_delete_for_all_users_in_context(): void {
        global $DB;
        $this->resetAfterTest();
        [, $cm, $recordid] = $this->make_entry();

        $context = \context_module::instance($cm->id);
        \mod_data\privacy\provider::delete_data_for_all_users_in_context($context);

        $this->assertSame(
            0,
            $DB->count_records('data_content', ['recordid' => $recordid]),
            'Purging the context must remove every stored map.'
        );
    }
}
