# Junxtion Deployment Guide

## Requirements

- PHP 8.0+ with extensions: pdo_mysql, json, mbstring, openssl
- MySQL 5.7+ or MariaDB 10.3+
- HTTPS enabled (required for PWA and Yoco)
- cPanel/Xneelo shared hosting compatible

---

## Repository Structure

The repo is designed to be cloned directly into your `public_html/` folder:

```
/public_html/             # Clone repo here (web root)
├── .htaccess             # Security & routing
├── index.php             # Main entry
├── error.php             # Error pages
├── admin/                # Admin panel
│   ├── index.php         # Login
│   ├── dashboard.php
│   ├── orders.php
│   ├── kitchen.php
│   └── menu.php
├── app/                  # Customer webapp
│   ├── index.php
│   ├── home.php
│   ├── menu.php
│   ├── cart.php
│   ├── orders.php
│   └── profile.php
├── api/                  # API endpoints
│   ├── index.php         # Router entry
│   ├── config/
│   ├── lib/
│   ├── services/
│   └── routes/
├── assets/               # Static files
│   ├── css/
│   ├── js/
│   └── images/
├── pwa/                  # PWA files
├── uploads/              # User uploads
├── migrations/           # Database SQL files
└── private/              # Config & cron (protected by .htaccess)
    ├── config.php
    ├── config.example.php
    └── cron/
```

**Note:** The `private/` folder includes an `.htaccess` that blocks all web access.
For additional security, you can move it outside public_html after deployment.

---

## Step 1: Upload Files

### Option A: Git Clone (Recommended)
```bash
cd /home/junxtbwaem/public_html
git clone https://github.com/AdriaanGeldenhuis/junxtion.git .
```

### Option B: FTP Upload
Upload all files directly to `public_html/`

### Set Permissions
```bash
find /home/junxtbwaem/public_html -type d -exec chmod 755 {} \;
find /home/junxtbwaem/public_html -type f -exec chmod 644 {} \;
chmod 600 /home/junxtbwaem/public_html/private/config.php
```

---

## Step 2: Database Setup

### Create Database
1. In cPanel > MySQL Databases
2. Create database: `junxtbwaem_db1`
3. Create user: `junxtbwaem_1`
4. Add user to database with ALL PRIVILEGES

### Run Migrations
```bash
mysql -u junxtbwaem_1 -p junxtbwaem_db1 < /home/junxtbwaem/migrations/000_run_all.sql
```

Or import via phpMyAdmin.

### Create Admin User
```sql
INSERT INTO users (phone, name, email, password_hash, role, verified, created_at)
VALUES (
  '0821234567',
  'Admin',
  'admin@junxtionapp.co.za',
  '$argon2id$v=19$m=65536,t=4,p=1$...', -- Generate with password_hash('YourPassword', PASSWORD_ARGON2ID)
  'admin',
  1,
  NOW()
);
```

Generate password hash in PHP:
```php
echo password_hash('YourSecurePassword123', PASSWORD_ARGON2ID);
```

---

## Step 3: Configuration

Copy and edit config file:
```bash
cp /home/junxtbwaem/public_html/private/config.example.php /home/junxtbwaem/public_html/private/config.php
```

Update these values:

```php
return [
    'db' => [
        'host' => 'dedi321.cpt1.host-h.net',
        'name' => 'junxtbwaem_db1',
        'user' => 'junxtbwaem_1',
        'pass' => 'YOUR_DB_PASSWORD',
    ],
    'app' => [
        'name' => 'Junxtion',
        'base_url' => 'https://junxtionapp.co.za',
        'timezone' => 'Africa/Johannesburg',
        'debug' => false, // Set false for production!
    ],
    'jwt' => [
        'secret' => 'GENERATE_32_CHAR_SECRET', // openssl rand -base64 32
        'expiry' => 86400,
    ],
    'yoco' => [
        'secret_key' => 'sk_live_xxxxx',
        'public_key' => 'pk_live_xxxxx',
        'webhook_secret' => 'whsec_xxxxx',
    ],
    'sms' => [
        'provider' => 'bulksms', // or 'log' for dev
        'api_key' => 'xxxxx',
        'api_secret' => 'xxxxx',
    ],
    'fcm' => [
        'project_id' => 'junxtion-app',
        'service_account_path' => '/home/junxtbwaem/public_html/private/firebase-sa.json',
    ],
];
```

