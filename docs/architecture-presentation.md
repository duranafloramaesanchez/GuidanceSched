
# GuidanceSched: presentation outline

Use this outline to explain the system in 11 slides. It is based on the project files. Any proposed improvements are labeled as recommendations. No load-test results are available yet.

## 1. Title

**GuidanceSched**  
A school guidance appointment and case-record system  
How it works, how it is set up, and how it protects information

Presenter: [Name] · School: [School] · Date: [Date]

## 2. Organization and problem

- **Organization:** a school guidance center that arranges student consultations and follow-up.
- **Problem:** appointment requests, student details, private case records, and follow-up notes can be hard to keep organized when tracked separately.
- **What the system does:** helps staff manage appointments, accounts, case records, and follow-ups. It also provides different pages for different users and a few ways for other programs to exchange data with it.
- **Project limit:** this is a school project prototype. It has not had a formal school privacy review and is not ready to be described as a production counseling system.

## 3. Users and main tasks

| User | What they can do |
| --- | --- |
| Admin / guidance staff | Manage accounts; schedule, approve, complete, or cancel appointments; view and update case records; add follow-up notes |
| Teacher | Work with assigned students; create consultation requests and set available times; view related appointments |
| Student | View their own assigned consultation date, time, and status; staff create requests for them |

Main tasks:

1. A staff member creates an appointment request for a student.
2. A teacher sets a time, or an admin schedules and approves it.
3. Staff records a case and can add follow-up notes later.
4. An admin manages user accounts.

## 4. Three main parts of the system

```mermaid
flowchart TB
  subgraph P[Part 1: What users see]
    B[Web pages in a browser]
    C[Other programs using the API]
  end
  subgraph A[Part 2: What processes requests]
    R[Laravel routes and login checks]
    CT[Controllers: check and handle requests]
    M[Models: work with the system's data]
  end
  subgraph D[Part 3: Where information is stored]
    PG[(PostgreSQL database)]
  end
  B -->|Web requests and pages| R
  C -->|Requests and JSON data| R
  R --> CT --> M --> PG
```

- **Part 1 — What users see:** web pages in a browser. Other programs can also use the system through its API, a few web addresses for exchanging information.
- **Part 2 — What processes requests:** the Laravel app checks who is signed in, checks what they are allowed to do, and handles their request.
- **Part 3 — Where information is stored:** the PostgreSQL database saves users, appointments, case records, and follow-ups.
- These are three parts of how the system works. They are not three separate apps: the current setup runs one Laravel app and one database.

## 5. How a teacher schedules an appointment

Example: a teacher sends an appointment request with a chosen time.

1. The teacher submits a form on a web page or sends a request through the API.
2. The Laravel app receives the request.
3. It checks that the teacher is signed in and allowed to work with that student.
4. It checks that the student and time are valid and that the time is available.
5. The app saves the appointment as **pending**, with the student, teacher, and time. The default appointment length is 30 minutes.
6. The database saves the appointment. The system then shows an updated page or sends back a result to the API user.
7. An admin can later approve, complete, or cancel the appointment.

The system can reject a request if the user is not signed in (API: 401), is not allowed to do it (403), or entered invalid information (422).

## 6. How parts of the system communicate

| Connection | What happens |
| --- | --- |
| Browser to app | The browser sends a web request and receives a page. Users sign in to the website. Use HTTPS, the secure form of web traffic, when people connect over a network. |
| Other program to app | A program can use the API to send or receive information as JSON, a simple text format computers use to share data. It signs in with an existing account. Use HTTPS when sending login details over a network. |
| App to database | The app sends database requests over the private Docker network. Database login details come from settings outside the source code. |

- The API shows lists in smaller pages of results. It returns JSON data and uses error codes when something goes wrong.
- Students see only limited information about their own appointments. Only admins can use the case and follow-up API addresses.
- The API uses Basic Authentication, a simple sign-in method. Before real use, the school should review whether a stronger sign-in method is needed.

### Communication protocol specification (summary)

GuidanceSched exposes a REST-style API under `/api`. Clients send and receive JSON over HTTP; use **HTTPS** whenever traffic crosses a network so credentials and student information are encrypted in transit. The checked-in development setup may use local HTTP, so it should not be treated as a secure public deployment. Every API endpoint requires HTTP Basic Authentication, and the server applies role and record-level access checks. Case and follow-up endpoints are administrator-only.

