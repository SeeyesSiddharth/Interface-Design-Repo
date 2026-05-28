# GameBench Catalogue

GameBench is a Vue 3 and Bootstrap starter website for a catalogue of games and their technical requirements.
It gives the team a working base with 13 routes, searchable catalogue data, comparison tools, mock authentication,
CRUD-style actions, likes, reviews, validation, pagination, and localStorage persistence.

## Run the project

```bash
npm install
npm run dev
```

Demo login:

- Email: `demo@gamebench.test`
- Password: `password123`

## Pages included

1. Home
2. Catalogue
3. Game detail
4. Compare
5. Requirements guide
6. Compatibility checker
7. Reviews
8. Login
9. Register
10. Profile
11. Submit game
12. Admin management
13. Team plan
14. Not found fallback

## Suggested page ownership

| Owner | Pages | Main responsibility |
| --- | --- | --- |
| You | Home + Team Plan | Overall integration, route map, screenshots, final polish |
| Teammate 1 | Catalogue + Game Detail | Search, sort, pagination, requirements display, likes |
| Teammate 2 | Compare + Requirements Guide | Spec comparison, educational content, hardware explanations |
| Teammate 3 | Compatibility Checker + Reviews | Reactive checker, computed verdicts, review/social features |
| Teammate 4 | Login + Register | Validation, auth states, account flow |
| Teammate 5 | Profile + Submit Game | Member dashboard, create form, protected actions |
| Shared backup | Admin + Not Found | Edit/delete/reset workflows, fallback route, QA |

## Important next steps for the final assignment

- Replace localStorage-only persistence with a backend database and RESTful API.
- Decide whether the catalogue will use fictional games or researched real games.
- Add individual advanced features for each student.
- Capture responsive screenshots for the group report.
- Deploy the Vite app to Mercury once backend and final content are ready.
