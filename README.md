# 🎯 Campusly v3 — Fixed & Enhanced

**Student Freelance Platform** | PHP + MySQL + XAMPP

---

## 🚀 Quick Setup (3 steps)

1. **Copy** the `campusly_v3` folder into your XAMPP `htdocs`:
   - Windows: `C:\xampp\htdocs\campusly_v3\`
   - Mac: `/Applications/XAMPP/htdocs/campusly_v3/`

2. **Import the database** in phpMyAdmin:
   - Open `http://localhost/phpmyadmin`
   - Click **Import** → choose `campusly_v3.sql` → click **Go**

3. **Open** `http://localhost/campusly_v3/`

---

## 🔑 Demo Login Credentials

| Role     | Email                   | Password   |
|----------|-------------------------|------------|
| Student  | student@demo.com        | Demo@1234  |
| Employer | employer@demo.com       | Demo@1234  |
| Admin    | admin@campusly.com      | Demo@1234  |

*(Click the demo account buttons on the login page to auto-fill)*

---

## 🛠 Database Config

Edit `db.php` if your MySQL has a different password:
```php
$host     = "localhost";
$db_user  = "root";
$db_pass  = "";          // ← change if needed
$database = "campusly";
```

---

## ✅ Changes in This Version (Fixed)

### 🐛 Bugs Fixed
| File | Issue | Fix Applied |
|------|-------|-------------|
| `candidate_dashboard.php` | **Messages appeared 3×, Saved Tasks 2×** in sidebar | Removed all 5 duplicate links |
| `campusly_v3.sql` | `employer@demo.com` had email in the `name` column | Fixed to `TechStartup Pvt Ltd` |
| `login.php` | 150+ lines of broken Fingerprint + Face camera code | Removed entirely — clean password login only |
| `profile.php` | Full "Biometric Login" card with WebAuthn + Face setup modals | Removed entirely — clean profile page |

### 🗑️ Removed Files
| File | Reason |
|------|--------|
| `face_verify.php` | Face recognition handler — not needed |
| `webauthn_register.php` | Fingerprint/WebAuthn handler — not needed |

### ✨ New Features Added
| Feature | File | Description |
|---------|------|-------------|
| **🎖️ Skill Badges** | `skill_badges.php` | Bronze/Silver/Gold badges per category (1/3/7 completions). Shows earnings per skill, progress bars, badge grid. |
| **✨ Smart Recommendations** | `task_recommendations.php` | AJAX endpoint that matches open tasks to a student's listed skills and past application categories. Shown on dashboard. |
| **⚡ Quick Apply (AJAX)** | `quick_apply.php` | JSON endpoint for submitting bids without page reload. Validates bid, proposal length, duplicate check, notifies employer. |
| **📊 Stats Card on Profile** | `profile.php` | New stats panel: total earned, tasks completed, avg rating, skills count — with live GitHub/LinkedIn/Portfolio links. |
| **🚪 Logout in sidebars** | All dashboard pages | Added a clear logout link at the bottom of every sidebar. |
| **Sidebar consistency** | `profile.php` | Both candidate and employer get clean, consistent, complete sidebar nav. |

---

## 📁 Full File Map

```
campusly_v3/
├── index.php                  Public landing page
├── login.php                  ✅ FIXED — clean password login, no biometric
├── register.php               Student / Employer registration
├── forgot_password.php        Password reset by email code
├── reset_password.php         Reset password handler
├── logout.php                 Session destroy
│
├── candidate_dashboard.php    ✅ FIXED — no duplicate sidebar links
├── browse_tasks.php           Search & filter open tasks
├── task_view.php              Task detail + bid form
├── my_applications.php        Track all bids
├── my_earnings.php            Earnings history
├── saved_tasks.php            Bookmarked tasks
├── submit_task.php            Submit completed work
├── review_task.php            Leave star review
├── leaderboard.php            Top earners ranking
├── notifications.php          All notifications
├── messages.php               Direct messaging
│
├── skill_badges.php           ✨ NEW — Skill badge system
├── task_recommendations.php   ✨ NEW — AJAX skill-matched recommendations
├── quick_apply.php            ✨ NEW — AJAX fast-apply endpoint
│
├── employer_dashboard.php     Employer home
├── post_task.php              Create a new task
├── manage_tasks.php           View/delete own tasks
├── view_applicants.php        Review bids, select/reject
├── close_task.php             Mark task complete
├── payment_history.php        Payments sent
│
├── profile.php                ✅ FIXED — no biometric section, + stats card
├── view_student_profile.php   View any student's public profile
├── admin_dashboard.php        Admin overview
│
├── db.php                     Database connection + helpers
├── campusly_v3.sql            ✅ FIXED — import this, biometric columns removed
│
├── includes/
│   ├── head.php               HTML <head> + CSS link
│   ├── navbar.php             Top nav with notification badges
│   └── footer.php            Footer + main.js
│
└── assets/
    ├── css/style.css          Full dark glassmorphism design system
    └── js/main.js             Toast, modal, stars, nav scroll helpers
```

---

## 💡 Tips for Your Professor Demo

- Import `campusly_v3.sql` — it includes 6 demo students, 3 employers, 8 tasks, applications, payments, reviews and messages already set up
- Login as **student@demo.com** to see the full candidate experience including recommendations and badges
- Login as **employer@demo.com** to post tasks and review applicants  
- Login as **admin@campusly.com** to see the admin panel

---

*Built with PHP 8+, MySQL, vanilla CSS & JS. No Composer dependencies.*
