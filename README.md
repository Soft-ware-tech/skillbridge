# SkillBridge.lk

Sri Lanka's local freelancer marketplace — trilingual (Tamil / English / Sinhala), local payments via PayHere & Genie Pay, and a trust-first, verified-freelancer model. Built for CSE5015 (Group 07).

## Tech Stack
- **Frontend:** HTML5, CSS3, JavaScript
- **Backend:** PHP 8+
- **Database:** MySQL / MariaDB (utf8mb4 — required for Tamil/Sinhala script)
- **Payments:** PayHere (sandbox + live)

## Project Structure
```
skillbridge/
├── index.html              # Language picker (entry point)
├── set_language.php        # Stores language choice, routes into a language folder
├── config/
│   ├── db.example.php       # Copy to db.php and fill in your DB credentials
│   └── payhere.example.php  # Copy to payhere.php and fill in your PayHere merchant details
├── database/
│   ├── schema.sql            # Full table structure — import this first
│   └── seed-demo-data.sql    # Optional demo freelancers/services for testing
├── english/                 # English site (fully built)
├── tamil/                   # Tamil site (structure only — not yet built)
└── sinhala/                 # Sinhala site (structure only — not yet built)
```

## Local Setup (XAMPP)

1. Copy this whole folder into `htdocs/skillbridge/`.
2. Start Apache + MySQL in XAMPP.
3. In phpMyAdmin, import `database/schema.sql`, then optionally `database/seed-demo-data.sql`.
4. Copy `config/db.example.php` → `config/db.php` and fill in your DB credentials (XAMPP default: host `127.0.0.1`, user `root`, empty password).
5. Copy `config/payhere.example.php` → `config/payhere.php`. Real payments need a [PayHere](https://www.payhere.lk) sandbox account — see the comments in that file for setup, including why `notify_url` needs a tunnel (e.g. `ngrok`) for local testing.
6. Visit `http://localhost/skillbridge/index.html`.

Maps (freelancer location picker, profile map, "Nearby" search) run on Leaflet.js + OpenStreetMap tiles, which need no API key or billing account — they'll just work once you visit the site, no extra config step.

## Features (English)
- Trilingual landing page with language picker
- Search & filter freelancers by category, keyword, verified status, price
- **"Nearby" search** — sort/filter freelancers by distance from your current location (browser Geolocation + a plain SQL Haversine calc, no paid API), with a map view of results
- Freelancer profiles with ratings & reviews, and a map showing where they're based
- Freelancer location picker (click-to-pin / address search via OSM's free Nominatim / "Use My Location") in the profile editor
- Booking + PayHere payment flow (hosted checkout, webhook-verified)
- In-app messaging between clients and freelancers
- Freelancer dashboard (manage services, bookings, profile)
- Client dashboard (booking history, leave reviews)
- Admin dashboard (freelancer verification, user management)

## Demo Accounts
If you imported `seed-demo-data.sql`, every demo freelancer account uses the password `Passw0rd!`. Admin accounts aren't self-registered — create one manually (see the comment at the top of `english/admin-dashboard.php`).

## Status
- ✅ English — fully built
- ✅ Tamil / Sinhala — fully built (translated UI, same features and backend logic as English)
