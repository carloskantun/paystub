<?php
namespace App\Services;

use PDO;

class AuditService
{
    public function log(string $action, ?string $orderId = null, array $meta = [], string $actor = 'user'): void
    {
        $pdo = db();
        $stmt = $pdo->prepare('INSERT INTO audit_logs (actor, action, order_id, meta_json, created_at) VALUES (:actor,:action,:order_id,:meta,NOW())');
        $stmt->execute([
            ':actor' => $actor,
            ':action' => $action,
            ':order_id' => $orderId,
            ':meta' => $meta ? json_encode($meta) : null,
        ]);
    }
}