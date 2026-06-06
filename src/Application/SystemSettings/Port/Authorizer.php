<?php
declare(strict_types=1);

namespace App\Application\SystemSettings\Port;

interface Authorizer
{
    public function assertCanUpdateSystemSettings(int $tenantId, int $actorUserId): void;
}
