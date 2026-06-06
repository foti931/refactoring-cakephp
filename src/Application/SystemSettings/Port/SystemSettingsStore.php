<?php
declare(strict_types=1);

namespace App\Application\SystemSettings\Port;

interface SystemSettingsStore
{
    public function get(int $tenantId): StoredSystemSettings;

    public function saveAtomically(int $tenantId, StoredSystemSettings $settings): void;
}
