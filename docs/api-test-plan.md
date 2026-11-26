# API test plan and run record

Base URL: `http://localhost` (replace with the app's actual local URL). Run `vendor/bin/sail artisan db:seed` first. Demo credentials are `admin@guidancesched.test` / `ChangeMe123!`, `teacher@guidancesched.test` / `ChangeMe123!`, and `student@guidancesched.test` / `ChangeMe123!`. Seeded case ID and appointment ID can be looked up in the database; use `CASE_ID` and `APPOINTMENT_ID` below. To obtain a student id for appointment creation, query the seeded student by email and set `STUDENT_ID`.

All commands use curl and `-i` so the HTTP status is captured. Each route has one success and one failure check. Failure checks expect the common body `{"error":{"code":"...","message":"..."}}`; request validation errors use HTTP 400 and may also include `details`. A failure status differing from the expected result should be recorded as failed.

## Endpoint cases

| # | Endpoint | Success | Failure | Result |
|---|---|---|---|---|
| 1 | `GET /api/appointments` | `curl -i -u student@guidancesched.test:ChangeMe123! http://localhost/api/appointments` → 200 | `curl -i http://localhost/api/appointments` → 401 `UNAUTHENTICATED` | Not run: Sail reports Docker/Podman is not running |
| 2 | `POST /api/appointments` | `curl -i -u admin@guidancesched.test:ChangeMe123! -H 'Content-Type: application/json' -d "{\"student_id\":$STUDENT_ID,\"title\":\"API test request\"}" http://localhost/api/appointments` → 201 | `curl -i -u admin@guidancesched.test:ChangeMe123! -H 'Content-Type: application/json' -d "{\"student_id\":$STUDENT_ID}" http://localhost/api/appointments` → 400 `BAD_REQUEST` | Not run: Sail reports Docker/Podman is not running |
| 3 | `GET /api/appointments/{appointment}` | `curl -i -u student@guidancesched.test:ChangeMe123! http://localhost/api/appointments/$APPOINTMENT_ID` → 200 | `curl -i -u admin@guidancesched.test:ChangeMe123! http://localhost/api/appointments/99999999` → 404 `NOT_FOUND` | Not run: Sail reports Docker/Podman is not running |
| 4 | `PATCH /api/appointments/{appointment}` | `curl -i -u student@guidancesched.test:ChangeMe123! -X PATCH -H 'Content-Type: application/json' -d '{"title":"Updated API request"}' http://localhost/api/appointments/$APPOINTMENT_ID` → 200 | Same command with `-d '{}'` → 400 `BAD_REQUEST` | Not run: Sail reports Docker/Podman is not running |
| 5 | `PATCH /api/appointments/{appointment}/cancel` | `curl -i -u admin@guidancesched.test:ChangeMe123! -X PATCH http://localhost/api/appointments/$APPOINTMENT_ID/cancel` → 200 | Student credentials on the same request → 403 `FORBIDDEN` | Not run: Sail reports Docker/Podman is not running |
| 6 | `DELETE /api/appointments/{appointment}` | `curl -i -u admin@guidancesched.test:ChangeMe123! -X DELETE http://localhost/api/appointments/$APPOINTMENT_ID` → 204 with no body; verify the row is gone | Student credentials on the same request → 403 `FORBIDDEN` | Not run: Sail reports Docker/Podman is not running |
| 7 | `GET /api/cases` | `curl -i -u admin@guidancesched.test:ChangeMe123! http://localhost/api/cases` → 200 | `curl -i -u student@guidancesched.test:ChangeMe123! http://localhost/api/cases` → 403 `FORBIDDEN` | Not run: Sail reports Docker/Podman is not running |
| 8 | `GET /api/cases/{case}` | `curl -i -u admin@guidancesched.test:ChangeMe123! http://localhost/api/cases/$CASE_ID` → 200 | Same URL with case `99999999` → 404 `NOT_FOUND` | Not run: Sail reports Docker/Podman is not running |
| 9 | `PATCH /api/cases/{case}/status` | `curl -i -u admin@guidancesched.test:ChangeMe123! -X PATCH -H 'Content-Type: application/json' -d '{"status":"in_progress"}' http://localhost/api/cases/$CASE_ID/status` → 200 | Same command with `{"status":"bad"}` → 400 `BAD_REQUEST` | Not run: Sail reports Docker/Podman is not running |
| 10 | `GET /api/cases/{case}/follow-ups` | `curl -i -u admin@guidancesched.test:ChangeMe123! http://localhost/api/cases/$CASE_ID/follow-ups` → 200 | Student credentials on the same URL → 403 `FORBIDDEN` | Not run: Sail reports Docker/Podman is not running |
| 11 | `POST /api/cases/{case}/follow-ups` | `curl -i -u admin@guidancesched.test:ChangeMe123! -H 'Content-Type: application/json' -d '{"follow_up_date":"2026-11-26","notes":"API test follow-up"}' http://localhost/api/cases/$CASE_ID/follow-ups` → 201 | Same command with `{"notes":"missing date"}` → 400 `BAD_REQUEST` | Not run: Sail reports Docker/Podman is not running |

## Execution note

Attempted `vendor/bin/sail artisan route:list --path=api --except-vendor` and `vendor/bin/sail artisan test --help`. Both stopped before execution with `Docker or Podman is not running`. No HTTP requests could be sent from this environment, so the cases above are prepared but their actual HTTP results remain unverified. Re-run the table after starting Sail and replace each `Not run` entry with the observed status and response summary.
