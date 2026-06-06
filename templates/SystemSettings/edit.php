<?php
/**
 * @var \App\Model\Entity\SystemSetting $settings
 * @var list<string> $recipients
 * @var array<string, string> $featureOptions
 * @var list<string> $enabledFlags
 */
?>
<div class="settings">
    <h1>System Settings</h1>
    <p>This is the runnable FatController baseline. The code is intentionally messy.</p>

    <?= $this->Flash->render() ?>

    <?= $this->Form->create($settings) ?>
    <?= $this->Form->control('notification_enabled', ['type' => 'checkbox']) ?>
    <?= $this->Form->control('maintenance_mode', ['type' => 'checkbox']) ?>
    <?= $this->Form->control('sender_name') ?>
    <?= $this->Form->control('support_email') ?>
    <?= $this->Form->control('allowed_ip_addresses', [
        'type' => 'textarea',
        'value' => $settings->allowed_ip_addresses,
        'label' => 'Allowed IP addresses, comma or newline separated',
    ]) ?>
    <?= $this->Form->control('recipients', [
        'type' => 'textarea',
        'value' => implode("\n", $recipients),
        'label' => 'Recipients, one email per line',
    ]) ?>
    <fieldset>
        <legend>Feature flags</legend>
        <?php foreach ($featureOptions as $featureKey => $label): ?>
            <label>
                <input
                    type="checkbox"
                    name="feature_flags[]"
                    value="<?= h($featureKey) ?>"
                    <?= in_array($featureKey, $enabledFlags, true) ? 'checked' : '' ?>
                >
                <?= h($label) ?>
            </label>
        <?php endforeach; ?>
    </fieldset>
    <?= $this->Form->button('Save') ?>
    <?= $this->Form->end() ?>
</div>
