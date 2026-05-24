# RallyMaster Pro → RallyCorps — instructions for Mark

**Read this before emailing Mirza.** Staging API: `https://rallycorps.onrender.com`  
**Your repo:** https://github.com/MarbleMark/RallyCorps  
**Reference PHP in this folder:** `rally_corps.php`, `rally_corps_events.php`

---

## 1. Two directions (do not confuse them)

| Direction | Who calls who | When |
|-----------|----------------|------|
| **Inbound (you → RC)** | RMP POSTs JSON to RallyCorps | Event sync, personnel, assignments |
| **Outbound (RC → you)** | RallyCorps POSTs JSON to **your** HTTPS URL | After a volunteer **registers** for an RMP event in RallyCorps |

Inbound is what you build first. Outbound needs a receive URL from you (e.g. your `RC_Event_Update.php`); Mirza stores it on the RMP connection.

---

## 2. Authentication (mandatory on every inbound POST)

There is **no test mode** without a secret.

1. Mirza sends you one string (separate email — not in git).
2. On your server: `RALLYCORPS_WEBHOOK_IMPORT_SECRET=<that string>`
3. On every HTTP request to RallyCorps:

```http
POST https://rallycorps.onrender.com/v1/webhooks/import/events
Content-Type: application/json
Authorization: Bearer <exact same secret>
```

- Put the secret in the **header**, not in the JSON body.
- Wrong or missing secret → `401` or `503` with `"WEBHOOK_IMPORT_SECRET is not set"` / invalid secret.
- That error means the call reached RC; fix the header or ask Mirza to confirm Render env.

---

## 3. JSON shape (all inbound webhooks)

**Always** this envelope:

```json
{
  "source_system": "rmp",
  "partner_rows": [
    { }
  ]
}
```

| Rule | Detail |
|------|--------|
| `source_system` | Must be `"rmp"` for you (Mike uses `"rd2"` — separate namespace). |
| `partner_rows` | Must be a **JSON array** — even for one event. |
| Keys inside each row | Your names: `events.event_ID`, `events.group_ID`, `personnel.pers_ID`, etc. |
| Do **not** send a flat object without `partner_rows` | Common bug — RC will reject or mis-parse. |

**PHP:** build one associative array `$partnerRow`, then:

```php
$data = [
  'source_system' => 'rmp',
  'partner_rows'  => [ $partnerRow ],
];
$json = json_encode($data);
```

Use `json_encode` for the body. Do **not** SQL-escape field values for RC (no `mysqli_real_escape_string` on strings going into JSON).

---

## 4. Events (start here)

### 4.1 URL

```
POST https://rallycorps.onrender.com/v1/webhooks/import/events
```

### 4.2 Your fields → RallyCorps

| Send in `partner_rows[0]` | RC column | Required? |
|---------------------------|-----------|-------------|
| `events.event_ID` | `source_eid` | **Yes** — stable id forever |
| `events.group_ID` | `partner_group_id` | Yes (separate field — do not merge into event_ID) |
| `events.event_name` | `event_name` | Recommended |
| `events.start_date` | `start_date` | Recommended — use **`YYYY-MM-DD`** |
| `events.end_date` | `end_date` | Recommended — use **`YYYY-MM-DD`** |
| `events.registration_open` | `open_for_volunteers` | Optional (`0` / `1`) |
| `events.rallies` | `schedule_json` | Optional — array of rally-day objects |

### 4.3 Event IDs — read carefully

- **`events.event_ID`** = your permanent external id. Use the **same** value every time you sync that rally.
- **First POST** for that id → RC **creates** the event and assigns an internal `events.id` (integer).
- **Later POSTs** with same `"rmp"` + same `events.event_ID` → RC **updates** name, dates, flags, rallies — **does not** create a new event.
- You do **not** need `(group × 1000) + event` to avoid clashing with Mike. Mike’s ids are under `source_system: "rd2"`; yours under `"rmp"`.
- Keep **group** in `events.group_ID` and **event** in `events.event_ID` as two fields.

### 4.4 Rally days (`events.rallies`)

Put all competition days in **one array** on the **parent** event row:

```json
"events.rallies": [
  {
    "rally_ID": "1",
    "date": "2026-07-04",
    "vol_default_on": true,
    "day": "Friday",
    "sub_rally_name": "",
    "event_order": 0,
    "rally_name": ""
  }
]
```

- **Do not** prefix each inner field with `events.` (e.g. not `events.date`).
- **`vol_default_on`** — keep sending it; we will use it for registration UX later.
- **`rally_name`**, **`event_order`** — optional; sort by `date` in RMP.

### 4.5 Full copy-paste example

```json
{
  "source_system": "rmp",
  "partner_rows": [
    {
      "events.group_ID": "2",
      "events.event_ID": "5",
      "events.event_name": "Ojibwe Forest Rally",
      "events.start_date": "2026-07-04",
      "events.end_date": "2026-07-06",
      "events.registration_open": 1,
      "events.rallies": [
        {
          "rally_ID": "10",
          "date": "2026-07-04",
          "vol_default_on": true,
          "day": "Friday"
        },
        {
          "rally_ID": "11",
          "date": "2026-07-05",
          "vol_default_on": true,
          "day": "Saturday"
        }
      ]
    }
  ]
}
```

