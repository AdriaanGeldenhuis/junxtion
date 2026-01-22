<?php
/**
 * Daily Report Cron Job
 *
 * Run daily at midnight via cron:
 * 0 0 * * * php /path/to/private/cron/daily_report.php >> /path/to/logs/cron.log 2>&1
 *
 * Generates daily summary of:
 * - Total orders and revenue
 * - Popular items
 * - Order status breakdown
 */

declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

$startTime = microtime(true);
echo "[" . date('Y-m-d H:i:s') . "] Daily report cron started\n";

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

// Get yesterday's date
$yesterday = date('Y-m-d', strtotime('-1 day'));
echo "  Generating report for: {$yesterday}\n";

// ========================================
// 1. Order Summary
// ========================================
$stmt = $pdo->prepare("
    SELECT
        COUNT(*) as total_orders,
        SUM(CASE WHEN status = 'COMPLETED' THEN 1 ELSE 0 END) as completed_orders,
        SUM(CASE WHEN status = 'CANCELLED' THEN 1 ELSE 0 END) as cancelled_orders,
        SUM(CASE WHEN status = 'COMPLETED' THEN total_cents ELSE 0 END) as total_revenue,
        AVG(CASE WHEN status = 'COMPLETED' THEN total_cents ELSE NULL END) as avg_order_value,
        SUM(CASE WHEN order_type = 'pickup' THEN 1 ELSE 0 END) as pickup_orders,
        SUM(CASE WHEN order_type = 'delivery' THEN 1 ELSE 0 END) as delivery_orders
    FROM orders
    WHERE DATE(created_at) = ?
");
$stmt->execute([$yesterday]);
$summary = $stmt->fetch();

echo "\n  === ORDER SUMMARY ===\n";
echo "  Total Orders: {$summary['total_orders']}\n";
echo "  Completed: {$summary['completed_orders']}\n";
echo "  Cancelled: {$summary['cancelled_orders']}\n";
echo "  Total Revenue: R" . number_format(($summary['total_revenue'] ?? 0) / 100, 2) . "\n";
echo "  Avg Order Value: R" . number_format(($summary['avg_order_value'] ?? 0) / 100, 2) . "\n";
echo "  Pickup Orders: {$summary['pickup_orders']}\n";
echo "  Delivery Orders: {$summary['delivery_orders']}\n";

// ========================================
// 2. Top Selling Items
// ========================================
$stmt = $pdo->prepare("
    SELECT
        oi.name_snapshot as item_name,
        SUM(oi.qty) as total_qty,
        SUM(oi.subtotal_cents) as total_sales
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    WHERE DATE(o.created_at) = ?
    AND o.status = 'COMPLETED'
    GROUP BY oi.item_id, oi.name_snapshot
    ORDER BY total_qty DESC
    LIMIT 10
");
$stmt->execute([$yesterday]);
$topItems = $stmt->fetchAll();

echo "\n  === TOP SELLING ITEMS ===\n";
foreach ($topItems as $i => $item) {
    $num = $i + 1;
    echo "  {$num}. {$item['item_name']} - {$item['total_qty']} sold (R" . number_format($item['total_sales'] / 100, 2) . ")\n";
}

// ========================================
// 3. Hourly Distribution
// ========================================
$stmt = $pdo->prepare("
    SELECT
        HOUR(created_at) as hour,
        COUNT(*) as order_count
    FROM orders
    WHERE DATE(created_at) = ?
    AND status != 'CANCELLED'
    GROUP BY HOUR(created_at)
    ORDER BY hour
");
$stmt->execute([$yesterday]);
$hourlyOrders = $stmt->fetchAll();

echo "\n  === HOURLY DISTRIBUTION ===\n";
foreach ($hourlyOrders as $hour) {
    $h = str_pad($hour['hour'], 2, '0', STR_PAD_LEFT);
    echo "  {$h}:00 - {$hour['order_count']} orders\n";
}

// ========================================
// 4. New Customers
// ========================================
$stmt = $pdo->prepare("
    SELECT COUNT(*) as new_customers
    FROM users
    WHERE DATE(created_at) = ?
    AND role = 'customer'
");
$stmt->execute([$yesterday]);
$newCustomers = $stmt->fetchColumn();

echo "\n  === NEW CUSTOMERS ===\n";
echo "  New sign-ups: {$newCustomers}\n";

// ========================================
// 5. Save Report to Database
// ========================================
$reportData = json_encode([
    'date' => $yesterday,
    'summary' => $summary,
    'top_items' => $topItems,
    'hourly' => $hourlyOrders,
    'new_customers' => $newCustomers
], JSON_PRETTY_PRINT);

$stmt = $pdo->prepare("
    INSERT INTO settings (setting_key, setting_value, updated_at)
    VALUES (?, ?, NOW())
    ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()
");
$stmt->execute(["daily_report_{$yesterday}", $reportData]);

echo "\n  Report saved to database.\n";

// Done
$duration = round((microtime(true) - $startTime) * 1000, 2);
echo "\n[" . date('Y-m-d H:i:s') . "] Daily report cron completed in {$duration}ms\n\n";
