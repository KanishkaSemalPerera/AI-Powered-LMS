# LearnHub LMS — Architecture

## System overview

```mermaid
flowchart LR
    subgraph Browser
        SPA["React SPA (Vite :5173)<br/>React Router · React Query · Axios"]
    end
    subgraph Backend["Laravel 12 API (:8000) /api/v1"]
        MW["Middleware<br/>ForceJson · Sanctum · role · throttle"]
        C["Thin Controllers<br/>Form Requests · Policies · API Resources"]
        S["Services<br/>PaymentService · AiService<br/>CertificateService · ProgressService"]
        Q["Queue worker (database driver)<br/>emails · notifications"]
    end
    DB[("MariaDB 10.4<br/>lms_db")]
    FS[("Storage<br/>public disk / S3")]
    Stripe["Stripe Checkout"]
    Claude["Anthropic Claude API"]

    SPA -- "Bearer token, JSON" --> MW --> C --> S
    S --> DB
    S --> FS
    S -- "Checkout session" --> Stripe
    Stripe -- "Webhook (signed)" --> C
    S -- "server-side only" --> Claude
    S --> Q --> DB
```

Key rules:

- The Claude API key and Stripe secret live only in `backend/.env`. The browser never calls Claude or Stripe's secret APIs directly.
- Every response uses one envelope: `{ success, message, data, errors, meta }` (`App\Support\ApiResponse`).
- Ownership and enrollment are always checked on the server (Policies), never trusted from the client.

## Entity–relationship diagram

```mermaid
erDiagram
    USERS ||--o{ COURSES : "teaches (instructor_id)"
    CATEGORIES ||--o{ COURSES : contains
    COURSES ||--o{ SECTIONS : has
    SECTIONS ||--o{ LESSONS : has
    LESSONS ||--o| QUIZZES : "has one"
    QUIZZES ||--o{ QUESTIONS : has
    USERS ||--o{ ENROLLMENTS : enrolls
    COURSES ||--o{ ENROLLMENTS : "enrolled in"
    ENROLLMENTS ||--o{ LESSON_PROGRESS : tracks
    LESSONS ||--o{ LESSON_PROGRESS : "completed in"
    USERS ||--o{ QUIZ_ATTEMPTS : makes
    QUIZZES ||--o{ QUIZ_ATTEMPTS : "attempted in"
    USERS ||--o{ PAYMENTS : pays
    COURSES ||--o{ PAYMENTS : "paid for"
    PAYMENTS |o--o| ENROLLMENTS : creates
    USERS ||--o{ REVIEWS : writes
    COURSES ||--o{ REVIEWS : receives
    USERS ||--o{ CERTIFICATES : earns
    COURSES ||--o{ CERTIFICATES : awards
    USERS ||--o{ AI_REQUESTS : logs
    USERS ||--o{ NOTIFICATIONS : receives

    USERS {
        bigint id PK
        string name
        string email UK
        string password
        enum role "student|instructor|admin"
        string avatar_path
        text bio
        boolean is_active
    }
    CATEGORIES {
        bigint id PK
        string name
        string slug UK
        string icon
    }
    COURSES {
        bigint id PK
        bigint instructor_id FK
        bigint category_id FK
        string title
        string slug UK
        text description
        string thumbnail_path
        decimal price "0 = free"
        enum level "beginner|intermediate|advanced"
        string language
        enum status "draft|pending|published|rejected"
        text rejection_reason
        decimal rating_avg
        int rating_count
        timestamp published_at
    }
    SECTIONS {
        bigint id PK
        bigint course_id FK
        string title
        int position
    }
    LESSONS {
        bigint id PK
        bigint section_id FK
        string title
        enum type "video|text"
        string video_path
        string video_url
        longtext content
        int duration_seconds
        boolean is_preview
        int position
    }
    QUIZZES {
        bigint id PK
        bigint lesson_id FK "unique"
        string title
        int pass_mark "percent"
        enum status "draft|published"
        boolean ai_generated
    }
    QUESTIONS {
        bigint id PK
        bigint quiz_id FK
        text question
        json options
        int correct_option
        text explanation
        int position
    }
    ENROLLMENTS {
        bigint id PK
        bigint user_id FK
        bigint course_id FK
        bigint last_lesson_id FK "resume point"
        int progress_percent
        timestamp completed_at
    }
    LESSON_PROGRESS {
        bigint id PK
        bigint enrollment_id FK
        bigint lesson_id FK
        timestamp completed_at
    }
    QUIZ_ATTEMPTS {
        bigint id PK
        bigint user_id FK
        bigint quiz_id FK
        json answers
        int score
        int total
        boolean passed
    }
    PAYMENTS {
        bigint id PK
        bigint user_id FK
        bigint course_id FK
        string stripe_session_id UK
        string stripe_payment_intent
        decimal amount
        string currency
        enum status "pending|paid|failed|refunded"
        timestamp paid_at
    }
    REVIEWS {
        bigint id PK
        bigint user_id FK
        bigint course_id FK
        tinyint rating "1-5"
        text comment
    }
    CERTIFICATES {
        bigint id PK
        bigint user_id FK
        bigint course_id FK
        string code UK "verification code"
        timestamp issued_at
    }
    AI_REQUESTS {
        bigint id PK
        bigint user_id FK
        enum feature "quiz_generator|study_assistant|description_helper"
        string model
        int input_tokens
        int output_tokens
        enum status "success|error"
        int duration_ms
    }
    NOTIFICATIONS {
        uuid id PK
        string notifiable_type
        bigint notifiable_id
        string type
        json data
        timestamp read_at
    }
```

Unique constraints: `enrollments(user_id, course_id)`, `lesson_progress(enrollment_id, lesson_id)`, `reviews(user_id, course_id)`, `certificates(user_id, course_id)`, `quizzes(lesson_id)`.

## API modules (`/api/v1`)

| Module | Prefix | Access | Purpose |
|---|---|---|---|
| System | `/health` | Public | API and database status |
| Auth | `/auth/*` | Public / Auth | Register, login, logout, me, forgot/reset password |
| Profile | `/profile/*` | Auth | Update profile, avatar, change password |
| Catalog | `/categories`, `/courses` | Public | Browse, search, filter, sort, course detail |
| Enrollment | `/courses/{course}/enroll`, `/my/courses` | Student | Free enroll, my courses |
| Payments | `/payments/*`, `/webhooks/stripe` | Student / Stripe | Checkout session, history, webhook |
| Learning | `/learn/*` | Enrolled student | Course player, lesson content, mark complete, resume |
| Quizzes | `/quizzes/*` | Enrolled student | Take quiz, submit attempt, attempt history |
| Reviews | `/courses/{course}/reviews` | Public read / enrolled write | Ratings and comments |
| Certificates | `/certificates/*` | Student / Public verify | List, PDF download, verify by code |
| AI | `/ai/*` | Student / Instructor | Study assistant, quiz generator, description helper |
| Instructor | `/instructor/*` | Instructor | Course, section, lesson, quiz CRUD; analytics |
| Admin | `/admin/*` | Admin | Users and roles, course approvals, categories, stats |
| Notifications | `/notifications/*` | Auth | List, mark read |

The full endpoint table and the Postman collection are delivered with the phases that add each module.