### 4.6 Success response (what you should see)

JSON roughly like:

```json
{
  "created": 0,
  "updated": 1,
  "received": 1,
  "mapping_environment": "staging",
  "partner_row_reports": [ { "persist": { "action": "updated", "source_eid": "5", "rc_event_id": 42 } } ]
}
```

- `persist.rc_event_id` = RallyCorps internal id (for your logs only).
- `persist.source_eid` = your `events.event_ID`.

If fields are missing from the response’s `mapped_rc_fields`, **RMP POST + RC GET** is off for that field in the adapter — tell Mirza which field, not “RC is broken.”

---

## 5. Personnel (no group id)

Shared across all rally groups.

```
POST https://rallycorps.onrender.com/v1/webhooks/import/users
```

```json
{
  "source_system": "rmp",
  "partner_rows": [
    {
      "personnel.pers_ID": "101",
      "personnel.e_mail": "volunteer@example.com",
      "personnel.first_name": "Sample",
      "personnel.last_name": "Volunteer",
      "personnel.phone": "+15551234567"
    }
  ]
}
```

RC links **user + volunteer + legacy mapping** (`pers_ID`). Same secret header.

---

## 6. Assignments (group id matters)

```
POST https://rallycorps.onrender.com/v1/webhooks/import/assignments
```

Requires `WEBHOOK_ASSIGNMENT_IMPORT_ENABLED=1` on API (Mirza turns on for staging).

Use your real assignment column names in `partner_rows`. Include group/rally identifiers as separate keys when you have them.

---

## 7. Outbound (RC → RMP) — registration only

**Trigger:** a member registers for an event in RallyCorps that has `source_system = rmp` and your `source_eid`.

RC POSTs **once** to your URL with full JSON (not “notify then you GET”).

Example shape:

```json
{
  "source_system": "rmp",
  "source_eid": "5",
  "event_type": "volunteer_event_registration",
  "partner_rows": [
    {
      "events.event_ID": "5",
      "rc_people.PID": "12345"
    }
  ],
  "rc_event_id": 42,
  "rc_volunteer_id": 17
}
```

| Field | Meaning |
|-------|---------|
| `source_eid` | Your `events.event_ID` |
| `rc_event_id` | RC internal integer — **not** your event id |
| `partner_rows` | Partner field names per adapter (**RC POST + RMP GET** flags) |

Optional header: `X-RC-Webhook-Secret: <same shared secret>`.

**Give Mirza:** HTTPS URL that accepts POST + returns 2xx (your test script that emails you the body is fine for debugging).

---

## 8. PHP checklist (`EventToRC` / `CurlToRC`)

- [ ] `require_once` `rally_corps.php`
- [ ] `$partnerRow` is one associative array of `events.*` keys
- [ ] `CurlToRC($url, $partnerRow)` wraps it in `partner_rows => [ $partnerRow ]`
- [ ] Secret from `getenv('RALLYCORPS_WEBHOOK_IMPORT_SECRET')` in **Authorization** header
- [ ] Dates to RC as `YYYY-MM-DD` via `SQLDateFromStandard` (good)
- [ ] Log `curl` response body on failure
- [ ] Debug: uncomment `json_encode(..., JSON_PRETTY_PRINT)` in `rally_corps.php` line 14 — **not** for production

---

## 9. What you can skip sending

- Modification timestamp, modified-by username (unless we add mapping later)
- Extra PHP `filter_var` passes **only** for RC if data is already sane from your DB
- New `events.event_ID` when only **dates** changed — reuse the same id

---

## 10. Troubleshooting (check before email)

| Symptom | Fix |
|---------|-----|
| `WEBHOOK_IMPORT_SECRET is not set on the server` | Mirza sets env on Render; you use Bearer with same value |
| `401` / `403` | Wrong secret or missing `Bearer ` prefix |
| `partner_rows` / validation error | Body must be `{ source_system, partner_rows: [ {...} ] }` |
| Event “not updating” | Same `events.event_ID`? Adapter flags on for that field? |
| Field ignored | Need **RMP POST + RC GET** enabled in adapter for that key |
| Mike’s EID vs yours | Irrelevant — different `source_system` |

---

## 11. Reference files in this folder

| File | Purpose |
|------|---------|
| `rally_corps.php` | `CurlToRC()` — correct envelope + Bearer |
| `rally_corps_events.php` | `EventToRC()` — builds event + `events.rallies` |

---

## 12. Longer RC docs (optional)

- [merging-rd2-rmp-into-rallycorps.md](../../docs/merging-rd2-rmp-into-rallycorps.md)
- [partner-user-volunteer-import.md](../../docs/partner-user-volunteer-import.md)
