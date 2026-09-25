# SkillBridge.lk

Sri Lanka's local freelancer marketplace — trilingual (Tamil / English / Sinhala), local payments via PayHere & Stripe, and a trust-first, verified-freelancer model. Built for CSE5015 (Group 07).

## Tech Stack
- **Frontend:** HTML5, CSS3, JavaScript
- **Backend:** PHP 8+
- **Database:** MySQL / MariaDB (utf8mb4 — required for Tamil/Sinhala script)
- **Payments:** PayHere (sandbox) + Stripe (test mode) — Stripe was added as a free-to-test alternative since PayHere's sandbox requires a paid merchant setup
- **Maps:** Leaflet.js + OpenStreetMap (Nominatim geocoding)

## Project Structure
```
skillbridge/
├── index.html              # Language picker (entry point)
├── set_language.php        # Stores language choice, routes into a language folder
├── config/
│   ├── db.example.php       # Copy to db.php and fill in your DB credentials
│   ├── payhere.example.php  # Copy to payhere.php and fill in your PayHere merchant details
│   └── stripe.php           # Stripe test-mode keys (already included — see note below)
├── database/
│   └── gpss_database.sql    # Full table structure + demo seed data — import this
├── english/                 # English site (fully built)
├── tamil/                   # Tamil site (fully built)
└── sinhala/                 # Sinhala site (fully built)
```

## Local Setup (XAMPP)

1. Copy this whole folder into `htdocs/skillbridge/`.
2. Start Apache + MySQL in XAMPP.
3. In phpMyAdmin, click **New** and create an empty database named exactly `gpss_database` (this must match `DB_NAME` in `config/db.php`).
4. With `gpss_database` selected, go to the **Import** tab and import `database/gpss_database.sql`. This creates all 10 tables and loads the demo seed data — no separate seed file needed.
5. Copy `config/db.example.php` → `config/db.php` and fill in your DB credentials (XAMPP default: host `127.0.0.1`, user `root`, empty password, database `gpss_database`).
6. Copy `config/payhere.example.php` → `config/payhere.php`. Real PayHere payments need a [PayHere](https://www.payhere.lk) sandbox account — see the comments in that file for setup, including why `notify_url` needs a tunnel (e.g. `ngrok`) for local testing.
7. `config/stripe.php` already contains working Stripe **test-mode** keys, so Stripe checkout works out of the box for local testing. ⚠️ These are test keys, but avoid committing/sharing this file publicly as a matter of good practice — rotate them if this repo is ever made public.
8. Visit `http://localhost/skillbridge/index.html`.

Maps (freelancer location picker, profile map, "Nearby" search) run on Leaflet.js + OpenStreetMap tiles, which need no API key or billing account — they'll just work once you visit the site, no extra config step.

## Features (English — mirrored in Tamil & Sinhala)
- Trilingual landing page with language picker
- Search & filter freelancers by category, keyword, verified status, price
- **"Nearby" search** — sort/filter freelancers by distance from your current location (browser Geolocation + a plain SQL Haversine calc, no paid API), with a map view of results
- Freelancer profiles with ratings & reviews, and a map showing where they're based
- Freelancer location picker (click-to-pin / address search via OSM's free Nominatim / "Use My Location") in the profile editor
- Booking + payment flow via **PayHere or Stripe** (client's choice; both are webhook/callback-verified)
- In-app messaging between clients and freelancers (short-poll updates, no page reload)
- Freelancer dashboard (manage services, bookings — mark Completed/Cancelled — and refund decisions)
- Client dashboard (booking history, refund requests, leave reviews)
- Admin dashboard (platform stats overview, freelancer verification, user management, refund monitoring/force-processing of overdue requests)

## Demo Accounts
The imported `gpss_database.sql` already includes demo data. Every demo freelancer account uses the password `Passw0rd!`. Admin accounts aren't self-registered — create one manually (see the comment at the top of `english/admin-dashboard.php`).

## Status
- ✅ English — fully built
- ✅ Tamil / Sinhala — fully built (translated UI, same features and backend logic as English)


