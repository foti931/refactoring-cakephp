<?php
declare(strict_types=1);

namespace App\Infrastructure\Persistence;

/**
 * First extraction only: the existing ORM transaction moved here unchanged.
 *
 * Concrete Table class names differ by project, so this snapshot uses object.
 * Replace them with the generated Table classes in the real application.
 */
final class SystemSettingsWriter
{
    public function __construct(
        private readonly object $settingsTable,
        private readonly object $recipientsTable,
    ) {
    }

    public function save(
        int $tenantId,
        bool $enabled,
        string $senderName,
        array $recipients
    ): void {
        $settings = $this->settingsTable
            ->find()
            ->where(['tenant_id' => $tenantId])
            ->firstOrFail();

        $this->settingsTable->getConnection()->transactional(function () use (
            $settings,
            $tenantId,
            $enabled,
            $senderName,
            $recipients
        ): void {
            $this->settingsTable->patchEntity($settings, [
                'notification_enabled' => $enabled,
                'sender_name' => $senderName,
            ]);
            if (!$this->settingsTable->save($settings)) {
                throw new \RuntimeException('Could not save settings.');
            }

            $this->recipientsTable->deleteAll(['tenant_id' => $tenantId]);
            foreach ($recipients as $email) {
                $entity = $this->recipientsTable->newEntity([
                    'tenant_id' => $tenantId,
                    'email' => $email,
                ]);
                if (!$this->recipientsTable->save($entity)) {
                    throw new \RuntimeException('Could not save recipients.');
                }
            }
        });
    }
}
