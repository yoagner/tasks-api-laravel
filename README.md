# Tasks API — Laravel 12

REST API for task management built with Laravel 12, SQLite, and no external packages.

## 1. Project overview

A CRUD API for tasks. Each task has a title, description, status (`pending` / `in_progress` / `done`), assignee, and due date. Supports filtering by status and assignee, with pagination.

Built as a technical test. Scope was deliberately limited to what the brief asked for: no authentication, no queues, no external filter packages.

## 2. Requirements

- PHP >= 8.2
- Composer >= 2.x
- SQLite 3 (bundled with PHP)
- Git

## 3. Installation

```bash
git clone https://github.com/yoagner/tasks-api-laravel.git
cd tasks-api-laravel
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve
```

The API will be available at `http://127.0.0.1:8000/api/tasks`.

> `--seed` is optional: it loads 30 demo tasks so you can immediately test filters and pagination. Skip it if you want an empty database.

## 4. Endpoints

Base URL: `http://127.0.0.1:8000/api`

| Method | Endpoint | Description | Success | Errors |
|---|---|---|---|---|
| GET | `/tasks` | List tasks (paginated, filterable) | 200 | — |
| POST | `/tasks` | Create a task | 201 | 422 |
| GET | `/tasks/{id}` | Show a task | 200 | 404 |
| PUT/PATCH | `/tasks/{id}` | Update a task | 200 | 404, 422 |
| DELETE | `/tasks/{id}` | Delete a task | 204 | 404 |

### Query parameters for `GET /tasks`

- `status` — exact match, one of `pending`, `in_progress`, `done`
- `assignee` — partial match, case-insensitive (`LIKE '%value%'`)
- `page` — page number (default `1`, 10 items per page)

**Note on `assignee` filter:** it uses `LIKE '%value%'`, so `?assignee=ar` returns any task whose assignee contains `"ar"` in any position. With the demo data this returns **18 results** (Armando, María, and Carlos — all contain "ar"). If exact match is needed, see "What I'd do with more time".

### Examples

All examples assume the server is running (`php artisan serve`) and the database has been seeded (`php artisan migrate:fresh --seed`). If you want to try them from scratch, you can first clean the database and reseed.

#### 1. List tasks (paginated)

```bash
curl "http://127.0.0.1:8000/api/tasks" -H "Accept: application/json"
```

Response `200 OK` (truncated):

```json
{
  "current_page": 1,
  "data": [
    {
      "id": 1,
      "title": "Design database schema",
      "description": null,
      "status": "pending",
      "assignee": "Armando",
      "due_date": "2026-11-20T00:00:00.000000Z",
      "created_at": "2026-11-19T02:08:00.000000Z",
      "updated_at": "2026-11-19T02:08:00.000000Z"
    }
  ],
  "first_page_url": "http://127.0.0.1:8000/api/tasks?page=1",
  "from": 1,
  "last_page": 3,
  "last_page_url": "http://127.0.0.1:8000/api/tasks?page=3",
  "links": [...],
  "next_page_url": "http://127.0.0.1:8000/api/tasks?page=2",
  "path": "http://127.0.0.1:8000/api/tasks",
  "per_page": 10,
  "prev_page_url": null,
  "to": 10,
  "total": 30
}
```

#### 2. List tasks with filters

```bash
curl "http://127.0.0.1:8000/api/tasks?status=pending&assignee=ar&page=1" \
  -H "Accept: application/json"
```

