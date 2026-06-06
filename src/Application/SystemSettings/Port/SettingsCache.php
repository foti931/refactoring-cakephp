<?php
declare(strict_types=1);

namespace App\Application\SystemSettings\Port;

interface SettingsCache
{
    public function evict(int $tenantId): void;
}
