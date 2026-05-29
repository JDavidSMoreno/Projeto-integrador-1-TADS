<?php
declare(strict_types=1);

namespace App\Models;

final class LoginAttemptModel extends BaseModel
{
    protected static string $table = 'login_attempts';

    private const MAX_FAILURES = 5;

    private const WINDOW_MINUTES = 15;

    public function isBlocked(string $email, string $ipAddress): bool
    {
        return $this->countRecentFailures($email, $ipAddress) >= self::MAX_FAILURES;
    }

    public function remainingMinutes(string $email, string $ipAddress): int
    {
        $row = $this
            ->query(
                "SELECT TIMESTAMPDIFF(MINUTE, MIN(attempted_at), NOW()) AS elapsed
                 FROM login_attempts
                 WHERE success = 0
                   AND attempted_at >= (NOW() - INTERVAL " . self::WINDOW_MINUTES . " MINUTE)
                   AND (email = :email OR ip_address = :ip)",
                ['email' => mb_strtolower(trim($email), 'UTF-8'), 'ip' => $ipAddress]
            )
            ->fetch();

        $elapsed = isset($row['elapsed']) ? (int)$row['elapsed'] : 0;

        return max(1, self::WINDOW_MINUTES - $elapsed);
    }

    public function record(string $email, string $ipAddress, bool $success): void
    {
        $this->insert([
            'email' => mb_strtolower(trim($email), 'UTF-8'),
            'ip_address' => $ipAddress,
            'user_agent' => substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
            'success' => $success ? 1 : 0,
        ]);
    }

    public function countRecentFailures(string $email, string $ipAddress): int
    {
        return (int)$this
            ->query(
                "SELECT COUNT(*)
                 FROM login_attempts
                 WHERE success = 0
                   AND attempted_at >= (NOW() - INTERVAL " . self::WINDOW_MINUTES . " MINUTE)
                   AND (email = :email OR ip_address = :ip)",
                ['email' => mb_strtolower(trim($email), 'UTF-8'), 'ip' => $ipAddress]
            )
            ->fetchColumn();
    }

    public function countFailuresLastDay(): int
    {
        return (int)$this
            ->query(
                'SELECT COUNT(*) FROM login_attempts WHERE success = 0 AND attempted_at >= (NOW() - INTERVAL 1 DAY)'
            )
            ->fetchColumn();
    }
}
