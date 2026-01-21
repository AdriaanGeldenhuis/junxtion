<?php
/**
 * Audit Service
 *
 * Handles audit logging for all actions
 */

class AuditService
{
    private Database $db;

    public function __construct()
    {
        $this->db = $GLOBALS['db'];
    }

    /**
     * Log an audit event
     */
    public function log(
        string $action,
        string $entityType,
        ?int $entityId = null,
        ?array $before = null,
        ?array $after = null,
        ?int $staffId = null,
        ?int $userId = null
    ): int {
        // Calculate changes if both before and after are provided
        $changes = null;
        if ($before !== null && $after !== null) {
            $changes = $this->calculateChanges($before, $after);
        }

        return $this->db->insert('audit_logs', [
            'staff_id' => $staffId ?? (Auth::isStaff() ? Auth::user()['id'] : null),
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'before_json' => $before ? json_encode($before) : null,
            'after_json' => $after ? json_encode($after) : null,
            'changes_json' => $changes ? json_encode($changes) : null,
            'ip' => Request::ip(),
            'user_agent' => substr(Request::userAgent(), 0, 500),
            'request_id' => $this->getRequestId(),
        ]);
    }

    /**
     * Log activity (lighter weight than audit)
     */
    public function logActivity(
        string $activity,
        ?string $description = null,
        ?array $metadata = null,
        string $userType = 'system',
        ?int $userId = null
    ): int {
        return $this->db->insert('activity_logs', [
            'user_type' => $userType,
            'user_id' => $userId,
            'activity' => $activity,
            'description' => $description,
            'metadata' => $metadata ? json_encode($metadata) : null,
            'ip' => Request::ip(),
        ]);
    }

    /**
     * Log an error
     */
    public function logError(
        string $message,
        string $level = 'error',
        ?array $context = null,
        ?string $file = null,
        ?int $line = null,
        ?string $trace = null
    ): int {
        $user = Auth::user();

        return $this->db->insert('error_logs', [
            'level' => $level,
            'message' => $message,
            'context' => $context ? json_encode($context) : null,
            'file' => $file,
            'line' => $line,
            'trace' => $trace,
            'request_uri' => $_SERVER['REQUEST_URI'] ?? null,
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? null,
            'ip' => Request::ip(),
            'user_type' => Auth::userType(),
            'user_id' => $user['id'] ?? null,
        ]);
    }

    /**
     * Get audit logs with filters
     */
    public function getAuditLogs(array $filters = [], int $page = 1, int $perPage = 50): array
    {
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['staff_id'])) {
            $where[] = 'staff_id = ?';
            $params[] = $filters['staff_id'];
        }

        if (!empty($filters['action'])) {
            $where[] = 'action = ?';
            $params[] = $filters['action'];
        }

        if (!empty($filters['entity_type'])) {
            $where[] = 'entity_type = ?';
            $params[] = $filters['entity_type'];
        }

        if (!empty($filters['entity_id'])) {
            $where[] = 'entity_id = ?';
            $params[] = $filters['entity_id'];
        }

        if (!empty($filters['from_date'])) {
            $where[] = 'created_at >= ?';
            $params[] = $filters['from_date'];
        }

        if (!empty($filters['to_date'])) {
            $where[] = 'created_at <= ?';
            $params[] = $filters['to_date'];
        }

        $whereClause = implode(' AND ', $where);
        $offset = ($page - 1) * $perPage;

        // Get total count
        $total = (int) $this->db->queryValue(
            "SELECT COUNT(*) FROM audit_logs WHERE {$whereClause}",
            $params
        );

        // Get paginated results
        $logs = $this->db->query(
            "SELECT al.*, su.full_name as staff_name
             FROM audit_logs al
             LEFT JOIN staff_users su ON al.staff_id = su.id
             WHERE {$whereClause}
             ORDER BY al.created_at DESC
             LIMIT ? OFFSET ?",
            array_merge($params, [$perPage, $offset])
        );

        // Decode JSON fields
        foreach ($logs as &$log) {
            $log['before_json'] = $log['before_json'] ? json_decode($log['before_json'], true) : null;
            $log['after_json'] = $log['after_json'] ? json_decode($log['after_json'], true) : null;
            $log['changes_json'] = $log['changes_json'] ? json_decode($log['changes_json'], true) : null;
        }

        return [
            'items' => $logs,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage),
        ];
    }

    /**
     * Calculate changes between before and after states
     */
    private function calculateChanges(array $before, array $after): array
    {
        $changes = [];

        // Check for modified and removed fields
        foreach ($before as $key => $oldValue) {
            if (!array_key_exists($key, $after)) {
                $changes[$key] = ['old' => $oldValue, 'new' => null, 'action' => 'removed'];
            } elseif ($after[$key] !== $oldValue) {
                $changes[$key] = ['old' => $oldValue, 'new' => $after[$key], 'action' => 'modified'];
            }
        }

        // Check for added fields
        foreach ($after as $key => $newValue) {
            if (!array_key_exists($key, $before)) {
                $changes[$key] = ['old' => null, 'new' => $newValue, 'action' => 'added'];
            }
        }

        return $changes;
    }

    /**
     * Get or generate request ID for correlation
     */
    private function getRequestId(): string
    {
        if (!isset($GLOBALS['request_id'])) {
            $GLOBALS['request_id'] = 'req_' . bin2hex(random_bytes(16));
        }
        return $GLOBALS['request_id'];
    }

    /**
     * Cleanup old logs
     */
    public function cleanup(int $daysToKeep = 90): int
    {
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$daysToKeep} days"));

        $deleted = 0;

        // Clean activity logs
        $deleted += $this->db->delete('activity_logs', 'created_at < ?', [$cutoff]);

        // Clean error logs (keep longer - 180 days)
        $errorCutoff = date('Y-m-d H:i:s', strtotime('-180 days'));
        $deleted += $this->db->delete('error_logs', 'created_at < ?', [$errorCutoff]);

        // Audit logs kept for 1 year
        $auditCutoff = date('Y-m-d H:i:s', strtotime('-365 days'));
        $deleted += $this->db->delete('audit_logs', 'created_at < ?', [$auditCutoff]);

        return $deleted;
    }
}
