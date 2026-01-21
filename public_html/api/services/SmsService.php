<?php
/**
 * SMS Service
 *
 * Handles sending SMS messages (OTP, notifications)
 * Supports multiple providers with log mode for development
 */

class SmsService
{
    private array $config;
    private string $provider;

    public function __construct()
    {
        $this->config = $GLOBALS['config']['sms'] ?? [];
        $this->provider = $this->config['provider'] ?? 'log';
    }

    /**
     * Send SMS message
     */
    public function send(string $phone, string $message): array
    {
        // Normalize phone number
        $phone = Validator::normalizePhone($phone);

        // Route to provider
        switch ($this->provider) {
            case 'log':
                return $this->sendViaLog($phone, $message);
            case 'clickatell':
                return $this->sendViaClickatell($phone, $message);
            case 'bulksms':
                return $this->sendViaBulkSms($phone, $message);
            default:
                return $this->sendViaLog($phone, $message);
        }
    }

    /**
     * Send OTP code
     */
    public function sendOtp(string $phone, string $code): array
    {
        $appName = $GLOBALS['config']['app']['name'] ?? 'Junxtion';
        $message = "{$appName}: Your verification code is {$code}. Valid for 5 minutes. Do not share this code.";

        return $this->send($phone, $message);
    }

    /**
     * Send order notification
     */
    public function sendOrderNotification(string $phone, string $orderNumber, string $status): array
    {
        $appName = $GLOBALS['config']['app']['name'] ?? 'Junxtion';

        $messages = [
            'PLACED' => "{$appName}: Order #{$orderNumber} received! We're preparing it now.",
            'ACCEPTED' => "{$appName}: Order #{$orderNumber} has been accepted!",
            'IN_PREP' => "{$appName}: Order #{$orderNumber} is being prepared.",
            'READY' => "{$appName}: Order #{$orderNumber} is ready for pickup!",
            'OUT_FOR_DELIVERY' => "{$appName}: Order #{$orderNumber} is on its way!",
            'COMPLETED' => "{$appName}: Order #{$orderNumber} completed. Thank you!",
            'CANCELLED' => "{$appName}: Order #{$orderNumber} has been cancelled. Contact us for details.",
        ];

        $message = $messages[$status] ?? "{$appName}: Order #{$orderNumber} status: {$status}";

        return $this->send($phone, $message);
    }

    /**
     * Log mode - write to file instead of sending
     */
    private function sendViaLog(string $phone, string $message): array
    {
        $logPath = $GLOBALS['config']['paths']['logs'] ?? __DIR__ . '/../../private/logs';
        $logFile = $logPath . '/sms.log';

        // Ensure log directory exists
        if (!is_dir($logPath)) {
            mkdir($logPath, 0755, true);
        }

        $logEntry = sprintf(
            "[%s] TO: %s | MSG: %s\n",
            date('Y-m-d H:i:s'),
            $phone,
            $message
        );

        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);

        return [
            'success' => true,
            'provider' => 'log',
            'message_id' => 'log_' . time(),
            'phone' => $phone,
        ];
    }

    /**
     * Clickatell provider
     */
    private function sendViaClickatell(string $phone, string $message): array
    {
        $apiKey = $this->config['api_key'] ?? '';

        if (empty($apiKey)) {
            return ['success' => false, 'error' => 'Clickatell API key not configured'];
        }

        $url = 'https://platform.clickatell.com/messages/http/send';
        $params = [
            'apiKey' => $apiKey,
            'to' => preg_replace('/[^0-9]/', '', $phone),
            'content' => $message,
        ];

        $response = $this->httpPost($url, $params);

        if ($response['success'] && isset($response['data']['messages'][0]['apiMessageId'])) {
            return [
                'success' => true,
                'provider' => 'clickatell',
                'message_id' => $response['data']['messages'][0]['apiMessageId'],
                'phone' => $phone,
            ];
        }

        return [
            'success' => false,
            'provider' => 'clickatell',
            'error' => $response['error'] ?? 'Unknown error',
        ];
    }

    /**
     * BulkSMS provider
     */
    private function sendViaBulkSms(string $phone, string $message): array
    {
        $username = $this->config['api_key'] ?? '';
        $password = $this->config['api_secret'] ?? '';

        if (empty($username) || empty($password)) {
            return ['success' => false, 'error' => 'BulkSMS credentials not configured'];
        }

        $url = 'https://api.bulksms.com/v1/messages';
        $data = [
            'to' => preg_replace('/[^0-9+]/', '', $phone),
            'body' => $message,
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Basic ' . base64_encode("{$username}:{$password}"),
            ],
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_TIMEOUT => 30,
        ]);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 201) {
            $response = json_decode($result, true);
            return [
                'success' => true,
                'provider' => 'bulksms',
                'message_id' => $response[0]['id'] ?? null,
                'phone' => $phone,
            ];
        }

        return [
            'success' => false,
            'provider' => 'bulksms',
            'error' => "HTTP {$httpCode}: {$result}",
        ];
    }

    /**
     * Generic HTTP POST helper
     */
    private function httpPost(string $url, array $params): array
    {
        $ch = curl_init($url . '?' . http_build_query($params));
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
        ]);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['success' => false, 'error' => $error];
        }

        if ($httpCode >= 200 && $httpCode < 300) {
            return ['success' => true, 'data' => json_decode($result, true)];
        }

        return ['success' => false, 'error' => "HTTP {$httpCode}: {$result}"];
    }

    /**
     * Get current provider
     */
    public function getProvider(): string
    {
        return $this->provider;
    }

    /**
     * Check if in log mode
     */
    public function isLogMode(): bool
    {
        return $this->provider === 'log';
    }
}
