# SUFIA Healthcare - Official Website & Admin Portal

[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B%20%7C%208.x-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![Database](https://img.shields.io/badge/Database-SQLite%203-003B57?logo=sqlite&logoColor=white)](https://www.sqlite.org/)
[![License](https://img.shields.io/badge/License-Proprietary-red)](#)
[![Status](https://img.shields.io/badge/Status-Active-brightgreen)](#)

> **SUFIA Healthcare** is a modern, responsive, and SEO-optimized healthcare web platform for a premier multispeciality hospital based in Siwan, Bihar. The platform offers comprehensive patient information, department showcases, doctor directory, 24/7 emergency details, career portal with resume uploads, and an integrated SQLite-powered Admin Management Dashboard.

---

## Table of Contents

- [Overview](#overview)
- [Key Features](#key-features)
  - [Patient & Public Portal](#patient--public-portal)
  - [Admin Management Panel](#admin-management-panel)
- [Tech Stack](#tech-stack)
- [Folder Structure](#folder-structure)
- [Installation & Local Setup](#installation--local-setup)
  - [Option 1: PHP Built-in Server (Recommended)](#option-1-php-built-in-server-recommended)
  - [Option 2: XAMPP / WAMP / Apache Server](#option-2-xampp--wamp--apache-server)
- [Admin Panel Access](#admin-panel-access)
- [Server & Routing Configuration](#server--routing-configuration)
- [Production Deployment](#production-deployment)
- [Contact & Support](#contact--support)

---

## Overview

- **Location**: Siwan, Bihar, India
- **Website Domain**: [sufiahealthcare.in](https://sufiahealthcare.in)
- **Primary Objective**: Provide patients with accessible information about doctors, specialized treatments, emergency care, and appointment booking, while giving hospital staff full control over incoming inquiries and career applications through a lightweight admin dashboard.

---

## Key Features

### Patient & Public Portal
- **Modern & Responsive UI**: Mobile-first, accessible, and fast-loading web design.
- **Find a Doctor & Departments**: Browse doctors by specialization, OPD timings, and experience.
- **24/7 Emergency Care**: Quick emergency helpline numbers, ambulance dispatch contact, and immediate care guidelines.
- **Contact & Inquiry Forms**: Real-time form submissions routed directly to the database.
- **Careers Portal (`careers.php`)**:
  - Live job vacancies and application submission.
  - Resume file upload (`PDF`, `DOC`, `DOCX`) with secure storage in the `files/` directory.
- **SEO & Social Optimization**:
  - Pre-configured `sitemap.xml` and `robots.txt`.
  - Canonical URLs, Open Graph meta tags, Schema markup for medical organizations.
  - Clean URL support without `.html` or `.php` extensions.

### Admin Management Panel
- **Secure Authentication**: Session-based login with role management.
- **Dashboard Overview (`admin/index.php`)**: Real-time statistics of inquiries, job applications, and quick actions.
- **Inquiry Management (`admin/contact_enquiries.php`)**: View, filter, update statuses, and respond to patient inquiries.
- **Career Enquiries & Resumes (`admin/career_enquiries.php`)**:
  - Review applicant details and qualifications.
  - One-click resume downloads.
  - Status updates (`New`, `Reviewed`, `Shortlisted`, `Rejected`).
- **User Management (`admin/users.php`)**: Create and manage staff accounts and roles.
- **Profile & Personalization (`admin/profile.php`)**: Customizable dashboard themes (sidebar, header, and footer color schemes).

---

## Tech Stack

| Layer | Technologies |
|---|---|
| **Frontend** | HTML5, CSS3, Vanilla JavaScript, Font Awesome Icons |
| **Backend** | PHP 7.4+ / PHP 8.x (Native PDO, Clean Architecture) |
| **Database** | SQLite 3 (Zero-configuration, single-file DB at `admin/database.sqlite`) |
| **Routing / Server** | Apache (`.htaccess` with `mod_rewrite`) & PHP Built-in Server (`router.php`) |

---

## Folder Structure

```text
sufia-healthcare/
├── .htaccess                 # Apache rewrite rules for clean URLs & caching
├── 404.html / 404.php        # Custom 404 error pages
├── about.html                # About hospital, mission, and vision
├── admin/                    # Administrative dashboard module
│   ├── .htaccess             # Security rules for admin folder
│   ├── assets/               # Admin specific CSS/JS/images
│   ├── career_enquiries.php  # Candidate applications & resumes management
│   ├── contact_enquiries.php # Patient contact messages & follow-ups
│   ├── database.sqlite       # SQLite database file
│   ├── db.php                # Database connection, migrations & seeding
│   ├── index.php             # Main admin dashboard
│   ├── login.php             # Admin authentication page
│   ├── profile.php           # User profile settings & theme preferences
│   └── users.php             # Admin team & user accounts management
├── assets/                   # Site assets (images, logos, icons, fonts)
├── career-success.php        # Career application submission confirmation
├── careers.php               # Job portal & application form
├── contact.html              # Contact page with interactive form
├── contact-submit.php        # Contact form POST handler
├── css/                      # Stylesheets (premium styling, responsive CSS)
├── departments.html          # Medical departments list
├── doctors.html              # Doctor profiles and schedule
├── emergency.html            # 24/7 emergency & trauma care details
├── files/                    # Stored resumes and uploaded documents
├── includes/                 # Reusable components (header & footer)
├── index.html                # Main homepage
├── js/                       # Client-side JavaScript scripts
├── patient-portal.html       # Patient guide and portal overview
├── privacy-policy.html       # Privacy policy
├── robots.txt                # Search engine crawler instructions
├── router.php                # Local PHP development server router
├── sitemap.xml               # XML sitemap for search engines
└── terms-conditions.html     # Terms and conditions
```

---

## Installation & Local Setup

### Prerequisites
- **PHP** installed (version 7.4 or higher, PHP 8.x recommended).
- Ensure the following PHP extensions are enabled in your `php.ini`:
  - `pdo_sqlite`
  - `sqlite3`
  - `fileinfo` (for upload mime validation)

---

### Option 1: PHP Built-in Server (Recommended)

1. **Clone or navigate** to the project directory:
   ```bash
   cd path/to/sufia-healthcare
   ```

2. **Start the local server** using the included router:
   ```bash
   php -S localhost:8000 router.php
   ```

3. **Open in your browser**:
   - Public Website: [http://localhost:8000](http://localhost:8000)
   - Admin Panel: [http://localhost:8000/admin](http://localhost:8000/admin)

> [!TIP]
> The `router.php` script automatically handles clean URL rewriting (e.g. `/doctors` loads `doctors.html`) and direct routing for form actions.

---

### Option 2: XAMPP / WAMP / Apache Server

1. Copy the `sufia-healthcare` folder into your web root:
   - **XAMPP**: `C:\xampp\htdocs\sufia-healthcare`
   - **WAMP**: `C:\wamp64\www\sufia-healthcare`
2. Ensure **`mod_rewrite`** is enabled in Apache's `httpd.conf`.
3. Open your browser and navigate to:
   - `http://localhost/sufia-healthcare/`
   - Admin: `http://localhost/sufia-healthcare/admin/`

---

## Admin Panel Access

The administrative dashboard can be accessed at:
- **Local URL**: `http://localhost:8000/admin` (or `/admin/login.php`)
- **Live URL**: `https://sufiahealthcare.in/admin`

> [!NOTE]
> For security reasons, administrative login credentials are not published in public documentation. Authorized staff should obtain login credentials directly from the system administrator.

---

## Server & Routing Configuration

- **Clean URLs**: Clean URLs are configured both for Apache (via `.htaccess`) and PHP CLI (via `router.php`).
- **File Upload Directory**: Ensure the `files/` directory has write permissions (`chmod 775` or `chmod 777` on Linux servers) for candidate resume uploads.
- **Database Protection**: The `.htaccess` in the `admin/` folder prevents direct download or public access to `.sqlite` database files.

---

## Production Deployment

1. **Upload Files**: Upload all project files to `public_html` or the domain root via FTP/SFTP/Git.
2. **Permissions**:
   - Ensure `admin/database.sqlite` is writable by the web server process.
   - Ensure `files/` folder is writable (`0755` or `0775`).
3. **SSL Certificate**: Enforce HTTPS via hosting panel / Cloudflare / Let's Encrypt.
4. **Update Credentials**: Update default admin passwords.

---

## Contact & Support

- **Organization**: SUFIA Healthcare
- **Location**: Siwan, Bihar, India
- **Website**: [https://sufiahealthcare.in](https://sufiahealthcare.in)
- **Technical Queries**: [iam-techbrain](https://github.com/iam-techbrain)
