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
 * CLI seed for the datafield_vimipad Playwright user stories.
 *
 * Creates a course with a teacher and a student, a Database activity with a ViMi
 * Pad field, and one entry holding a map (so the profile-lock and browse stories
 * have content). Prints the environment the Playwright run reads. Disposable
 * dev/CI site only.
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

/**
 * Create or fetch an enrolled user with a known password.
 *
 * @param string $base Username stem.
 * @param string $first First name.
 * @param string $last Last name.
 * @param string $pass Password.
 * @param int $courseid Course to enrol into.
 * @param string $rolename Archetype role shortname.
 * @return stdClass The user record.
 */
function datafield_seed_user($base, $first, $last, $pass, $courseid, $rolename) {
    global $DB, $CFG;
    $username = $base . '_' . $courseid;
    $user = $DB->get_record('user', ['username' => $username]);
    if (!$user) {
        $user = (object) [
            'username' => $username, 'auth' => 'manual', 'confirmed' => 1,
            'firstname' => $first, 'lastname' => $last,
            'email' => $username . '@example.invalid', 'mnethostid' => $CFG->mnet_localhost_id,
        ];
        $user->id = user_create_user($user, false, false);
    }
    update_internal_user_password($DB->get_record('user', ['id' => $user->id]), $pass);
    $role = $DB->get_record('role', ['archetype' => $rolename], '*', MUST_EXIST);
    $manual = enrol_get_plugin('manual');
    $instance = $DB->get_record('enrol', ['courseid' => $courseid, 'enrol' => 'manual'], '*', MUST_EXIST);
    $manual->enrol_user($instance, $user->id, $role->id);
    return $user;
}

$now = time();
$course = create_course((object) [
    'fullname' => 'ViMi datafield stories ' . $now,
    'shortname' => 'vdstories' . $now,
    'category' => 1,
    'numsections' => 1,
]);
$teacher = datafield_seed_user('vdf_t', 'Tay', 'Teacher', 'Vimi!df_T1', $course->id, 'editingteacher');
$student = datafield_seed_user('vdf_s', 'Sam', 'Student', 'Vimi!df_S1', $course->id, 'student');

$module = $DB->get_record('modules', ['name' => 'data'], '*', MUST_EXIST);
$created = add_moduleinfo((object) [
    'modulename' => 'data', 'module' => $module->id, 'course' => $course->id, 'section' => 1,
    'visible' => 1, 'name' => 'Map bank', 'intro' => '', 'introformat' => FORMAT_HTML,
], $course);
$datacmid = (int) $created->coursemodule;
$dataid = (int) $created->instance;

$fieldid = (int) $DB->insert_record('data_fields', (object) [
    'dataid' => $dataid, 'type' => 'vimipad', 'name' => 'Map', 'description' => 'A ViMi Pad map',
    'param1' => 'conceptmap',
]);

// One entry with a map so the profile is locked and the list view has content.
$map = json_encode([
    'profile' => 'conceptmap',
    'nodes' => [['stableid' => 'n1', 'label' => 'Cat'], ['stableid' => 'n2', 'label' => 'Animal']],
    'relations' => [['stableid' => 'r1', 'sourceid' => 'n1', 'targetid' => 'n2', 'label' => 'is a']],
]);
$recordid = (int) $DB->insert_record('data_records', (object) [
    'dataid' => $dataid, 'userid' => $student->id, 'groupid' => 0,
    'timecreated' => $now, 'timemodified' => $now, 'approved' => 1,
]);
$DB->insert_record('data_content', (object) ['fieldid' => $fieldid, 'recordid' => $recordid, 'content' => $map]);

$datapath = '/mod/data/view.php?id=' . $datacmid;

echo "export VIMIDATAFIELD_BASE_URL='{$CFG->wwwroot}'\n";
echo "export VIMIDATAFIELD_DATA_PATH='{$datapath}'\n";
echo "export VIMIDATAFIELD_DATA_CMID='{$datacmid}'\n";
echo "export VIMIDATAFIELD_DATA_ID='{$dataid}'\n";
echo "export VIMIDATAFIELD_TEACHER='{$teacher->username}'\n";
echo "export VIMIDATAFIELD_TEACHER_PASS='Vimi!df_T1'\n";
echo "export VIMIDATAFIELD_TEACHER_NAME='Tay Teacher'\n";
echo "export VIMIDATAFIELD_STUDENT='{$student->username}'\n";
echo "export VIMIDATAFIELD_STUDENT_PASS='Vimi!df_S1'\n";
echo "export VIMIDATAFIELD_STUDENT_NAME='Sam Student'\n";
