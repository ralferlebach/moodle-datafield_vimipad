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
 * Mount the ViMi Pad editor onto a database activity field value.
 *
 * The field stores the whole map as one value. This module reads the current
 * value from a hidden input, mounts the embeddable editor bound to an in-memory
 * value transport (mod_vimipad/editor_lazy mountValue), and mirrors every edit
 * back into the hidden input so it is saved with the entry form.
 *
 * @module     datafield_vimipad/field
 * @copyright  2026 Ralf Erlebach
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Initialise the editor for one field value.
 *
 * @param {string} containerId The id of the element to mount the editor into.
 * @param {string} inputId The id of the hidden input carrying the map value.
 * @param {string} profile The diagram profile to constrain the map to.
 * @param {string} formconfigJson The profile form config (JSON) from mod_vimipad.
 * @param {boolean} readonly Whether the value is shown view-only (browse mode);
 *     read-only instances are mounted lazily when they scroll into view.
 */
export const init = (containerId, inputId, profile, formconfigJson, readonly) => {
    const container = document.getElementById(containerId);
    const input = document.getElementById(inputId);
    if (!container || !input) {
        return;
    }

    let formconfig;
    try {
        formconfig = formconfigJson ? JSON.parse(formconfigJson) : undefined;
    } catch (e) {
        formconfig = undefined;
    }

    const mount = () => {
        require(['mod_vimipad/editor_lazy'], (editor) => {
            editor.mountValue(container, {
                value: input.value || '',
                onChange: (valuejson) => {
                    input.value = valuejson;
                },
                profile: profile,
                readonly: readonly === true,
                formconfig: formconfig,
                getString: (key) => {
                    const store = window.M && window.M.str && window.M.str.mod_vimipad;
                    return store && store[key] !== undefined ? store[key] : undefined;
                },
            });
        });
    };

    // A read-only browse view can list many entries on one page. Mounting a full
    // editor for each up front is wasteful, so defer the mount until the entry
    // scrolls near the viewport. The editable add/edit form is a single instance
    // and mounts immediately.
    if (readonly === true && typeof IntersectionObserver !== 'undefined') {
        const observer = new IntersectionObserver((entries, obs) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    obs.disconnect();
                    mount();
                }
            });
        }, {rootMargin: '200px'});
        observer.observe(container);
    } else {
        mount();
    }
};
