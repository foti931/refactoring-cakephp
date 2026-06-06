<?php
declare(strict_types=1);

namespace App\Application\SystemSettings\Port;

interface SettingsChangeNotifier
{
    /**
     * @param list<string> $recipients
     */
    public function recipientsChanged(int $tenantId, array $recipients): void;
}
