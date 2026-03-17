-- PostgreSQL Database Initialization Script
-- Run this script to set up the complete Household Connect database

-- Create database
CREATE DATABASE household_connect;
\c household_connect;

-- Run schema setup
\i schema_postgresql.sql

-- Run admin setup
\i admin_setup_postgresql.sql

-- Create database user for application
CREATE USER household_app WITH PASSWORD 'Jesuslove@12';
GRANT CONNECT ON DATABASE household_connect TO household_app;
GRANT USAGE ON SCHEMA public TO household_app;
GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA public TO household_app;
GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public TO household_app;

-- Set default permissions for future tables
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT SELECT, INSERT, UPDATE, DELETE ON TABLES TO household_app;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT USAGE, SELECT ON SEQUENCES TO household_app;
