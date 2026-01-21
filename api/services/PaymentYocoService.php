<?php
/**
 * Yoco Payment Service
 *
 * Handles Yoco checkout creation, webhook verification, and refunds
 */

class PaymentYocoService
{
    private Database $db;
    private AuditService $audit;
    private array $config;

    public function __construct()
    {
        $this->db = $GLOBALS['db'];
        $this->audit = new AuditService();
        $this->config = $GLOBALS['config']['yoco'] ?? [];
    }

    /**
     * Create Yoco checkout for order
     */
    public function createCheckout(int $orderId): array
    {
        // Get order
        $order = $this->db->queryOne(
            "SELECT id, order_number, total_cents, status, payment_status
             FROM orders WHERE id = ?",
            [$orderId]
        );

        if (!$order) {
            throw new Exception('Order not found');
        }

        if ($order['status'] !== 'PENDING_PAYMENT') {
            throw new Exception('Order is not pending payment');
        }

        if ($order['payment_status'] === 'paid') {
            throw new Exception('Order is already paid');
        }

        $secretKey = $this->config['secret_key'] ?? '';
        if (empty($secretKey)) {
            throw new Exception('Yoco secret key not configured');
        }

        $baseUrl = $GLOBALS['config']['app']['base_url'] ?? 'https://junxtionapp.co.za';

        // Create checkout via Yoco API
        $checkoutData = [
            'amount' => $order['total_cents'],
            'currency' => 'ZAR',
            'successUrl' => "{$baseUrl}/app/order-success.php?order={$order['order_number']}",
            'cancelUrl' => "{$baseUrl}/app/checkout.php?order={$order['order_number']}&cancelled=1",
            'failureUrl' => "{$baseUrl}/app/checkout.php?order={$order['order_number']}&failed=1",
            'metadata' => [
                'order_id' => $orderId,
                'order_number' => $order['order_number'],
            ],
        ];

        $response = $this->apiRequest('POST', '/checkouts', $checkoutData);

        if (!isset($response['id'])) {
            throw new Exception('Failed to create Yoco checkout: ' . ($response['message'] ?? 'Unknown error'));
        }

        // Store payment record
        $paymentId = $this->db->insert('payments', [
            'order_id' => $orderId,
            'provider' => 'yoco',
            'checkout_id' => $response['id'],
            'status' => 'pending',
            'amount_cents' => $order['total_cents'],
            'currency' => 'ZAR',
            'redirect_url' => $response['redirectUrl'] ?? null,
            'raw_response' => json_encode($response),
        ]);

        $this->audit->log('payment.initiated', 'payment', $paymentId, null, [
            'checkout_id' => $response['id'],
            'amount' => $order['total_cents'],
        ]);

        return [
            'checkout_id' => $response['id'],
            'redirect_url' => $response['redirectUrl'],
            'payment_id' => $paymentId,
        ];
    }

    /**
     * Process Yoco webhook
     */
    public function processWebhook(string $rawBody, array $headers): array
    {
        // Extract headers
        $webhookId = $headers['webhook-id'] ?? $headers['HTTP_WEBHOOK_ID'] ?? '';
        $timestamp = $headers['webhook-timestamp'] ?? $headers['HTTP_WEBHOOK_TIMESTAMP'] ?? '';
        $signature = $headers['webhook-signature'] ?? $headers['HTTP_WEBHOOK_SIGNATURE'] ?? '';

        // Verify signature
        $webhookSecret = $this->config['webhook_secret'] ?? '';
        if (empty($webhookSecret)) {
            throw new Exception('Webhook secret not configured');
        }

        $isValid = Crypto::verifyYocoWebhook(
            $webhookId,
            $timestamp,
            $rawBody,
            $signature,
            $webhookSecret
        );

        // Log the webhook event (even if invalid, for debugging)
        $eventData = json_decode($rawBody, true) ?? [];
        $eventType = $eventData['type'] ?? 'unknown';
        $checkoutId = $eventData['payload']['id'] ?? null;
        $paymentId = $eventData['payload']['paymentId'] ?? null;

        $eventLogId = $this->db->insert('payment_events', [
            'provider' => 'yoco',
            'checkout_id' => $checkoutId,
            'payment_id' => $paymentId,
            'event_type' => $eventType,
            'event_id' => $webhookId,
            'event_json' => $rawBody,
            'signature_valid' => $isValid ? 1 : 0,
            'processed' => 0,
        ]);

        if (!$isValid) {
            $this->db->update('payment_events', [
                'process_error' => 'Invalid signature',
            ], 'id = ?', [$eventLogId]);

            throw new Exception('Invalid webhook signature');
        }

        // Process based on event type
        try {
            $result = $this->handleWebhookEvent($eventType, $eventData['payload'] ?? []);

            $this->db->update('payment_events', [
                'processed' => 1,
                'processed_at' => date('Y-m-d H:i:s'),
            ], 'id = ?', [$eventLogId]);

            return $result;

        } catch (Exception $e) {
            $this->db->update('payment_events', [
                'process_error' => $e->getMessage(),
            ], 'id = ?', [$eventLogId]);

            throw $e;
        }
    }