---

## Step 4: Yoco Setup

1. Log into [Yoco Developer Portal](https://developer.yoco.com)
2. Get API keys (test first, then live)
3. Add webhook endpoint: `https://junxtionapp.co.za/api/webhooks/yoco`
4. Subscribe to events:
   - `payment.succeeded`
   - `payment.failed`
   - `refund.succeeded`
5. Copy webhook secret to config

---

## Step 5: Set Up Cron Jobs

In cPanel > Cron Jobs:

```bash
# Cleanup (every 5 minutes)
*/5 * * * * /usr/local/bin/php /home/junxtbwaem/public_html/private/cron/cleanup.php >> /home/junxtbwaem/logs/cron_cleanup.log 2>&1

# Order reminders (every minute)
* * * * * /usr/local/bin/php /home/junxtbwaem/public_html/private/cron/order_reminders.php >> /home/junxtbwaem/logs/cron_reminders.log 2>&1

# Daily report (midnight)
0 0 * * * /usr/local/bin/php /home/junxtbwaem/public_html/private/cron/daily_report.php >> /home/junxtbwaem/logs/cron_daily.log 2>&1
```

Create logs directory:
```bash
mkdir -p /home/junxtbwaem/logs
```

---

## Step 6: SSL/HTTPS

1. In cPanel > SSL/TLS
2. Install Let's Encrypt certificate
3. Enable "Force HTTPS Redirect"

---

## Step 7: Final Checks

### Test Endpoints
```bash
# Public menu
curl https://junxtionapp.co.za/api/menu

# Admin login
curl -X POST https://junxtionapp.co.za/api/staff/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@junxtionapp.co.za","password":"xxx"}'
```

### Verify Security
- [ ] `https://junxtionapp.co.za/private/` returns 403
- [ ] `https://junxtionapp.co.za/uploads/test.php` returns 403
- [ ] `.htaccess` rules active

### Test Payment Flow
1. Use Yoco test mode
2. Create order, complete payment
3. Verify webhook updates order

---

## Maintenance

### View Logs
```bash
tail -f /home/junxtbwaem/logs/cron_cleanup.log
```

### Clear Cache
```bash
rm -rf /home/junxtbwaem/public_html/private/cache/*
```

### Database Backup
```bash
mysqldump -u junxtbwaem_1 -p junxtbwaem_db1 > backup_$(date +%Y%m%d).sql
```

---

## Troubleshooting

### 500 Internal Server Error
- Check `/home/junxtbwaem/logs/error.log`
- Verify config.php exists and is readable
- Check file permissions

### Database Connection Failed
- Verify credentials in config.php
- Check MySQL user has proper grants
- Ensure database exists

### Yoco Webhook Not Working
- Check webhook URL is correct
- Verify webhook secret matches
- Check server can receive POST requests
- Look for webhook logs in audit_log table

### SMS Not Sending
- Verify SMS provider credentials
- Check SMS balance
- Use 'log' mode to debug (check logs)

---

## Native App Webview Configuration

For native iOS/Android wrapper:

```
URL: https://junxtionapp.co.za/app/home.php
User Agent: JunxtionApp/1.0 iOS (or Android)
```

Enable:
- JavaScript
- Local Storage
- Cookies
- File upload (for future image upload)

Handle:
- Deep links (junxtion://order/123)
- Push notification tokens -> POST to /api/customer/fcm-token