| Core transaction | Endpoint | Method | Access |
| --- | --- | --- | --- |
| List appointments | `/api/appointments` | GET | Signed-in users; results scoped by role |
| Create appointment request | `/api/appointments` | POST | Admin or teacher |
| Read appointment | `/api/appointments/{appointment}` | GET | Authorized owner or staff |
| Update appointment | `/api/appointments/{appointment}` | PATCH | Authorized staff; fields depend on role/state |
| Cancel appointment | `/api/appointments/{appointment}/cancel` | PATCH | Admin |
| Delete appointment | `/api/appointments/{appointment}` | DELETE | Admin |
| List / read cases | `/api/cases`, `/api/cases/{case}` | GET | Admin |
| Change case status | `/api/cases/{case}/status` | PATCH | Admin |
| List / record follow-ups | `/api/cases/{case}/follow-ups` | GET / POST | Admin |

Successful reads and updates return **200**, new records return **201**, and deletes return **204** with no response body. Errors use a consistent JSON object, for example `{"error":{"code":"BAD_REQUEST","message":"The request could not be processed.","details":{"title":["The title field is required."]}}}`. The API uses **400** for invalid input, **401** for missing or invalid credentials, **403** for insufficient permissions, **404** for missing or inaccessible records, and **500** for unexpected server errors.

## Appendix A. Full communication protocol specification

The machine-readable **OpenAPI 3.0.3** specification is in [`docs/openapi.yaml`](openapi.yaml). It defines the endpoint URIs, HTTP methods, request and response formats, authentication, and possible status codes for all 11 operations listed above. It is the full endpoint reference for this summary.

The endpoint-by-endpoint curl success and failure cases are in [`docs/api-test-plan.md`](api-test-plan.md). They are prepared but have **not been executed** because the local Docker/Podman service was unavailable when checked. Record actual HTTP results there after running the application; the listed cases are not evidence of successful execution.

All error responses use `{ "error": { "code": "...", "message": "...", "details": { ... } } }`. `details` is included for validation errors when field-level information is available. Authentication is required for every operation. The API's common status codes are 200, 201, 204, 400, 401, 403, 404, and 500; route-specific responses are documented in the OpenAPI file.

## 7. Where the system runs

| Area | Deployment |
| --- | --- |
| Environment | Development runs in WSL Ubuntu with Docker Desktop. A temporary online test deployment is defined for Render in Singapore. |
| Containers | Docker Compose runs the PHP 8.5 Laravel app and PostgreSQL 18 locally. Render builds the app image from `Dockerfile` and connects it to a managed PostgreSQL database. |
| Virtual machines | No project-specific virtual machine is configured. Docker Desktop and Render provide the underlying host infrastructure. |
| Network access | Locally, the browser reaches the app on the host's web port; the app and database communicate over a private Docker network. PostgreSQL is bound to localhost only. On Render, users reach the web service at its HTTPS URL and the app connects to the database over Render's private network. |

The Render setup is for testing, not production. Before shared use, review HTTPS, secret settings, database access, backups, monitoring, and the school's privacy requirements. Use synthetic records until the school approves real data use.

## 8. Data design and storage plan

GuidanceSched uses PostgreSQL, a relational database management system, as the authoritative store for business data. The local Docker Compose setup runs PostgreSQL 18. The core entities are users, appointments, case records, and follow-ups. Foreign keys connect records and define what happens when a related record is deleted.

### Entity-relationship diagram

