<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Cache\Cache;
use Cake\Mailer\Mailer;

/**
 * Deliberately bad starting point: representative, not production-ready.
 */
final class SystemSettingsController extends AppController
{
    public function edit()
    {
        $tenantId = (int)$this->request->getSession()->read('Auth.tenant_id');
        $userId = (int)$this->request->getSession()->read('Auth.user_id');
        $role = (string)$this->request->getSession()->read('Auth.role');

        if ($role !== 'admin') {
            $this->Flash->error('You are not allowed to edit settings.');
            return $this->redirect(['action' => 'edit']);
        }

        $settingsTable = $this->fetchTable('SystemSettings');
        $recipientsTable = $this->fetchTable('SystemSettingRecipients');
        $auditLogsTable = $this->fetchTable('AuditLogs');
        $settings = $settingsTable->find()->where(['tenant_id' => $tenantId])->firstOrFail();

        if ($this->request->is(['post', 'put'])) {
            $data = $this->request->getData();
            $enabled = !empty($data['notification_enabled']);
            $senderName = trim((string)($data['sender_name'] ?? ''));
            $recipients = array_values(array_unique(array_filter(array_map(
                static fn ($email) => strtolower(trim((string)$email)),
                (array)($data['recipients'] ?? [])
            ))));

            if ($senderName === '' || mb_strlen($senderName) > 100) {
                $this->Flash->error('Sender name is required and must not exceed 100 characters.');
                return;
            }
            foreach ($recipients as $recipient) {
                if (filter_var($recipient, FILTER_VALIDATE_EMAIL) === false) {
                    $this->Flash->error('A recipient email address is invalid.');
                    return;
                }
            }

            $beforeRecipients = $recipientsTable->find()
                ->select(['email'])
                ->where(['tenant_id' => $tenantId])
                ->all()
                ->extract('email')
                ->toList();

            $connection = $settingsTable->getConnection();
            try {
                $connection->transactional(function () use (
                    $settingsTable,
                    $recipientsTable,
                    $settings,
                    $tenantId,
                    $enabled,
                    $senderName,
                    $recipients
                ): void {
                    $settingsTable->patchEntity($settings, [
                        'notification_enabled' => $enabled,
                        'sender_name' => $senderName,
                    ]);
                    if (!$settingsTable->save($settings)) {
                        throw new \RuntimeException('Could not save settings.');
                    }
                    $recipientsTable->deleteAll(['tenant_id' => $tenantId]);
                    foreach ($recipients as $email) {
                        $entity = $recipientsTable->newEntity([
                            'tenant_id' => $tenantId,
                            'email' => $email,
                        ]);
                        if (!$recipientsTable->save($entity)) {
                            throw new \RuntimeException('Could not save recipients.');
                        }
                    }
                });

                $auditLogsTable->saveOrFail($auditLogsTable->newEntity([
                    'tenant_id' => $tenantId,
                    'user_id' => $userId,
                    'event' => 'system_settings.updated',
                ]));
                Cache::delete("system_settings.{$tenantId}");

                if ($beforeRecipients !== $recipients) {
                    (new Mailer('default'))
                        ->setTo($recipients)
                        ->setSubject('Notification recipients changed')
                        ->deliver('Settings have been updated.');
                }
                $this->Flash->success('Settings saved.');
                return $this->redirect(['action' => 'edit']);
            } catch (\Throwable $e) {
                $this->log($e->getMessage(), 'error');
                $this->Flash->error('Could not save settings.');
            }
        }

        $this->set(compact('settings'));
    }
}