    /**
     * Handle specific webhook event types
     */
    private function handleWebhookEvent(string $eventType, array $payload): array
    {
        switch ($eventType) {
            case 'checkout.succeeded':
            case 'payment.succeeded':
                return $this->handlePaymentSucceeded($payload);

            case 'checkout.failed':
            case 'payment.failed':
                return $this->handlePaymentFailed($payload);

            case 'refund.succeeded':
                return $this->handleRefundSucceeded($payload);

            case 'refund.failed':
                return $this->handleRefundFailed($payload);

            default:
                return ['status' => 'ignored', 'event_type' => $eventType];
        }
    }

    /**
     * Handle successful payment
     */
    private function handlePaymentSucceeded(array $payload): array
    {
        $checkoutId = $payload['id'] ?? null;
        $paymentId = $payload['paymentId'] ?? null;

        if (!$checkoutId) {
            throw new Exception('Missing checkout ID');
        }

        // Find payment record
        $payment = $this->db->queryOne(
            "SELECT * FROM payments WHERE checkout_id = ?",
            [$checkoutId]
        );

        if (!$payment) {
            throw new Exception('Payment record not found for checkout: ' . $checkoutId);
        }

        if ($payment['status'] === 'succeeded') {
            // Already processed - idempotent
            return ['status' => 'already_processed', 'order_id' => $payment['order_id']];
        }

        // Update payment
        $this->db->update('payments', [
            'status' => 'succeeded',
            'payment_id' => $paymentId,
            'raw_response' => json_encode($payload),
            'completed_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$payment['id']]);

        // Update order
        $this->db->update('orders', [
            'payment_status' => 'paid',
        ], 'id = ?', [$payment['order_id']]);

        // Transition order to PLACED
        $orderService = new OrderService();
        $orderService->updateStatus($payment['order_id'], 'PLACED', null, 'Payment succeeded via Yoco');

        $this->audit->log('payment.succeeded', 'payment', $payment['id'], [
            'status' => 'pending',
        ], [
            'status' => 'succeeded',
        ]);

        // Trigger notifications
        $this->notifyPaymentSuccess($payment['order_id']);

        return [
            'status' => 'processed',
            'order_id' => $payment['order_id'],
            'payment_id' => $payment['id'],
        ];
    }

    /**
     * Handle failed payment
     */
    private function handlePaymentFailed(array $payload): array
    {
        $checkoutId = $payload['id'] ?? null;
        $failureReason = $payload['failureReason'] ?? 'Unknown error';

        if (!$checkoutId) {
            throw new Exception('Missing checkout ID');
        }

        $payment = $this->db->queryOne(
            "SELECT * FROM payments WHERE checkout_id = ?",
            [$checkoutId]
        );

        if (!$payment) {
            throw new Exception('Payment record not found');
        }

        $this->db->update('payments', [
            'status' => 'failed',
            'failure_reason' => $failureReason,
            'raw_response' => json_encode($payload),
        ], 'id = ?', [$payment['id']]);

        $this->db->update('orders', [
            'payment_status' => 'failed',
        ], 'id = ?', [$payment['order_id']]);

        $this->audit->log('payment.failed', 'payment', $payment['id'], null, [
            'failure_reason' => $failureReason,
        ]);

        return [
            'status' => 'processed',
            'order_id' => $payment['order_id'],
        ];
    }

    /**
     * Handle successful refund
     */
    private function handleRefundSucceeded(array $payload): array
    {
        $refundId = $payload['id'] ?? null;

        $refund = $this->db->queryOne(
            "SELECT * FROM refunds WHERE refund_id = ?",
            [$refundId]
        );

        if (!$refund) {
            // Try to find by checkout relationship
            return ['status' => 'refund_record_not_found'];
        }

        $this->db->update('refunds', [
            'status' => 'succeeded',
            'raw_response' => json_encode($payload),
            'completed_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$refund['id']]);

        // Update order payment status
        $this->db->update('orders', [
            'payment_status' => 'refunded',
        ], 'id = ?', [$refund['order_id']]);

        $this->audit->log('payment.refunded', 'refund', $refund['id']);

        return ['status' => 'processed', 'refund_id' => $refund['id']];
    }

    /**
     * Handle failed refund
     */
    private function handleRefundFailed(array $payload): array
    {
        $refundId = $payload['id'] ?? null;
        $failureReason = $payload['failureReason'] ?? 'Unknown error';

        $this->db->update('refunds', [
            'status' => 'failed',
            'failure_reason' => $failureReason,
            'raw_response' => json_encode($payload),
        ], 'refund_id = ?', [$refundId]);

        return ['status' => 'processed'];
    }

    /**
     * Initiate refund (manager only)
     */
    public function initiateRefund(int $orderId, ?int $amountCents = null, ?string $reason = null): array
    {
        // Get order and payment
        $order = $this->db->queryOne(
            "SELECT o.*, p.id as payment_id, p.checkout_id, p.amount_cents as paid_amount
             FROM orders o
             INNER JOIN payments p ON o.id = p.order_id AND p.status = 'succeeded'
             WHERE o.id = ?",
            [$orderId]
        );

        if (!$order) {
            throw new Exception('Order not found or not paid');
        }

        if ($order['payment_status'] === 'refunded') {
            throw new Exception('Order already refunded');
        }

        // Default to full refund
        $refundAmount = $amountCents ?? $order['paid_amount'];

        if ($refundAmount > $order['paid_amount']) {
            throw new Exception('Refund amount exceeds payment amount');
        }

        // Generate idempotency key
        $idempotencyKey = Crypto::generateIdempotencyKey();

        // Check for existing refund with same key
        $existing = $this->db->queryOne(
            "SELECT * FROM refunds WHERE order_id = ? AND status IN ('pending', 'processing')",
            [$orderId]
        );

        if ($existing) {
            throw new Exception('Refund already in progress');
        }

        // Create refund record
        $refundRecordId = $this->db->insert('refunds', [
            'payment_id' => $order['payment_id'],
            'order_id' => $orderId,
            'idempotency_key' => $idempotencyKey,
            'amount_cents' => $refundAmount,
            'reason' => $reason,
            'status' => 'pending',
            'initiated_by_staff_id' => Auth::user()['id'] ?? null,
        ]);

        // Call Yoco refund API
        $response = $this->apiRequest(
            'POST',
            "/checkouts/{$order['checkout_id']}/refund",
            [],
            ['Idempotency-Key' => $idempotencyKey]
        );

        if (isset($response['id'])) {
            $this->db->update('refunds', [
                'refund_id' => $response['id'],
                'status' => 'processing',
                'raw_response' => json_encode($response),
            ], 'id = ?', [$refundRecordId]);

            $this->audit->log('payment.refund_initiated', 'refund', $refundRecordId, null, [
                'amount' => $refundAmount,
                'reason' => $reason,
            ]);

            return [
                'refund_id' => $refundRecordId,
                'yoco_refund_id' => $response['id'],
                'status' => 'processing',
            ];
        }

        // Refund failed
        $this->db->update('refunds', [
            'status' => 'failed',
            'failure_reason' => $response['message'] ?? 'Unknown error',
            'raw_response' => json_encode($response),
        ], 'id = ?', [$refundRecordId]);

        throw new Exception('Refund failed: ' . ($response['message'] ?? 'Unknown error'));
    }

    /**
     * Make API request to Yoco
     */
    private function apiRequest(string $method, string $endpoint, array $data = [], array $extraHeaders = []): array
    {
        $secretKey = $this->config['secret_key'] ?? '';
        $baseUrl = $this->config['api_url'] ?? 'https://payments.yoco.com/api';

        $url = $baseUrl . $endpoint;

        $headers = [
            'Authorization: Bearer ' . $secretKey,
            'Content-Type: application/json',
        ];

        foreach ($extraHeaders as $key => $value) {
            $headers[] = "{$key}: {$value}";
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if (!empty($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception("Yoco API error: {$error}");
        }

        $response = json_decode($result, true) ?? [];
        $response['_http_code'] = $httpCode;

        return $response;
    }

    /**
     * Notify on successful payment
     */
    private function notifyPaymentSuccess(int $orderId): void
    {
        try {
            $notificationService = new NotificationService();
            $notificationService->sendOrderNotification($orderId, 'PLACED');
        } catch (Exception $e) {
            // Log but don't fail
            $this->audit->logError('Failed to send payment notification: ' . $e->getMessage());
        }
    }

    /**
     * Get payment status for order
     */
    public function getPaymentStatus(int $orderId): ?array
    {
        return $this->db->queryOne(
            "SELECT id, checkout_id, status, amount_cents, created_at, completed_at
             FROM payments
             WHERE order_id = ?
             ORDER BY created_at DESC
             LIMIT 1",
            [$orderId]
        );
    }
}