```mermaid
erDiagram
    USERS ||--o{ USERS : "assigned teacher"
    USERS ||--o{ APPOINTMENTS : "student"
    USERS ||--o{ APPOINTMENTS : "creates"
    USERS ||--o{ APPOINTMENTS : "assigned staff"
    APPOINTMENTS ||--o{ CASE_RECORDS : "optional link"
    USERS ||--o{ CASE_RECORDS : "student"
    USERS ||--o{ CASE_RECORDS : "creates"
    CASE_RECORDS ||--o{ FOLLOW_UPS : "has"
    USERS ||--o{ FOLLOW_UPS : "creates"
    USERS ||--o{ PASSKEYS : "registers"

    USERS {
        bigint id PK
        string email UK
        string role
        bigint assigned_teacher_id FK
        string student_number UK
    }
    APPOINTMENTS {
        bigint id PK
        bigint student_id FK
        bigint created_by FK
        bigint assigned_to FK
        datetime scheduled_at
        string status
    }
    CASE_RECORDS {
        bigint id PK
        bigint student_id FK
        bigint created_by FK
        bigint appointment_id FK
        string case_number UK
        string status
    }
    FOLLOW_UPS {
        bigint id PK
        bigint case_record_id FK
        bigint created_by FK
        date follow_up_date
        text notes
    }
    PASSKEYS {
        bigint id PK
        bigint user_id FK
        string credential_id UK
        json credential
    }
```

`PK` means primary key, `FK` means foreign key, and `UK` means unique value. Laravel also defines support tables for sessions, password resets, caching, and queued jobs.

### Data dictionary

| Table | Important fields | Purpose and relationships |
| --- | --- | --- |
| `users` | `id`, `name`, `email`, `password`, `role`, `student_number`, `grade_level`, `section`, contact and guardian fields, `assigned_teacher_id` | Student, teacher, and administrator accounts. `assigned_teacher_id` optionally relates a student to a teacher in this table. Email and student number are unique. Passwords are stored as hashes. |
| `appointments` | `id`, `student_id`, `created_by`, `assigned_to`, `title`, `reason`, `scheduled_at`, `duration_minutes`, `status`, `notes` | A consultation request for a student, created by an account and optionally assigned to staff. `notes` are staff-only. Student deletion cascades to appointments; deleting creator or assignee accounts clears those references. |
| `case_records` | `id`, `student_id`, `created_by`, `appointment_id`, `case_number`, `title`, `description`, `status`, `category`, `remarks`, session date/time, `findings`, `recommendations` | Confidential guidance case linked to a student and creator, optionally linked to an appointment. Case number is unique. Deleting a student deletes their cases; deleting a linked appointment clears the optional link. |
| `follow_ups` | `id`, `case_record_id`, `created_by`, `follow_up_date`, `notes`, `action_taken` | Dated actions and notes belonging to a case. Deleting a case cascades to its follow-ups. Creator account deletion is restricted to preserve attribution. |
| `passkeys` | `id`, `user_id`, `name`, `credential_id`, `credential`, `last_used_at` | Authentication credentials associated with a user account. Deleting the account deletes its passkeys. |
| `sessions` | `id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity` | Laravel browser session data. `.env.example` selects the database session driver, so the session table is used with that configuration. |
| Support tables | `password_reset_tokens`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs` | Framework support for password resets, cache, and queued work. |

### Client-side storage

The browser does not store business records such as appointments, profiles, cases, or follow-up notes locally. Those remain on the server in PostgreSQL. Browser authentication uses a session cookie, while session state is managed server-side according to the Laravel session configuration. Do not put student information, passwords, or authentication tokens in browser local storage.

### Backup and restore procedure

Backups contain sensitive student information. Keep them access-controlled, encrypted when stored or transferred, and outside source control. Run these scripts from the project root while Sail's PostgreSQL service is running. Use a separate, disposable database for restore verification.

**Backup script** (`backup-guidancesched.sh`):

```sh
#!/bin/sh
set -eu

backup_file="${1:-backups/guidancesched-$(date +%Y%m%d-%H%M%S).dump}"
mkdir -p "$(dirname "$backup_file")"

vendor/bin/sail exec -T pgsql sh -c \
  'pg_dump -U "$POSTGRES_USER" -d "$POSTGRES_DB" --format=custom' \
  > "$backup_file"

chmod 600 "$backup_file"
printf 'Backup created: %s\n' "$backup_file"
```

**Restore script** (`restore-guidancesched.sh`):

```sh
#!/bin/sh
set -eu

backup_file="${1:?Usage: restore-guidancesched.sh BACKUP_FILE}"
test -r "$backup_file"
restore_db="guidancesched_restore_test"

