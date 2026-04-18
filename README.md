# ⚡ VentureIQ – E-Commerce Startup Planner
### AI-Powered Business Model Recommendation Engine

A full-stack, production-grade PHP + MySQL web application for e-commerce founders.

---

## 🗂 Project Structure

```
ventureiq/
├── .htaccess                    ← Root redirect to public/
├── config/
│   ├── config.php               ← App config (DB, reCAPTCHA, security)
│   ├── db.php                   ← PDO singleton + query helpers
│   └── schema.sql               ← Full MySQL schema + seed data
├── includes/
│   ├── helpers.php              ← CSRF, rate limit, session, audit, sanitize
│   ├── header.php               ← Global navbar, flash alerts, meta tags
│   └── footer.php               ← Scripts, footer HTML
├── public/                      ← Web root (point domain here)
│   ├── .htaccess                ← Security headers, URL rewriting, compression
│   ├── css/
│   │   └── main.css             ← Full design system (dark SaaS aesthetic)
│   ├── js/
│   │   └── app.js               ← Quiz engine, charts, UI interactions
│   ├── index.php                ← Landing page
│   ├── login.php                ← Auth: Sign in + reCAPTCHA + rate limit
│   ├── register.php             ← Auth: Sign up + password strength + CAPTCHA
│   ├── logout.php               ← Auth: Secure session destroy
│   ├── forgot.php               ← Auth: Password reset request
│   ├── quiz.php                 ← Interactive 7-step quiz
│   ├── results.php              ← Results dashboard (charts, roadmap, alts)
│   ├── explore.php              ← Model Explorer (filter, compare, search)
│   ├── how.php                  ← How It Works explainer
│   ├── dashboard.php            ← User dashboard (saved results, history)
│   └── profile.php              ← User profile (edit info, change password)
├── admin/
│   ├── index.php                ← Admin dashboard (stats, charts, recent data)
│   ├── users.php                ← User CRUD (search, role change, toggle, add)
│   ├── models.php               ← Business model editor (full CRUD)
│   ├── results.php              ← Quiz results viewer (filter, export CSV)
│   ├── analytics.php            ← Analytics (line, doughnut, bar charts)
│   ├── audit.php                ← Audit log viewer (filter by action)
│   ├── settings.php             ← Environment info, config reference, DB stats
│   └── api/
│       ├── toggle-user.php      ← AJAX: toggle user active status
│       └── export-results.php   ← CSV export of all quiz results
├── api/
│   └── recommend.php            ← Recommendation engine (POST endpoint)
└── uploads/                     ← User upload storage (future)
```

---

## 🚀 Deployment (cPanel / LAMP)

### Step 1 – Upload files
Upload the entire project to your hosting account.  
Set the **document root** to `ventureiq/public/` in cPanel → Domains.

### Step 2 – Create MySQL database
In cPanel → MySQL Databases:
1. Create a database: `ventureiq`
2. Create a user with a strong password
3. Grant ALL PRIVILEGES on `ventureiq` to that user

### Step 3 – Import schema
In phpMyAdmin → select `ventureiq` → Import → select `config/schema.sql`

### Step 4 – Configure `config/config.php`
```php
define('APP_URL',  'https://yourdomain.com');
define('APP_ENV',  'production');
define('DB_HOST',  'localhost');
define('DB_NAME',  'ventureiq');
define('DB_USER',  'your_db_user');
define('DB_PASS',  'your_db_password');
define('SECRET_KEY', 'generate-64-random-bytes-here');
define('RECAPTCHA_SITE_KEY',   'your-recaptcha-v3-site-key');
define('RECAPTCHA_SECRET_KEY', 'your-recaptcha-v3-secret-key');
```

### Step 5 – reCAPTCHA v3
1. Visit https://www.google.com/recaptcha/admin/create
2. Choose **reCAPTCHA v3**
3. Add your domain
4. Copy Site Key and Secret Key into `config.php`

### Step 6 – Test
- Visit `https://yourdomain.com` → Landing page
- Visit `https://yourdomain.com/login` → Sign in
- **Default Admin:** `admin@ventureiq.com` / `Admin@123`
- **Change the admin password immediately after first login!**

---

## 🔐 Security Features

| Feature | Implementation |
|---------|---------------|
| Password hashing | bcrypt cost-12 via `password_hash()` |
| SQL injection | Parameterized PDO queries everywhere |
| XSS protection | `htmlspecialchars()` on all output |
| CSRF protection | Per-session tokens, validated on every POST |
| Rate limiting | 5 attempts / 15 min window, 30 min lockout |
| reCAPTCHA v3 | Score-based (threshold 0.5) on login + register |
| Session fixation | `session_regenerate_id(true)` on login |
| User enumeration | Constant-time hash comparison always runs |
| Clickjacking | `X-Frame-Options: SAMEORIGIN` header |
| MIME sniffing | `X-Content-Type-Options: nosniff` header |
| Directory listing | `Options -Indexes` in .htaccess |
| Sensitive file access | Blocked via .htaccess RewriteRules |
| Audit trail | Every user and admin action logged to `audit_log` |
| Cookie security | HttpOnly + SameSite=Strict + Secure (prod) |
| Auto-rehash | Passwords rehashed if bcrypt cost changes |

---

## 🧠 Recommendation Engine

The engine uses a **weighted distance algorithm** across 7 dimensions:

```
confidence = Σ ( max(0, 5 − |user[dim] − model[dim]|) × weight[dim] ) / max_score × 100%
```

| Dimension | Weight |
|-----------|--------|
| Risk tolerance | 1.3× |
| Startup capital | 1.2× |
| Passive income goal | 1.1× |
| Tech skill | 1.0× |
| Time availability | 0.9× |
| Creativity | 0.8× |
| Team ambition | 0.7× |

---

## 🎨 Tech Stack

| Layer | Technology |
|-------|-----------|
| Frontend | HTML5, CSS3 (custom design system), Vanilla JS |
| Charts | Chart.js 4.4.1 (Radar, Bar, Doughnut, Line) |
| Backend | PHP 8.1+ |
| Database | MySQL 8.0+ via PDO |
| Auth | Custom session auth + bcrypt |
| CAPTCHA | Google reCAPTCHA v3 |
| Server | Apache + mod_rewrite (.htaccess) |

---

## 🎨 Design System

- **Primary:** `#7c6dfa` (Electric Purple)
- **Accent:** `#00d4aa` (Cyan)
- **CTA:** `#4f9fff` (Blue)
- **Background:** `#09090f` → `#0f0f1a` → `#141420`
- **Typography:** Inter (body) + Poppins (headings)
- **Aesthetic:** Dark SaaS — inspired by Linear, Vercel, Stripe

---

## 📊 Admin Panel

| Page | Features |
|------|---------|
| Dashboard | KPI cards, doughnut chart, recent users/results |
| Analytics | Line chart (30d), model distribution, confidence histogram |
| Users | CRUD, search, role management, active toggle, export |
| Quiz Results | View all results, filter by model/user, CSV export |
| Business Models | Full editor — add/edit/deactivate models, scoring config |
| Audit Log | Full action history, filter by type |
| Settings | Environment checks, config reference, DB stats |

---

## 📞 Support

Default admin credentials (change immediately):
- **Email:** admin@ventureiq.com  
- **Password:** Admin@123
