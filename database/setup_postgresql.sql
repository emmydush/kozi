-- Household Connect Database Setup for PostgreSQL
-- Create database if not exists
CREATE DATABASE household_connect;
\c household_connect;

-- Create enum types
CREATE TYPE user_role AS ENUM ('employer', 'worker');
CREATE TYPE job_type_enum AS ENUM ('cleaning', 'cooking', 'childcare', 'eldercare', 'gardening', 'other');
CREATE TYPE application_status AS ENUM ('pending', 'under_review', 'accepted', 'rejected');
CREATE TYPE booking_status_enum AS ENUM ('pending', 'confirmed', 'completed', 'cancelled');
CREATE TYPE message_status AS ENUM ('sent', 'delivered', 'read');
CREATE TYPE payment_status_enum AS ENUM ('pending', 'paid', 'cancelled');

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role user_role NOT NULL,
    phone VARCHAR(20),
    location VARCHAR(255),
    bio TEXT,
    skills JSONB,
    experience INT DEFAULT 0,
    expected_salary DECIMAL(10, 2),
    availability VARCHAR(100),
    profile_image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create trigger for updated_at
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_users_updated_at BEFORE UPDATE ON users
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    token_hash VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used_at TIMESTAMP NULL,
    requested_ip VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_password_reset_tokens_hash ON password_reset_tokens(token_hash);
CREATE INDEX IF NOT EXISTS idx_password_reset_tokens_user_id ON password_reset_tokens(user_id);

-- Jobs table
CREATE TABLE IF NOT EXISTS jobs (
    id SERIAL PRIMARY KEY,
    employer_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    job_type job_type_enum NOT NULL,
    salary DECIMAL(10, 2) NOT NULL,
    location VARCHAR(255) NOT NULL,
    work_hours VARCHAR(100) NOT NULL,
    status booking_status_enum DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employer_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TRIGGER update_jobs_updated_at BEFORE UPDATE ON jobs
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Job applications table
CREATE TABLE IF NOT EXISTS job_applications (
    id SERIAL PRIMARY KEY,
    job_id INT NOT NULL,
    worker_id INT NOT NULL,
    status application_status DEFAULT 'pending',
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    FOREIGN KEY (worker_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE (job_id, worker_id)
);

CREATE TRIGGER update_job_applications_updated_at BEFORE UPDATE ON job_applications
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Bookings table
CREATE TABLE IF NOT EXISTS bookings (
    id SERIAL PRIMARY KEY,
    job_id INT NOT NULL,
    worker_id INT NOT NULL,
    employer_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE,
    status booking_status_enum DEFAULT 'pending',
    total_amount DECIMAL(10, 2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    FOREIGN KEY (worker_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (employer_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TRIGGER update_bookings_updated_at BEFORE UPDATE ON bookings
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Messages table
CREATE TABLE IF NOT EXISTS messages (
    id SERIAL PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    job_id INT NULL,
    message TEXT NOT NULL,
    status message_status DEFAULT 'sent',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE SET NULL
);

-- Reviews table
CREATE TABLE IF NOT EXISTS reviews (
    id SERIAL PRIMARY KEY,
    reviewer_id INT NOT NULL,
    reviewee_id INT NOT NULL,
    job_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    review TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewee_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    UNIQUE (reviewer_id, reviewee_id, job_id)
);

-- Earnings table (for workers)
CREATE TABLE IF NOT EXISTS earnings (
    id SERIAL PRIMARY KEY,
    worker_id INT NOT NULL,
    job_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    payment_status payment_status_enum DEFAULT 'pending',
    payment_date DATE,
    work_date DATE NOT NULL,
    hours_worked DECIMAL(4, 2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (worker_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE
);

CREATE TRIGGER update_earnings_updated_at BEFORE UPDATE ON earnings
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