# This dedicated test database is recreated on each run; the configured app database is untouched.
vendor/bin/sail exec -T pgsql sh -c \
  'dropdb --if-exists -U "$POSTGRES_USER" guidancesched_restore_test && createdb -U "$POSTGRES_USER" guidancesched_restore_test'

vendor/bin/sail exec -T pgsql sh -c \
  'pg_restore --no-owner -U "$POSTGRES_USER" -d guidancesched_restore_test' \
  < "$backup_file"

vendor/bin/sail exec -T pgsql sh -c \
  'psql -U "$POSTGRES_USER" -d guidancesched_restore_test -c "\\dt"'

printf 'Restored %s into %s; verify expected tables and synthetic records before removing the test database.\n' "$backup_file" "$restore_db"
```

After restoring, check that expected tables and known synthetic records exist, then confirm the restored data can be read by the application or a test connection. Record the backup date, restore date, target test database, and verification result. The test database and backup must be protected and removed securely when the drill is complete.

**Restore demonstration:** Not yet recorded. Run the scripts against the local Sail database and replace this note with the observed result. A successful `pg_restore` command alone is not enough; verify expected tables and records.

The school still needs to set its retention and deletion schedule and decide how often backups are taken, where protected copies are stored, and who may restore them.

## 9. Security and student privacy (RA 10173)

- RA 10173, the Data Privacy Act of 2012, says personal information must be handled with transparency, for a proper purpose, and only as much as needed. See the [National Privacy Commission's copy of the law](https://privacy.gov.ph/data-privacy-act/).
- **Already in the project:** users must sign in; the app checks user roles and access; only admins can view case and follow-up records; forms check the information entered; passwords are saved in protected form; students see limited appointment details; anyone cannot sign themselves up.
- **Private information in the system:** student and guardian details, appointment reasons, case descriptions, findings, recommendations, and follow-up notes.
- **Before using real student information:** the school should explain why it collects information, who can see it, how it is protected, and when it is deleted. Use secure connections, protect backups, review staff access, and plan what to do if information is exposed.
- These system features do not prove that the school follows the law. Ask the school's Data Protection Officer to review the system before using real student records. Use made-up records for demos.

## 10. Checking speed and comparing designs

**Speed test status:** the project has no recorded test results. Run the test with made-up data before presenting any speed numbers.

When testing, record:

- Date, PHP and database versions, and computer or server used.
- Which page or API address was tested, how many requests were sent, and how many at once.
- How many requests succeeded or failed, how long they took, and how many requests finished per second.
- Repeat the test and note whether the app had already been used recently.

Suggested small test: request the appointment list 100 times, with 5 requests at once, using the made-up admin account. This is only a basic check, not proof the system can handle a large school. Do not test with real student information or on a shared live system without approval.

| Design | Possible benefit | Possible cost |
| --- | --- | --- |
| Current design: one Laravel app and one database | Easier to set up and maintain for a small project; related changes can be saved together | If more capacity is needed, the app is scaled as a whole; all features share one release |
| Split the system into several apps | Each app could be changed or scaled separately | More pieces to set up, connect, secure, and monitor; keeping information in sync is harder |

To compare the designs, test the same tasks on the same computer or server. Compare speed, errors, computer use, ease of maintenance, and whether information stays consistent. No such comparison has been run yet; the table lists expected trade-offs only.

## 11. Limits and next steps

**Current limits**

- This is a prototype and has not had a formal school privacy review.
- No speed test or design comparison results have been recorded.
- The API sign-in and local Docker setup need review before shared or real use.
- The school has not set record-keeping time limits or a full plan for monitoring and responding to problems.
- The local setup does not include a complete plan for keeping a live service available or recovering from a major failure.

**Next steps**

1. Ask school staff and the Data Protection Officer to review the work process, information collected, who can see it, and when it should be deleted.
2. Review sign-in, secure connections, passwords and secret settings, activity records, and how access is checked.
3. Write down how to back up and restore information and respond to a security problem.
4. Run and record speed tests using made-up records.
5. Try the system with made-up records first. Use real student information only after school approval.

---

**Project files used:** `README.md`, `compose.yaml`, web and API routes, appointment controllers, and database setup files. Laravel Boost setup could not finish because Composer could not connect to Packagist; this outline was prepared by reading the project files directly.
