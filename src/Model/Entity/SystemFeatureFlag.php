<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * @property int $id
 * @property int $tenant_id
 * @property string $feature_key
 * @property bool $enabled
 * @property \Cake\I18n\FrozenTime|null $created
 * @property \Cake\I18n\FrozenTime|null $modified
 */
final class SystemFeatureFlag extends Entity
{
    protected $_accessible = [
        'tenant_id' => true,
        'feature_key' => true,
        'enabled' => true,
        'created' => true,
        'modified' => true,
    ];
}
