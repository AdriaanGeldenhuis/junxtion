<?php
/**
 * API Response Handler
 *
 * Consistent JSON response formatting
 */

class Response
{
    /**
     * Send success response
     */
    public static function success($data = null, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        echo json_encode([
            'success' => true,
            'data' => $data
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Send error response
     */
    public static function error(
        string $message,
        int $statusCode = 400,
        string $code = 'ERROR',
        ?array $fields = null
    ): void {
        http_response_code($statusCode);

        $error = [
            'code' => $code,
            'message' => $message
        ];

        if ($fields !== null) {
            $error['fields'] = $fields;
        }

        echo json_encode([
            'success' => false,
            'error' => $error
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Send validation error response
     */
    public static function validationError(array $fields): void
    {
        self::error(
            'Validation failed',
            422,
            'VALIDATION_ERROR',
            $fields
        );
    }

    /**
     * Send unauthorized response
     */
    public static function unauthorized(string $message = 'Unauthorized'): void
    {
        self::error($message, 401, 'UNAUTHORIZED');
    }

    /**
     * Send forbidden response
     */
    public static function forbidden(string $message = 'Access denied'): void
    {
        self::error($message, 403, 'FORBIDDEN');
    }

    /**
     * Send not found response
     */
    public static function notFound(string $message = 'Resource not found'): void
    {
        self::error($message, 404, 'NOT_FOUND');
    }

    /**
     * Send rate limit exceeded response
     */
    public static function rateLimited(int $retryAfter = 60): void
    {
        header("Retry-After: {$retryAfter}");
        self::error(
            'Too many requests. Please try again later.',
            429,
            'RATE_LIMITED'
        );
    }

    /**
     * Send method not allowed response
     */
    public static function methodNotAllowed(array $allowedMethods): void
    {
        header('Allow: ' . implode(', ', $allowedMethods));
        self::error(
            'Method not allowed',
            405,
            'METHOD_NOT_ALLOWED'
        );
    }

    /**
     * Send server error response
     */
    public static function serverError(string $message = 'Internal server error'): void
    {
        self::error($message, 500, 'SERVER_ERROR');
    }

    /**
     * Send paginated response
     */
    public static function paginated(
        array $items,
        int $page,
        int $perPage,
        int $total
    ): void {
        self::success([
            'items' => $items,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => ceil($total / $perPage)
            ]
        ]);
    }
}
