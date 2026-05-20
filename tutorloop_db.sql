-- TutorLoop Database Schema
-- Generated from analysis of all PHP files in the project
-- Database: tutorloop_db

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS tutorloop_db;
USE tutorloop_db;

-- ============================================
-- TABLE: users
-- ============================================
-- Stores all user accounts (both tutors and tutees)
-- Contains authentication credentials, profile information,
-- and role assignment. The is_verified field controls account status.
CREATE TABLE IF NOT EXISTS users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('tutor', 'tutee') NOT NULL,
    student_id VARCHAR(50),
    profile_pic VARCHAR(255) DEFAULT 'default.png',
    is_verified TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: subjects
-- ============================================
-- Master list of all available subjects that can be tutored.
-- Used for categorizing tutors and filtering search results.
CREATE TABLE IF NOT EXISTS subjects (
    subject_id INT PRIMARY KEY AUTO_INCREMENT,
    subject_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: tutor_profiles
-- ============================================
-- Extended profile information specific to tutors.
-- Contains bio, contact info, and performance metrics.
-- One-to-one relationship with users table (tutor_id = user_id).
CREATE TABLE IF NOT EXISTS tutor_profiles (
    tutor_id INT PRIMARY KEY,
    description TEXT,
    phone_number VARCHAR(20),
    tutoring_rate DECIMAL(10,2) DEFAULT 0.00,
    average_rating DECIMAL(3,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tutor_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: tutor_subjects
-- ============================================
-- Junction table linking tutors to subjects they can teach.
-- Allows tutors to teach multiple subjects with different rates.
-- Each record represents one subject a tutor offers.
CREATE TABLE IF NOT EXISTS tutor_subjects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tutor_id INT NOT NULL,
    subject_id INT NOT NULL,
    rate DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tutor_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(subject_id) ON DELETE CASCADE,
    UNIQUE KEY unique_tutor_subject (tutor_id, subject_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: tutor_availability
-- ============================================
-- Stores the weekly availability schedule for each tutor per subject.
-- Used to validate session requests and display available time slots.
-- day_of_week stores the day name (e.g., 'Monday', 'Tuesday').
CREATE TABLE IF NOT EXISTS tutor_availability (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tutor_subject_id INT NOT NULL,
    day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tutor_subject_id) REFERENCES tutor_subjects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: sessions
-- ============================================
-- Core table for managing tutoring session requests and bookings.
-- Tracks the complete lifecycle from request to completion.
-- session_status tracks the current state of each session.
CREATE TABLE IF NOT EXISTS sessions (
    session_id INT PRIMARY KEY AUTO_INCREMENT,
    tutor_id INT NOT NULL,
    tutee_id INT NOT NULL,
    subject_id INT NOT NULL,
    requested_schedule DATETIME NOT NULL,
    session_status ENUM('Pending', 'Accepted', 'Ongoing', 'Declined', 'Completed', 'Cancelled') DEFAULT 'Pending',
    request_note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tutor_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (tutee_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(subject_id) ON DELETE CASCADE,
    INDEX idx_tutor_status (tutor_id, session_status),
    INDEX idx_tutee_status (tutee_id, session_status),
    INDEX idx_requested_schedule (requested_schedule)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: messages
-- ============================================
-- Stores all private messages between users.
-- Supports tutor-tutee communication for session coordination.
-- date_sent records when the message was sent.
CREATE TABLE IF NOT EXISTS messages (
    message_id INT PRIMARY KEY AUTO_INCREMENT,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    message_content TEXT NOT NULL,
    date_sent TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_read TINYINT(1) DEFAULT 0,
    FOREIGN KEY (sender_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_conversation (sender_id, receiver_id),
    INDEX idx_receiver_date (receiver_id, date_sent DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: feedback_ratings
-- ============================================
-- Stores ratings and feedback submitted by tutees for tutors.
-- Used to calculate tutor average ratings displayed on profiles.
-- Each session can only have one feedback entry.
CREATE TABLE IF NOT EXISTS feedback_ratings (
    feedback_id INT PRIMARY KEY AUTO_INCREMENT,
    session_id INT NOT NULL UNIQUE,
    tutor_id INT NOT NULL,
    rating TINYINT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    feedback_comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES sessions(session_id) ON DELETE CASCADE,
    FOREIGN KEY (tutor_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_tutor_ratings (tutor_id, rating)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SAMPLE DATA (Optional - for testing)
-- ============================================

-- Insert default subjects
INSERT INTO subjects (subject_name, description) VALUES
('Mathematics', 'Algebra, Calculus, Statistics, and more'),
('Science', 'Physics, Chemistry, Biology'),
('English', 'Grammar, Writing, Literature'),
('Programming', 'Web Development, Python, Java, C++'),
('History', 'World History, Philippine History'),
('Filipino', 'Wikang Filipino, Panitikan'),
('Social Studies', 'Economics, Political Science'),
('Arts', 'Visual Arts, Music'),
('Physical Education', 'Sports, Health, Fitness'),
('Technology', 'Computer Applications, ICT');

-- Insert a sample admin/test user (password: test123)
-- NOTE: In production, remove or secure this sample data
-- INSERT INTO users (name, email, password, role, student_id, is_verified) VALUES
-- ('Test Tutor', 'tutor@test.students.isatu.edu.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'tutor', '2024-0001-T', 1),
-- ('Test Tutee', 'tutee@test.students.isatu.edu.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'tutee', '2024-0002-S', 1);