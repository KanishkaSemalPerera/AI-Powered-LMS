# LearnHub API Reference

Base URL: `http://localhost:8000/api/v1`

## Conventions

**Authentication** — send the token from login/register on every protected request:

```
Authorization: Bearer <token>
Accept: application/json
```

**Response envelope** — every response, success or error:

```json
{ "success": true, "message": "Logged in successfully.", "data": { }, "errors": null, "meta": null }
```

| Status | Meaning | `errors` |
|---|---|---|
| 200 / 201 | Success / created | `null` |
| 401 | Missing, invalid or expired token | `null` |
| 403 | Wrong role, not the owner, or account deactivated (`errors.account`) | `null` or `{ "account": ["deactivated"] }` |
| 404 | Resource or route not found | `null` |
| 422 | Validation failed | `{ "field": ["message", ...] }` |
| 429 | Rate limit exceeded (`data.retry_after` seconds) | `null` |
| 500 | Server error (details hidden unless `APP_DEBUG=true`) | `null` |

List endpoints return pagination in `meta`: `current_page, last_page, per_page, total, from, to`.

**Rate limits**: 120 requests/min per user or IP on all routes; login 5/min per email+IP (20/min per IP); register 10/hour per IP; forgot/reset password 5/min per IP.

## Endpoints

Roles: **Public** = no token · **Auth** = any signed-in active user.

### System

| Method | URL | Role | Description |
|---|---|---|---|
| GET | `/health` | Public | API and database status (503 if the database is down) |

### Auth

| Method | URL | Role | Description |
|---|---|---|---|
| POST | `/auth/register` | Public | Create a student or instructor account; returns user + token |
| POST | `/auth/login` | Public | Log in; returns user + token |
| POST | `/auth/forgot-password` | Public | Email a reset link (same response whether or not the email exists) |
| POST | `/auth/reset-password` | Public | Set a new password with the emailed token; signs out all devices |
| POST | `/auth/logout` | Auth | Revoke the current token |
| GET | `/auth/me` | Auth | Current user |

### Profile

| Method | URL | Role | Description |
|---|---|---|---|
| PUT | `/profile` | Auth | Update name, email, headline, bio |
| POST | `/profile/avatar` | Auth | Upload avatar (`multipart/form-data`, field `avatar`: jpg/png/webp, ≤ 2 MB, 64–4000 px) |
| DELETE | `/profile/avatar` | Auth | Remove avatar |
| PUT | `/profile/password` | Auth | Change password (`current_password`, `password`, `password_confirmation`); signs out other devices |

### Catalogue

| Method | URL | Role | Description |
|---|---|---|---|
| GET | `/categories` | Public | Categories with published course counts (`per_page` ≤ 100) |
| GET | `/courses` | Public | Published courses. Query: `q`, `category` (slug), `level`, `price` (`free`/`paid`), `min_rating`, `featured`, `sort` (`newest`, `popular`, `rating`, `price_asc`, `price_desc`, `title`), `page`, `per_page` (≤ 50) |
| GET | `/courses/{slug}` | Public* | Course detail with curriculum outline (no lesson content). *Drafts return 404 except to the owner or an admin sending a Bearer token |

### Enrollment & payments

| Method | URL | Role | Description |
|---|---|---|---|
| POST | `/courses/{id}/enroll` | Student | Enroll in a free published course. 402 for paid courses, 409 if already enrolled |
| POST | `/courses/{id}/checkout` | Student | Start Stripe Checkout for a paid course → `{ checkout_url, session_id }`. 503 if Stripe isn't configured. Rate limited (10/min) |
| POST | `/checkout/confirm` | Student | `{ session_id }` from the success redirect. Confirms with Stripe and grants access if paid (idempotent, owner only) |
| GET | `/my/courses` | Student | My enrollments with course and progress. Query: `status` (`in_progress`/`completed`), `page`, `per_page` |
| GET | `/payments` | Auth | My payment history (abandoned checkouts excluded) |
| POST | `/webhooks/stripe` | Stripe | Signed with `Stripe-Signature` (400 if invalid or older than 5 min). Handles `checkout.session.completed`, `checkout.session.async_payment_succeeded/failed`, `checkout.session.expired`, `charge.refunded` (revokes access) |

`GET /courses/{slug}` with a student's token also returns `enrollment: { progress_percent, last_lesson_id, completed_at }` (or `null`).

Payment safety: the amount and currency charged by Stripe must match the recorded payment or it is marked `failed`; fulfilment locks the payment row so the webhook and the success page can run at the same time without double enrollment.

### Course player — enrolled students (owner/admin get read-only `preview` mode)

| Method | URL | Description |
|---|---|---|
| GET | `/learn/{slug}` | Curriculum with `is_completed` per lesson, `mode` (`student`/`preview`) and `progress`: `{ progress_percent, completed_lesson_ids, completed_lessons, total_lessons, completed_at, resume_lesson_id }` (null in preview). 403 if not enrolled |
| GET | `/learn/{slug}/lessons/{id}` | Full lesson (content, `playback_url`), `is_completed`, `navigation: { index, total, previous_lesson_id, next_lesson_id }`. Also saves the resume point |
| POST | `/learn/{slug}/lessons/{id}/complete` | Mark complete (idempotent) → progress plus `course_completed_now` (true once, the first time 100% is reached) |
| DELETE | `/learn/{slug}/lessons/{id}/complete` | Mark not complete. A completed course stays completed |
| GET | `/courses/{slug}/lessons/{id}/preview` | Public: a published course's free-preview lesson (404 otherwise) |

