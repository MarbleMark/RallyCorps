# RallyMaster Pro → RallyCorps (Mark)

Reference PHP aligned with RC webhook contract. Canonical repo: https://github.com/MarbleMark/RallyCorps

## Env

`RALLYCORPS_WEBHOOK_IMPORT_SECRET` — same value as Render `WEBHOOK_IMPORT_SECRET`.

## RC mapping (adapter Events tab)

Enable **RMP POST + RC GET** for `events.event_ID`, `events.group_ID`, dates, name, `registration_open`.

`events.rallies` → RC `events.schedule_json`.
