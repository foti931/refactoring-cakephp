<?php
declare(strict_types=1);

namespace App\Application\SystemSettings\Port;

final readonly class StoredSystemSettings
{
    /**
     * @param list<string> $recipients
     */
    public function __construct(
        public bool $notificationEnabled,
        public string $senderName,
        public array $recipients,
    ) {
    }
}
