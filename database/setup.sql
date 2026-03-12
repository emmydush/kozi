-- Household Connect Database Setup
-- Create database if not exists
CREATE DATABASE IF NOT EXISTS household_connect;
USE household_connect;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('employer', 'worker') NOT NULL,
    phone VARCHAR(20),
    location VARCHAR(255),
    bio TEXT,
    skills JSON,
    experience INT DEFAULT 0,
    expected_salary DECIMAL(10, 2),
    availability VARCHAR(100),
    profile_image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Jobs table
CREATE TABLE IF NOT EXISTS jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employer_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    job_type ENUM('cleaning', 'cooking', 'childcare', 'eldercare', 'gardening', 'other') NOT NULL,
    salary DECIMAL(10, 2) NOT NULL,
    location VARCHAR(255) NOT NULL,
    work_hours VARCHAR(100) NOT NULL,
    status ENUM('active', 'filled', 'closed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employer_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Job applications table
CREATE TABLE IF NOT EXISTS job_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    worker_id INT NOT NULL,
    status ENUM('pending', 'under_review', 'accepted', 'rejected') DEFAULT 'pending',
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    FOREIGN KEY (worker_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_application (job_id, worker_id)
);

-- Bookings table
CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    worker_id INT NOT NULL,
    employer_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE,
    status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
    total_amount DECIMAL(10, 2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    FOREIGN KEY (worker_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (employer_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Messages table
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    job_id INT NULL,
    message TEXT NOT NULL,
    status ENUM('sent', 'delivered', 'read') DEFAULT 'sent',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE SET NULL
);

-- Reviews table
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reviewer_id INT NOT NULL,
    reviewee_id INT NOT NULL,
    job_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    review TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewee_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    UNIQUE KEY unique_review (reviewer_id, reviewee_id, job_id)
);

-- Earnings table (for workers)
CREATE TABLE IF NOT EXISTS earnings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    worker_id INT NOT NULL,
    job_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    payment_status ENUM('pending', 'paid', 'cancelled') DEFAULT 'pending',
    payment_date DATE,
    work_date DATE NOT NULL,
    hours_worked DECIMAL(4, 2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (worker_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE
);

-- Insert sample data for testing
INSERT INTO users (name, email, password, role, phone, location, bio, skills, experience, expected_salary, availability) VALUES
('John Mukiza', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employer', '+250788123456', 'Kigali', 'Looking for reliable household workers', NULL, 0, 0, 'full-time'),
('Marie Uwimana', 'marie@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'worker', '+250788234567', 'Kicukiro', 'Experienced house cleaner and childcare provider', '["cleaning", "childcare"]', 3, 50000, 'full-time'),
('Grace Kantengwa', 'grace@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employer', '+250788345678', 'Gasabo', 'Need gardening and childcare help', NULL, 0, 0, 'part-time'),
('Joseph Niyonzima', 'joseph@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'worker', '+250788456789', 'Nyarugenge', 'Specialized in eldercare and cooking', '["eldercare", "cooking"]', 5, 80000, 'full-time');

-- Insert sample jobs
INSERT INTO jobs (employer_id, title, description, job_type, salary, location, work_hours, status) VALUES
(1, 'House Cleaner Needed', 'Looking for an experienced house cleaner for a family home in Kigali. Responsibilities include cleaning, laundry, and occasional cooking.', 'cleaning', 50000, 'Kigali', 'Full-time', 'active'),
(3, 'Childcare Provider', 'Need a reliable childcare provider for 2 children (ages 3 and 5). Must have experience with toddlers and be patient.', 'childcare', 35000, 'Kicukiro', 'Part-time', 'active'),
(1, 'Weekend Gardener', 'Looking for someone to maintain garden and lawn on weekends. Knowledge of plants and basic landscaping required.', 'gardening', 20000, 'Gasabo', 'Weekend Only', 'active'),
(3, 'Elderly Care Assistant', 'Seeking a compassionate caregiver for an elderly person. Duties include companionship, medication reminders, and light housekeeping.', 'eldercare', 80000, 'Nyarugenge', 'Full-time', 'active');

-- Insert sample applications
INSERT INTO job_applications (job_id, worker_id, status) VALUES
(1, 2, 'accepted'),
(2, 2, 'pending'),
(3, 4, 'under_review'),
(4, 4, 'accepted');

-- Insert sample bookings
INSERT INTO bookings (job_id, worker_id, employer_id, start_date, end_date, status, total_amount) VALUES
(1, 2, 1, '2024-12-01', '2024-12-31', 'confirmed', 50000),
(4, 4, 3, '2024-12-15', '2024-12-31', 'confirmed', 80000);

-- Insert sample messages
INSERT INTO messages (sender_id, receiver_id, job_id, message) VALUES
(1, 2, 1, 'Hi Marie, I would like to discuss the house cleaning job with you.'),
(2, 1, 1, 'Hello John! I am available and interested in the position.'),
(3, 4, 4, 'Thank you for applying to the eldercare position.'),
(4, 3, 4, 'You are welcome. I have 5 years of experience in eldercare.');

-- Insert sample reviews
INSERT INTO reviews (reviewer_id, reviewee_id, job_id, rating, review) VALUES
(1, 2, 1, 5, 'Excellent house cleaner! Very thorough and reliable.'),
(3, 4, 4, 4, 'Good caregiver, very patient with elderly person.');

-- Insert sample earnings
INSERT INTO earnings (worker_id, job_id, amount, payment_status, payment_date, work_date, hours_worked) VALUES
(2, 1, 50000, 'paid', '2024-12-01', '2024-12-01', 40.00),
(4, 4, 80000, 'pending', NULL, '2024-12-15', 35.00);
