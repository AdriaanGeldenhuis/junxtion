<?php
/**
 * FCM Service
 *
 * Firebase Cloud Messaging HTTP v1 implementation
 * No SDK required - pure PHP with OAuth2
 */

class FcmService
{
    private array $config;
    private ?string $accessToken = null;
    private int $tokenExpiry = 0;

    public function __construct()
    {
        $this->config = $GLOBALS['config']['fcm'] ?? [];
    }

    /**
     * Send push notification to single device
     */
    public function sendToDevice(string $fcmToken, array $notification, array $data = []): array
    {
        return $this->send([
            'token' => $fcmToken,
        ], $notification, $data);
    }

    /**
     * Send push notification to topic
     */
    public function sendToTopic(string $topic, array $notification, array $data = []): array
    {
        return $this->send([
            'topic' => $topic,
        ], $notification, $data);
    }

    /**
     * Send push notification to multiple devices
     */
    public function sendToDevices(array $fcmTokens, array $notification, array $data = []): array
    {
        $results = [];
        $successCount = 0;
        $failureCount = 0;

        foreach ($fcmTokens as $token) {
            $result = $this->sendToDevice($token, $notification, $data);
            $results[] = [
                'token' => $token,
                'success' => $result['success'],
                'message_id' => $result['message_id'] ?? null,
                'error' => $result['error'] ?? null,
            ];

            if ($result['success']) {
                $successCount++;
            } else {
                $failureCount++;
            }
        }

        return [
            'success' => $failureCount === 0,
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'results' => $results,
        ];
    }

    /**
     * Core send method
     */
    private function send(array $target, array $notification, array $data = []): array
    {
        $projectId = $this->config['project_id'] ?? '';

        if (empty($projectId)) {
            return ['success' => false, 'error' => 'FCM project_id not configured'];
        }

        // Get access token
        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return ['success' => false, 'error' => 'Failed to get FCM access token'];
        }

        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

        // Build message
        $message = $target;

        if (!empty($notification)) {
            $message['notification'] = [
                'title' => $notification['title'] ?? '',
                'body' => $notification['body'] ?? '',
            ];

            if (!empty($notification['image'])) {
                $message['notification']['image'] = $notification['image'];
            }
        }

        if (!empty($data)) {
            // FCM data must be string key-value pairs
            $message['data'] = array_map('strval', $data);
        }

        // Android specific config
        $message['android'] = [
            'priority' => 'high',
            'notification' => [
                'channel_id' => $notification['channel'] ?? 'default',
                'sound' => 'default',
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            ],
        ];

        // iOS specific config
        $message['apns'] = [
            'payload' => [
                'aps' => [
                    'sound' => 'default',
                    'badge' => 1,
                ],
            ],
        ];

        // Web push config
        $message['webpush'] = [
            'notification' => [
                'icon' => $GLOBALS['config']['app']['base_url'] . '/assets/img/icon-192x192.png',
            ],
            'fcm_options' => [
                'link' => $data['deeplink'] ?? $GLOBALS['config']['app']['base_url'],
            ],
        ];

        $payload = ['message' => $message];

        // Send request
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 30,
        ]);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['success' => false, 'error' => "CURL error: {$error}"];
        }

        $response = json_decode($result, true);

        if ($httpCode === 200 && isset($response['name'])) {
            return [
                'success' => true,
                'message_id' => $response['name'],
            ];
        }

        $errorMessage = $response['error']['message'] ?? "HTTP {$httpCode}";
        $errorCode = $response['error']['details'][0]['errorCode'] ?? null;

        // Handle specific errors
        if ($errorCode === 'UNREGISTERED' || $errorCode === 'INVALID_ARGUMENT') {
            // Token is invalid - should be removed from database
            return [
                'success' => false,
                'error' => $errorMessage,
                'error_code' => $errorCode,
                'should_remove_token' => true,
            ];
        }

        return [
            'success' => false,
            'error' => $errorMessage,
            'error_code' => $errorCode ?? null,
        ];
    }

    /**
     * Get OAuth2 access token for FCM
     */
    private function getAccessToken(): ?string
    {
        // Return cached token if still valid
        if ($this->accessToken && time() < $this->tokenExpiry - 60) {
            return $this->accessToken;
        }

        $serviceAccountPath = $this->config['service_account_path'] ?? '';

        if (!file_exists($serviceAccountPath)) {
            // Log mode - return fake token for development
            if (($GLOBALS['config']['app']['debug'] ?? false)) {
                return 'debug_token';
            }
            return null;
        }

        $serviceAccount = json_decode(file_get_contents($serviceAccountPath), true);

        if (!$serviceAccount) {
            return null;
        }

        // Create JWT for service account auth
        $now = time();
        $jwt = $this->createServiceAccountJwt($serviceAccount, $now);

        // Exchange JWT for access token
        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]),
            CURLOPT_TIMEOUT => 30,
        ]);

        $result = curl_exec($ch);
        curl_close($ch);

        $response = json_decode($result, true);

        if (isset($response['access_token'])) {
            $this->accessToken = $response['access_token'];
            $this->tokenExpiry = $now + ($response['expires_in'] ?? 3600);
            return $this->accessToken;
        }

        return null;
    }

    /**
     * Create JWT for Google service account authentication
     */
    private function createServiceAccountJwt(array $serviceAccount, int $now): string
    {
        $header = [
            'alg' => 'RS256',
            'typ' => 'JWT',
        ];

        $payload = [
            'iss' => $serviceAccount['client_email'],
            'sub' => $serviceAccount['client_email'],
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
        ];

        $headerEncoded = $this->base64UrlEncode(json_encode($header));
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload));

        $signatureInput = "{$headerEncoded}.{$payloadEncoded}";

        openssl_sign(
            $signatureInput,
            $signature,
            $serviceAccount['private_key'],
            OPENSSL_ALGO_SHA256
        );

        $signatureEncoded = $this->base64UrlEncode($signature);

        return "{$headerEncoded}.{$payloadEncoded}.{$signatureEncoded}";
    }

    /**
     * Base64 URL encode
     */
    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Subscribe device to topic
     */
    public function subscribeToTopic(string $fcmToken, string $topic): bool
    {
        // Would use FCM Instance ID API
        // For simplicity, we'll manage topics in our database
        return true;
    }

    /**
     * Unsubscribe device from topic
     */
    public function unsubscribeFromTopic(string $fcmToken, string $topic): bool
    {
        return true;
    }

    /**
     * Check if FCM is configured
     */
    public function isConfigured(): bool
    {
        $projectId = $this->config['project_id'] ?? '';
        $serviceAccountPath = $this->config['service_account_path'] ?? '';

        return !empty($projectId) && file_exists($serviceAccountPath);
    }
}
