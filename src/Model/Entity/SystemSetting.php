<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * @property int $id
 * @property int $tenant_id
 * @property bool $notification_enabled
 * @property string $sender_name
 * @property string $support_email
 * @property bool $maintenance_mode
 * @property string $allowed_ip_addresses
 * @property \Cake\I18n\FrozenTime|null $created
 * @property \Cake\I18n\FrozenTime|null $modified
 */
final class SystemSetting extends Entity
{
    protected $_accessible = [
        'tenant_id' => true,
        'notification_enabled' => true,
        'sender_name' => true,
        'support_email' => true,
        'maintenance_mode' => true,
        'allowed_ip_addresses' => true,
        'created' => true,
        'modified' => true,
    ];
}
