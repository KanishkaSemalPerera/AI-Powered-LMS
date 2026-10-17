<?php

/*
|--------------------------------------------------------------------------
| Demo course catalogue
|--------------------------------------------------------------------------
|
| Real lesson text (Markdown) so the AI study assistant and quiz generator
| have meaningful content to work with. Video lessons play a short public
| sample clip and keep their notes in "content".
|
| Lesson "duration" is in minutes. A lesson may carry one "quiz".
|
*/

// Every demo video lesson uses the same short CC0-licensed clip hosted by MDN.
// The name argument only labels which lesson the clip stands in for.
$video = fn (string $name) => 'https://interactive-examples.mdn.mozilla.net/media/cc0-videos/flower.mp4';

return [

    // ------------------------------------------------------------------ 1
    [
        'title' => 'Modern JavaScript from Scratch',
        'subtitle' => 'Learn the language of the web: variables, functions, arrays, objects and async code.',
        'category' => 'web-development',
        'instructor' => 'instructor1@lms.test',
        'level' => 'beginner',
        'price' => 0,
        'status' => 'published',
        'featured' => true,
        'description' => <<<'MD'
JavaScript runs in every browser and powers most interactive websites. This course takes you from zero to writing clean, modern JavaScript (ES2023+).

You will learn how values and variables work, how to write reusable functions, how to work with arrays and objects, and how asynchronous code with promises and async/await fits together. Every lesson ends with a small exercise you can run in your browser console.
MD,
        'what_you_will_learn' => [
            'Declare variables with let and const and understand scope',
            'Write arrow functions and use callbacks confidently',
            'Transform data with map, filter and reduce',
            'Fetch data from an API with async/await',
        ],
        'requirements' => ['A computer with a modern browser', 'No programming experience needed'],
        'sections' => [
            [
                'title' => 'JavaScript Basics',
                'lessons' => [
                    [
                        'title' => 'Welcome and setting up your editor',
                        'type' => 'video',
                        'video' => $video('ForBiggerBlazes'),
                        'duration' => 6,
                        'preview' => true,
                        'content' => <<<'MD'
## Lesson notes

In this lesson we install **Visual Studio Code** and open the browser **DevTools console** (F12 or Ctrl+Shift+J in Chrome).

The console is a REPL: type an expression, press Enter, and see the result immediately. Try:

```js
2 + 3
"Hello".toUpperCase()
```

Throughout the course, write your code in a file called `app.js`, link it from `index.html` with `<script src="app.js" defer></script>`, and use `console.log()` to inspect values.
MD,
                    ],
                    [
                        'title' => 'Variables, types and let vs const',
                        'type' => 'text',
                        'duration' => 12,
                        'content' => <<<'MD'
## Variables

Modern JavaScript has two ways to declare variables:

- `const` creates a binding that **cannot be reassigned**. Use it by default.
- `let` creates a binding that **can be reassigned**, for counters or values that change.

Avoid the old `var` keyword: it is function-scoped and hoisted, which causes confusing bugs. Both `let` and `const` are **block-scoped**, meaning they only exist inside the nearest `{ }`.

## Primitive types

JavaScript has seven primitive types: `string`, `number`, `bigint`, `boolean`, `undefined`, `null` and `symbol`. Use `typeof` to check a value's type: `typeof 42` returns `"number"`.

Note that `const` does not make objects immutable. `const user = { name: "Ana" }` can still have `user.name` changed; you just cannot point `user` at a different object.
MD,
                    ],
                    [
                        'title' => 'Functions and arrow functions',
                        'type' => 'text',
                        'duration' => 15,
                        'content' => <<<'MD'
## Functions

A function is a reusable block of code. A **function declaration** is hoisted, so it can be called before it appears in the file:

```js
function add(a, b) {
  return a + b
}
```

An **arrow function** is a shorter syntax, commonly used for callbacks:

```js
const add = (a, b) => a + b
```

When an arrow function body is a single expression, the result is returned automatically (an *implicit return*). Arrow functions do not have their own `this`; they use the `this` of the surrounding scope, which makes them ideal inside methods and event handlers.

**Default parameters** give arguments fallback values: `function greet(name = "friend") { ... }`.
MD,
                        'quiz' => [
                            'title' => 'JavaScript Basics Check',
                            'pass_mark' => 70,
                            'questions' => [
                                [
                                    'question' => 'Which keyword should you use by default for a variable that is never reassigned?',
                                    'options' => ['var', 'let', 'const', 'static'],
                                    'correct' => 2,
                                    'explanation' => 'const prevents reassignment and signals intent, so it is the recommended default.',
                                ],
                                [
                                    'question' => 'What is the scope of a variable declared with let?',
                                    'options' => ['Global scope', 'Function scope', 'Block scope', 'Module scope only'],
                                    'correct' => 2,
                                    'explanation' => 'let and const are block-scoped: they exist only inside the nearest pair of braces.',
                                ],
                                [
                                    'question' => 'What does the arrow function `const double = n => n * 2` return for double(4)?',
                                    'options' => ['undefined', '8', '"44"', 'An error'],
                                    'correct' => 1,
                                    'explanation' => 'A single-expression arrow function implicitly returns its result: 4 * 2 = 8.',
                                ],
                                [
                                    'question' => 'Which statement about `const user = { name: "Ana" }` is true?',
                                    'options' => [
                                        'user.name can never change',
                                        'user can be reassigned to another object',
                                        'user.name can be changed, but user cannot be reassigned',
                                        'const objects are frozen automatically',
                                    ],
                                    'correct' => 2,
                                    'explanation' => 'const only protects the binding. The object it points to is still mutable.',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'title' => 'Working with Data',
                'lessons' => [
                    [
                        'title' => 'Arrays and array methods',
                        'type' => 'video',
                        'video' => $video('ForBiggerEscapes'),
                        'duration' => 14,
                        'content' => <<<'MD'
## Lesson notes

Arrays store ordered lists: `const scores = [72, 88, 95]`.

The three most useful array methods return **new arrays or values** without changing the original:

- `map(fn)` transforms every item: `scores.map(s => s + 5)`
- `filter(fn)` keeps items that pass a test: `scores.filter(s => s >= 80)`
- `reduce(fn, start)` combines items into one value: `scores.reduce((sum, s) => sum + s, 0)`

Other handy methods: `find`, `some`, `every`, `includes`, and the spread syntax `[...a, ...b]` for copying and merging arrays.
MD,
                    ],
                    [
                        'title' => 'Objects and destructuring',
                        'type' => 'text',
                        'duration' => 12,
                        'content' => <<<'MD'
## Objects

Objects group related data as key/value pairs:

```js
const course = { title: "JavaScript", lessons: 6, free: true }
```

Read properties with dot notation (`course.title`) or brackets (`course["title"]`), which is useful when the key is in a variable.

## Destructuring

Destructuring pulls values out of objects and arrays into variables:

```js
const { title, lessons } = course
const [first, second] = [10, 20]
```

You can rename and give defaults: `const { title: name = "Untitled" } = course`.

The **spread operator** copies objects: `const updated = { ...course, lessons: 7 }` creates a new object without mutating the original — a pattern used everywhere in React.
MD,
                    ],
                    [
                        'title' => 'Async JavaScript: promises and async/await',
                        'type' => 'text',
                        'duration' => 18,
                        'content' => <<<'MD'
## Why async?

Network requests take time. JavaScript does not stop and wait; instead, slow operations return a **Promise** — an object representing a value that will arrive later.

A promise is *pending*, then either *fulfilled* (success) or *rejected* (error).

## async/await

`async` functions let you write asynchronous code that reads top to bottom:

```js
async function loadUser(id) {
  try {
    const response = await fetch(`/api/users/${id}`)
    if (!response.ok) throw new Error("Request failed")
    return await response.json()
  } catch (error) {
    console.error(error)
  }
}
```

`await` pauses only the current async function until the promise settles. Always handle errors with `try/catch`, and remember that `fetch` does **not** reject on HTTP errors like 404 — check `response.ok` yourself.

Use `Promise.all([...])` to run independent requests in parallel.
MD,
                    ],
                ],
            ],
        ],
    ],

    // ------------------------------------------------------------------ 2
    [
        'title' => 'React 19 Fundamentals: Build Real Apps',
        'subtitle' => 'Components, state, effects and data fetching — the skills you need to ship React apps.',
        'category' => 'web-development',
        'instructor' => 'instructor1@lms.test',
        'level' => 'intermediate',
        'price' => 29.99,
        'status' => 'published',
        'featured' => true,
        'description' => <<<'MD'
React is the most popular library for building user interfaces. In this course you will build a task manager app step by step while learning how React really works.

We cover JSX, components and props, state with useState, side effects with useEffect, lists and keys, forms, and fetching server data with TanStack Query.
MD,
        'what_you_will_learn' => [
            'Think in components and pass data with props',
            'Manage state and understand re-rendering',
            'Handle forms and user input',
            'Fetch and cache server data with React Query',
        ],
        'requirements' => ['Comfortable with modern JavaScript (ES6+)', 'Node.js installed'],
        'sections' => [
            [
                'title' => 'Core Concepts',
                'lessons' => [
                    [
                        'title' => 'Components and JSX',
                        'type' => 'video',
                        'video' => $video('ForBiggerFun'),
                        'duration' => 10,
                        'preview' => true,
                        'content' => <<<'MD'
## Lesson notes

A React **component** is a JavaScript function that returns UI described with **JSX**:

```jsx
function Welcome({ name }) {
  return <h1>Hello, {name}!</h1>
}
```

Rules of JSX: return a single root element (or a fragment `<>...</>`), close every tag, use `className` instead of `class`, and put JavaScript expressions inside `{ }`.

Component names must start with a capital letter so React can tell them apart from HTML tags.
MD,
                    ],
                    [
                        'title' => 'Props and composition',
                        'type' => 'text',
                        'duration' => 11,
                        'content' => <<<'MD'
## Props

Props are the inputs to a component. They flow **one way**: from parent to child. A component must never modify its own props.

```jsx
<CourseCard title="React" price={29.99} />
```

## Composition

The special `children` prop lets components wrap other content, which is how layouts and cards are built:

```jsx
function Card({ children }) {
  return <div className="card">{children}</div>
}
```

Prefer composition over deeply nested configuration props. Small components that each do one job are easier to test and reuse.
MD,
                    ],
                    [
                        'title' => 'State with useState',
                        'type' => 'text',
                        'duration' => 16,
                        'content' => <<<'MD'
## State

State is data that changes over time and affects what the component renders.

```jsx
const [count, setCount] = useState(0)
```

Calling the setter **schedules a re-render** with the new value. State updates are not applied immediately inside the same function call.

When the next value depends on the previous one, use the **updater form**: `setCount(c => c + 1)`. This is safe even when several updates are batched together.

Never mutate state directly. For objects and arrays, create a new copy: `setTasks([...tasks, newTask])`.

**Hooks rules:** call hooks only at the top level of a component (not inside loops or conditions) and only from React functions.
MD,
                        'quiz' => [
                            'title' => 'React Core Concepts',
                            'pass_mark' => 75,
                            'questions' => [
                                [
                                    'question' => 'In which direction do props flow in React?',
                                    'options' => ['Child to parent', 'Parent to child', 'Both directions', 'Through global variables'],
                                    'correct' => 1,
                                    'explanation' => 'Props flow one way, from parent to child. Children communicate upward by calling functions passed as props.',
                                ],
                                [
                                    'question' => 'Which attribute replaces the HTML "class" attribute in JSX?',
                                    'options' => ['class', 'cssClass', 'className', 'styleName'],
                                    'correct' => 2,
                                    'explanation' => '"class" is a reserved word in JavaScript, so JSX uses className.',
                                ],
                                [
                                    'question' => 'When should you use the updater form setCount(c => c + 1)?',
                                    'options' => [
                                        'Never, it is deprecated',
                                        'When the next state depends on the previous state',
                                        'Only inside useEffect',
                                        'Only for string state',
                                    ],
                                    'correct' => 1,
                                    'explanation' => 'The updater form always receives the latest state, which avoids bugs when updates are batched.',
                                ],
                                [
                                    'question' => 'How should you add an item to an array held in state?',
                                    'options' => [
                                        'tasks.push(item)',
                                        'setTasks(tasks.push(item))',
                                        'setTasks([...tasks, item])',
                                        'tasks[tasks.length] = item',
                                    ],
                                    'correct' => 2,
                                    'explanation' => 'State must be treated as immutable. Create a new array so React detects the change.',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'title' => 'Effects and Data',
                'lessons' => [
                    [
                        'title' => 'Side effects with useEffect',
                        'type' => 'video',
                        'video' => $video('ForBiggerJoyrides'),
                        'duration' => 15,
                        'content' => <<<'MD'
## Lesson notes

`useEffect` synchronises a component with something **outside React**: timers, subscriptions, the document title, or browser APIs.

```jsx
useEffect(() => {
  const id = setInterval(tick, 1000)
  return () => clearInterval(id) // cleanup
}, [])
```

The **dependency array** controls when the effect re-runs. Include every value from the component that the effect uses.

You probably *don't* need an effect to transform data for rendering — compute it during render instead. And for fetching server data, prefer a library like React Query over hand-written effects.
MD,
                    ],
                    [
                        'title' => 'Lists, keys and forms',
                        'type' => 'text',
                        'duration' => 13,
                        'content' => <<<'MD'
## Rendering lists

Use `map` to turn data into elements, and give each item a stable, unique `key`:

```jsx
{tasks.map(task => <TaskItem key={task.id} task={task} />)}
```

Keys help React match items between renders. Avoid using the array index as a key when items can be reordered or removed.

## Controlled forms

In a controlled input, React state is the single source of truth:

```jsx
<input value={title} onChange={e => setTitle(e.target.value)} />
```

For larger forms, libraries like **React Hook Form** with **Zod** validation reduce boilerplate and re-renders.
MD,
                    ],
                    [
                        'title' => 'Fetching data with TanStack Query',
                        'type' => 'text',
                        'duration' => 17,
                        'content' => <<<'MD'
## Server state is different

Data from an API is *server state*: it can become stale, needs caching, and has loading and error states. TanStack Query handles all of this.

```jsx
const { data, isPending, error } = useQuery({
  queryKey: ['courses', filters],
  queryFn: () => fetchCourses(filters),
})
```

The **query key** identifies cached data; when it changes, the query refetches.

For creating or updating data use `useMutation`, then call `queryClient.invalidateQueries({ queryKey: ['courses'] })` so lists refresh automatically.

Benefits: request de-duplication, background refetching, caching, and retries — with far less code than manual `useEffect` fetching.
MD,
                    ],
                ],
            ],
        ],
    ],

    // ------------------------------------------------------------------ 3
    [
        'title' => 'Building REST APIs with Laravel',
        'subtitle' => 'Design secure, well-structured JSON APIs with routing, validation, resources and Sanctum.',
        'category' => 'web-development',
        'instructor' => 'instructor1@lms.test',
        'level' => 'intermediate',
        'price' => 39.99,
        'status' => 'pending',
        'featured' => false,
        'description' => <<<'MD'
Learn to build production-ready REST APIs with Laravel. We cover resource routing, Eloquent relationships, Form Request validation, API Resources for consistent JSON, and token authentication with Sanctum.
MD,
        'what_you_will_learn' => [
            'Design RESTful routes and controllers',
            'Validate input with Form Requests',
            'Shape responses with API Resources',
            'Secure endpoints with Sanctum and Policies',
        ],
        'requirements' => ['Basic PHP knowledge', 'PHP 8.2 and Composer installed'],
        'sections' => [
            [
                'title' => 'API Foundations',
                'lessons' => [
                    [
                        'title' => 'REST principles and routing',
                        'type' => 'video',
                        'video' => $video('ForBiggerMeltdowns'),
                        'duration' => 12,
                        'preview' => true,
                        'content' => <<<'MD'
## Lesson notes

REST APIs expose **resources** (courses, users) at URLs and use HTTP methods for actions: `GET` reads, `POST` creates, `PUT/PATCH` updates and `DELETE` removes.

In Laravel, `Route::apiResource('courses', CourseController::class)` registers all five standard routes. Version your API with a prefix like `/api/v1` so you can evolve it without breaking clients.

Return proper status codes: 200 OK, 201 Created, 204 No Content, 401 Unauthenticated, 403 Forbidden, 404 Not Found, 422 Validation failed.
MD,
                    ],
                    [
                        'title' => 'Validation with Form Requests',
                        'type' => 'text',
                        'duration' => 14,
                        'content' => <<<'MD'
## Form Requests

A Form Request class moves validation and authorization out of the controller:

```php
class StoreCourseRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'price' => ['required', 'numeric', 'min:0'],
        ];
    }
}
```

Type-hint it in the controller method and Laravel validates automatically, returning a **422** response with field errors when validation fails. Use `$request->validated()` to get only the validated data — never pass `$request->all()` straight to `create()`.
MD,
                    ],
                    [
                        'title' => 'API Resources for consistent JSON',
                        'type' => 'text',
                        'duration' => 12,
                        'content' => <<<'MD'
## API Resources

Resources transform models into JSON and act as a contract between your API and its clients:

```php
class CourseResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'instructor' => new UserResource($this->whenLoaded('instructor')),
        ];
    }
}
```

`whenLoaded` includes relationships only when they were eager-loaded, preventing N+1 queries. Resources also hide sensitive columns by default because you list fields explicitly.
MD,
                        'quiz' => [
                            'title' => 'API Foundations Quiz',
                            'pass_mark' => 70,
                            'questions' => [
                                [
                                    'question' => 'Which status code should a successful create (POST) request return?',
                                    'options' => ['200', '201', '204', '302'],
                                    'correct' => 1,
                                    'explanation' => '201 Created indicates a new resource was created.',
                                ],
                                [
                                    'question' => 'What status code does Laravel return when Form Request validation fails on an API route?',
                                    'options' => ['400', '403', '422', '500'],
                                    'correct' => 2,
                                    'explanation' => 'Laravel returns 422 Unprocessable Content with the field errors.',
                                ],
                                [
                                    'question' => 'Why use $request->validated() instead of $request->all()?',
                                    'options' => [
                                        'It is faster',
                                        'It only returns fields that passed validation, preventing mass-assignment of unexpected input',
                                        'It converts values to integers',
                                        'It is required for JSON requests',
                                    ],
                                    'correct' => 1,
                                    'explanation' => 'validated() limits data to the rules you defined, so extra fields cannot sneak into your models.',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'title' => 'Authentication and Authorization',
                'lessons' => [
                    [
                        'title' => 'Token authentication with Sanctum',
                        'type' => 'text',
                        'duration' => 15,
                        'content' => <<<'MD'
## Sanctum tokens

Sanctum issues **personal access tokens** for SPAs and mobile apps. After a successful login, call `$user->createToken('spa')->plainTextToken` and return it to the client.

The client sends it on every request: `Authorization: Bearer <token>`. Protect routes with the `auth:sanctum` middleware.

On logout, delete the current token: `$request->user()->currentAccessToken()->delete()`. Tokens are stored hashed in the `personal_access_tokens` table, and you can set an expiry in `config/sanctum.php`.
MD,
                    ],
                    [
                        'title' => 'Policies and ownership checks',
                        'type' => 'text',
                        'duration' => 13,
                        'content' => <<<'MD'
## Policies

Authentication answers *who are you?* Authorization answers *what may you do?*

A Policy groups authorization rules for a model:

```php
public function update(User $user, Course $course): bool
{
    return $user->id === $course->instructor_id;
}
```

In a controller, call `$this->authorize('update', $course)` or use `Gate::authorize(...)`. Unauthorized requests receive **403 Forbidden**.

Never rely on the frontend hiding a button — always enforce ownership on the server.
MD,
                    ],
                    [
                        'title' => 'Rate limiting and error handling',
                        'type' => 'text',
                        'duration' => 10,
                        'content' => <<<'MD'
## Rate limiting

Define limiters in a service provider with `RateLimiter::for('login', fn ($request) => Limit::perMinute(5)->by($request->ip()))`, then apply them with the `throttle:login` middleware. Exceeding the limit returns **429 Too Many Requests**.

## Consistent errors

Register exception renderers in `bootstrap/app.php` so every error — validation, 404, 403, 500 — uses the same JSON shape. Hide internal error messages in production by checking `config('app.debug')`.
MD,
                    ],
                ],
            ],
        ],
    ],

    // ------------------------------------------------------------------ 4
    [
        'title' => 'Python for Data Analysis',
        'subtitle' => 'Clean, analyse and visualise real datasets with Python, pandas and Matplotlib.',
        'category' => 'data-science',
        'instructor' => 'instructor2@lms.test',
        'level' => 'beginner',
        'price' => 24.99,
        'status' => 'published',
        'featured' => true,
        'description' => <<<'MD'
Turn raw data into insight. You will learn enough Python to be productive, then use pandas to load, clean, filter, group and summarise data, and Matplotlib to chart the results.
MD,
        'what_you_will_learn' => [
            'Use Python lists, dictionaries and functions',
            'Load CSV files into pandas DataFrames',
            'Clean missing values and filter rows',
            'Group, aggregate and visualise data',
        ],
        'requirements' => ['No prior programming experience', 'Python 3.12 or Jupyter installed'],
        'sections' => [
            [
                'title' => 'Python Essentials',
                'lessons' => [
                    [
                        'title' => 'Why Python for data?',
                        'type' => 'video',
                        'video' => $video('ForBiggerBlazes'),
                        'duration' => 7,
                        'preview' => true,
                        'content' => <<<'MD'
## Lesson notes

Python is the most popular language for data work because it is readable and has an outstanding ecosystem: **pandas** for tables, **NumPy** for numbers, **Matplotlib/Seaborn** for charts and **scikit-learn** for machine learning.

We will use **Jupyter notebooks**, where code, output and notes live together. Run a cell with Shift+Enter.
MD,
                    ],
                    [
                        'title' => 'Lists, dictionaries and loops',
                        'type' => 'text',
                        'duration' => 14,
                        'content' => <<<'MD'
## Core data structures

- A **list** is an ordered, changeable collection: `prices = [9.99, 14.50, 3.25]`
- A **dictionary** maps keys to values: `product = {"name": "Tea", "price": 3.25}`

Loop over a list with `for price in prices:` and over a dictionary with `for key, value in product.items():`.

**List comprehensions** build lists concisely:

```python
discounted = [p * 0.9 for p in prices if p > 5]
```

Python uses **indentation** (four spaces) to define blocks instead of braces.
MD,
                    ],
                    [
                        'title' => 'Functions and modules',
                        'type' => 'text',
                        'duration' => 11,
                        'content' => <<<'MD'
## Functions

Define functions with `def`:

```python
def average(values):
    return sum(values) / len(values) if values else 0
```

Give parameters default values (`def greet(name="friend")`) and document functions with a docstring.

## Modules

Import libraries with `import pandas as pd` or `from math import sqrt`. The `as` alias is a convention: `pd` for pandas and `np` for NumPy are universal in data science code.
MD,
                        'quiz' => [
                            'title' => 'Python Essentials Quiz',
                            'pass_mark' => 70,
                            'questions' => [
                                [
                                    'question' => 'Which Python structure maps keys to values?',
                                    'options' => ['list', 'tuple', 'dictionary', 'set'],
                                    'correct' => 2,
                                    'explanation' => 'Dictionaries store key/value pairs such as {"name": "Tea"}.',
                                ],
                                [
                                    'question' => 'How does Python define a code block, such as a loop body?',
                                    'options' => ['Curly braces', 'Indentation', 'BEGIN/END keywords', 'Semicolons'],
                                    'correct' => 1,
                                    'explanation' => 'Python uses indentation, conventionally four spaces.',
                                ],
                                [
                                    'question' => 'What is the conventional alias when importing pandas?',
                                    'options' => ['pa', 'pn', 'pd', 'pds'],
                                    'correct' => 2,
                                    'explanation' => '"import pandas as pd" is the universal convention.',
                                ],
                                [
                                    'question' => 'What does [p * 2 for p in [1, 2, 3]] produce?',
                                    'options' => ['[1, 2, 3, 1, 2, 3]', '[2, 4, 6]', '12', 'An error'],
                                    'correct' => 1,
                                    'explanation' => 'The list comprehension doubles each element.',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'title' => 'Analysis with pandas',
                'lessons' => [
                    [
                        'title' => 'DataFrames: loading and inspecting data',
                        'type' => 'video',
                        'video' => $video('ForBiggerEscapes'),
                        'duration' => 15,
                        'content' => <<<'MD'
## Lesson notes

A **DataFrame** is a table with labelled columns. Load a CSV file:

```python
df = pd.read_csv("sales.csv")
```

Inspect it with `df.head()` (first rows), `df.info()` (column types and missing values), `df.describe()` (summary statistics) and `df.shape` (rows, columns).

Select a column with `df["region"]` and several with `df[["region", "revenue"]]`.
MD,
                    ],
                    [
                        'title' => 'Cleaning and filtering data',
                        'type' => 'text',
                        'duration' => 16,
                        'content' => <<<'MD'
## Missing values

Real data is messy. Count missing values with `df.isna().sum()`. Then either drop them (`df.dropna(subset=["revenue"])`) or fill them (`df["discount"].fillna(0)`).

## Filtering rows

Use boolean masks:

```python
big_orders = df[df["revenue"] > 1000]
emea = df[(df["region"] == "EMEA") & (df["year"] == 2025)]
```

Combine conditions with `&` (and) and `|` (or), wrapping each in parentheses. Fix types with `pd.to_datetime(df["date"])` and `df["qty"].astype(int)`.
MD,
                    ],
                    [
                        'title' => 'Grouping and visualising results',
                        'type' => 'text',
                        'duration' => 15,
                        'content' => <<<'MD'
## groupby

`groupby` splits data into groups, applies a calculation, and combines results:

```python
revenue_by_region = df.groupby("region")["revenue"].sum().sort_values(ascending=False)
```

Use `.agg(["sum", "mean", "count"])` for several statistics at once.

## Charts

pandas plots with Matplotlib under the hood:

```python
revenue_by_region.plot(kind="bar", title="Revenue by region")
```

Choose the chart for the question: bars to compare categories, lines for trends over time, histograms for distributions.
MD,
                    ],
                ],
            ],
        ],
    ],

    // ------------------------------------------------------------------ 5
    [
        'title' => 'Machine Learning Foundations',
        'subtitle' => 'Understand how models learn, how to evaluate them, and how to avoid common pitfalls.',
        'category' => 'artificial-intelligence',
        'instructor' => 'instructor2@lms.test',
        'level' => 'intermediate',
        'price' => 49.99,
        'status' => 'published',
        'featured' => false,
        'description' => <<<'MD'
A practical introduction to machine learning with scikit-learn. Learn the difference between supervised and unsupervised learning, train regression and classification models, and evaluate them honestly with train/test splits and the right metrics.
MD,
        'what_you_will_learn' => [
            'Explain supervised vs unsupervised learning',
            'Train regression and classification models',
            'Split data and avoid overfitting',
            'Choose metrics like accuracy, precision and recall',
        ],
        'requirements' => ['Python and pandas basics', 'High-school level maths'],
        'sections' => [
            [
                'title' => 'How Machines Learn',
                'lessons' => [
                    [
                        'title' => 'What is machine learning?',
                        'type' => 'video',
                        'video' => $video('ForBiggerFun'),
                        'duration' => 9,
                        'preview' => true,
                        'content' => <<<'MD'
## Lesson notes

Machine learning builds models that learn patterns from **data** instead of following hand-written rules.

- **Supervised learning** learns from labelled examples (inputs with known answers). Examples: predicting house prices (*regression*) or detecting spam (*classification*).
- **Unsupervised learning** finds structure in unlabelled data, such as grouping customers into segments (*clustering*).

The inputs are called **features** and the value to predict is the **target** or label.
MD,
                    ],
                    [
                        'title' => 'Linear regression',
                        'type' => 'text',
                        'duration' => 15,
                        'content' => <<<'MD'
## Linear regression

Linear regression predicts a number by fitting a straight line (or hyperplane) through the data: `price = w1 * size + w2 * rooms + b`.

Training finds the weights that minimise the **mean squared error** between predictions and true values.

```python
from sklearn.linear_model import LinearRegression
model = LinearRegression().fit(X_train, y_train)
predictions = model.predict(X_test)
```

Evaluate regression with **MAE** (mean absolute error, in the target's units) or **R²** (share of variance explained; 1.0 is perfect).
MD,
                    ],
                    [
                        'title' => 'Classification and logistic regression',
                        'type' => 'text',
                        'duration' => 14,
                        'content' => <<<'MD'
## Classification

Classification predicts a category, like *spam* or *not spam*. Despite its name, **logistic regression** is a classification algorithm: it outputs a probability between 0 and 1, and a threshold (often 0.5) turns that into a class.

Other popular classifiers include decision trees, random forests and gradient boosting.

All scikit-learn models share the same API: `fit(X, y)` to train, `predict(X)` for classes and `predict_proba(X)` for probabilities.
MD,
                        'quiz' => [
                            'title' => 'How Machines Learn — Quiz',
                            'pass_mark' => 75,
                            'questions' => [
                                [
                                    'question' => 'Predicting house prices from size and location is an example of…',
                                    'options' => ['Clustering', 'Regression', 'Classification', 'Dimensionality reduction'],
                                    'correct' => 1,
                                    'explanation' => 'Predicting a continuous number is regression.',
                                ],
                                [
                                    'question' => 'Grouping customers into segments without labels is…',
                                    'options' => ['Supervised learning', 'Reinforcement learning', 'Unsupervised learning', 'Linear regression'],
                                    'correct' => 2,
                                    'explanation' => 'Without labelled answers, finding groups is unsupervised learning (clustering).',
                                ],
                                [
                                    'question' => 'What does logistic regression output before a threshold is applied?',
                                    'options' => ['A category name', 'A probability between 0 and 1', 'A cluster ID', 'A straight line'],
                                    'correct' => 1,
                                    'explanation' => 'Logistic regression outputs a probability, which a threshold converts into a class.',
                                ],
                                [
                                    'question' => 'Which scikit-learn method trains a model?',
                                    'options' => ['train()', 'learn()', 'fit()', 'predict()'],
                                    'correct' => 2,
                                    'explanation' => 'All scikit-learn estimators are trained with fit(X, y).',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'title' => 'Evaluating Models',
                'lessons' => [
                    [
                        'title' => 'Train/test split and overfitting',
                        'type' => 'video',
                        'video' => $video('ForBiggerJoyrides'),
                        'duration' => 12,
                        'content' => <<<'MD'
## Lesson notes

A model must be evaluated on data it has **never seen**. Split your dataset:

```python
from sklearn.model_selection import train_test_split
X_train, X_test, y_train, y_test = train_test_split(X, y, test_size=0.2, random_state=42)
```

**Overfitting** happens when a model memorises the training data — great training score, poor test score. **Underfitting** is the opposite: the model is too simple to capture the pattern.

Cross-validation (`cross_val_score`) gives a more reliable estimate by averaging several splits.
MD,
                    ],
                    [
                        'title' => 'Accuracy, precision and recall',
                        'type' => 'text',
                        'duration' => 15,
                        'content' => <<<'MD'
## Beyond accuracy

**Accuracy** is the share of correct predictions, but it misleads on imbalanced data: if 99% of emails are not spam, a model that always says "not spam" is 99% accurate and useless.

- **Precision**: of the items predicted positive, how many were actually positive? (Avoid false alarms.)
- **Recall**: of the actual positives, how many did we find? (Avoid misses.)
- **F1 score**: the harmonic mean of precision and recall.

A **confusion matrix** shows true/false positives and negatives, making these trade-offs visible. For medical screening, recall matters most; for spam filtering, precision often matters more.
MD,
                    ],
                    [
                        'title' => 'Feature engineering basics',
                        'type' => 'text',
                        'duration' => 12,
                        'content' => <<<'MD'
## Features matter most

Better features often beat fancier algorithms.

- **Scale** numeric features (`StandardScaler`) for models sensitive to magnitude, like logistic regression.
- **Encode** categories as numbers with one-hot encoding (`OneHotEncoder`).
- **Create** new features from domain knowledge, e.g. price per square metre.

Wrap preprocessing and the model in a `Pipeline` so the exact same transformations are applied in training and prediction — and so test data never leaks into the fitting step.
MD,
                    ],
                ],
            ],
        ],
    ],

    // ------------------------------------------------------------------ 6
    [
        'title' => 'Prompt Engineering for Everyday Work',
        'subtitle' => 'Get reliable, useful results from AI assistants like Claude with clear, structured prompts.',
        'category' => 'artificial-intelligence',
        'instructor' => 'instructor2@lms.test',
        'level' => 'beginner',
        'price' => 0,
        'status' => 'published',
        'featured' => true,
        'description' => <<<'MD'
AI assistants are powerful, but the quality of their output depends on the quality of your instructions. Learn practical techniques — context, examples, structure and iteration — that work across writing, research, coding and analysis tasks.
MD,
        'what_you_will_learn' => [
            'Write clear prompts with context and constraints',
            'Use examples and structure to shape output',
            'Break complex tasks into steps',
            'Review AI output critically and iterate',
        ],
        'requirements' => ['Access to any AI assistant', 'No technical background needed'],
        'sections' => [
            [
                'title' => 'Prompting Fundamentals',
                'lessons' => [
                    [
                        'title' => 'How large language models work (in plain English)',
                        'type' => 'video',
                        'video' => $video('ForBiggerMeltdowns'),
                        'duration' => 8,
                        'preview' => true,
                        'content' => <<<'MD'
## Lesson notes

A large language model (LLM) is trained on vast amounts of text to predict likely continuations. That makes it excellent at language tasks, but it does not "look things up" unless given tools or documents.

Key takeaways:

- The model only knows what is in its training data and **what you put in the prompt**.
- It can sound confident while being wrong, so verify important facts.
- Clear instructions and relevant context dramatically improve results.
MD,
                    ],
                    [
                        'title' => 'Be clear and specific: context, task, format',
                        'type' => 'text',
                        'duration' => 10,
                        'content' => <<<'MD'
## A simple prompt structure

Treat the assistant like a brilliant new colleague who has no background on your situation. Give it:

1. **Context** — who you are, who the audience is, why the task matters.
2. **Task** — exactly what you want done.
3. **Constraints** — length, tone, things to avoid.
4. **Format** — bullet list, table, JSON, email draft.

Weak: *"Write about our product."*

Strong: *"I run a small bakery. Write a 120-word Instagram caption announcing our new sourdough, for local customers, friendly tone, ending with a call to visit this weekend."*
MD,
                    ],
                    [
                        'title' => 'Using examples (few-shot prompting)',
                        'type' => 'text',
                        'duration' => 9,
                        'content' => <<<'MD'
## Show, don't just tell

Including one to three **examples** of the input and desired output is one of the most reliable ways to get a consistent style or format. This is called *few-shot prompting*.

Tips:

- Make examples varied so the model doesn't copy them too literally.
- Wrap examples in clear markers such as XML-style tags (`<example>...</example>`) so they are not confused with instructions.
- If the output must be machine-readable, show the exact JSON structure you expect.
MD,
                        'quiz' => [
                            'title' => 'Prompting Fundamentals Quiz',
                            'pass_mark' => 70,
                            'questions' => [
                                [
                                    'question' => 'Which addition most improves a vague prompt like "Write about our product"?',
                                    'options' => [
                                        'Adding "please" several times',
                                        'Context, audience, constraints and the desired format',
                                        'Writing in all capital letters',
                                        'Making the prompt as short as possible',
                                    ],
                                    'correct' => 1,
                                    'explanation' => 'Specific context, task, constraints and format give the model what it needs to produce useful output.',
                                ],
                                [
                                    'question' => 'What is few-shot prompting?',
                                    'options' => [
                                        'Asking the model the same question repeatedly',
                                        'Including a few examples of input and desired output',
                                        'Limiting the answer to a few words',
                                        'Using several different AI models',
                                    ],
                                    'correct' => 1,
                                    'explanation' => 'Few-shot prompting shows examples so the model can match the style and format.',
                                ],
                                [
                                    'question' => 'Why should you verify important facts in AI output?',
                                    'options' => [
                                        'AI models never make mistakes',
                                        'Models can produce confident but incorrect statements',
                                        'It is a legal requirement for all text',
                                        'Verification makes the model faster',
                                    ],
                                    'correct' => 1,
                                    'explanation' => 'LLMs generate plausible text and can be wrong while sounding confident.',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'title' => 'Advanced Techniques',
                'lessons' => [
                    [
                        'title' => 'Breaking tasks into steps',
                        'type' => 'text',
                        'duration' => 10,
                        'content' => <<<'MD'
## Decompose complex work

Large tasks produce better results when split into stages: first outline, then draft each section, then review. You can do this in one prompt ("First list the key points, then write the summary") or across several messages.

Asking the model to **think through the problem before answering** helps with reasoning-heavy tasks like analysis or maths. Ask it to put its final answer in a clearly marked section so it's easy to find.
MD,
                    ],
                    [
                        'title' => 'Giving documents as context',
                        'type' => 'text',
                        'duration' => 9,
                        'content' => <<<'MD'
## Ground answers in your material

When you paste a document, place it **before** your question and label it clearly:

```
<document title="Q3 report">
...
</document>

Using only the report above, list the three biggest risks.
```

Telling the model to rely only on the provided material — and to say when the answer isn't there — reduces made-up answers. Ask for quotes from the document to back up each claim when accuracy matters.
MD,
                    ],
                    [
                        'title' => 'Reviewing and iterating on output',
                        'type' => 'text',
                        'duration' => 8,
                        'content' => <<<'MD'
## Iterate like an editor

Your first prompt is a draft. When output misses the mark, diagnose *why* — missing context? unclear format? — and fix the prompt rather than starting over.

Useful follow-ups:

- "Make it shorter and more direct."
- "What assumptions did you make? List them."
- "Critique this draft against the brief, then improve it."

Keep prompts that work in a personal library so you can reuse them.
MD,
                    ],
                ],
            ],
        ],
    ],

    // ------------------------------------------------------------------ 7
    [
        'title' => 'UI Design Essentials',
        'subtitle' => 'Design clean, accessible interfaces using layout, typography, colour and spacing.',
        'category' => 'design',
        'instructor' => 'instructor3@lms.test',
        'level' => 'beginner',
        'price' => 19.99,
        'status' => 'published',
        'featured' => false,
        'description' => <<<'MD'
Great interfaces feel effortless because of many small, deliberate decisions. Learn the core principles of visual hierarchy, spacing systems, typography, colour and accessibility, and apply them to real screens.
MD,
        'what_you_will_learn' => [
            'Create clear visual hierarchy',
            'Use a consistent spacing scale and grid',
            'Pair fonts and set readable type',
            'Choose accessible colour palettes',
        ],
        'requirements' => ['Figma (free plan) or any design tool', 'No design experience needed'],
        'sections' => [
            [
                'title' => 'Visual Foundations',
                'lessons' => [
                    [
                        'title' => 'Visual hierarchy',
                        'type' => 'video',
                        'video' => $video('ForBiggerBlazes'),
                        'duration' => 9,
                        'preview' => true,
                        'content' => <<<'MD'
## Lesson notes

Visual hierarchy tells users what to look at first. Create it with **size**, **weight**, **colour** and **position**.

- Make the most important element the most prominent — usually the page title or primary action.
- De-emphasise secondary information with lighter colour rather than smaller-and-smaller text.
- Use only **one primary button** per view; other actions become secondary or text buttons.
MD,
                    ],
                    [
                        'title' => 'Spacing and layout grids',
                        'type' => 'text',
                        'duration' => 11,
                        'content' => <<<'MD'
## Spacing scale

Pick a spacing scale based on multiples of 4 or 8 pixels (4, 8, 12, 16, 24, 32, 48, 64) and use only those values. Consistency makes designs feel polished.

**Proximity** communicates grouping: related items sit closer together than unrelated ones. Start with more white space than feels necessary, then remove it.

## Grids

A 12-column grid adapts well from mobile to desktop. Design **mobile first**: a single column on small screens, expanding to more columns as space allows.
MD,
                    ],
                    [
                        'title' => 'Typography that reads well',
                        'type' => 'text',
                        'duration' => 12,
                        'content' => <<<'MD'
## Readable type

- Body text: 16px or larger on the web, line height around 1.5.
- Line length: 45–75 characters per line for comfortable reading.
- Use a type scale (for example 14, 16, 20, 24, 30, 36) instead of arbitrary sizes.

One well-designed sans-serif family with several weights is enough for most interfaces. Create hierarchy with weight and colour before reaching for more font sizes.
MD,
                        'quiz' => [
                            'title' => 'Visual Foundations Quiz',
                            'pass_mark' => 70,
                            'questions' => [
                                [
                                    'question' => 'How many primary buttons should a typical view have?',
                                    'options' => ['None', 'One', 'Two', 'As many as there are actions'],
                                    'correct' => 1,
                                    'explanation' => 'A single primary action keeps the hierarchy clear; others become secondary.',
                                ],
                                [
                                    'question' => 'Which spacing values best follow an 8-point system?',
                                    'options' => ['5, 10, 15, 20', '8, 16, 24, 32', '7, 13, 21, 34', '3, 6, 9, 12'],
                                    'correct' => 1,
                                    'explanation' => 'An 8-point system uses multiples of 8 (with 4 for fine adjustments).',
                                ],
                                [
                                    'question' => 'What is a comfortable line length for body text?',
                                    'options' => ['10–20 characters', '45–75 characters', '120–150 characters', 'Unlimited'],
                                    'correct' => 1,
                                    'explanation' => '45–75 characters per line is the widely recommended range for readability.',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'title' => 'Colour and Accessibility',
                'lessons' => [
                    [
                        'title' => 'Building a colour palette',
                        'type' => 'video',
                        'video' => $video('ForBiggerFun'),
                        'duration' => 10,
                        'content' => <<<'MD'
## Lesson notes

A practical UI palette has:

- **Greys** (8–10 shades) for text, borders and backgrounds — most of the interface.
- **One primary colour** with several shades for actions and highlights.
- **Semantic colours**: green for success, amber for warnings, red for errors.

Define shades up front (50–900) rather than inventing new colours per screen. For dark mode, don't simply invert: reduce contrast slightly and avoid pure black backgrounds.
MD,
                    ],
                    [
                        'title' => 'Contrast and accessible design',
                        'type' => 'text',
                        'duration' => 12,
                        'content' => <<<'MD'
## WCAG contrast

The WCAG guidelines require a contrast ratio of at least **4.5:1** for normal text and **3:1** for large text (24px, or 18.66px bold) and interface components.

## Beyond colour

- Never use colour as the **only** signal — pair error colours with icons or text.
- Every interactive element needs a visible **focus state** for keyboard users.
- Touch targets should be at least **44×44 px**.
- Every form input needs a visible label, not only a placeholder.
MD,
                    ],
                    [
                        'title' => 'Designing for states: empty, loading and error',
                        'type' => 'text',
                        'duration' => 9,
                        'content' => <<<'MD'
## The forgotten screens

Designs often show only the "happy path" with perfect data. Real interfaces also need:

- **Empty states** that explain what will appear and offer a next action.
- **Loading states** — skeleton screens feel faster than spinners for content areas.
- **Error states** that explain what went wrong in plain language and how to recover.

Designing these states up front prevents confusing blank screens in production.
MD,
                    ],
                ],
            ],
        ],
    ],

    // ------------------------------------------------------------------ 8
    [
        'title' => 'Personal Branding for Creatives',
        'subtitle' => 'Define your niche, build a portfolio and grow an audience that hires you.',
        'category' => 'marketing',
        'instructor' => 'instructor3@lms.test',
        'level' => 'all_levels',
        'price' => 14.99,
        'status' => 'draft',
        'featured' => false,
        'description' => <<<'MD'
Talent alone doesn't win clients — being known for something does. This course helps designers, writers and other creatives define a clear positioning, build a portfolio that converts, and share their work consistently.
MD,
        'what_you_will_learn' => [
            'Define a clear niche and positioning statement',
            'Structure portfolio case studies',
            'Create a sustainable content routine',
        ],
        'requirements' => ['A few pieces of work to showcase'],
        'sections' => [
            [
                'title' => 'Positioning',
                'lessons' => [
                    [
                        'title' => 'Finding your niche',
                        'type' => 'video',
                        'video' => $video('ForBiggerEscapes'),
                        'duration' => 8,
                        'preview' => true,
                        'content' => <<<'MD'
## Lesson notes

A niche is the intersection of **what you're good at**, **what you enjoy**, and **what clients pay for**. Being specific ("brand identities for independent coffee roasters") makes you memorable and easier to recommend than being a generalist.

Write a one-line positioning statement: *I help [audience] achieve [outcome] through [service].*
MD,
                    ],
                    [
                        'title' => 'Portfolio case studies that convert',
                        'type' => 'text',
                        'duration' => 12,
                        'content' => <<<'MD'
## Tell the story

Clients hire for thinking, not just final images. Structure each case study as:

1. **The challenge** — the client's problem in their words.
2. **Your process** — research, sketches, decisions and trade-offs.
3. **The result** — final work plus measurable outcomes where possible.

Show three to five strong projects aligned with your niche rather than everything you've ever made.
MD,
                    ],
                ],
            ],
            [
                'title' => 'Growing an Audience',
                'lessons' => [
                    [
                        'title' => 'A sustainable content routine',
                        'type' => 'text',
                        'duration' => 10,
                        'content' => <<<'MD'
## Consistency beats intensity

Choose **one primary platform** where your clients spend time, and post on a schedule you can maintain for a year.

Content ideas that showcase expertise: process breakdowns, before/after comparisons, lessons from projects, and answers to common client questions. Repurpose one piece across formats — a case study becomes a thread, a short video and a newsletter.
MD,
                        'quiz' => [
                            'title' => 'Personal Branding Check',
                            'pass_mark' => 70,
                            'questions' => [
                                [
                                    'question' => 'What does a strong portfolio case study emphasise?',
                                    'options' => ['Only the final visuals', 'The challenge, process and results', 'The number of tools used', 'How long the project took'],
                                    'correct' => 1,
                                    'explanation' => 'Clients hire for problem-solving, so show the challenge, your process and the outcome.',
                                ],
                                [
                                    'question' => 'Why is a specific niche usually better than being a generalist?',
                                    'options' => [
                                        'It limits you to fewer clients forever',
                                        'It makes you memorable and easier to recommend',
                                        'It allows higher prices automatically',
                                        'It removes the need for a portfolio',
                                    ],
                                    'correct' => 1,
                                    'explanation' => 'Specific positioning makes you easier to remember and refer.',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],

];
