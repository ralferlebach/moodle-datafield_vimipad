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
 * ViMi Pad database activity field.
 *
 * @package    datafield_vimipad
 * @copyright  2026 Ralf Erlebach
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * A database activity field whose value is a ViMi Pad knowledge map.
 *
 * The map is stored as JSON in core's {data_content} table (content column); the
 * chosen diagram profile is stored in the field definition (param1). The field
 * reuses the mod_vimipad public profile API so the profile list always matches
 * the activity.
 *
 * This is an early stub: the value is entered and displayed through a plain-text
 * area carrying the serialised map so entries can already be created, stored,
 * searched, exported and backed up. The interactive editor embed (a ViMi Pad
 * transport bound to the field value) replaces that area in a follow-up step.
 */
class data_field_vimipad extends data_field_base {
    /** @var string The field type key. */
    public $type = 'vimipad';

    /**
     * Provide a sensible default field definition when a new field is created.
     *
     * @return bool
     */
    public function define_default_field() {
        parent::define_default_field();
        if (empty($this->field->param1)) {
            $this->field->param1 = 'conceptmap';
        }
        return true;
    }

    /**
     * Render the value-entry control shown to the user adding/editing an entry.
     *
     * @param int $recordid The record being edited (0 for a new record).
     * @param mixed $formdata Submitted form data, if any.
     * @return string HTML fragment.
     */
    public function display_add_field($recordid = 0, $formdata = null) {
        global $DB;

        $content = '';
        if ($formdata) {
            $fieldname = 'field_' . $this->field->id;
            $content = $formdata->$fieldname ?? '';
        } else if ($recordid) {
            $content = (string)$DB->get_field(
                'data_content',
                'content',
                ['fieldid' => $this->field->id, 'recordid' => $recordid]
            );
        }

        $fieldid = 'field_' . $this->field->id;
        $profile = self::clamp_profile($this->field->param1);

        $label = html_writer::tag(
            'label',
            s($this->field->name),
            ['for' => $fieldid, 'class' => 'accesshide']
        );
        $textarea = html_writer::tag('textarea', s($content), [
            'id' => $fieldid,
            'name' => $fieldid,
            'rows' => 6,
            'class' => 'form-control datafield_vimipad_value',
            'data-profile' => $profile,
            'spellcheck' => 'false',
        ]);
        $hint = html_writer::tag(
            'div',
            get_string('stubhint', 'datafield_vimipad', $profile),
            ['class' => 'datafield_vimipad_stubhint text-muted']
        );

        return html_writer::div($label . $textarea . $hint, 'datafield_vimipad');
    }

    /**
     * Store the submitted map value for a record.
     *
     * @param int $recordid The record ID.
     * @param mixed $value The submitted value.
     * @param string $name The sub-field name (unused; single-value field).
     * @return bool
     */
    public function update_content($recordid, $value, $name = '') {
        global $DB;

        $content = new stdClass();
        $content->fieldid = $this->field->id;
        $content->recordid = $recordid;
        $content->content = self::normalise_value($value);

        if (
            $oldid = $DB->get_field(
                'data_content',
                'id',
                ['fieldid' => $this->field->id, 'recordid' => $recordid]
            )
        ) {
            $content->id = $oldid;
            return $DB->update_record('data_content', $content);
        }
        return (bool)$DB->insert_record('data_content', $content);
    }

    /**
     * Render the stored value when browsing entries.
     *
     * @param int $recordid The record ID.
     * @param string $template The list/single template being rendered.
     * @return string HTML fragment.
     */
    public function display_browse_field($recordid, $template) {
        global $DB;

        $content = $DB->get_field(
            'data_content',
            'content',
            ['fieldid' => $this->field->id, 'recordid' => $recordid]
        );
        if ($content === false || trim((string)$content) === '') {
            return '';
        }
        return html_writer::div(s(self::map_summary($content)), 'datafield_vimipad_browse');
    }

    /**
     * The field can be exported as text.
     *
     * @return bool
     */
    public function text_export_supported() {
        return true;
    }

    /**
     * Export the raw stored map for a record.
     *
     * @param stdClass $record A record from the {data_content} table.
     * @return string
     */
    public function export_text_value($record) {
        return (string)($record->content ?? '');
    }

    /**
     * Clamp a profile key to one the mod_vimipad public API recognises.
     *
     * @param string|null $profile Requested profile key.
     * @return string A valid profile key (falls back to conceptmap).
     */
    public static function clamp_profile(?string $profile): string {
        $profile = (string)$profile;
        if (
            class_exists('\mod_vimipad\profile\profiles')
                && \mod_vimipad\profile\profiles::exists($profile)
        ) {
            return $profile;
        }
        return 'conceptmap';
    }

    /**
     * The list of diagram profiles offered in the field configuration form.
     *
     * @return array Profile key => human-readable name.
     */
    public static function profile_options(): array {
        $options = [];
        if (class_exists('\mod_vimipad\profile\profiles')) {
            foreach (\mod_vimipad\profile\profiles::all() as $key) {
                $config = \mod_vimipad\profile\profiles::form_config($key);
                $options[$key] = $config['name'] ?? $key;
            }
        }
        if (empty($options)) {
            $options['conceptmap'] = 'conceptmap';
        }
        return $options;
    }

    /**
     * Normalise a submitted value to a trimmed string for storage.
     *
     * @param mixed $value The submitted value.
     * @return string
     */
    protected static function normalise_value($value): string {
        if (is_array($value)) {
            $value = reset($value);
        }
        return trim((string)$value);
    }

    /**
     * A short, plain-text summary of a stored map (node/relation counts).
     *
     * @param string|null $json The stored map JSON.
     * @return string
     */
    public static function map_summary(?string $json): string {
        $decoded = json_decode((string)$json, true);
        if (is_array($decoded)) {
            $nodes = isset($decoded['nodes']) && is_array($decoded['nodes']) ? count($decoded['nodes']) : 0;
            $relations = isset($decoded['relations']) && is_array($decoded['relations'])
                ? count($decoded['relations']) : 0;
            return get_string(
                'mapsummary',
                'datafield_vimipad',
                (object)['nodes' => $nodes, 'relations' => $relations]
            );
        }
        return shorten_text((string)$json, 100);
    }
}
