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
 * The field shows its icon in mod_data's "Create a field" menu.
 *
 * Moodle 5.0 and later build that menu from the field's own pix/icon. Moodle
 * 4.5 asks for pix_icon('field/vimipad', ..., 'data') instead, i.e. an image in
 * mod_data's own pix folder, which a plugin may not supply: mapping it through
 * get_fontawesome_icon_map() is refused by core, because a plugin may only map
 * its own icons. On 4.5 the icon is therefore a site setting - the file goes to
 * $CFG->dataroot/pix_plugins/mod/data/field/vimipad.svg - and the test checks
 * that this route resolves.
 *
 * @package    datafield_vimipad
 * @copyright  2026 Ralf Erlebach
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \data_field_vimipad
 */
final class icon_test extends \advanced_testcase {
    /**
     * The real "Create a field" menu renders a drawable icon for this field.
     *
     * @return void
     */
    public function test_create_field_menu_shows_an_icon(): void {
        global $CFG, $PAGE;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $data = $this->getDataGenerator()->create_module('data', ['course' => $course->id]);
        $cm = get_coursemodule_from_instance('data', $data->id);
        $PAGE->set_url('/mod/data/field.php', ['id' => $cm->id]);
        $PAGE->set_context(\context_module::instance($cm->id));

        // The action bar takes the database instance id, not the course module id.
        $actionbar = new \mod_data\output\action_bar((int) $data->id, $PAGE->url);
        $html = $PAGE->get_renderer('core')->render($actionbar->get_create_fields());

        $entry = $this->entry_for($html, 'newtype=vimipad');
        $this->assertNotSame('', $entry, 'The menu must offer the ViMi Pad field.');

        if (str_contains($entry, 'datafield_vimipad')) {
            // Moodle 5.0 and later: the field's own icon, which the plugin ships.
            $this->assertFileExists($CFG->dirroot . '/mod/data/field/vimipad/pix/icon.svg');
            return;
        }

        // Moodle 4.5: the menu asks mod_data for field/vimipad. Without the site
        // file that resolves to nothing and only the alt text shows; with it in
        // the dataroot override folder the image resolves.
        $this->assertStringContainsString('field/vimipad', $entry);
        $this->assertNull($PAGE->theme->resolve_image_location('field/vimipad', 'mod_data', true));

        $dir = $CFG->dataroot . '/pix_plugins/mod/data/field';
        make_writable_directory($dir);
        copy($CFG->dirroot . '/mod/data/field/vimipad/pix/icon.svg', $dir . '/vimipad.svg');
        $this->assertSame(
            $dir . '/vimipad.svg',
            $PAGE->theme->resolve_image_location('field/vimipad', 'mod_data', true),
            'On Moodle 4.5 the icon must resolve once it is placed in the dataroot override folder.'
        );
    }

    /**
     * The markup of the menu entry whose link contains the given marker.
     *
     * @param string $html The rendered menu.
     * @param string $marker A string unique to the wanted link.
     * @return string The entry's markup, or '' when absent.
     */
    private function entry_for(string $html, string $marker): string {
        $at = strpos($html, $marker);
        if ($at === false) {
            return '';
        }
        $start = strrpos(substr($html, 0, $at), '<a ');
        $end = strpos($html, '</a>', $at);
        return substr($html, $start, $end - $start);
    }
}
