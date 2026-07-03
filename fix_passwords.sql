-- ============================================================
--  CAMPUSLY — Credential Fix (run if login fails)
--  phpMyAdmin → Select campusly DB → SQL tab → paste & Run
-- ============================================================
USE campusly;

-- Fix admin: email=nit@gmail.com, password=Nit@1234
UPDATE users
SET email    = 'nit@gmail.com',
    password = '$2b$10$sdpAFeTxGeIZow70iLmWtOCyyCVZDuOpiH/oHlgDOUlDiNJgd7Byi'
WHERE role = 'admin';

-- Fix all other demo accounts: password=Demo@1234
UPDATE users
SET password = '$2b$10$fnz05mOrrDyjxYqocybIueopYgY.5zO1QS./b7qodT.OxTW7og8SK'
WHERE role IN ('candidate','employer');

SELECT name, email, role FROM users ORDER BY role, name;
