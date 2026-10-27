-- Create database (if not exists)
CREATE DATABASE IF NOT EXISTS exam_timetable;
USE exam_timetable;

-- --------------------------------------------------------
-- Table: users (authentication)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- --------------------------------------------------------
-- Table: courses (optional – to store parsed course data)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_code VARCHAR(20) NOT NULL UNIQUE,
    enrolment INT NOT NULL,
    level INT NOT NULL
);

-- --------------------------------------------------------
-- Table: venues (optional – to store venue data)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS venues (
    id INT AUTO_INCREMENT PRIMARY KEY,
    venue_name VARCHAR(20) NOT NULL UNIQUE,
    capacity INT NOT NULL
);

-- --------------------------------------------------------
-- Table: timetable_assignments (stores generated timetables per user)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS timetable_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    course_code VARCHAR(20) NOT NULL,
    venue_json TEXT NOT NULL,          -- JSON array of venue names
    day VARCHAR(20) NOT NULL,
    time_slot VARCHAR(20) NOT NULL,    -- e.g., "7:00-10:00"
    slot_order INT NOT NULL,           -- 0..35 for chronological order
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Optional: Indexes for faster queries
CREATE INDEX idx_user_id ON timetable_assignments(user_id);
CREATE INDEX idx_course_code ON timetable_assignments(course_code);