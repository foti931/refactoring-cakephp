<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * @property int $id
 * @property int $tenant_id
 * @property string $email
 * @property \Cake\I18n\FrozenTime|null $created
 * @property \Cake\I18n\FrozenTime|null $modified
 */
final class SystemSettingRecipient extends Entity
{
    protected $_accessible = [
        'tenant_id' => true,
        'email' => true,
        'created' => true,
        'modified' => true,
    ];
}
