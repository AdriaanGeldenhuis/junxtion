# Junxtion Cron Jobs

## Setup Instructions (cPanel / Xneelo)

Add the following cron jobs in cPanel:

### 1. Cleanup Job (every 5 minutes)
```
*/5 * * * * /usr/local/bin/php /home/junxtbwaem/private/cron/cleanup.php >> /home/junxtbwaem/logs/cron_cleanup.log 2>&1
```

Handles:
- Expire old OTP codes
- Clean expired sessions
- Cancel stale pending payment orders (30 min timeout)
- Remove old rate limit files
- Delete old audit logs (90+ days)

### 2. Order Reminders (every minute)
```
* * * * * /usr/local/bin/php /home/junxtbwaem/private/cron/order_reminders.php >> /home/junxtbwaem/logs/cron_reminders.log 2>&1
```

Handles:
- Alert staff when orders wait >10 minutes
- Remind customers of ready orders not collected

### 3. Daily Report (midnight)
```
0 0 * * * /usr/local/bin/php /home/junxtbwaem/private/cron/daily_report.php >> /home/junxtbwaem/logs/cron_daily.log 2>&1
```

Generates daily summary:
- Total orders and revenue
- Popular items
- Hourly distribution
- New customers

## Manual Execution

Test cron jobs manually:

```bash
# Cleanup
php /home/junxtbwaem/private/cron/cleanup.php

# Order reminders
php /home/junxtbwaem/private/cron/order_reminders.php

# Daily report
php /home/junxtbwaem/private/cron/daily_report.php
```

## Log Files

Create the logs directory if it doesn't exist:
```bash
mkdir -p /home/junxtbwaem/logs
```

Monitor logs:
```bash
tail -f /home/junxtbwaem/logs/cron_cleanup.log
```

## Security Notes

- All cron scripts check for CLI execution only
- Scripts are stored in `/private/` (outside web root)
- Database credentials loaded from config.php
