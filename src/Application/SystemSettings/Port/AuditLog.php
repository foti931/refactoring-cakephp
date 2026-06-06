<?php
declare(strict_types=1);

namespace App\Application\SystemSettings\Port;

interface AuditLog
{
    public function record(int $tenantId, int $actorUserId, string $event): void;
}
