# GuidanceSched

GuidanceSched is an academic prototype for a school guidance center. It demonstrates consultation scheduling, account management, confidential case metadata, follow-up records, role-based dashboards, and a small JSON API. It is not a production counseling platform and has not completed an institutional privacy review.

## Roles

The application uses three role values: `admin`, `teacher`, and `student`. The administrator role includes guidance personnel functions and account administration. Teachers and students can see and manage only their own consultation requests. Only administrators can access case descriptions and follow-up notes. There is no public registration; accounts are created by an administrator.

## Requirements and local setup

The checked-in Sail configuration uses PHP 8.5 and PostgreSQL 18. The locked Composer dependencies require PHP 8.4.1 or newer, and `composer.json` declares `^8.4.1`. Use Sail or another PHP 8.4.1+ runtime; PHP 8.1 cannot run Artisan with this lock file.

1. Copy `.env.example` to `.env` if needed. It is configured for the private Compose PostgreSQL service; change the development database password if desired.
2. Install Composer dependencies before starting Sail. This provides the Sail runtime files used by Compose:

   ```sh
   composer install
   ```

3. Start Sail and finish setup:

   ```sh
   ./vendor/bin/sail up -d --build
   ./vendor/bin/sail artisan key:generate
   ./vendor/bin/sail artisan migrate --seed
   ./vendor/bin/sail npm install
   ./vendor/bin/sail npm run build
   ```

4. Open the configured `APP_URL` (Sail defaults to `http://localhost`).

The PostgreSQL port in `compose.yaml` binds to `127.0.0.1` for local development only; the database container is also on the private Compose network. Do not publish port 5432 on a public interface in a deployment.

### Synthetic demonstration accounts

`./vendor/bin/sail artisan db:seed` creates these accounts only if the email address does not already exist:

| Role | Email | Password |
| --- | --- | --- |
| Administrator / guidance personnel | `admin@guidancesched.test` | `ChangeMe123!` |
| Teacher | `teacher@guidancesched.test` | `ChangeMe123!` |
| Student | `student@guidancesched.test` | `ChangeMe123!` |

These are fictional demo credentials. Change them before exposing a development instance to anyone else. The seeder adds only synthetic consultation, case, and follow-up records.

## Appointment workflow

Appointment statuses are `pending`, `approved`, `completed`, and `cancelled`. New requests start as `pending`. Administrators can approve pending requests, complete approved requests, or cancel pending/approved requests. Teachers and students may edit or cancel only their own pending requests. Completed and cancelled requests are terminal.

## REST API

All API routes are under `/api` and use HTTP Basic authentication against existing user accounts. Send `Accept: application/json`; use HTTPS whenever credentials cross a network. The API enforces the same ownership rules as the browser interface. Confidential case and follow-up endpoints require the `admin` role. Appointment JSON deliberately omits the internal `notes` field.

| Method and path | Purpose and request fields | Authorization |
| --- | --- | --- |
| `GET /api/appointments` | List; optional `status` query filter for staff | Students see only assigned date/time and status; teachers/admins see all requests |
| `POST /api/appointments` | Create for a student; required `student_id` and `title`; optional `reason`. Teachers must include `scheduled_at`; administrators may leave it unassigned. | Teacher/admin |
| `GET /api/appointments/{id}` | Student response contains only `scheduled_at` and `status`; staff can view request details | Assigned owner, teacher, or admin |
| `PATCH /api/appointments/{id}` | Teacher: `scheduled_at`; admin: `status`, `scheduled_at`, `assigned_to`, `notes` | Teacher or admin |
| `PATCH /api/appointments/{id}/cancel` | Cancel a request; no body required | Admin only |
| `GET /api/cases` | Search/filter with optional `status`, `search` | Admin |
| `GET /api/cases/{id}` | View case details and follow-up history | Admin |
| `PATCH /api/cases/{id}/status` | Required `status`: `open`, `in_progress`, or `closed` | Admin |
| `GET /api/cases/{id}/follow-ups` | List dated follow-ups | Admin |
| `POST /api/cases/{id}/follow-ups` | Required `follow_up_date`, `notes`; optional `action_taken` | Admin |

JSON responses use a `data` key for individual resources and Laravel's standard pagination object for lists. Example appointment response:

```json
{
  "data": {
    "id": 17,
    "student_id": 4,
    "title": "Study planning consultation",
    "reason": "Discuss study routines",
    "scheduled_at": "2026-10-25T09:00:00.000000Z",
    "duration_minutes": 30,
    "status": "pending",
    "created_at": "2026-10-23T08:00:00.000000Z"
  }
}
```

Validation errors return HTTP 422, unauthenticated requests are challenged with HTTP Basic authentication, and unauthorized role/ownership attempts return HTTP 403.

## Tests

Run the automated suite in the supported PHP/Sail environment:

```sh
./vendor/bin/sail artisan test
```

The test environment should use a separate test database, never the development database. Check the test result output before reporting acceptance criteria as passed.

## Basic load check

For a repeatable local smoke test, use only the seeded synthetic admin account and synthetic database. With ApacheBench (`ab`) installed and the app running locally, issue a small authenticated API workload:

```sh
ab -n 100 -c 5 -A 'admin@guidancesched.test:ChangeMe123!' -H 'Accept: application/json' http://127.0.0.1/api/appointments
```

Record the date, runtime/PHP version, database, concurrency, request count, failed requests, requests per second, and latency summary. This repository has not recorded a load-test result yet; the current shell lacks compatible PHP and Docker, so the procedure has not been run here. Do not run a load test against real student data or a shared deployment without approval.

## Backup and restore

Back up PostgreSQL with `pg_dump` to a protected location and restrict access to the dump because it contains personal and case information. Test restoration only into a separate empty test database, verify the restored data, and document the date and outcome. Never restore over the active project database without an explicit backup and approval.

Example local dump and safe test restore (replace names with values from the local environment):

```sh
docker compose exec -T pgsql pg_dump -U DB_USER DB_NAME > guidance-backup.sql
docker compose exec -T pgsql psql -U DB_USER -d GUIDANCE_TEST_DB < guidance-backup.sql
```

## Privacy and deployment limits

Use synthetic records in demos. Keep the database on a private network and serve the application through HTTPS in any shared environment. Review account access, logging, retention, backups, and school policy before using real data. This prototype does not claim production readiness or formal compliance certification.
