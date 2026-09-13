# datafield_vimipad — Playwright user-story tests

Browser end-to-end tests that walk the real UI for each user role (Teacher,
Student) as specified in the family-wide `ViMi_User_Stories.md`. **Every run
records a video** — success as well as failure — so the HTML report shows real
usage rather than a bare pass/fail list.

These need a **running Moodle site** with datafield_vimipad installed. They do not run inside
`moodle-plugin-ci`'s static jobs, and they cannot run in a sandbox without a
stable browser; the shipped `playwright.yml` workflow stands up a site, seeds
it, runs the suite and uploads the report and videos as an artifact.

## Run locally

```
cd tests/playwright
php seed.php > .env.sh          # creates the course, users and datafield fixture
set -a; . ./.env.sh; set +a
npm ci
npx playwright install --with-deps chromium
npm test                       # videos + report land in test-results/ and playwright-report/
npx playwright show-report     # open the report with embedded videos
```

The seed prints `VIMIDATAFIELD_*` environment variables that `support/env.ts` reads.
`VIMIDATAFIELD_BASE_URL` defaults to `http://localhost:8000`; override it if your
site lives elsewhere.

## Not distributed

`tests/playwright` is `export-ignore` in `.gitattributes`, so it never ships
in a release ZIP. `node_modules`, `test-results`, `playwright-report` and the
seeded `.env` are gitignored.


## Dependency ordering (important for CI)

This plugin requires a specific mod_vimipad build (see `version.php`,
`$plugin->dependencies`). The Playwright workflow clones mod_vimipad from
`VIMIPAD_REF` (default `main`) and installs it alongside. **The workflow
only succeeds once the mod_vimipad branch or ref it pulls actually meets that
version requirement** - otherwise Moodle stops at "Dependencies check failed" and
no test runs.

For a release-candidate run, either merge the matching mod_vimipad first, or pin
`VIMIPAD_REF` (workflow input or the `VIMIPAD_REF` variable) to a tag/SHA that
provides the required build.
