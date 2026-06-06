<?php
declare(strict_types=1);

namespace App\Application\SystemSettings;

use App\Application\SystemSettings\Port\AuditLog;
use App\Application\SystemSettings\Port\Authorizer;
use App\Application\SystemSettings\Port\SettingsCache;
use App\Application\SystemSettings\Port\SettingsChangeNotifier;
use App\Application\SystemSettings\Port\SystemSettingsStore;
use App\Application\SystemSettings\Port\StoredSystemSettings;

final class UpdateSystemSettings
{
    public function __construct(
        private readonly Authorizer $authorizer,
        private readonly SystemSettingsStore $store,
        private readonly AuditLog $auditLog,
        private readonly SettingsCache $cache,
        private readonly SettingsChangeNotifier $notifier,
    ) {
    }

    public function execute(UpdateSystemSettingsCommand $command): void
    {
        $this->authorizer->assertCanUpdateSystemSettings(
            $command->tenantId,
            $command->actorUserId,
        );

        $settings = new StoredSystemSettings(
            $command->notificationEnabled,
            trim($command->senderName),
            $this->normalizeRecipients($command->recipients),
        );
        $this->validate($settings);

        $previous = $this->store->get($command->tenantId);
        $this->store->saveAtomically($command->tenantId, $settings);
        $this->auditLog->record(
            $command->tenantId,
            $command->actorUserId,
            'system_settings.updated',
        );
        $this->cache->evict($command->tenantId);

        if ($previous->recipients !== $settings->recipients) {
            $this->notifier->recipientsChanged($command->tenantId, $settings->recipients);
        }
    }

    /**
     * @param list<string> $recipients
     * @return list<string>
     */
    private function normalizeRecipients(array $recipients): array
    {
        $normalized = array_map(
            static fn (mixed $email): string => strtolower(trim((string)$email)),
            $recipients,
        );

        return array_values(array_unique(array_filter($normalized)));
    }

    private function validate(StoredSystemSettings $settings): void
    {
        if ($settings->senderName === '' || mb_strlen($settings->senderName) > 100) {
            throw new InvalidSettings('Sender name is required and must not exceed 100 characters.');
        }

        foreach ($settings->recipients as $recipient) {
            if (filter_var($recipient, FILTER_VALIDATE_EMAIL) === false) {
                throw new InvalidSettings('A recipient email address is invalid.');
            }
        }
    }
}
