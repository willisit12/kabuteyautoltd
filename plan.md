```markdown
# Williams Auto – Personal Used Car Sales Website

**Project Plan**  
**Version:** 1.4 (Refined February 2026) – Pure PHP + MySQL
**Author:** Williams (Solo Owner / Developer)
**Goal:** Refactor the existing "Kabutey Auto" codebase into a premium, front-controller based platform for "Williams Auto".

## 1. Project Overview

We are evolving the current PHP/MySQL site into a unified, modular platform. The core remains pure PHP, but we are moving to a **Front Controller** pattern for cleaner URLs and better maintainability.

### Key Enhancements
- **Front Controller Implementation**: Routing all traffic through `index.php`.
- **Modular Includes**: Separating configuration, authentication, and helper functions.
- **Enhanced Schema**: Adopting the comprehensive database structure from v1.3.
- **Unified Assets**: Organised directory for CSS, JS, and Media.

## 2. Tech Stack

| Layer | Technology |
| :--- | :--- |
| **Backend** | Pure PHP 8.3+ |
| **Database** | MySQL (PDO) |
| **Frontend** | Tailwind CSS (CDN), Alpine.js, Swiper JS, Lenis Scroll |
| **Routing** | .htaccess + index.php (Router) |

## 3. Refined Project Structure

```bash
car-website/
├── index.php                     # Front Controller & Router
├── .htaccess                     # URL Rewriting
├── config.php                    # Legacy redirect or entry point
├── includes/                     # Core Logic
│   ├── config.php                # DB Credentials & Constants
│   ├── functions.php             # Global Helpers
│   ├── auth.php                  # Session Management
│   ├── router.php                # Routing Logic
│   └── layout/                   # Shared UI Components
│       ├── header.php
│       └── footer.php
├── pages/                        # Page Templates (Content)
│   ├── home.php                  # From index.php
│   ├── cars.php                  # Inventory
│   ├── car-detail.php            # Single View
│   ├── about.php
│   ├── contact.php
│   └── 404.php
├── admin/                        # Dashboard & CMS
│   ├── dashboard.php
│   ├── inventory.php             # Unified Car Management
│   ├── users.php
│   └── login.php
├── assets/                       # Static Files
│   ├── css/
│   ├── js/
│   └── images/                   # Brand assets
└── uploads/                      # User-uploaded Car Images
```

## 4. Implementation Strategy

### Phase 1: Preparation
1. **Directory Setup**: Create `includes/`, `pages/`, `assets/`.
2. **Database Migration**: Run the new SQL schema (see below) to create `williams_auto` DB.
3. **Core Refactor**: Extract logic from current `config.php` into `includes/config.php` and `includes/functions.php`.

### Phase 2: Routing
1. **Front Controller**: Set up `index.php` to handle URL parameters and include files from `pages/`.
2. **.htaccess**: Enable clean URLs (e.g., `/cars` instead of `cars.php`).

### Phase 3: Content Migration
1. **Porting Templates**: Move existing HTML/PHP logic from `index.php`, `cars.php`, etc., into `pages/`.
2. **Updating Links**: Ensure all internal links use the new routing pattern.

### Phase 4: Branding & AI
1. **Branding**: Update styles and text to "Williams Auto".
2. **AI Assistant**: Implement `api/chat.php` endpoint.

## 5. Database Schema (Enhanced)

```sql
-- Database: williams_auto
CREATE DATABASE IF NOT EXISTS williams_auto CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE williams_auto;

-- Tables: users, cars, car_images, inquiries, testimonials, activity_logs, site_settings
-- [Refer to plan.md v1.3 for full SQL]
```

## 6. Next Steps

1. [ ] **Restructure**: Move current files to their respective `pages/` or `includes/` locations.
2. [ ] **Boot**: Create the new `index.php` router.
3. [ ] **DB**: Execute the new schema and update `includes/config.php`.
4. [ ] **Verify**: Ensure the site renders correctly under the new architecture.
```