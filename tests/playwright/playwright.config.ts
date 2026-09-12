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

import {defineConfig, devices} from '@playwright/test';

const baseURL = process.env.VIMIDATAFIELD_BASE_URL ?? 'http://localhost:8000';

export default defineConfig({
    testDir: '.',
    testMatch: '**/*.spec.ts',
    timeout: 60_000,
    expect: {timeout: 15_000},
    fullyParallel: false,
    workers: 1,
    retries: process.env.CI ? 1 : 0,
    // Always emit the HTML report so a green run is not an empty report.
    reporter: process.env.CI
        ? [['github'], ['html', {open: 'never'}], ['list']]
        : [['html', {open: 'never'}], ['list']],
    use: {
        baseURL,
        // Record every run, success included, so the report shows real usage.
        video: 'on',
        trace: 'on',
        screenshot: 'on',
    },
    projects: [
        {name: 'chromium', use: {...devices['Desktop Chrome']}},
    ],
});
