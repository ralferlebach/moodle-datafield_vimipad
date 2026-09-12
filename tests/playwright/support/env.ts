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

/** Environment for the datafield_vimipad Playwright user stories. */

export interface TestUser { username: string; password: string; fullname: string; }

export interface DatafieldEnv {
    baseURL: string;
    /** The database activity view path, e.g. /mod/data/view.php?id=42. */
    dataPath: string;
    /** The database activity course module id. */
    dataCmid: string;
    /** The database instance id, for the fields page. */
    dataId: string;
    teacher: TestUser;
    student: TestUser;
}

/**
 * Read the environment, throwing a clear error if the seed step has not run.
 *
 * @returns The resolved environment.
 */
export function readEnv(): DatafieldEnv {
    const need = (name: string): string => {
        const value = process.env[name];
        if (!value) {
            throw new Error(`Missing ${name}. Run tests/playwright/seed.php first (see README.md).`);
        }
        return value;
    };
    return {
        baseURL: process.env.VIMIDATAFIELD_BASE_URL ?? 'http://localhost:8000',
        dataPath: need('VIMIDATAFIELD_DATA_PATH'),
        dataCmid: need('VIMIDATAFIELD_DATA_CMID'),
        dataId: need('VIMIDATAFIELD_DATA_ID'),
        teacher: {
            username: need('VIMIDATAFIELD_TEACHER'),
            password: need('VIMIDATAFIELD_TEACHER_PASS'),
            fullname: process.env.VIMIDATAFIELD_TEACHER_NAME ?? 'Tay Teacher',
        },
        student: {
            username: need('VIMIDATAFIELD_STUDENT'),
            password: need('VIMIDATAFIELD_STUDENT_PASS'),
            fullname: process.env.VIMIDATAFIELD_STUDENT_NAME ?? 'Sam Student',
        },
    };
}

/**
 * Log a user in through the standard Moodle login form.
 *
 * @param page The Playwright page.
 * @param baseURL The site base URL.
 * @param user The user to log in as.
 */
export async function login(page: import('@playwright/test').Page, baseURL: string, user: TestUser): Promise<void> {
    const {expect} = await import('@playwright/test');
    for (let attempt = 1; attempt <= 2; attempt++) {
        await page.goto(`${baseURL}/login/index.php`);
        await page.locator('#username').fill(user.username);
        await page.locator('#password').fill(user.password);
        await page.locator('#loginbtn').click();
        try {
            await expect(page).not.toHaveURL(/\/login\//, {timeout: 20_000});
            return;
        } catch (error) {
            if (attempt === 2) { throw error; }
        }
    }
}
