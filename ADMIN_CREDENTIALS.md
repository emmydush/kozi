# Household Connect - Admin Credentials

## Default Administrator Account

**Email:** admin@householdconnect.rw  
**Password:** admin123

## Security Instructions

⚠️ **IMPORTANT:** Change the default password immediately after first login!

1. Login to admin dashboard
2. Go to Settings > Change Password
3. Set a strong, unique password
4. Enable two-factor authentication if available

## Access URLs

- **Admin Dashboard:** `admin-dashboard.php`
- **Login Page:** `login.php`

## Database Setup

If you need to recreate the admin account:

1. Run `setup-admin.php`
2. Or execute SQL directly:
```sql
INSERT INTO users (name, email, password, role, is_verified, status, created_at) 
VALUES ('System Administrator', 'admin@householdconnect.rw', '$2y$10$...', 'admin', 1, 'active', NOW());
```

## Support

For technical support:
- Check database connection in `config.php`
- Verify file permissions
- Review error logs

---
*Generated automatically during system setup*
