<?php
declare(strict_types=1);

namespace App\Controller;

use App\Application\SystemSettings\InvalidSettings;
use App\Application\SystemSettings\UpdateSystemSettings;
use App\Application\SystemSettings\UpdateSystemSettingsCommand;

final class SystemSettingsController extends AppController
{
    /**
     * CakePHP 4.5 resolves typed action arguments from the DI container.
     */
    public function edit(UpdateSystemSettings $updateSystemSettings)
    {
        if (!$this->request->is(['post', 'put'])) {
            return;
        }

        try {
            $updateSystemSettings->execute(new UpdateSystemSettingsCommand(
                tenantId: (int)$this->request->getSession()->read('Auth.tenant_id'),
                actorUserId: (int)$this->request->getSession()->read('Auth.user_id'),
                notificationEnabled: !empty($this->request->getData('notification_enabled')),
                senderName: (string)$this->request->getData('sender_name', ''),
                recipients: (array)$this->request->getData('recipients', []),
            ));
            $this->Flash->success('Settings saved.');
        } catch (InvalidSettings $e) {
            $this->Flash->error($e->getMessage());
            return;
        } catch (\Throwable $e) {
            $this->log($e->getMessage(), 'error');
            $this->Flash->error('Could not save settings.');
            return;
        }

        return $this->redirect(['action' => 'edit']);
    }
}
