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
 * CLI seed for the datafield_vimipad JMeter/k6 load test.
 *
 * Creates a course with a database activity carrying a ViMi Pad field and many
 * entries, each holding a map. The field exposes no web service, so the load run
 * is session-based and requests the list and single views — the list view is the
 * interesting one, because it renders every visible entry's map on one page.
 *
 * Usage: php mod/data/field/vimipad/tests/load/seed_large.php [entries] [nodes] [users]
 *   entries  database records to create (default 100)
 *   nodes    nodes in each entry's map (default 150)
 *   users    students to create (default 25)
 *
 * Intended for a disposable dev/staging site — never point it at production.
 *
 * @package    datafield_vimipad
 * @copyright  2026 Ralf Erlebach
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../../../../config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/course/modlib.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->libdir . '/enrollib.php');

$entrycount = isset($argv[1]) ? max(1, (int) $argv[1]) : 100;
$nodecount = isset($argv[2]) ? max(1, (int) $argv[2]) : 150;
$usercount = isset($argv[3]) ? max(1, (int) $argv[3]) : 25;
$now = time();
$password = 'Vimi!load_1';

// Course.
$course = create_course((object) [
    'fullname' => 'datafield ViMi Pad load ' . $now,
    'shortname' => 'dfvpload' . $now,
    'category' => 1,
    'summaryformat' => FORMAT_HTML,
]);
$studentrole = $DB->get_record('role', ['archetype' => 'student'], '*', MUST_EXIST);
$manual = enrol_get_plugin('manual');
$enrol = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'manual'], '*', MUST_EXIST);

// Students.
$usernames = [];
$userids = [];
for ($i = 0; $i < $usercount; $i++) {
    $username = 'dfvp_load_' . $now . '_' . $i;
    $user = (object) [
        'username' => $username,
        'auth' => 'manual',
        'confirmed' => 1,
        'firstname' => 'Data',
        'lastname' => 'Load' . $i,
        'email' => $username . '@example.invalid',
        'mnethostid' => $CFG->mnet_localhost_id,
    ];
    $user->id = user_create_user($user, false, false);
    update_internal_user_password($DB->get_record('user', ['id' => $user->id]), $password);
    $manual->enrol_user($enrol, $user->id, $studentrole->id);
    $usernames[] = $username;
    $userids[] = (int) $user->id;
}

// Database activity with a ViMi Pad field.
$module = $DB->get_record('modules', ['name' => 'data'], '*', MUST_EXIST);
$created = add_moduleinfo((object) [
    'modulename' => 'data',
    'module' => $module->id,
    'course' => $course->id,
    'section' => 0,
    'name' => 'Load database',
    'intro' => '',
    'introformat' => FORMAT_HTML,
    // Add_moduleinfo passes these straight to the module's callbacks, which read
    // them without checking: mod_data's grade-item update dereferences
    // cmidnumber, so omitting it raises a PHP warning during seeding.
    'cmidnumber' => '',
    'groupmode' => NOGROUPS,
    'groupingid' => 0,
    'completion' => COMPLETION_TRACKING_NONE,
    'visible' => 1,
], $course);
$cmid = (int) $created->coursemodule;
$dataid = (int) $created->instance;

$fieldid = (int) $DB->insert_record('data_fields', (object) [
    'dataid' => $dataid,
    'type' => 'vimipad',
    'name' => 'Map',
    'description' => 'A ViMi Pad map',
    'param1' => 'conceptmap',
]);

// One map document per entry.
$makemap = function (int $index) use ($nodecount): string {
    $nodes = [];
    $relations = [];
    for ($i = 0; $i < $nodecount; $i++) {
        $nodes[] = ['stableid' => 'n' . $i, 'label' => 'E' . $index . ' N' . $i];
    }
    for ($i = 0; $i < $nodecount - 1; $i++) {
        $relations[] = [
            'stableid' => 'r' . $i,
            'sourceid' => 'n' . $i,
            'targetid' => 'n' . ($i + 1),
            'label' => 'links',
        ];
    }
    return (string) json_encode([
        'profile' => 'conceptmap',
        'nodes' => $nodes,
        'relations' => $relations,
    ]);
};

$maplength = 0;
for ($i = 0; $i < $entrycount; $i++) {
    $recordid = (int) $DB->insert_record('data_records', (object) [
        'dataid' => $dataid,
        'userid' => $userids[$i % count($userids)],
        'groupid' => 0,
        'timecreated' => $now,
        'timemodified' => $now,
        'approved' => 1,
    ]);
    $map = $makemap($i);
    $maplength = strlen($map);
    $DB->insert_record('data_content', (object) [
        'fieldid' => $fieldid,
        'recordid' => $recordid,
        'content' => $map,
    ]);
}

echo "export BASE_URL='{$CFG->wwwroot}'\n";
echo "export CMID='{$cmid}'\n";
echo "export DATAID='{$dataid}'\n";
echo "export USERNAMES='" . implode(',', $usernames) . "'\n";
echo "export PASSWORD='{$password}'\n";
echo "# Database: {$entrycount} entries x {$nodecount} nodes ({$maplength} bytes per map); "
    . "{$usercount} students.\n";
echo "# The list view renders every visible entry's map, so PERPAGE drives the page weight.\n";
echo "# Run: make jmeter  (or: make load-k6)\n";
