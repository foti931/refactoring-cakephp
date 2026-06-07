<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\SystemSettingsController;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\SystemSettingsController Test Case
 *
 * @uses \App\Controller\SystemSettingsController
 */
class SystemSettingsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Test edit method
     *
     * @return void
     * @uses \App\Controller\SystemSettingsController::edit()
     */
    public function test_GETで設定画面が表示される(): void
    {
        $this->get('/');

        $this->assertResponseOk();
        $this->assertResponseContains('System Settings');
        $this->assertResponseContains('notification_enabled');
        $this->assertResponseContains('sender_name');
    }

    public function test_PUTで設定画面が保存される(): void
    {
        $this->put('/', [
            'notification_enabled' => '1',
            'maintenance_mode' => '1',
            'sender_name' => 'Changed Sender',
            'support_email' => 'changed@example.com',
            'allowed_ip_addresses' => "127.0.0.1\n192.168.0.1",
            'recipients' => "first@example.com\nsecond@example.com",
            'feature_flags' => ['new_dashboard', 'beta_notice'],
        ]);

        $this->assertRedirect(['controller' => 'SystemSettings', 'action' => 'edit']);

        $settings = $this->fetchTable('SystemSettings')
            ->find()
            ->where(['tenant_id' => 1])
            ->firstOrFail();

        $this->assertSame('Changed Sender', $settings->sender_name);
        $this->assertSame('changed@example.com', $settings->support_email);
        $this->assertSame(1, (int)$settings->notification_enabled);
        $this->assertSame(1, (int)$settings->maintenance_mode);
        $this->assertSame("127.0.0.1\n192.168.0.1", $settings->allowed_ip_addresses);
    }

    public function test_PUTで不正なメールなら保存されない(): void
    {
        $settingsTable = $this->fetchTable('SystemSettings');
        $auditLogsTable = $this->fetchTable('AuditLogs');

        $beforeSettings = $settingsTable
            ->find()
            ->where(['tenant_id' => 1])
            ->firstOrFail();

        $beforeAuditCount = $auditLogsTable
            ->find()
            ->where(['tenant_id' => 1, 'user_id' => 100])
            ->all()
            ->count();

        $this->put('/', [
            'notification_enabled' => '1',
            'maintenance_mode' => '1',
            'sender_name' => 'Should Not Save',
            'support_email' => 'invalid-email',
            'allowed_ip_addresses' => "127.0.0.1\n192.168.0.1",
            'recipients' => "first@example.com\nsecond@example.com",
            'feature_flags' => ['new_dashboard', 'beta_notice'],
        ]);

        $this->assertNoRedirect();
        $this->assertResponseOk();
        $this->assertResponseContains('Support email is invalid.');

        $afterSettings = $settingsTable
            ->find()
            ->where(['tenant_id' => 1])
            ->firstOrFail();

        $this->assertSame($beforeSettings->sender_name, $afterSettings->sender_name);
        $this->assertSame($beforeSettings->support_email, $afterSettings->support_email);
        $this->assertSame((int)$beforeSettings->maintenance_mode, (int)$afterSettings->maintenance_mode);

        $afterAuditCount = $auditLogsTable
            ->find()
            ->where(['tenant_id' => 1, 'user_id' => 100])
            ->all()
            ->count();

        $this->assertSame($beforeAuditCount, $afterAuditCount);
    }

    public function test_PUT後にaudit_logsが増える(): void
    {
        $beforeAuditCount = $this->fetchTable('AuditLogs')
            ->find()
            ->where(['tenant_id' => 1, 'user_id' => 100])
            ->all()
            ->count();

        $this->put('/', [
            'notification_enabled' => '1',
            'maintenance_mode' => '1',
            'sender_name' => 'Changed Sender',
            'support_email' => 'changed@example.com',
            'allowed_ip_addresses' => "127.0.0.1\n192.168.0.1",
            'recipients' => "first@example.com\nsecond@example.com",
            'feature_flags' => ['new_dashboard', 'beta_notice'],
        ]);

        $this->assertRedirect(['controller' => 'SystemSettings', 'action' => 'edit']);

        $auditLogs = $this->fetchTable('AuditLogs')
            ->find()
            ->where(['tenant_id' => 1, 'user_id' => 100])
            ->orderDesc('id')
            ->all();

        $this->assertSame($beforeAuditCount + 1, $auditLogs->count());
        $latestAuditLog = $auditLogs->first();

        $this->assertSame('system_settings.updated', $latestAuditLog->event);
    }

    public function test_PUT後にrecipientsが更新される(): void
    {
        $this->put('/', [
            'notification_enabled' => '1',
            'maintenance_mode' => '0',
            'sender_name' => 'Changed Sender',
            'support_email' => 'changed@example.com',
            'allowed_ip_addresses' => "127.0.0.1\n192.168.0.1",
            'recipients' => " Second@Example.com \nfirst@example.com\nsecond@example.com\n",
            'feature_flags' => ['new_dashboard'],
        ]);

        $this->assertRedirect(['controller' => 'SystemSettings', 'action' => 'edit']);

        $recipients = $this->fetchTable('SystemSettingRecipients')
            ->find()
            ->select(['email'])
            ->where(['tenant_id' => 1])
            ->orderAsc('email')
            ->all()
            ->extract('email')
            ->toList();

        $this->assertSame([
            'first@example.com',
            'second@example.com',
        ], $recipients);
    }

    public function test_PUTでadmin以外の場合にアクセスできないこと(): void
    {
        $settingsTable = $this->fetchTable('SystemSettings');
        $auditLogsTable = $this->fetchTable('AuditLogs');

        $beforeSettings = $settingsTable
            ->find()
            ->where(['tenant_id' => 1])
            ->firstOrFail();

        $beforeAuditCount = $auditLogsTable
            ->find()
            ->where(['tenant_id' => 1, 'user_id' => 100])
            ->all()
            ->count();

        $this->put('/?role=viewer', [
            'notification_enabled' => '1',
            'maintenance_mode' => '0',
            'sender_name' => 'Changed Sender',
            'support_email' => 'changed@example.com',
            'allowed_ip_addresses' => "127.0.0.1\n192.168.0.1",
            'recipients' => " Second@Example.com \nfirst@example.com\nsecond@example.com\n",
            'feature_flags' => ['new_dashboard'],
        ]);

        $this->assertRedirect(['controller' => 'SystemSettings', 'action' => 'edit']);

        $afterSettings = $settingsTable
            ->find()
            ->where(['tenant_id' => 1])
            ->firstOrFail();

        $afterAuditCount = $auditLogsTable
            ->find()
            ->where(['tenant_id' => 1, 'user_id' => 100])
            ->all()
            ->count();

        // TODO:settings は更新されない
        $this->assertSame($beforeSettings->sender_name, $afterSettings->sender_name);
        $this->assertSame($beforeSettings->support_email, $afterSettings->support_email);
        $this->assertSame((int)$beforeSettings->notification_enabled, (int)$afterSettings->notification_enabled);
        $this->assertSame((int)$beforeSettings->maintenance_mode, (int)$afterSettings->maintenance_mode);
        $this->assertSame($beforeSettings->allowed_ip_addresses, $afterSettings->allowed_ip_addresses);

        // TODO:audit_logs は増えない
        $this->assertSame($beforeAuditCount, $afterAuditCount);
    }
}
