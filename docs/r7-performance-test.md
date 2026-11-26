# R7 Performance Test

## Scope and method

This plan uses ApacheBench (`ab`) against the REST API from R2. It covers one read transaction (`GET /api/appointments`) and one write transaction (`POST /api/appointments`). The write endpoint requires an administrator account and a valid synthetic student ID.

Run this only against a disposable local database populated with synthetic records. Each successful write creates an appointment, so restore or discard that database after the experiment. Do not run the write workload against a shared or production database.

Set the base URL and synthetic credentials in the shell. `R7_STUDENT_ID` must identify a student in the test database. `R7_REQUESTS` is 300 for every run below, which gives each run enough samples to report the p95 latency while keeping the write count controlled.

```sh
export R7_BASE_URL=http://127.0.0.1
export R7_ADMIN_EMAIL=admin@guidancesched.test
export R7_ADMIN_PASSWORD='ChangeMe123!'
export R7_STUDENT_ID=3
export R7_REQUESTS=300
```

Create the request body file for ApacheBench:

```sh
cat > /tmp/r7-appointment.json <<EOF
{"student_id":${R7_STUDENT_ID},"title":"R7 synthetic load test","reason":"Synthetic performance test"}
EOF
```

Run each transaction at concurrency 1, 10, and 50. Save each complete output to a separate file so the summary values can be transcribed into the results table.

```sh
for concurrency in 1 10 50; do
    ab -n "$R7_REQUESTS" -c "$concurrency" \
        -A "$R7_ADMIN_EMAIL:$R7_ADMIN_PASSWORD" \
        -H 'Accept: application/json' \
        "$R7_BASE_URL/api/appointments" \
        > "r7-read-c${concurrency}.txt" 2>&1
done

for concurrency in 1 10 50; do
    ab -n "$R7_REQUESTS" -c "$concurrency" \
        -A "$R7_ADMIN_EMAIL:$R7_ADMIN_PASSWORD" \
        -H 'Accept: application/json' \
        -T 'application/json' \
        -p /tmp/r7-appointment.json \
        "$R7_BASE_URL/api/appointments" \
        > "r7-write-c${concurrency}.txt" 2>&1
done
```

For each output, record `Requests per second`, `Time per request` (mean), `95%` latency, and `Failed requests`. Check the HTTP status counts as well: a run with authentication or validation failures is not a successful performance result. For this API, successful list requests return 200 and creates return 201. Since `ab` repeats the same POST body, all created rows are synthetic and must be discarded with the test database after the run.

## Environment

| Item | Observed value |
| --- | --- |
| Host CPU | 12th Gen Intel Core i5-12500H; 16 logical CPUs (8 cores) |
| Host memory | 7.6 GiB total; approximately 4.4 GiB available when inspected |
| Runtime stack | Laravel Sail, PHP 8.5 application container, PostgreSQL 18 Alpine container |
| Container CPU/memory limits | None declared in `compose.yaml` |
| Load tool | ApacheBench (`ab`); present at `/usr/bin/ab` |
| Application state at report preparation | Not running; Docker or Podman daemon unavailable |

Host memory availability is a point-in-time observation and may change. Container allocation is unrestricted by Compose, so report the host limits and do not claim a smaller per-container allocation.

## Results

The six ApacheBench runs completed on 2026-11-26. Each run sent 300 requests. ApacheBench reported zero failed requests for all runs, giving a measured failure rate of 0%. Follow-up checks confirmed that the read endpoint returns HTTP 200 and the write endpoint returns HTTP 201.

| Transaction | Concurrency | Requests | Throughput (req/s) | Mean (ms) | p95 (ms) | Failed / error rate |
| --- | ---: | ---: | ---: | ---: | ---: | ---: |
| Read: list appointments | 1 | 300 | 3.31 | 301.8 | 356 | 0 / 0% |
| Read: list appointments | 10 | 300 | 16.53 | 604.8 | 947 | 0 / 0% |
| Read: list appointments | 50 | 300 | 13.35 | 3,745.7 | 4,609 | 0 / 0% |
| Write: create appointment | 1 | 300 | 3.39 | 295.4 | 338 | 0 / 0% |
| Write: create appointment | 10 | 300 | 15.00 | 666.7 | 943 | 0 / 0% |
| Write: create appointment | 50 | 300 | 14.94 | 3,347.6 | 4,063 | 0 / 0% |

## Interpretation

Performance begins to degrade between concurrency 10 and 50. Read throughput falls from 16.53 to 13.35 requests per second, while mean response time rises from 604.8 ms to 3,745.7 ms and p95 from 947 ms to 4,609 ms. Write throughput is nearly flat, changing from 15.00 to 14.94 requests per second, while mean response time rises from 666.7 ms to 3,347.6 ms and p95 from 943 ms to 4,063 ms. All runs report zero failed requests.

The similar slowdown in both reads and writes points to contention in shared capacity, most likely the application and/or database tier. The measurements do not isolate which one: CPU, memory, database activity, and application worker utilization were not recorded during the runs. Collecting those measurements during a repeat would be needed to identify a specific bottleneck. Throughput and latency alone are evidence of saturation under 50 concurrent connections, not proof of which component caused it.
