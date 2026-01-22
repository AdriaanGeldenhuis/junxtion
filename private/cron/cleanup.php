<?php
/**
 * Cleanup Cron Job
 *
 * Run every 5-10 minutes via cron:
 * */5 * * * * php /path/to/private/cron/cleanup.php >> /path/to/logs/cron.log 2>&1
 *
 * Tasks:
 * - Expire old OTP codes
 * - Clean up expired sessions
 * - Cancel stale pending orders
 * - Delete old rate limit files
 */

declare(strict_types=1);

// Prevent web access
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

$startTime = microtime(true);
echo "[" . date('Y-m-d H:i:s') . "] Cleanup cron started\n";

// Load config
$configPath = __DIR__ . '/../config.php';
if (!file_exists($configPath)) {
    die("Config file not found\n");
}
$config = require $configPath;

// Database connection
try {
    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        $config['db']['host'],
        $config['db']['name'],
        $config['db']['charset'] ?? 'utf8mb4'
    );
    $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage() . "\n");
}

// ========================================
// 1. Expire Old OTP Codes (older than 10 minutes)
// ========================================
$stmt = $pdo->prepare("
    UPDATE otp_codes
    SET used = 1
    WHERE used = 0 AND expires_at < NOW()
");
$stmt->execute();
$expiredOtps = $stmt->rowCount();
echo "  - Expired OTP codes: {$expiredOtps}\n";

// ========================================
// 2. Clean Up Expired Sessions (older than 30 days)
// ========================================
$stmt = $pdo->prepare("
    DELETE FROM sessions
    WHERE expires_at < NOW()
");
$stmt->execute();
$expiredSessions = $stmt->rowCount();
echo "  - Expired sessions removed: {$expiredSessions}\n";

// ========================================
// 3. Cancel Stale Pending Payment Orders (older than 30 minutes)
// ========================================
$stmt = $pdo->prepare("
    UPDATE orders
    SET status = 'CANCELLED',
        cancelled_at = NOW(),
        cancellation_reason = 'Payment timeout - auto-cancelled'
    WHERE status = 'PENDING_PAYMENT'
    AND created_at < DATE_SUB(NOW(), INTERVAL 30 MINUTE)
");
$stmt->execute();
$cancelledOrders = $stmt->rowCount();
echo "  - Stale pending orders cancelled: {$cancelledOrders}\n";

// ========================================
// 4. Clean Up Old Rate Limit Files (older than 1 hour)
// ========================================
$rateLimitDir = __DIR__ . '/../../public_html/api/tmp/rate_limits';
$cleanedFiles = 0;
if (is_dir($rateLimitDir)) {
    $files = glob($rateLimitDir . '/*.json');
    $oneHourAgo = time() - 3600;

    foreach ($files as $file) {
        if (filemtime($file) < $oneHourAgo) {
            unlink($file);
            $cleanedFiles++;
        }
    }
}
echo "  - Rate limit files cleaned: {$cleanedFiles}\n";

// ========================================
// 5. Clean Up Old Audit Logs (older than 90 days)
// ========================================
$stmt = $pdo->prepare("
    DELETE FROM audit_log
    WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)
");
$stmt->execute();
$deletedLogs = $stmt->rowCount();
echo "  - Old audit logs removed: {$deletedLogs}\n";

// Done
$duration = round((microtime(true) - $startTime) * 1000, 2);
echo "[" . date('Y-m-d H:i:s') . "] Cleanup cron completed in {$duration}ms\n\n";
