# NaijaHaven — Nigerian Housing Marketplace (PHP + MySQL)

A responsive, charismatic dark-luxury marketplace with 3D visual effects
(glassmorphism, tilt cards, parallax hero, scroll reveals) — re-skinned in
Nigerian green & gold. Multi-agent: agents/landlords list properties, admin
approves, seekers browse, enquire and book shortlets instantly in ₦.

## Listing categories
- **Rent** — annual rent (₦/year), Nigerian style
- **Shortlet** — nightly rate with date booking, availability check & checkout
- **Land** — outright price, size in sqm, title document (C of O, Governor's Consent…)

## Requirements
PHP 8.0+ (with `pdo_mysql`, `curl`, `fileinfo`) and MySQL/MariaDB (XAMPP, WAMP,
Laragon or any LAMP host)

## Setup (XAMPP example)
1. Copy the `naijahaven` folder into `C:\xampp\htdocs\`
2. Start **Apache** and **MySQL**
3. phpMyAdmin → **Import** → `database.sql` (creates DB + 12 demo listings)
4. Copy `.env.example` to `.env` and fill in your DB credentials (and
   `APP_URL`, e.g. `http://localhost/naijahaven`)
5. Visit **http://localhost/naijahaven/**

`.env` is git-ignored — never commit real credentials. `config.php` reads it
via a small built-in loader (no Composer dependency required).

## Logins
| Role  | Email             | Password | Notes                          |
|-------|-------------------|----------|--------------------------------|
| Admin | admin@haven.test  | admin123 | Approves agents & listings     |
| Agent | agent@haven.test  | agent123 | Verified demo agent (12 listings) |

Both demo accounts are flagged to force a password change on first sign-in.
Seekers and new agents register via **Join free** (new agents start *pending*
until the admin verifies them; new/edited listings need approval to go live).
New accounts must verify their email before they can sign in.

## No SMTP server? (dev mode)
This environment has no mail server configured, so `send_mail()` in
`config.php` falls back to logging every email (verification links, password
resets) to `storage/mail.log` instead of failing silently. Wire up a real
SMTP/API mailer there for production.

## Structure
```
naijahaven/
├─ database.sql            # schema + Lagos/Abuja/PH seed data
├─ .env.example            # copy to .env (git-ignored) — DB + Paystack + APP_URL
├─ config.php              # env loading, DB, helpers, CSRF, security, notifications
├─ index.php               # 3D hero, featured, agent CTA
├─ rent.php / shortlet.php / land.php  (shared includes/listing.php + card.php)
├─ property.php             # detail, gallery, reviews, favorites, agent box, enquiry
├─ book.php / checkout.php  # shortlet booking (row-locked) + Paystack or demo payment
├─ paystack-callback.php    # server-side payment verification
├─ login / register / logout / account / contact.php
├─ forgot-password.php / reset-password.php / change-password.php
├─ verify-email.php / resend-verification.php
├─ favorite.php / notifications-read.php
├─ agent/                  # agent dashboard: listings CRUD (+ photo upload), bookings, enquiries
├─ admin/                  # approvals, agent verification, bookings, enquiries, audit log
├─ sitemap.php / robots.txt / .htaccess
└─ assets/                 # green-gold 3D theme (css) + tilt/parallax/calc (js) + uploads/
```

## Features
- Rent / shortlet / land listings with search, filters, sort and pagination
- Multi-photo galleries per listing (URL or direct upload, validated)
- Favorites ("saved listings") and star ratings/reviews per listing
- In-app notifications (bell dropdown) for enquiries, bookings, approvals
- Admin audit log of approvals, rejections and agent status changes
- Shortlet booking with a row-locked transaction to prevent double-booking
  the same dates under concurrent requests
- Paystack checkout (initialize → redirect → server-verified callback) when
  `PAYSTACK_SECRET_KEY` is set in `.env`; falls back to an instant demo
  payment otherwise

## Customising
- Brand/currency: `SITE_NAME`, `CURRENCY` in `config.php`; cities in `$NG_CITIES`
- Colors: CSS variables at top of `assets/css/style.css`
- Real payments: set `PAYSTACK_SECRET_KEY` / `PAYSTACK_PUBLIC_KEY` in `.env`
- Real email: replace `send_mail()` in `config.php` with an SMTP/API mailer

## Security included
PDO prepared statements, bcrypt hashing, timing-safe CSRF tokens on all
forms, output escaping, hardened session cookies (HttpOnly/SameSite/Secure),
session regeneration, safe redirects, per-role route guards, login
brute-force lockout, mandatory email verification, honeypot + timing spam
guards on public forms, and forced password change for default/demo
credentials. Agents can only modify their own listings.
