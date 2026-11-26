# GuidanceSched

GuidanceSched is an academic prototype for a school guidance center. It includes consultation scheduling, role based dashboards, account management, confidential case records, follow ups, and a JSON API.

> **Prototype notice:** Use synthetic data only. This project is not a production counseling platform and has not completed an institutional privacy review.

## Quick start

### Requirements

- Docker with Docker Compose
- Composer
- PHP 8.4.1 or newer if running PHP commands outside Sail

Sail runs the application with PHP 8.5 and PostgreSQL 18. The Composer lock file requires PHP 8.4.1 or newer.

### Install and start

Run these commands from the project root:

1. Create the environment file if it does not exist, then install Composer dependencies on the host. Composer installation provides Sail, which is needed to start the containers.

   ```sh
   cp .env.example .env
   composer install
   ```

   If you already have a local `.env`, keep it and skip the copy command.

2. Build and start the application and database, then prepare Laravel and its database:

   ```sh
   ./vendor/bin/sail up -d --build
   ./vendor/bin/sail artisan key:generate
   ./vendor/bin/sail artisan migrate --seed
   ```

3. Install frontend packages and build the browser assets:

   ```sh
   ./vendor/bin/sail npm install
   ./vendor/bin/sail npm run build
   ```

4. Match Sail's host port to the URL. The example `APP_URL` is `http://localhost:8000`, so set `APP_PORT=8000` in `.env` before starting Sail. If `APP_PORT` is unset, Compose maps the app to port 80 instead. Open the matching URL and sign in at `/login` using one of the demo accounts below.

To stop the containers, run:

```sh
./vendor/bin/sail stop
```

The database is stored in a Docker volume and remains available after the containers stop. To rebuild a disposable local database from scratch, run `./vendor/bin/sail artisan migrate:fresh --seed`. This drops all database tables and data; do not run it if you need to preserve existing data.

## Synthetic demo accounts

The normal setup commands `migrate --seed` and `db:seed` run `DatabaseSeeder` only. It creates these fictional users and a small set of linked synthetic consultation, case, and follow up records. The sample appointment and case belong to the demo student. The demo teacher is not assigned that student by default, so the teacher account starts without assigned-student records. The separate `LoadTestDataSeeder` is not run during normal setup.

| Role | Email | Password |
| --- | --- | --- |
| Administrator / guidance personnel | `admin@guidancesched.test` | `ChangeMe123!` |
| Teacher | `teacher@guidancesched.test` | `ChangeMe123!` |
| Student | `student@guidancesched.test` | `ChangeMe123!` |

The seed creates each account only when its email does not already exist. These shared demo passwords are for local development only. Change them before allowing anyone else to access a running instance.

## Roles and main features

The application has three roles: `admin`, `teacher`, and `student`. There is no public registration; administrators create accounts.

- **Students** can view the dates, times, and statuses of their scheduled appointments. They cannot create, edit, cancel, or delete appointments.
- **Teachers** can create appointments for students assigned to them and schedule pending appointments. They can view those students' profiles. They cannot change appointment statuses or delete appointments.
- **Administrators** manage accounts and all appointments, including statuses and private staff notes, and can access confidential case descriptions and follow up notes.

Appointment statuses are `pending`, `approved`, `completed`, and `cancelled`. New requests start as pending. Administrators can set any of these statuses; approving a request requires an assigned time.

## REST API

API routes are under `/api`. Requests use HTTP Basic authentication with an existing account. Send `Accept: application/json`; JSON writes should also send `Content-Type: application/json`. Use HTTPS whenever credentials cross a network. The API applies role and record ownership rules; case and follow up endpoints are administrator only. Appointment JSON omits the internal `notes` field.

| Method and path | Purpose | Access |
| --- | --- | --- |
| `GET /api/appointments` | List appointments; optional `status` filter for staff | Students see only their scheduled appointments; teachers see appointments for assigned students; admins see all |
| `POST /api/appointments` | Create a request with `student_id`, `title`, optional `reason`; teachers must include `scheduled_at` | Teacher (assigned students only) or administrator |
| `GET /api/appointments/{id}` | View appointment details; students receive only title, scheduled time, and status | Student owner, assigned teacher, or administrator |
| `PATCH /api/appointments/{id}` | Teachers may assign a time to pending appointments; admins may change status, time, assignee, or private notes | Assigned teacher or administrator |
| `PATCH /api/appointments/{id}/cancel` | Set status to `cancelled`; returns the updated appointment | Administrator |
| `DELETE /api/appointments/{id}` | Permanently delete an appointment; returns HTTP 204 | Administrator |
| `GET /api/cases` | Search cases; optional `status` and `search` filters | Administrator |
| `GET /api/cases/{id}` | View a case and its follow ups | Administrator |
| `PATCH /api/cases/{id}/status` | Change case status | Administrator |
| `GET /api/cases/{id}/follow-ups` | List follow ups | Administrator |
| `POST /api/cases/{id}/follow-ups` | Add a follow up | Administrator |

There are no API delete routes for cases or follow ups. Appointment times must be future half-hour slots on weekdays between 08:00 and 16:30, and cannot conflict with another pending or approved appointment.

Single resource responses use a `data` object; collections are paginated. Validation errors return HTTP 422, authentication failures return HTTP 401, authorization failures return HTTP 403, and missing resources return HTTP 404.

## Tests

Run the automated suite inside Sail:

```sh
./vendor/bin/sail artisan test
```

Tests should use the separate test database, not a development database containing data you need to keep.

## Optional local API load check

ApacheBench (`ab`) can send a small workload to the seeded synthetic database while the application is running locally:

```sh
ab -n 100 -c 5 -A 'admin@guidancesched.test:ChangeMe123!' -H 'Accept: application/json' http://127.0.0.1:8000/api/appointments
```

Record the date, runtime, database, concurrency, request count, failures, requests per second, and latency summary. Do not run this against real student data or a shared deployment. No load test result is recorded in this repository.

## Database backup and restore

Database backups contain personal and case information. Store them in a protected location and restrict access. Test restores only into a separate, empty database; never restore over the active project database.

Example commands (replace the placeholders with values from your environment):

```sh
docker compose exec -T pgsql pg_dump -U DB_USER DB_NAME > guidance-backup.sql
docker compose exec -T pgsql psql -U DB_USER -d GUIDANCE_TEST_DB < guidance-backup.sql
```

## Privacy and deployment

Use synthetic records only. Keep PostgreSQL on a private network and serve any shared instance over HTTPS. Review account access, logging, retention, backups, and school policy before considering real data. The prototype does not claim production readiness or formal privacy compliance. See [Data Privacy Compliance Note](docs/data-privacy-compliance-note.md) for the project data inventory and related procedures.
