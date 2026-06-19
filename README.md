# StudentCollab

Student marketplace for freelance gigs, collaboration, skill tagging, project proposals, team formation, and portfolio showcase.

## Requirements

- PHP 7.4+ with PDO SQLite extension
- A web browser

## Run locally

From the `logproject` folder:

```bash
php -S localhost:8000
```

Open [http://localhost:8000/index.html](http://localhost:8000/index.html)

The SQLite database is created automatically in `data/studentcollab.db` on first API request.

## Demo accounts

All demo accounts use password: **demo123**

| Email | Role |
|-------|------|
| alice@demo.com | Project poster (accept applications) |
| bob@demo.com | Applicant / teammate |
| rezwan@demo.com | Profile with portfolio |

## Presentation flow

1. Log in as **bob@demo.com** → Browse Solutions → Apply to a gig
2. Log in as **alice@demo.com** → Applications → Accept Bob
3. Messages → Team chat
4. View profiles and portfolio
5. Post a new gig with skill tags

## Project structure

- `index.html` — Browse opportunities
- `solutions.html` — Skills hub
- `members.html` — Browse students
- `post.html` — Post gig/collaboration
- `applications.html` — Manage proposals
- `messages.html` — Team chat
- `profile.html` — Portfolio showcase
- `api/` — PHP REST endpoints
- `database/` — Schema and seed
- `css/`, `js/` — Shared assets



Demo accounts (password: demo123)
alice@demo.com — post gigs, accept applications, host chats
bob@demo.com — apply to gigs, teammate chats
rezwan@demo.com — profile with portfolio items
