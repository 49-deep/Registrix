# Registrix — Live Student Registry & Search System

A campus-style student registry with an admin portal (CRUD + live search) and a student self-registration/profile portal.

**Stack:** PHP 8.x · MySQL 8 · Bootstrap 5 · Vanilla JS · No build step required.

---

## Local XAMPP Setup

1. Start **XAMPP** → Apache + MySQL.
2. Copy the entire `registrix/` folder into `C:\xampp\htdocs\registrix\`.
3. Open your browser and visit `http://localhost/registrix/`.
4. The app automatically creates the database, tables, and the default admin account on first load. **No manual SQL import needed.**

### Admin Environment Setup

Create a `.env` file in the project root to set admin credentials:

```env
ADMIN_USERNAME=dc1671860@gmail.com
ADMIN_PASSWORD=Deepika@123
```

> The application auto-loads `.env` and seeds/updates the admin account upon launch.


---

## Project Structure

```
registrix/
├── Dockerfile
├── docker-entrypoint.sh
├── schema.sql                  (reference DDL; auto-applied on first run)
├── index.php                   (public landing page)
├── config/
│   └── db.php                  (PDO connection + auto-migration + admin seed)
├── includes/
│   ├── session.php             (session start, CSRF helpers, flash messages)
│   ├── header.php              (shared <head> + navbar)
│   ├── footer.php              (shared footer + Bootstrap JS)
│   ├── auth_admin.php          (admin session guard)
│   └── auth_student.php        (student session guard)
├── assets/
│   ├── css/style.css           (full custom design system)
│   └── js/search.js            (debounced live search)
├── admin/
│   ├── login.php
│   ├── dashboard.php           (live-search table + export/print bar)
│   ├── add_student.php
│   ├── edit_student.php
│   ├── delete_student.php
│   ├── export_csv.php
│   ├── print_students.php      (clean print-to-PDF view)
│   └── logout.php
├── student/
│   ├── register.php            (2-step: verify identity → choose credentials)
│   ├── login.php
│   ├── profile.php             (read-only index card view)
│   └── logout.php
└── api/
    └── search.php              (admin-protected JSON search endpoint)
```

---

## Railway Deployment

### Requirements
- A **Railway** project with a **MySQL plugin** added.
- The Dockerfile in this repo is used automatically.

### Steps
1. Push this repository to GitHub.
2. In Railway: **New Project → Deploy from GitHub Repo** → select your repo.
3. Add a **MySQL** plugin to the project.
4. Railway automatically injects these environment variables into your service:

| Variable        | Description              |
|-----------------|--------------------------|
| `MYSQLHOST`     | MySQL host               |
| `MYSQLPORT`     | MySQL port               |
| `MYSQLDATABASE` | Database name            |
| `MYSQLUSER`     | MySQL username           |
| `MYSQLPASSWORD` | MySQL password           |
| `PORT`          | HTTP port (auto-set by Railway) |

5. Deploy. The app creates all tables and seeds the admin account automatically.

> **No code change required** between XAMPP and Railway — only environment variables differ.

---

## Security Features

| Feature | Implementation |
|---|---|
| SQL Injection | PDO prepared statements everywhere — no string concatenation in SQL |
| Password Hashing | `password_hash(PASSWORD_BCRYPT)` + `password_verify` |
| Session Auth Guards | `auth_admin.php` / `auth_student.php` on every protected page |
| XSS Prevention | `htmlspecialchars()` on all output via the `e()` helper |
| CSRF Protection | Per-session CSRF token verified on every POST form |
| Photo Uploads | MIME validated via `finfo`, max 2 MB, JPEG/PNG only |

---

## Features

### Admin Portal
- **Live search** — debounced `fetch` to `api/search.php`; results update as you type
- **Add / Edit / Delete** students with photo upload
- **CSV export** — all student fields, UTF-8 BOM for Excel compatibility
- **Print view** — clean HTML table, browser "Print to PDF" ready

### Student Portal
- **Self-register** — verify roll no + DOB, then choose username + password
- **Profile view** — read-only index card with full personal/academic/guardian details
- **Admin-only edits** — students cannot change their own data

---

## Design System

Academic "library card catalog" aesthetic:

| Token | Hex | Use |
|---|---|---|
| Paper background | `#EDEFF2` | Page background |
| Surface | `#FFFFFF` | Cards, tables |
| Ink Primary | `#1B2A4A` | Headings, buttons, nav |
| Ink Secondary | `#5C6B7A` | Labels, placeholders |
| Accent Brass | `#B08D4F` | Badges, roll numbers, hover |
| Success | `#2F6E4F` | Active status |
| Danger | `#A13D3D` | Inactive, errors, delete |

Fonts: **Lora** (display headings) · **Inter** (body) · **IBM Plex Mono** (roll numbers, dates, IDs)
