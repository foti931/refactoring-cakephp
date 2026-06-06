<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

final class SystemSettingsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('system_settings');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');

        $this->hasMany('SystemSettingRecipients', [
            'foreignKey' => 'tenant_id',
            'bindingKey' => 'tenant_id',
            'dependent' => true,
            'cascadeCallbacks' => true,
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('tenant_id')
            ->requirePresence('tenant_id', 'create')
            ->boolean('notification_enabled')
            ->requirePresence('notification_enabled', 'create')
            ->notEmptyString('sender_name')
            ->maxLength('sender_name', 100)
            ->email('support_email')
            ->boolean('maintenance_mode')
            ->scalar('allowed_ip_addresses');

        return $validator;
    }

    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->isUnique(['tenant_id']));

        return $rules;
    }
}
