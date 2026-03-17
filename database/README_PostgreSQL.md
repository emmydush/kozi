# PostgreSQL Database Setup for Household Connect

This directory contains PostgreSQL database scripts for the Household Connect application.

## Files Overview

- `schema_postgresql.sql` - Main database schema with all tables and indexes
- `setup_postgresql.sql` - Alternative setup script (simplified version)
- `admin_setup_postgresql.sql` - Admin functionality and system configuration
- `init_postgresql.sql` - Complete initialization script that runs all setup files
- `README_PostgreSQL.md` - This documentation file

## Key Changes from MySQL

### Data Types
- `INT AUTO_INCREMENT` → `SERIAL PRIMARY KEY`
- `ENUM` types → Custom PostgreSQL enum types
- `JSON` → `JSONB` (better performance in PostgreSQL)
- `BOOLEAN` remains the same
- `TIMESTAMP` remains the same

### Auto-updating Timestamps
- MySQL's `ON UPDATE CURRENT_TIMESTAMP` → PostgreSQL trigger function
- Created `update_updated_at_column()` function for all tables with `updated_at`

### Indexes
- MySQL's partial indexes `WHERE role = 'admin'` → PostgreSQL partial indexes (supported)
- `UNIQUE KEY` → `UNIQUE` constraint

### Database Creation
- MySQL's `CREATE DATABASE IF NOT EXISTS` → PostgreSQL `CREATE DATABASE`
- Added `\c household_connect` to switch to the database context

## Setup Instructions

### Option 1: Complete Initialization (Recommended)
```bash
psql -U postgres -f init_postgresql.sql
```

### Option 2: Step-by-Step Setup
```bash
# Create database and run schema
psql -U postgres -f schema_postgresql.sql

# Run admin setup
psql -U postgres -d household_connect -f admin_setup_postgresql.sql
```

### Option 3: Alternative Setup (Simplified)
```bash
psql -U postgres -f setup_postgresql.sql
```

## Post-Setup Configuration

1. **Update Application Database Connection**
   - Host: localhost (or your PostgreSQL server)
   - Port: 5432 (default)
   - Database: household_connect
   - User: household_app (or your preferred user)
   - Password: Set in `init_postgresql.sql`

2. **Change Default Admin Password**
   - Default admin: admin@householdconnect.com / admin123
   - Change immediately after first login

3. **Configure Environment Variables**
   ```env
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=household_connect
   DB_USERNAME=household_app
   DB_PASSWORD=your_secure_password
   ```

## Migration Notes

### Enum Types
PostgreSQL requires enum types to be created before they can be used in table definitions. All enum types are created at the beginning of the schema files.

### JSON vs JSONB
- PostgreSQL uses `JSONB` instead of `JSON` for better performance
- JSONB is stored in a binary format and supports indexing

### Foreign Key Constraints
- All foreign key constraints are preserved
- `ON DELETE CASCADE` and `ON DELETE SET NULL` behaviors maintained

### Indexes
- All MySQL indexes have been converted to PostgreSQL equivalents
- Performance should be equivalent or better with PostgreSQL

## Testing the Setup

1. Connect to the database:
   ```bash
   psql -U household_app -d household_connect
   ```

2. Verify tables exist:
   ```sql
   \dt
   ```

3. Check admin user:
   ```sql
   SELECT * FROM users WHERE role = 'admin';
   ```

4. Test admin stats view:
   ```sql
   SELECT * FROM admin_stats;
   ```

## Troubleshooting

### Permission Issues
If you encounter permission errors, ensure the PostgreSQL user has sufficient privileges:
```sql
GRANT ALL PRIVILEGES ON DATABASE household_connect TO household_app;
```

### Enum Type Conflicts
If enum types already exist with different definitions, you may need to drop them first:
```sql
DROP TYPE IF EXISTS user_role CASCADE;
```

### Trigger Function Conflicts
The `update_updated_at_column()` function is created once and reused. If it exists with a different definition, drop it first:
```sql
DROP FUNCTION IF EXISTS update_updated_at_column();
```

## Performance Considerations

PostgreSQL offers several performance advantages over MySQL:

1. **JSONB Indexing**: JSON columns can be indexed for better query performance
2. **Partial Indexes**: More efficient indexing for specific conditions
3. **Better Query Planner**: Generally more sophisticated query optimization
4. **Concurrency**: Better handling of concurrent operations

## Security Recommendations

1. Use strong passwords for database users
2. Limit application user permissions to only what's needed
3. Enable SSL connections in production
4. Regularly update PostgreSQL to the latest version
5. Consider using connection pooling for high-traffic applications
