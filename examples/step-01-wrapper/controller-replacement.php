<?php
declare(strict_types=1);

/*
 * In the original action, replace only the ORM transaction block.
 * Validation, audit log, cache eviction, mail, flash, and redirect remain in
 * the controller for this step. Keeping this change small makes it reviewable.
 */

$settingsWriter = new SystemSettingsWriter($settingsTable, $recipientsTable);
$settingsWriter->save($tenantId, $enabled, $senderName, $recipients);
