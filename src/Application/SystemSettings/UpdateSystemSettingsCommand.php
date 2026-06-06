<?php
declare(strict_types=1);

namespace App\Application\SystemSettings;

final readonly class UpdateSystemSettingsCommand
{
    /**
     * @param list<string> $recipients
     */
    public function __construct(
        public int $tenantId,
        public int $actorUserId,
        public bool $notificationEnabled,
        public string $senderName,
        public array $recipients,
    ) {
    }
}