Response `200 OK` (truncated): same pagination shape, but `total` reflects the filtered count. With the demo data, `?status=pending&assignee=ar` returns the pending tasks of Armando and Carlos (María's tasks exist but the exact count depends on the rotation — the important part is that `total` is smaller than 30 and `data` only contains matching rows).

#### 3. Create a task

```bash
curl -X POST http://127.0.0.1:8000/api/tasks \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "title": "Write README",
    "description": "Document decisions",
    "status": "pending",
    "assignee": "Armando",
    "due_date": "2026-12-01"
  }'
```

Response `201 Created`:

```json
{
  "title": "Write README",
  "description": "Document decisions",
  "status": "pending",
  "assignee": "Armando",
  "due_date": "2026-12-01T00:00:00.000000Z",
  "updated_at": "2026-11-19T02:21:30.000000Z",
  "created_at": "2026-11-19T02:21:30.000000Z",
  "id": 31
}
```

#### 4. Show a single task

```bash
curl "http://127.0.0.1:8000/api/tasks/1" -H "Accept: application/json"
```

Response `200 OK`:

```json
{
  "id": 1,
  "title": "Design database schema",
  "description": null,
  "status": "pending",
  "assignee": "Armando",
  "due_date": "2026-11-20T00:00:00.000000Z",
  "created_at": "2026-11-19T02:08:00.000000Z",
  "updated_at": "2026-11-19T02:08:00.000000Z"
}
```

If the task does not exist, the endpoint returns `404 Not Found`:

```json
{
  "message": "No query results for model [App\\Models\\Task] 9999"
}
```

#### 5. Update a task (partial update with PATCH)

```bash
curl -X PATCH http://127.0.0.1:8000/api/tasks/1 \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"status": "in_progress"}'
```

Response `200 OK`:

```json
{
  "id": 1,
  "title": "Design database schema",
  "description": null,
  "status": "in_progress",
  "assignee": "Armando",
  "due_date": "2026-11-20T00:00:00.000000Z",
  "created_at": "2026-11-19T02:08:00.000000Z",
  "updated_at": "2026-11-19T02:23:55.000000Z"
}
```

Only `status` changed; the rest of the fields are untouched (thanks to `sometimes` in `UpdateTaskRequest`).

#### 6. Delete a task

```bash
curl -X DELETE http://127.0.0.1:8000/api/tasks/1 -H "Accept: application/json"
```

Response `204 No Content` with an **empty body**.

Deleting the same task again returns `404 Not Found`.

#### 7. Validation error (`422 Unprocessable Content`)

```bash
curl -X POST http://127.0.0.1:8000/api/tasks \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"title":"","status":"invalid","assignee":"","due_date":"not-a-date"}'
```

Response `422 Unprocessable Content`:

```json
{
  "message": "The title field is required. (and 3 more errors)",
  "errors": {
    "title": ["The title field is required."],
    "status": ["The selected status is invalid."],
    "assignee": ["The assignee field is required."],
    "due_date": ["The due date field is invalid."]
  }
}
```

> Note: the `Accept: application/json` header is not strictly required because a `ForceJsonResponse` middleware forces JSON on every request. It is included in the examples for clarity.


## 5. Technical decisions

**Laravel 12 over Symfony.** Familiarity with Laravel reduces friction and time-to-delivery. Laravel 12 also has security support until Feb 2027.

**SQLite over MySQL/PostgreSQL.** Allowed by the brief. Zero setup, no server to run, the DB is a single file. For a technical test it's the right trade-off: the reviewer runs `migrate` and it works.

**Removed default Laravel migrations (users, cache, jobs).** The scope is a CRUD API with no auth or queues. Keeping them would have added unused tables and noise to the schema.

**File-based session and cache.** Same reasoning: the API is stateless, no DB persistence needed for sessions.

**No Sanctum / no auth.** Not required by the brief. Adding it would have been over-engineering.

**FormRequests for validation.** Keeps controllers thin and validation declarative. `StoreTaskRequest` uses `required` on all fields except `description`; `UpdateTaskRequest` uses `sometimes` to allow partial updates (PATCH).

**`in:` rule for status instead of a PHP enum.** Three values, no need for an enum class. Explicit and readable.

**`assignee` filter uses partial match (`LIKE '%...%'`).** More useful for a search box than exact match. Documented behavior (see section 4). Case-insensitive by default in SQLite for ASCII.

**No `TaskResource` / `TaskCollection`.** For this scope, `response()->json($model)` is enough. If the client needed a custom response shape, API Resources would be the right call.

**`ForceJsonResponse` middleware.** Forces `Accept: application/json` on every request so Laravel always returns JSON, regardless of client headers. Prevents the reviewer from seeing HTML on validation errors.

**`orderBy('due_date')` on index.** Sensible default for a task manager: earliest deadline first.

**Demo seeder with 30 deterministic tasks.** Reproducible (no `fake()` randomness in the seeder): 10 per status, 6 per assignee, dates spanning 30 days. Makes filtering and pagination immediately visible without typing 30 curls.


## 6. Tests

Run:

```bash
php artisan test
```

Coverage (4 feature tests):

1. **`test_can_create_a_task`** — POST returns 201, response body is correct, DB has the row (`assertDatabaseHas`).
2. **`test_validation_fails_with_invalid_payload`** — POST returns 422 with errors for all invalid fields (`assertJsonValidationErrors`).
3. **`test_can_list_tasks_filtered_by_status`** — GET with `?status=done` returns only `done` tasks, pagination `total` is correct.
4. **`test_can_delete_a_task`** — DELETE returns 204, row is gone (`assertDatabaseMissing`).

Tests use `RefreshDatabase` (transaction per test, isolated), `Task::factory()` for data, and the real HTTP stack (no `WithoutMiddleware`, so `ForceJsonResponse` is exercised).


## 7. What I'd do with more time

- **Authentication** (Sanctum) and per-user task ownership.
- **Service layer** or actions for the business logic, so the controller is even thinner.
- **API Resources** for explicit response shaping (currently the model is serialized directly).
- **Exact-match filter** for `assignee` (`?assignee_exact=...`) alongside the current partial match.
- **`after_or_equal:today`** validation on `due_date`.
- **OpenAPI/Swagger** spec generation.
- **Docker Compose** with PHP-FPM + Nginx + MySQL for a production-like setup.
- **CI** (GitHub Actions) running `php artisan test` on push.
- **Integration tests against MySQL** to catch SQLite-specific behavior.
- **Soft deletes** and a `deleted_at` filter.
- **Rate limiting** on the API.

## 8. Use of AI

I used an AI assistant (Claude) as a pair-programmer during the test. Here's an honest breakdown.

**What I asked the AI for:**
- Phase-by-phase execution plan to fit the 3-hour budget.
- Exact commands for WSL2 (Ubuntu) + Laravel 12 + SQLite.
- Boilerplate for the migration, model, FormRequests, controller, and tests.
- Content for this README.

**What I accepted from the AI:**
- The phase breakdown and commit strategy.
- The FormRequest rules and the `sometimes` pattern for updates.
- The `when()` + `filled()` pattern for filters (avoids the `?status=` empty-string bug).
- The `ForceJsonResponse` middleware approach.
- The `TaskSeeder` / `DatabaseSeeder` split.
- The structure of this README.

**What I corrected or rejected:**
- The AI initially suggested Laravel 11; I switched to Laravel 12 due to security advisories affecting 11.
- The AI initially suggested keeping the default Laravel migrations; I chose to remove them (users/cache/jobs) because they're out of scope.
- The AI initially suggested `Yoagner` as a demo assignee; I changed it to `Armando` so the partial filter `?assignee=ar` returns multiple people (Armando + María + Carlos), demonstrating the `LIKE '%...%'` behavior.
- The AI predicted `?assignee=ar` would return 12 results; the real result was 18 (it missed `María`). I verified with `curl` and we documented the actual behavior instead of changing the code to match the wrong prediction.
- The AI suggested an inline seeder in `DatabaseSeeder`; I asked for a dedicated `TaskSeeder` and orchestration via `$this->call([...])`.
- I ran every smoke test myself (curl for all 5 endpoints, 422, 404, 204) and verified status codes and JSON bodies.

## 9. Time spent

| Phase | Estimated | Actual |
|---|---|---|
| 0 — Setup + cleanup (Laravel 12, SQLite, Git, remove default migrations) | 20 min | ~40 min |
| 1 — Task model, migration, API routes, empty controller | 25 min | ~15 min |
| 2 — FormRequest validation (Store + Update) | 20 min | ~10 min |
| 3 — CRUD controller + smoke tests | 30 min | ~20 min |
| 4 — Filters (`status`, `assignee`) + pagination | 20 min | ~10 min |
| 4.5 — `ForceJsonResponse` middleware + `orderBy('due_date')` | 10 min | ~8 min |
| 5 — Feature tests (4) + TaskFactory | 40 min | ~15 min |
| 5.5 — Demo seeder (`TaskSeeder` + `DatabaseSeeder`) + `assignee=ar` fix | 12 min | ~15 min |
| 6 — README (9 sections) | 25 min | ~20 min |
| **Total** | **~202 min** | **~153 min** |

**Total commits:** 9, one per phase (see `git log --oneline`).

Notes on the actual times:

- **Phase 0** took longer than estimated because I switched from Laravel 11 to Laravel 12 mid-setup (security advisories on 11), deleted the first project folder, and reconfigured Git and the remote.
- **Phase 4.5** was added mid-project after the Phase 3 smoke tests showed that `GET /api/tasks` returned `text/html` without an explicit `Accept` header. Adding the `ForceJsonResponse` middleware was a 1-file change that improved robustness.
- **Phase 5.5** was added after Phase 5 to give the reviewer demo data (30 tasks) so filters and pagination are visible without typing 30 `curl` commands. The `assignee=ar` behavior (returns 18 instead of 12) was discovered during this phase and documented instead of "fixed" — the partial `LIKE` is intentional.
- **The chat/AI was used throughout** as a pair-programmer (see section 8). The real time includes reading AI output, verifying it, running the commands, and correcting mistakes.

**Grand total: ~2.5 hours of active work**, within the 3-hour budget.

