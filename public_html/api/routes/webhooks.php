<?php
/**
 * Webhook Routes
 *
 * Endpoints for external service callbacks (Yoco, etc.)
 */

// Yoco payment webhook
route_post('/webhooks/yoco', function () {
    // Get raw body for signature verification
    $rawBody = Request::rawBody();

    if (empty($rawBody)) {
        Response::error('Empty request body', 400);
    }

    // Get headers
    $headers = [
        'webhook-id' => $_SERVER['HTTP_WEBHOOK_ID'] ?? '',
        'webhook-timestamp' => $_SERVER['HTTP_WEBHOOK_TIMESTAMP'] ?? '',
        'webhook-signature' => $_SERVER['HTTP_WEBHOOK_SIGNATURE'] ?? '',
    ];

    // Validate required headers
    if (empty($headers['webhook-id']) || empty($headers['webhook-timestamp']) || empty($headers['webhook-signature'])) {
        // Log attempt
        $audit = new AuditService();
        $audit->logError('Yoco webhook missing headers', 'warning', [
            'headers' => $headers,
            'ip' => Request::ip(),
        ]);

        Response::error('Missing required headers', 400);
    }

    try {
        $service = new PaymentYocoService();
        $result = $service->processWebhook($rawBody, $headers);

        // Return 200 to acknowledge receipt
        Response::success($result);

    } catch (Exception $e) {
        // Log error but return 200 to prevent retries for validation errors
        $audit = new AuditService();
        $audit->logError('Yoco webhook error: ' . $e->getMessage(), 'error', [
            'body' => $rawBody,
            'headers' => $headers,
        ]);

        // Return 400 only for signature errors (so Yoco retries)
        if (strpos($e->getMessage(), 'signature') !== false) {
            Response::error($e->getMessage(), 403);
        }

        // Return 200 for other errors to prevent infinite retries
        Response::success(['status' => 'error', 'message' => $e->getMessage()]);
    }
});

// Health check for webhook endpoint
route_get('/webhooks/health', function () {
    Response::success([
        'status' => 'ok',
        'endpoint' => 'webhooks',
        'timestamp' => date('c'),
    ]);
});

// Webhook test endpoint (development only)
route_post('/webhooks/test', function () {
    // Only allow in debug mode
    if (!($GLOBALS['config']['app']['debug'] ?? false)) {
        Response::notFound();
    }

    $data = Request::getJson();

    // Log test webhook
    $audit = new AuditService();
    $audit->logActivity('webhook.test', 'Test webhook received', $data);

    Response::success([
        'received' => true,
        'data' => $data,
        'headers' => getallheaders(),
    ]);
});
