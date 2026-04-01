/* # Database Schema Notes

- users: stores core alumni account credentials, authentication tokens, and event attendance status
- profiles: stores the public-facing biographical details and links for the user
- degrees: stores academic qualifications and university degrees
- employment_history: tracks past and present work experience
- professional_qualifications: stores additional certifications, licences, and short courses
- bids: handles the blind bidding system for the "Featured Alumnus" slots
- event_attendance: tracks which university events the alumni have attended (influences bidding limits)
- api_usage_logs: tracks API requests for rate-limiting and security monitoring
*/

-- Alumni Influencers Database Schema

CREATE DATABASE IF NOT EXISTS cw1_alumini_influencers DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cw1_alumini_influencers;

-- Drop tables in reverse dependency order
DROP TABLE IF EXISTS api_usage_logs;
DROP TABLE IF EXISTS event_attendance;
DROP TABLE IF EXISTS bids;
DROP TABLE IF EXISTS professional_qualifications;
DROP TABLE IF EXISTS employment_history;
DROP TABLE IF EXISTS degrees;
DROP TABLE IF EXISTS profiles;
DROP TABLE IF EXISTS users;

-- 1. Users: core account credentials and authentication tokens
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    is_verified TINYINT(1) NOT NULL DEFAULT 0,
    verification_token VARCHAR(255) NULL,
    verification_expires DATETIME NULL,
    reset_token VARCHAR(255) NULL,
    reset_expires DATETIME NULL,
    attended_event TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Profiles: public-facing bio and links
CREATE TABLE profiles (
    user_id INT UNSIGNED PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    bio TEXT NULL,
    linkedin_url VARCHAR(255) NULL,
    profile_image_url VARCHAR(255) NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_profiles_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Degrees: academic qualifications
CREATE TABLE degrees (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    degree_name VARCHAR(255) NOT NULL,
    institution VARCHAR(255) NOT NULL,
    official_url VARCHAR(255) NULL,
    completion_date DATE NOT NULL,
    CONSTRAINT fk_degrees_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Employment History: past and present work experience
CREATE TABLE employment_history (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    company VARCHAR(255) NOT NULL,
    role VARCHAR(255) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NULL,
    CONSTRAINT fk_employment_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Professional Qualifications: certifications, licences, and short courses
CREATE TABLE professional_qualifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    type ENUM('certification', 'licence', 'short_course') NOT NULL,
    title VARCHAR(255) NOT NULL,
    awarding_body VARCHAR(255) NOT NULL,
    url VARCHAR(255) NULL,
    completion_date DATE NOT NULL,
    CONSTRAINT fk_qualifications_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Bids: blind bidding system for Featured Alumnus slots
CREATE TABLE bids (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    target_date DATE NOT NULL,
    bid_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'won', 'lost') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_bids_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    INDEX idx_bids_target_date (target_date),
    INDEX idx_bids_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Event Attendance: tracks alumni event participation (enables 4th bid slot)
CREATE TABLE event_attendance (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    event_month VARCHAR(7) NOT NULL,
    attended TINYINT(1) NOT NULL DEFAULT 1,
    CONSTRAINT fk_events_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. API Usage Logs: tracks requests for rate-limiting and security monitoring
CREATE TABLE api_usage_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    endpoint VARCHAR(255) NOT NULL,
    method VARCHAR(10) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    accessed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_api_logs_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL,
    INDEX idx_api_logs_ip (ip_address),
    INDEX idx_api_logs_accessed_at (accessed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

