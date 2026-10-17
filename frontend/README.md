# LearnHub — Frontend

React 19 + Vite SPA for the AI-Powered LMS.

```powershell
npm install
Copy-Item .env.example .env   # set VITE_API_URL if the API is not on :8000
npm run dev                   # http://localhost:5173
npm run build                 # production build into dist/
npm run lint
```

## Structure

| Folder | Purpose |
|---|---|
| `src/api` | Axios client (Bearer token interceptor), React Query client, endpoint functions |
| `src/components/ui` | Reusable primitives (Button, Skeleton, EmptyState, ErrorState…) |
| `src/components/layout` | Navbar, Footer, Logo, ThemeToggle |
| `src/components/course`, `src/components/quiz` | Feature components |
| `src/pages/{public,auth,student,instructor,admin}` | Route pages per area |
| `src/layouts` | Page shells |
| `src/context` | React contexts (theme, auth) |
| `src/hooks` | Custom hooks |
| `src/routes` | Router definition and route guards |
| `src/utils` | Helpers (storage, class names, API errors) |
