# datafield_vimipad — load tests (JMeter / k6)

The field exposes no web service, so the load runs are session-based: they log in
and request the database activity's **list view** and a single view.

The list view is the interesting one. `mod_data` renders every visible entry with
the field's `display_browse_field()`, and each one writes its map into the page.
`PERPAGE` therefore drives the page weight directly, and raising it is the fastest
way to see how the list view scales.

## Quick start

```
make load-seed        # 100 entries x 150 nodes, 25 students
make jmeter           # or: make load-k6
```

`make load-seed` writes `BASE_URL`, `CMID`, `DATAID`, `USERNAMES` and `PASSWORD`
to `tests/load/.load-env`, read automatically by both runners. Each virtual user
logs in with its own account (Moodle's one-time `logintoken` is extracted from the
login page first).

## Sizing the fixture

```
make load-seed SEEDARGS="500 300 50"   # entries, nodes per map, students
make load-k6 PERPAGE=50
```

Compare `PERPAGE=10` against `PERPAGE=50`: `datafield_vimipad_page_bytes` should
grow roughly linearly with the number of maps on the page. That is expected — it
is what the review flagged as the field's structural cost, and it is the number to
watch if the list view ever moves to summaries with lazy detail.

## What is measured

| Metric | Meaning |
| --- | --- |
| `datafield_vimipad_page_bytes` | HTML shipped per page, tagged by page |
| `datafield_vimipad_login_errors` | zero tolerance: a failed login is a defect |
| `datafield_vimipad_http_errors` | zero tolerance: a non-200 is a defect |

## Not part of CI

These runs need a live, seeded site and create courses, users and database
entries. `/tests/load` is `export-ignore` in `.gitattributes` and never ships in a
release package; the downloaded JMeter distribution, the k6 binary and `.jtl`
results are gitignored.
