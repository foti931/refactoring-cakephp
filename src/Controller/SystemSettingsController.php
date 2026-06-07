<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Cache\Cache;

/**
 * Runnable FatController starting point.
 *
 * It deliberately mixes HTTP conversion, validation, ORM orchestration,
 * transaction management, audit logging, and cache eviction.
 */
final class SystemSettingsController extends AppController
{
    public function edit()
    {
        $tenantId = 1;
        $userId = 100;
        $role = (string)($this->request->getQuery('role') ?? 'admin');

        if ($role !== 'admin') {
            $this->Flash->error('You are not allowed to edit settings.');
            return $this->redirect(['action' => 'edit']);
        }

        $settingsTable = $this->fetchTable('SystemSettings');
        $recipientsTable = $this->fetchTable('SystemSettingRecipients');
        $featureFlagsTable = $this->fetchTable('SystemFeatureFlags');
        $auditLogsTable = $this->fetchTable('AuditLogs');
        $settings = $settingsTable->find()->where(['tenant_id' => $tenantId])->firstOrFail();
        $featureOptions = [
            'new_dashboard' => 'New dashboard',
            'csv_export' => 'CSV export',
            'beta_notice' => 'Beta notice',
        ];

        $recipients = $recipientsTable->find()
            ->select(['email'])
            ->where(['tenant_id' => $tenantId])
            ->all()
            ->extract('email')
            ->toList();

        $enabledFlags = $featureFlagsTable->find()
            ->select(['feature_key'])
            ->where(['tenant_id' => $tenantId, 'enabled' => true])
            ->all()
            ->extract('feature_key')
            ->toList();

        $this->set(compact('settings', 'recipients', 'featureOptions', 'enabledFlags'));

        if ($this->request->is(['post', 'put'])) {
            $data = $this->request->getData();
            $enabled = !empty($data['notification_enabled']);
            $maintenanceMode = !empty($data['maintenance_mode']);
            $senderName = trim((string)($data['sender_name'] ?? ''));
            $supportEmail = strtolower(trim((string)($data['support_email'] ?? '')));
            $recipients = array_values(array_unique(array_filter(array_map(
                static fn ($email) => strtolower(trim((string)$email)),
                preg_split('/\R/', (string)($data['recipients'] ?? '')) ?: []
            ))));
            $allowedIps = array_values(array_unique(array_filter(array_map(
                static fn ($value) => trim((string)$value),
                preg_split('/[\s,]+/', (string)($data['allowed_ip_addresses'] ?? '')) ?: []
            ))));
            $submittedFlags = (array)($data['feature_flags'] ?? []);
            $featureFlags = [];
            foreach ($featureOptions as $featureKey => $label) {
                $featureFlags[$featureKey] = in_array($featureKey, $submittedFlags, true);
            }

            if ($senderName === '' || mb_strlen($senderName) > 100) {
                $this->Flash->error('Sender name is required and must not exceed 100 characters.');
                return;
            }
            if (filter_var($supportEmail, FILTER_VALIDATE_EMAIL) === false) {
                $this->Flash->error('Support email is invalid.');
                return;
            }
            foreach ($recipients as $recipient) {
                if (filter_var($recipient, FILTER_VALIDATE_EMAIL) === false) {
                    $this->Flash->error('A recipient email address is invalid.');
                    return;
                }
            }
            foreach ($allowedIps as $allowedIp) {
                if (filter_var($allowedIp, FILTER_VALIDATE_IP) === false) {
                    $this->Flash->error('An allowed IP address is invalid.');
                    return;
                }
            }
            if ($maintenanceMode && $supportEmail === 'noreply@example.com') {
                $this->Flash->error('Support email cannot be noreply during maintenance mode.');
                return;
            }

            $connection = $settingsTable->getConnection();
            try {
                $connection->transactional(function () use (
                    $settingsTable,
                    $recipientsTable,
                    $featureFlagsTable,
                    $settings,
                    $tenantId,
                    $enabled,
                    $maintenanceMode,
                    $senderName,
                    $supportEmail,
                    $recipients,
                    $allowedIps,
                    $featureFlags
                ): void {
                    $settingsTable->patchEntity($settings, [
                        'notification_enabled' => $enabled,
                        'sender_name' => $senderName,
                        'support_email' => $supportEmail,
                        'maintenance_mode' => $maintenanceMode,
                        'allowed_ip_addresses' => implode("\n", $allowedIps),
                    ]);
                    $settingsTable->saveOrFail($settings);
                    $recipientsTable->deleteAll(['tenant_id' => $tenantId]);
                    foreach ($recipients as $email) {
                        $recipientsTable->saveOrFail($recipientsTable->newEntity([
                            'tenant_id' => $tenantId,
                            'email' => $email,
                        ]));
                    }
                    $featureFlagsTable->deleteAll(['tenant_id' => $tenantId]);
                    foreach ($featureFlags as $featureKey => $isEnabled) {
                        $featureFlagsTable->saveOrFail($featureFlagsTable->newEntity([
                            'tenant_id' => $tenantId,
                            'feature_key' => $featureKey,
                            'enabled' => $isEnabled,
                        ]));
                    }
                });

                $auditLogsTable->saveOrFail($auditLogsTable->newEntity([
                    'tenant_id' => $tenantId,
                    'user_id' => $userId,
                    'event' => 'system_settings.updated',
                ]));
                Cache::delete("system_settings.{$tenantId}");
                Cache::delete("system_feature_flags.{$tenantId}");
                $this->Flash->success('Settings saved.');
                return $this->redirect(['action' => 'edit']);
            } catch (\Throwable $e) {
                $this->log($e->getMessage(), 'error');
                $this->Flash->error('Could not save settings.');
            }
        }

        $this->set(compact('settings', 'recipients', 'featureOptions', 'enabledFlags'));
    }
}
