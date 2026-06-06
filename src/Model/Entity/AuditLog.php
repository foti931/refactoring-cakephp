<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $user_id
 * @property string $event
 * @property \Cake\I18n\FrozenTime|null $created
 * @property \Cake\I18n\FrozenTime|null $modified
 */
final class AuditLog extends Entity
{
    protected $_accessible = [
        'tenant_id' => true,
        'user_id' => true,
        'event' => true,
        'created' => true,
        'modified' => true,
    ];
}
