<?php
/**
 * Order Reminders Cron Job
 *
 * Run every minute via cron:
 * * * * * * php /path/to/private/cron/order_reminders.php >> /path/to/logs/cron.log 2>&1
 *
 * Tasks:
 * - Remind staff of orders waiting too long
 * - Notify customers when order is ready
 */

declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

$startTime = microtime(true);
echo "[" . date('Y-m-d H:i:s') . "] Order reminders cron started\n";

// Load config and services
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

// Load notification service
require_once __DIR__ . '/../../public_html/api/services/NotificationService.php';
$notificationService = new NotificationService($pdo, $config);

// ========================================
// 1. Alert staff for orders waiting > 10 minutes in PLACED status
// ========================================
$stmt = $pdo->prepare("
    SELECT o.id, o.order_type, o.total_cents, o.created_at
    FROM orders o
    WHERE o.status = 'PLACED'
    AND o.created_at < DATE_SUB(NOW(), INTERVAL 10 MINUTE)
    AND NOT EXISTS (
        SELECT 1 FROM notifications n
        WHERE n.type = 'order_wait_alert'
        AND n.reference_id = o.id
        AND n.created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
    )
    LIMIT 10
");
$stmt->execute();
$waitingOrders = $stmt->fetchAll();

foreach ($waitingOrders as $order) {
    $waitMinutes = floor((time() - strtotime($order['created_at'])) / 60);

    // Log notification to prevent duplicate alerts
    $logStmt = $pdo->prepare("
        INSERT INTO notifications (type, channel, recipient, title, body, reference_type, reference_id, status)
        VALUES ('order_wait_alert', 'internal', 'staff', ?, ?, 'order', ?, 'sent')
    ");
    $logStmt->execute([
        "Order #{$order['id']} waiting",
        "Order #{$order['id']} has been waiting for {$waitMinutes} minutes!",
        $order['id']
    ]);

    echo "  - Alert: Order #{$order['id']} waiting {$waitMinutes} minutes\n";
}

// ========================================
// 2. Remind customers of ready orders (pickup) not collected after 20 mins
// ========================================
$stmt = $pdo->prepare("
    SELECT o.id, o.customer_id, u.phone, u.fcm_token
    FROM orders o
    JOIN users u ON o.customer_id = u.id
    WHERE o.status = 'READY'
    AND o.order_type = 'pickup'
    AND o.ready_at < DATE_SUB(NOW(), INTERVAL 20 MINUTE)
    AND NOT EXISTS (
        SELECT 1 FROM notifications n
        WHERE n.type = 'pickup_reminder'
        AND n.reference_id = o.id
        AND n.created_at > DATE_SUB(NOW(), INTERVAL 30 MINUTE)
    )
    LIMIT 10
");
$stmt->execute();
$readyOrders = $stmt->fetchAll();

foreach ($readyOrders as $order) {
    // Send push notification if FCM token exists
    if (!empty($order['fcm_token'])) {
        try {
            $notificationService->sendPush(
                $order['fcm_token'],
                "Your order is ready!",
                "Order #{$order['id']} is waiting for pickup. Please collect it soon!"
            );
        } catch (Exception $e) {
            echo "  - Push failed for order #{$order['id']}: " . $e->getMessage() . "\n";
        }
    }

    // Log notification
    $logStmt = $pdo->prepare("
        INSERT INTO notifications (type, channel, recipient, title, body, reference_type, reference_id, status)
        VALUES ('pickup_reminder', 'push', ?, ?, ?, 'order', ?, 'sent')
    ");
    $logStmt->execute([
        $order['phone'],
        "Your order is ready!",
        "Order #{$order['id']} is waiting for pickup",
        $order['id']
    ]);

    echo "  - Pickup reminder sent for order #{$order['id']}\n";
}

// Done
$duration = round((microtime(true) - $startTime) * 1000, 2);
echo "[" . date('Y-m-d H:i:s') . "] Order reminders cron completed in {$duration}ms\n\n";
