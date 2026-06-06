<?php
declare(strict_types=1);

namespace Tests\Application\SystemSettings;

use App\Application\SystemSettings\InvalidSettings;
use App\Application\SystemSettings\Port\AuditLog;
use App\Application\SystemSettings\Port\Authorizer;
use App\Application\SystemSettings\Port\SettingsCache;
use App\Application\SystemSettings\Port\SettingsChangeNotifier;
use App\Application\SystemSettings\Port\StoredSystemSettings;
use App\Application\SystemSettings\Port\SystemSettingsStore;
use App\Application\SystemSettings\UpdateSystemSettings;
use App\Application\SystemSettings\UpdateSystemSettingsCommand;

final class UpdateSystemSettingsTest
{
    public function testUpdatesNormalizedSettingsAndEffects(): void
    {
        $fixture = new Fixture();
        $fixture->service->execute(new UpdateSystemSettingsCommand(
            tenantId: 10,
            actorUserId: 20,
            notificationEnabled: true,
            senderName: '  Example Sender  ',
            recipients: [' USER@example.com ', '', 'user@example.com', 'other@example.com'],
        ));

        assertSame('Example Sender', $fixture->store->saved?->senderName);
        assertSame(['user@example.com', 'other@example.com'], $fixture->store->saved?->recipients);
        assertSame([[10, 20]], $fixture->authorizer->calls);
        assertSame([[10, 20, 'system_settings.updated']], $fixture->auditLog->calls);
        assertSame([10], $fixture->cache->tenantIds);
        assertSame([[10, ['user@example.com', 'other@example.com']]], $fixture->notifier->calls);
    }

    public function testDoesNotNotifyWhenNormalizedRecipientsDidNotChange(): void
    {
        $fixture = new Fixture();
        $fixture->store->current = new StoredSystemSettings(false, 'Old', ['user@example.com']);

        $fixture->service->execute(new UpdateSystemSettingsCommand(
            tenantId: 10,
            actorUserId: 20,
            notificationEnabled: true,
            senderName: 'New',
            recipients: [' USER@example.com '],
        ));

        assertSame([], $fixture->notifier->calls);
    }

    public function testRejectsInvalidInputBeforeSaving(): void
    {
        $fixture = new Fixture();

        assertThrows(InvalidSettings::class, static function () use ($fixture): void {
            $fixture->service->execute(new UpdateSystemSettingsCommand(
                tenantId: 10,
                actorUserId: 20,
                notificationEnabled: true,
                senderName: 'Example',
                recipients: ['not-an-email'],
            ));
        });

        assertSame(null, $fixture->store->saved);
        assertSame([], $fixture->auditLog->calls);
        assertSame([], $fixture->cache->tenantIds);
        assertSame([], $fixture->notifier->calls);
    }

    public function testDoesNotReadOrSaveWhenAuthorizationFails(): void
    {
        $fixture = new Fixture();
        $fixture->authorizer->exception = new \RuntimeException('Forbidden');

        assertThrows(\RuntimeException::class, static function () use ($fixture): void {
            $fixture->executeValidCommand();
        });

        assertSame(0, $fixture->store->getCalls);
        assertSame(null, $fixture->store->saved);
        assertSame([], $fixture->auditLog->calls);
        assertSame([], $fixture->cache->tenantIds);
        assertSame([], $fixture->notifier->calls);
    }

    public function testDoesNotRunEffectsWhenAtomicSaveFails(): void
    {
        $fixture = new Fixture();
        $fixture->store->exception = new \RuntimeException('Database failure');

        assertThrows(\RuntimeException::class, static function () use ($fixture): void {
            $fixture->executeValidCommand();
        });

        assertSame([], $fixture->auditLog->calls);
        assertSame([], $fixture->cache->tenantIds);
        assertSame([], $fixture->notifier->calls);
    }
}

final class Fixture
{
    public readonly FakeAuthorizer $authorizer;
    public readonly FakeStore $store;
    public readonly FakeAuditLog $auditLog;
    public readonly FakeCache $cache;
    public readonly FakeNotifier $notifier;
    public readonly UpdateSystemSettings $service;

    public function __construct()
    {
        $this->authorizer = new FakeAuthorizer();
        $this->store = new FakeStore();
        $this->auditLog = new FakeAuditLog();
        $this->cache = new FakeCache();
        $this->notifier = new FakeNotifier();
        $this->service = new UpdateSystemSettings(
            $this->authorizer,
            $this->store,
            $this->auditLog,
            $this->cache,
            $this->notifier,
        );
    }

    public function executeValidCommand(): void
    {
        $this->service->execute(new UpdateSystemSettingsCommand(
            tenantId: 10,
            actorUserId: 20,
            notificationEnabled: true,
            senderName: 'Example',
            recipients: ['user@example.com'],
        ));
    }
}

final class FakeAuthorizer implements Authorizer
{
    /** @var list<array{int, int}> */
    public array $calls = [];
    public ?\Throwable $exception = null;

    public function assertCanUpdateSystemSettings(int $tenantId, int $actorUserId): void
    {
        $this->calls[] = [$tenantId, $actorUserId];
        if ($this->exception !== null) {
            throw $this->exception;
        }
    }
}

final class FakeStore implements SystemSettingsStore
{
    public StoredSystemSettings $current;
    public ?StoredSystemSettings $saved = null;
    public ?\Throwable $exception = null;
    public int $getCalls = 0;

    public function __construct()
    {
        $this->current = new StoredSystemSettings(false, 'Old', ['old@example.com']);
    }

    public function get(int $tenantId): StoredSystemSettings
    {
        $this->getCalls++;
        return $this->current;
    }

    public function saveAtomically(int $tenantId, StoredSystemSettings $settings): void
    {
        if ($this->exception !== null) {
            throw $this->exception;
        }
        $this->saved = $settings;
    }
}

final class FakeAuditLog implements AuditLog
{
    /** @var list<array{int, int, string}> */
    public array $calls = [];

    public function record(int $tenantId, int $actorUserId, string $event): void
    {
        $this->calls[] = [$tenantId, $actorUserId, $event];
    }
}

final class FakeCache implements SettingsCache
{
    /** @var list<int> */
    public array $tenantIds = [];

    public function evict(int $tenantId): void
    {
        $this->tenantIds[] = $tenantId;
    }
}

final class FakeNotifier implements SettingsChangeNotifier
{
    /** @var list<array{int, list<string>}> */
    public array $calls = [];

    public function recipientsChanged(int $tenantId, array $recipients): void
    {
        $this->calls[] = [$tenantId, $recipients];
    }
}