Progress is `floor(completed / total lessons × 100)`. Resume order: last visited lesson → first incomplete lesson → first lesson.

### Instructor — role `instructor`, own courses only

| Method | URL | Description |
|---|---|---|
| GET | `/instructor/courses` | My courses. Query: `q`, `status`, `page`, `per_page` |
| POST | `/instructor/courses` | Create a draft (only `title` required) |
| GET | `/instructor/courses/{id}` | One of my courses with curriculum |
| PUT | `/instructor/courses/{id}` | Update details (blocked while pending review; editing a rejected course returns it to draft) |
| DELETE | `/instructor/courses/{id}` | Delete a draft/rejected course with no students |
| POST | `/instructor/courses/{id}/thumbnail` | Upload thumbnail (`multipart/form-data`, field `thumbnail`: jpg/png/webp, ≤ 4 MB, ≥ 640×360) |
| POST | `/instructor/courses/{id}/submit` | Submit for review. 422 lists what is missing: `category_id`, `description` (≥ 100 chars), `lessons` (≥ 1) |

### Course builder — role `instructor`, own courses only (locked while pending review)

| Method | URL | Description |
|---|---|---|
| GET | `/instructor/courses/{id}/curriculum` | Sections with full lessons (content, `playback_url`) |
| PUT | `/instructor/courses/{id}/curriculum/order` | Save the complete order: `{ "sections": [{ "id": 3, "lessons": [7, 5] }, …] }`. Lessons may move between sections; every section/lesson of the course must appear exactly once (422 otherwise) |
| POST | `/instructor/courses/{id}/sections` | Add section `{ title }` (appended at the end) |
| PUT | `/instructor/sections/{id}` | Rename section `{ title }` |
| DELETE | `/instructor/sections/{id}` | Delete section with its lessons and uploaded videos |
| POST | `/instructor/sections/{id}/lessons` | Add lesson `{ title, type: video\|text, content?, video_url?, duration_minutes?, is_preview? }` |
| GET | `/instructor/lessons/{id}` | Full lesson |
| PUT | `/instructor/lessons/{id}` | Update lesson (a new `video_url` replaces an uploaded file; switching to `text` deletes the video) |
| DELETE | `/instructor/lessons/{id}` | Delete lesson and its video |
| POST | `/instructor/lessons/{id}/video` | Upload video (`multipart/form-data`: `video` mp4/webm/mov ≤ 500 MB, optional `duration_seconds`) |
| DELETE | `/instructor/lessons/{id}/video` | Remove the uploaded video |

### Lesson video playback

| Method | URL | Role | Description |
|---|---|---|---|
| GET | `/lessons/{id}/video?expires=…&signature=…` | Signed URL | Streams a private uploaded video (supports HTTP Range for seeking). The URL comes from a lesson's `playback_url`, expires after `LESSON_VIDEO_URL_TTL` minutes (default 240), and returns 403 if missing or tampered |

### Admin — role `admin`

| Method | URL | Description |
|---|---|---|
| GET | `/admin/courses` | All courses. Query: `status` (pending = oldest first), `q`, `page`, `per_page` |
| POST | `/admin/courses/{id}/approve` | Publish a pending course |
| POST | `/admin/courses/{id}/reject` | Reject a pending course: `{ "reason": "≥ 10 chars" }` |
| GET | `/admin/categories` | Categories with counts |
| POST | `/admin/categories` | Create: `{ name, description?, icon? }` |
| PUT | `/admin/categories/{id}` | Update (renaming regenerates the slug) |
| DELETE | `/admin/categories/{id}` | Delete; its courses become uncategorised |

## Request examples

**Register** `POST /auth/register`

```json
{
  "name": "Jane Learner",
  "email": "jane@example.com",
  "password": "secret123",
  "password_confirmation": "secret123",
  "role": "student"
}
```

`role` is `student` or `instructor`. Passwords need at least 8 characters with a letter and a number (production also requires mixed case and rejects breached passwords).

**Login** `POST /auth/login` → `200 OK` (register returns the same shape with `201 Created`)

```json
{
  "success": true,
  "message": "Logged in successfully.",
  "data": {
    "user": { "id": 5, "name": "Emma Wilson", "role": "student", "role_label": "Student", "email": "student1@lms.test", "avatar_url": null },
    "token": "12|Xyz...",
    "token_type": "Bearer",
    "expires_at": "2026-10-24T18:00:00+00:00"
  },
  "errors": null,
  "meta": null
}
```

## Postman

Import [`postman/LearnHub.postman_collection.json`](postman/LearnHub.postman_collection.json). Running **Auth → Login** stores the token in the collection variable `token`, which every protected request uses automatically.
