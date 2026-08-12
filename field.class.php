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
 * The value is entered through the embedded ViMi Pad editor (a transport bound to
 * the field value) and rendered read-only when browsing, so entries can be
 * created, stored, searched, exported and backed up like any other field. Values
 * are validated against the public map policy before they are stored.
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
     * The field-type icon, served from this plugin instead of mod_data core.
     *
     * The base implementation resolves the icon inside mod_data
     * ({@see \data_field_base::image()}); overriding it lets the subplugin
     * ship its own glyph, which is what renders next to the field name on the
     * field management and edit pages.
     *
     * @return string The rendered icon HTML.
     */
    public function image() {
        global $OUTPUT;
        return $OUTPUT->pix_icon('icon', $this->type, 'datafield_vimipad');
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
        $inputid = $fieldid . '_value';
        $containerid = $fieldid . '_editor';
        $profile = self::clamp_profile($this->field->param1);

        // Hidden value field carrying the serialised map; the editor mirrors edits into it.
        $hidden = html_writer::empty_tag('input', [
            'type' => 'hidden',
            'id' => $inputid,
            'name' => $fieldid,
            'value' => $content,
        ]);
        $container = html_writer::tag('div', '', [
            'id' => $containerid,
            'class' => 'datafield_vimipad_editor',
            'style' => 'min-height:480px;',
            'data-profile' => $profile,
        ]);
        $noscript = html_writer::tag(
            'noscript',
            html_writer::tag('div', get_string('noscript', 'datafield_vimipad'), ['class' => 'text-muted'])
        );

        $this->preload_editor_strings();
        global $PAGE;
        $formconfig = json_encode(\mod_vimipad\profile\profiles::form_config($profile));
        $PAGE->requires->js_call_amd('datafield_vimipad/field', 'init', [
            $containerid, $inputid, $profile, $formconfig, false,
        ]);

        return html_writer::div($hidden . $container . $noscript, 'datafield_vimipad');
    }

    /**
     * Preload the mod_vimipad editor language strings so the embedded editor
     * resolves them, without hard-coding the key list in this plugin.
     *
     * @return void
     */
    protected function preload_editor_strings() {
        global $PAGE;
        $strings = get_string_manager()->load_component_strings('mod_vimipad', current_language());
        $keys = [];
        foreach (array_keys($strings) as $key) {
            if (strpos($key, 'editor:') === 0 || strpos($key, 'constraint:') === 0) {
                $keys[] = $key;
            }
        }
        if ($keys) {
            $PAGE->requires->strings_for_js($keys, 'mod_vimipad');
        }
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
        $content->content = $this->validated_value($value);

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

        global $PAGE;
        $baseid = 'field_' . $this->field->id . '_r' . (int) $recordid;
        $inputid = $baseid . '_value';
        $containerid = $baseid . '_editor';
        $profile = self::clamp_profile($this->field->param1);

        // Read-only browse: no name (not submitted); the editor renders the map.
        $hidden = html_writer::empty_tag('input', [
            'type' => 'hidden',
            'id' => $inputid,
            'value' => (string) $content,
        ]);
        $container = html_writer::tag('div', '', [
            'id' => $containerid,
            'class' => 'datafield_vimipad_editor datafield_vimipad_browse',
            'style' => 'min-height:360px;',
            'data-profile' => $profile,
        ]);
        // No-JS fallback: the plain-text summary.
        $noscript = html_writer::tag(
            'noscript',
            html_writer::div(s(self::map_summary($content)), 'datafield_vimipad_browse')
        );

        $this->preload_editor_strings();
        $formconfig = json_encode(\mod_vimipad\profile\profiles::form_config($profile));
        $PAGE->requires->js_call_amd('datafield_vimipad/field', 'init', [
            $containerid, $inputid, $profile, $formconfig, true,
        ]);

        return html_writer::div($hidden . $container . $noscript, 'datafield_vimipad');
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
    /**
     * Validate a submitted map against the public ViMi Pad map policy before it
     * is stored. The record form is a plain POST and can be forged, so without
     * this an oversized or structurally broken document (or one from a different
     * diagram profile) would be written straight into {data_content}. An empty
     * value is allowed: it simply means the field was left blank.
     *
     * @param mixed $value The submitted field value.
     * @return string The value to store.
     * @throws moodle_exception When a non-empty value violates the map policy.
     */
    protected function validated_value($value): string {
        $normalised = self::normalise_value($value);
        if ($normalised === '') {
            return '';
        }
        \mod_vimipad\api\value::assert_valid($normalised, self::clamp_profile($this->field->param1));
        return $normalised;
    }

    /**
     * Reduce a submitted field value to the plain string that is stored.
     *
     * @param mixed $value The submitted value (may arrive as a single-element array).
     * @return string The trimmed value.
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
