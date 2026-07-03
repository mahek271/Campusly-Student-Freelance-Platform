-- ============================================================
--  CAMPUSLY v3 FIXED — Full Database Schema + Demo Data
--  Biometric columns removed (face_token, webauthn_*)
--  Import: phpMyAdmin → Import → campusly_v3.sql → Go
--  Passwords: Demo@1234 (all accounts)
-- ============================================================
SET NAMES utf8mb4;
SET foreign_key_checks = 0;
SET sql_mode = '';

CREATE DATABASE IF NOT EXISTS campusly CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE campusly;

DROP TABLE IF EXISTS saved_tasks, messages, password_resets, notifications, payments, reviews, applications, tasks, users;

-- ─────────────────────────────────
-- USERS
-- ─────────────────────────────────
CREATE TABLE users (
  id                      INT AUTO_INCREMENT PRIMARY KEY,
  name                    VARCHAR(100)  NOT NULL,
  email                   VARCHAR(150)  NOT NULL UNIQUE,
  password                VARCHAR(255)  NOT NULL,
  role                    ENUM('candidate','employer','admin') DEFAULT 'candidate',
  university              VARCHAR(250),
  company_name            VARCHAR(150),
  bio                     TEXT,
  location                VARCHAR(100),
  skills                  VARCHAR(800),
  github                  VARCHAR(300),
  linkedin                VARCHAR(300),
  portfolio               VARCHAR(300),
  avatar                  VARCHAR(300)  DEFAULT NULL,
  is_banned               TINYINT(1)    DEFAULT 0,
  created_at              TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────
-- TASKS
-- ─────────────────────────────────
CREATE TABLE tasks (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  employer_id  INT           NOT NULL,
  title        VARCHAR(255)  NOT NULL,
  description  TEXT,
  category     VARCHAR(100),
  price        DECIMAL(10,2) DEFAULT 0.00,
  location     VARCHAR(100)  DEFAULT 'Remote',
  skills       VARCHAR(800),
  deadline     DATE,
  duration     VARCHAR(80),
  urgency      ENUM('urgent','high','medium','low') DEFAULT 'medium',
  status       ENUM('open','assigned','completed','cancelled') DEFAULT 'open',
  created_at   TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_status   (status),
  INDEX idx_category (category),
  INDEX idx_employer (employer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────
-- APPLICATIONS / BIDS  (full table with all constraints)
-- ─────────────────────────────────
CREATE TABLE applications (
  id                INT AUTO_INCREMENT PRIMARY KEY,
  task_id           INT           NOT NULL,
  candidate_id      INT           NOT NULL,
  bid_amount        DECIMAL(10,2) NOT NULL COMMENT 'Min 100, max 150% of task price',
  delivery_days     INT           NOT NULL DEFAULT 7 COMMENT 'Between 1 and 90',
  proposal_text     TEXT          COMMENT 'Min 50 chars, max 2000 chars',
  status            ENUM('pending','selected','rejected','completed') DEFAULT 'pending',
  submission_link   VARCHAR(500),
  submission_note   TEXT,
  applied_at        TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  updated_at        TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_bid (task_id, candidate_id),
  INDEX idx_candidate (candidate_id),
  INDEX idx_status    (status),
  INDEX idx_task      (task_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────
-- REVIEWS
-- ─────────────────────────────────
CREATE TABLE reviews (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  task_id      INT      NOT NULL,
  reviewer_id  INT      NOT NULL,
  reviewee_id  INT      NOT NULL,
  rating       TINYINT  NOT NULL DEFAULT 5,
  comment      TEXT,
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_review (task_id, reviewer_id),
  INDEX idx_reviewee (reviewee_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────
-- PAYMENTS
-- ─────────────────────────────────
CREATE TABLE payments (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  task_id         INT           NOT NULL,
  application_id  INT           NOT NULL,
  employer_id     INT           NOT NULL,
  candidate_id    INT           NOT NULL,
  amount          DECIMAL(10,2) NOT NULL,
  status          ENUM('pending','released','refunded') DEFAULT 'pending',
  payment_method  VARCHAR(50)   DEFAULT 'platform_wallet',
  created_at      TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_candidate (candidate_id),
  INDEX idx_employer  (employer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────
-- NOTIFICATIONS
-- ─────────────────────────────────
CREATE TABLE notifications (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  user_id    INT      NOT NULL,
  message    TEXT     NOT NULL,
  link       VARCHAR(300),
  is_read    TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user (user_id, is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────
-- MESSAGES
-- ─────────────────────────────────
CREATE TABLE messages (
  id       INT AUTO_INCREMENT PRIMARY KEY,
  from_id  INT NOT NULL,
  to_id    INT NOT NULL,
  task_id  INT NOT NULL,
  body     TEXT NOT NULL,
  is_read  TINYINT(1) DEFAULT 0,
  sent_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_thread   (from_id, to_id, task_id),
  INDEX idx_to_unread (to_id, is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────
-- SAVED TASKS
-- ─────────────────────────────────
CREATE TABLE saved_tasks (
  id       INT AUTO_INCREMENT PRIMARY KEY,
  user_id  INT NOT NULL,
  task_id  INT NOT NULL,
  saved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_saved (user_id, task_id),
  INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────
-- PASSWORD RESETS
-- ─────────────────────────────────
CREATE TABLE password_resets (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  email      VARCHAR(150) NOT NULL,
  code       VARCHAR(10)  NOT NULL,
  expires_at DATETIME     NOT NULL,
  created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET foreign_key_checks = 1;

-- ============================================================
-- DEMO DATA  (password = Demo@1234 for ALL)
-- ============================================================
INSERT INTO users (name,email,password,role,university,company_name,bio,location,skills,github,linkedin) VALUES
('Admin','nit@gmail.com','$2b$10$sdpAFeTxGeIZow70iLmWtOCyyCVZDuOpiH/oHlgDOUlDiNJgd7Byi','admin',NULL,NULL,'Platform Administrator',NULL,NULL,NULL,NULL),
('Aryan Mehta','student@demo.com','$2b$10$fnz05mOrrDyjxYqocybIueopYgY.5zO1QS./b7qodT.OxTW7og8SK','candidate','IIT Bombay',NULL,'Full-stack dev | 3rd year CS @ IIT Bombay. React, Node.js, Firebase.','Mumbai, India','React,Node.js,Firebase,MongoDB,Tailwind CSS,TypeScript','https://github.com/aryanmehta','https://linkedin.com/in/aryanmehta'),
('Priya Sharma','priya.sharma@student.com','$2b$10$fnz05mOrrDyjxYqocybIueopYgY.5zO1QS./b7qodT.OxTW7og8SK','candidate','Delhi University',NULL,'UI/UX Designer. Figma, Adobe Suite, user research.','Delhi, India','Figma,Adobe XD,Photoshop,Illustrator,UI Design,Branding','https://github.com/priyasharma','https://linkedin.com/in/priyasharma'),
('Rohit Kumar','rohit.k@bits.ac.in','$2b$10$fnz05mOrrDyjxYqocybIueopYgY.5zO1QS./b7qodT.OxTW7og8SK','candidate','BITS Pilani',NULL,'Data Science enthusiast. Python, ML, Kaggle.','Pilani, Rajasthan','Python,Machine Learning,Pandas,TensorFlow,SQL','https://github.com/rohitkumar','https://linkedin.com/in/rohitkumar'),
('Ananya Iyer','ananya.iyer@vit.edu','$2b$10$fnz05mOrrDyjxYqocybIueopYgY.5zO1QS./b7qodT.OxTW7og8SK','candidate','VIT Vellore',NULL,'Content creator & digital marketer. SEO, copywriting.','Vellore, Tamil Nadu','Content Writing,SEO,Social Media,Copywriting',NULL,'https://linkedin.com/in/ananyaiyer'),
('Dev Patel','dev.patel@nirma.ac.in','$2b$10$fnz05mOrrDyjxYqocybIueopYgY.5zO1QS./b7qodT.OxTW7og8SK','candidate','Nirma University',NULL,'Flutter & Android developer. 4 apps on Play Store.','Ahmedabad, Gujarat','Flutter,Android,Kotlin,Firebase','https://github.com/devpatel','https://linkedin.com/in/devpatel'),
('Sneha Reddy','sneha.r@hyderabad.edu','$2b$10$fnz05mOrrDyjxYqocybIueopYgY.5zO1QS./b7qodT.OxTW7og8SK','candidate','Osmania University',NULL,'Graphic designer & video editor. Adobe Creative Suite expert.','Hyderabad, Telangana','Photoshop,Illustrator,Premiere Pro,After Effects,Motion Graphics',NULL,'https://linkedin.com/in/snehareddy'),
('TechStartup Pvt Ltd','employer@demo.com','$2b$10$fnz05mOrrDyjxYqocybIueopYgY.5zO1QS./b7qodT.OxTW7og8SK','employer',NULL,'TechStartup Pvt. Ltd.','Fast-growing SaaS startup.','Bangalore, India',NULL,NULL,NULL),
('Nexus Digital Agency','nexus@agency.com','$2b$10$fnz05mOrrDyjxYqocybIueopYgY.5zO1QS./b7qodT.OxTW7og8SK','employer',NULL,'Nexus Digital Agency','Full-service digital marketing agency.','Mumbai, India',NULL,NULL,NULL),
('EduTech Solutions','edutech@company.com','$2b$10$fnz05mOrrDyjxYqocybIueopYgY.5zO1QS./b7qodT.OxTW7og8SK','employer',NULL,'EduTech Solutions','Building the future of online learning.','Pune, India',NULL,NULL,NULL);

INSERT INTO tasks (employer_id,title,description,category,price,location,skills,deadline,duration,urgency,status) VALUES
(8,'Build a React Analytics Dashboard','We need a React developer to build an interactive analytics dashboard.\n\nDeliverables:\n- Responsive dashboard with 5+ chart types\n- Filter by date range, user segment\n- Light/dark mode toggle\n- Clean commented code','Development',8500,'Remote','React,Recharts,Tailwind CSS,TypeScript',DATE_ADD(CURDATE(),INTERVAL 14 DAY),'7-10 days','high','open'),
(9,'Brand Identity for D2C Fashion Startup','Create a complete brand identity for our sustainable fashion brand.\n\nDeliverables:\n- Logo (primary, icon, wordmark)\n- Color palette and typography\n- Social media templates\n- Brand guidelines PDF','Design',6000,'Remote','Figma,Illustrator,Branding,Logo Design',DATE_ADD(CURDATE(),INTERVAL 10 DAY),'5-7 days','urgent','completed'),
(10,'Python Automated E-commerce Scraper','Build a scraper for Amazon and Flipkart generating weekly Excel reports.\n\n- BeautifulSoup/Playwright\n- APScheduler automation\n- Excel export with charts\n- Full logging','Development',5500,'Remote','Python,BeautifulSoup,Pandas,Excel',DATE_ADD(CURDATE(),INTERVAL 7 DAY),'4-6 days','medium','open'),
(8,'10 SEO Blog Articles for SaaS','10 long-form blog articles (1500-2000 words) for project management SaaS.\n\n- Keyword-optimised\n- Research-backed\n- Google Docs format','Content Writing',4000,'Remote','Content Writing,SEO,Research',DATE_ADD(CURDATE(),INTERVAL 12 DAY),'5 days','medium','open'),
(9,'30-Day Social Media Content Calendar','30-day content calendar for Instagram, LinkedIn, Twitter.\n\nDeliverables:\n- 30 post captions\n- 15 Canva/Figma graphics\n- Hashtag strategy','Marketing',7000,'Remote','Social Media,Canva,Copywriting',DATE_ADD(CURDATE(),INTERVAL 15 DAY),'7-10 days','medium','open'),
(10,'Flutter Expense Tracker App','Cross-platform expense tracker.\n\nFeatures:\n- Add/edit/delete expenses\n- Charts (monthly/weekly)\n- Budget alerts\n- CSV export\n- Light/dark theme','Development',12000,'Remote','Flutter,Dart,Firebase,SQLite',DATE_ADD(CURDATE(),INTERVAL 21 DAY),'14 days','high','open'),
(8,'EV Industry Market Research Report 2025','Market research on EV industry in India.\n\n- 20+ page PDF\n- TAM/SAM/SOM analysis\n- Top 10 competitors\n- Growth projections','Research',5000,'Remote','Market Research,Data Analysis,Excel',DATE_ADD(CURDATE(),INTERVAL 20 DAY),'10 days','low','open'),
(9,'Edit 10 YouTube Shorts','Edit 10 x 60-second YouTube Shorts.\n\n- Snappy cuts, transitions\n- On-screen captions\n- Color grading\n- 9:16 vertical format','Video & Animation',3500,'Remote','Video Editing,Premiere Pro,After Effects',DATE_ADD(CURDATE(),INTERVAL 8 DAY),'4 days','urgent','open');

INSERT INTO applications (task_id,candidate_id,bid_amount,delivery_days,proposal_text,status,submission_link,applied_at) VALUES
(1,2,7800,9,'I have built 3 Recharts dashboards for real clients including a fintech startup. TypeScript, clean code, full docs guaranteed.','pending',NULL,DATE_SUB(NOW(),INTERVAL 2 DAY)),
(1,3,8500,7,'As a data science student I use React and Recharts daily. Interactive filters, responsive layout, unit tests included.','pending',NULL,DATE_SUB(NOW(),INTERVAL 1 DAY)),
(1,6,7500,10,'Full-stack dev, 2+ years React. Similar dashboards in internship. Mobile-friendly. D3 fallback available.','pending',NULL,DATE_SUB(NOW(),INTERVAL 3 HOUR)),
(2,4,5500,6,'Design is my passion. Have created 5+ brand identities including fashion brands. SVG, PNG, PDF and full brand guidelines.','completed','https://drive.google.com/demo-brand-assets',DATE_SUB(NOW(),INTERVAL 12 DAY)),
(2,5,5800,5,'UI/UX and branding specialist. Created identities for 3 D2C startups. Organised Figma files and PDF guide.','rejected',NULL,DATE_SUB(NOW(),INTERVAL 12 DAY)),
(3,2,5000,5,'Built Amazon and Flipkart scrapers for e-commerce research. Playwright for JS-heavy sites, APScheduler for automation.','pending',NULL,DATE_SUB(NOW(),INTERVAL 1 DAY)),
(3,6,5500,6,'Python is my strongest language. 4 scraping projects. Rotating proxies for anti-bot. Well-documented.','pending',NULL,DATE_SUB(NOW(),INTERVAL 4 HOUR)),
(5,5,6500,9,'Content strategy is my core strength. I manage social for 2 startups currently. Canva Pro graphics.','pending',NULL,DATE_SUB(NOW(),INTERVAL 1 DAY));

INSERT INTO payments (task_id,application_id,employer_id,candidate_id,amount,status,created_at) VALUES
(2,4,9,4,5500.00,'released',DATE_SUB(NOW(),INTERVAL 5 DAY));

UPDATE tasks SET status='completed' WHERE id=2;

INSERT INTO reviews (task_id,reviewer_id,reviewee_id,rating,comment,created_at) VALUES
(2,9,4,5,'Rohit delivered exceptional brand work. Understood our vision immediately. Logos clean, versatile, perfect for our Gen Z brand. Delivered early!',DATE_SUB(NOW(),INTERVAL 4 DAY)),
(2,4,9,4,'Great client — clear brief, fast responses, payment released within hours. Would work with Nexus Digital again.',DATE_SUB(NOW(),INTERVAL 4 DAY));

INSERT INTO notifications (user_id,message,link,is_read) VALUES
(2,'Your bid on "React Analytics Dashboard" is under review.','task_view.php?id=1',0),
(4,'You were selected for "Brand Identity for D2C Fashion Startup"!','my_applications.php',1),
(4,'Payment of Rs.5500 released for your completed task!','my_earnings.php',1),
(9,'New bid of Rs.7800 on your task: React Analytics Dashboard','view_applicants.php?id=1',0);

INSERT INTO messages (from_id,to_id,task_id,body,is_read,sent_at) VALUES
(9,4,2,'Hi Rohit! Can you include the source Figma file along with exports?',1,DATE_SUB(NOW(),INTERVAL 10 DAY)),
(4,9,2,'Absolutely! Ill include the organised Figma file with all layers named. Sending tomorrow.',1,DATE_SUB(NOW(),INTERVAL 10 DAY)),
(9,4,2,'Perfect! Looking forward to the final assets.',1,DATE_SUB(NOW(),INTERVAL 9 DAY));

INSERT INTO saved_tasks (user_id,task_id) VALUES (2,3),(2,6),(3,1);

-- ============================================================
--  v5 NEW TABLES
-- ============================================================

CREATE TABLE IF NOT EXISTS disputes (
  id           INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  task_id      INT UNSIGNED    NOT NULL,
  raised_by    INT UNSIGNED    NOT NULL,
  reason       VARCHAR(100)    NOT NULL,
  description  TEXT            NOT NULL,
  status       ENUM('open','reviewing','resolved','dismissed') DEFAULT 'open',
  admin_note   TEXT            DEFAULT NULL,
  resolved_at  DATETIME        DEFAULT NULL,
  created_at   DATETIME        DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  INDEX idx_task   (task_id),
  INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS portfolio_items (
  id           INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  user_id      INT UNSIGNED    NOT NULL,
  title        VARCHAR(200)    NOT NULL,
  description  TEXT            NOT NULL,
  url          VARCHAR(500)    DEFAULT NULL,
  image_url    VARCHAR(500)    DEFAULT NULL,
  tags         VARCHAR(400)    DEFAULT NULL,
  created_at   DATETIME        DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS task_invites (
  id           INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  task_id      INT UNSIGNED    NOT NULL,
  employer_id  INT UNSIGNED    NOT NULL,
  student_id   INT UNSIGNED    NOT NULL,
  message      TEXT            DEFAULT NULL,
  created_at   DATETIME        DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_invite (task_id, student_id),
  INDEX idx_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
--  v5 ADDITIONAL TABLES (append to existing DB)
--  Safe to run multiple times — uses IF NOT EXISTS
-- ============================================================

USE campusly;

-- Skill badges earned by candidates
CREATE TABLE IF NOT EXISTS skill_badges (
  id          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  user_id     INT UNSIGNED  NOT NULL,
  skill_name  VARCHAR(100)  NOT NULL,
  score       INT           NOT NULL DEFAULT 0,
  earned_at   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_user_skill (user_id, skill_name),
  INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Notification preferences per user
CREATE TABLE IF NOT EXISTS user_notification_prefs (
  id                   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id              INT UNSIGNED NOT NULL,
  notif_bid_received   TINYINT(1)   DEFAULT 1,
  notif_bid_selected   TINYINT(1)   DEFAULT 1,
  notif_bid_rejected   TINYINT(1)   DEFAULT 1,
  notif_payment        TINYINT(1)   DEFAULT 1,
  notif_message        TINYINT(1)   DEFAULT 1,
  notif_review         TINYINT(1)   DEFAULT 1,
  notif_task_update    TINYINT(1)   DEFAULT 1,
  notif_invite         TINYINT(1)   DEFAULT 1,
  PRIMARY KEY (id),
  UNIQUE KEY uq_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
