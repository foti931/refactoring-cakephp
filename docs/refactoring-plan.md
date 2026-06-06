# Incremental Refactoring Plan

## Why This Shape

A settings-heavy application is usually not dominated by complex state
machines. Its recurring difficulty is orchestration: authorization, request
conversion, validation, persistence across tables, transactions, and side
effects are duplicated or mixed together.

The first target is therefore not a large domain model. It is a small
application service per update use case, plus narrow interfaces around effects.
Add richer domain objects only where actual rules justify them.

## Step 00: Freeze The Existing Behavior

Read
[`examples/step-00-fat-controller/SystemSettingsController.php`](../examples/step-00-fat-controller/SystemSettingsController.php).
It is intentionally difficult to unit test. CakePHP request state, ORM calls,
authorization, transaction control, cache, logging, and mail are interleaved.

Before moving logic, add controller integration tests around the real action:

- authorized valid update;
- unauthorized update;
- invalid email address;
- persistence failure and rollback;
- changed and unchanged recipients.

These characterization tests are the safety net. They describe current behavior,
including behavior that may later be judged incorrect.

## Step 01: Add A Wrapper Without Moving Rules

Read
[`examples/step-01-wrapper/SystemSettingsWriter.php`](../examples/step-01-wrapper/SystemSettingsWriter.php).

Instantiate the wrapper through dependency injection and replace the ORM save
block in the legacy controller with:

```php
$settingsWriter->save($tenantId, $enabled, $senderName, $recipients);
```

The wrapper keeps the existing transaction boundary. The controller still owns
validation and side-effect decisions. This small step creates one place for
persistence integration tests and lowers the risk of the next move.

For the literal first replacement, see
[`examples/step-01-wrapper/controller-replacement.php`](../examples/step-01-wrapper/controller-replacement.php).
The teaching snapshot constructs the wrapper inline so that the replacement is
obvious. In the real application, register it in the CakePHP DI container after
the wrapper behavior is covered by a test.

## Step 02: Introduce A Command

Read
[`src/Application/SystemSettings/UpdateSystemSettingsCommand.php`](../src/Application/SystemSettings/UpdateSystemSettingsCommand.php).

Convert HTTP input once at the controller edge. The application layer receives
a typed command, not `ServerRequest`, session state, or CakePHP entities. This
makes the use case callable from a CLI, job, or future API without pretending
those transports are identical.

## Step 03: Move Orchestration Behind Ports

Read
[`src/Application/SystemSettings/UpdateSystemSettings.php`](../src/Application/SystemSettings/UpdateSystemSettings.php).

The application service now owns the stable order of work:

1. authorize;
2. normalize;
3. validate;
4. persist atomically;
5. record an audit entry;
6. evict cache;
7. notify only when recipients changed.

The interfaces in
[`src/Application/SystemSettings/Port`](../src/Application/SystemSettings/Port)
are deliberately narrow. Implement them with CakePHP ORM, the current cache
component, and the current notifier. During migration, adapters may simply call
the old code. This preserves behavior while making dependencies replaceable in
tests.

## Step 04: Keep The Controller Thin

Read
[`examples/step-04-thin-controller/SystemSettingsController.php`](../examples/step-04-thin-controller/SystemSettingsController.php).

The controller handles HTTP concerns only: request conversion, invoking the use
case, flash messages, and redirect selection. Do not force view rendering or
GET-only read models through the update service.

CakePHP 4.5 can inject typed action arguments from its DI container. Register
the concrete adapters and `UpdateSystemSettings` in `Application::services()`,
then use the action argument shown in the final controller. Do not add a custom
service locator to `AppController`.

The registration shape is:

```php
public function services(ContainerInterface $container): void
{
    $container->add(Authorizer::class, CakeAuthorizer::class);
    $container->add(SystemSettingsStore::class, CakeSystemSettingsStore::class);
    $container->add(AuditLog::class, CakeAuditLog::class);
    $container->add(SettingsCache::class, CakeSettingsCache::class);
    $container->add(SettingsChangeNotifier::class, CakeSettingsChangeNotifier::class);
    $container->add(UpdateSystemSettings::class)
        ->addArguments([
            Authorizer::class,
            SystemSettingsStore::class,
            AuditLog::class,
            SettingsCache::class,
            SettingsChangeNotifier::class,
        ]);
}
```

The `Cake*` adapters are not implemented in this hypothesis repository because
their correct implementation depends on the production tables, components, and
side-effect requirements.

## Rollout Across Many Screens

Do not create a generic `SettingsService` with dozens of methods. Extract one
high-change screen first. After three screens, compare the implementations and
extract only repeated infrastructure:

- CakePHP transaction adapter;
- authorization adapter;
- audit-log adapter;
- cache adapter;
- shared scalar normalizers where semantics are truly identical.

Keep each use case explicit, such as `UpdateMailSettings` or
`UpdateInvoiceSettings`. Similar-looking settings often acquire different rules.

## Decisions That Need Real-Code Evidence

The sample intentionally does not decide:

- whether authorization belongs before or inside the transaction;
- whether audit logging must roll back with the settings write;
- whether notification should be synchronous or queued;
- whether optimistic locking is needed;
- whether reads need query services;
- whether generic key-value storage should be retained.

Inspect the production behavior and operational requirements before deciding.
