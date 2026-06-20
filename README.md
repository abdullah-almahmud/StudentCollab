# StudentCollab

> Student marketplace for freelance gigs, collaboration, skill tagging, project proposals, team formation, and portfolio showcase.

## Features
- **Browse Opportunities:** View and filter freelance gigs and collaboration requests.
- **Post Gigs:** Easily post new opportunities with specific skill requirements and budgets.
- **Collaborator Directory:** Find and connect with other talented students across campus.
- **Integrated Messaging:** Direct messaging system to communicate with potential collaborators or clients.
- **Authentication:** Secure user registration and login system.

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


## Project structure

.
├── applications.html
├── index.html
├── login.html
├── members.html
├── messages.html
├── post.html
├── profile.html
├── README.md
├── signup.html
├── solutions.html
├── api
│   ├── applications.php
│   ├── auth.php
│   ├── db.php
│   ├── gigs.php
│   ├── helpers.php
│   ├── messages.php
│   ├── skills.php
│   └── users.php
├── css
│   ├── components.css
│   ├── layout.css
│   ├── main.css
│   └── pages
│       ├── applications.css
│       ├── auth.css
│       ├── home.css
│       ├── members.css
│       ├── messages.css
│       ├── post.css
│       ├── profile.css
│       └── solutions.css
├── data
│   └── studentcollab.db
├── database
│   ├── migrate.php
│   ├── schema.sql
│   ├── seed.php
│   ├── seed.sql
│   └── seed_extra.php
└── js
    ├── api.js
    ├── auth.js
    ├── nav.js
    └── pages
        ├── applications.js
        ├── home.js
        ├── members.js
        ├── messages.js
        ├── post.js
        ├── profile.js
        └── solutions.js

