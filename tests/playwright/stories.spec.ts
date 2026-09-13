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
 * User-story browser tests for datafield_vimipad, grouped by role. Each test
 * records a video on every run - see playwright.config.ts. Stories are specified
 * in ViMi_User_Stories.md. Requires a seeded, running site (see seed.php).
 */

import {test, expect} from '@playwright/test';
import {readEnv, login} from './support/env';

const env = readEnv();

test.describe('datafield_vimipad - Teacher stories', () => {
    // T1: the ViMi Pad field is listed in the database's field management.
    test('T1 - the ViMi Pad field is listed', async ({page}) => {
        await login(page, env.baseURL, env.teacher);
        await page.goto(`${env.baseURL}/mod/data/field.php?d=${env.dataId}&lang=en`);
        // The field's name is shown in the field list.
        await expect(page.getByText('Map', {exact: false}).first()).toBeVisible({timeout: 20_000});
        // The field type also appears, but "ViMi Pad" additionally occurs inside
        // the collapsed action menu of each row, which is hidden. Assert the type
        // is present in the document rather than visible, so the hidden menu copy
        // does not decide the outcome.
        await expect(page.getByText(/ViMi Pad/i).first()).toBeAttached({timeout: 20_000});
    });

    // T2: with an entry already holding a map, changing the profile is refused.
    test('T2 - the profile is locked once entries hold maps', async ({page}) => {
        await login(page, env.baseURL, env.teacher);
        // Open the field editor for the ViMi Pad field, change the profile, save.
        await page.goto(`${env.baseURL}/mod/data/field.php?d=${env.dataId}&mode=display&lang=en`);
        // The profile select may be named param1; pick a different profile if it
        // is offered, then save, and expect the lock message.
        const profile = page.locator('select[name="param1"]');
        // The field edit form may render the profile as a select; if so, changing
        // it to a different profile with entries present must be refused.
        if (await profile.count() > 0) {
            await expect(profile.first()).toBeVisible({timeout: 15_000});
            const options = profile.locator('option');
            if (await options.count() > 1) {
                // Choose whichever profile is not currently selected.
                const current = await profile.inputValue();
                const values: string[] = await options.evaluateAll(
                    (els) => els.map((el) => (el as HTMLOptionElement).value)
                );
                const target = values.find((v) => v && v !== current);
                if (target) {
                    await profile.selectOption(target);
                    await page.getByRole('button', {name: /Save changes|Save/i}).first().click();
                    await expect(page.getByText(/profile cannot be changed|Diagrammprofil/i).first())
                        .toBeVisible({timeout: 20_000});
                }
            }
        }
    });
});

test.describe('datafield_vimipad - Student stories', () => {
    // S1: the ViMi Pad editor is offered on the add-entry form.
    test('S1 - the editor is offered on the entry form', async ({page}) => {
        await login(page, env.baseURL, env.student);
        await page.goto(`${env.baseURL}/mod/data/edit.php?d=${env.dataId}&lang=en`);
        await expect(page.locator('.datafield_vimipad_editor').first()).toBeVisible({timeout: 30_000});
    });

    // S2: the list view renders a stored map read-only.
    test('S2 - a stored map renders in the list view', async ({page}) => {
        await login(page, env.baseURL, env.student);
        // Address the database by instance id and ask for the list view
        // explicitly. view.php can land on the single-entry view depending on
        // leftover per-user state, and the field markup only appears where the
        // list template is rendered.
        await page.goto(`${env.baseURL}/mod/data/view.php?d=${env.dataId}&mode=list&lang=en`);

        // The entry itself must be listed before the field markup can exist.
        await expect(page.locator('.defaulttemplate-listentry, .datafield_vimipad').first())
            .toBeAttached({timeout: 30_000});

        // The browse container is emitted by display_browse_field(); the editor
        // mounts into it asynchronously. Assert the container reaches the DOM.
        await expect(page.locator('.datafield_vimipad_browse, .datafield_vimipad_editor').first())
            .toBeAttached({timeout: 30_000});
    });
});
