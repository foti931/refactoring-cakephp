# CakePHP FatController Refactoring Exercise

This repository is a concrete starting point for discussing an incremental
refactoring of a commercial CakePHP 4.5.2 / PHP 8.3.6 application.

The assumed application has many settings screens and few tests. The example is
not a claim about the real system. It is a deliberately explicit hypothesis to
review and adjust before applying the approach to production code.

## Hypothetical Screen

The sample screen updates tenant-specific notification settings:

- only an administrator may update settings;
- input is normalized and validated;
- the main settings row and recipient rows are saved in one transaction;
- an audit log is written after a successful save;
- a settings cache is evicted;
- a change notification is sent only when recipients changed.

The initial implementation puts all of this in a controller action. This is the
kind of behavior that becomes expensive to test when repeated across many
settings screens.

## ORM Hypothesis

The sample now includes concrete CakePHP ORM classes:

- [`src/Model/Entity`](src/Model/Entity)
- [`src/Model/Table`](src/Model/Table)
- [`docs/schema-hypothesis.sql`](docs/schema-hypothesis.sql)

The model assumes dedicated tables for the screen. The SQL is illustrative, not
a production migration. Database engine, naming rules, foreign keys, and tenant
table definitions must be confirmed before turning it into a migration.

The runnable spaghetti baseline now uses:

- `system_settings`
- `system_setting_recipients`
- `system_feature_flags`
- `audit_logs`

## Read In Order

1. [`examples/step-00-fat-controller/SystemSettingsController.php`](examples/step-00-fat-controller/SystemSettingsController.php)
2. [`src/Model/Table/SystemSettingsTable.php`](src/Model/Table/SystemSettingsTable.php)
3. [`docs/refactoring-plan.md`](docs/refactoring-plan.md)
4. [`src/Application/SystemSettings/UpdateSystemSettings.php`](src/Application/SystemSettings/UpdateSystemSettings.php)
5. [`tests/Application/SystemSettings/UpdateSystemSettingsTest.php`](tests/Application/SystemSettings/UpdateSystemSettingsTest.php)

## Run

This project now runs with Docker, Apache HTTP, PHP 8.3, CakePHP 4.5.2, and
MariaDB 10.11:

```sh
docker compose up --build -d
docker compose exec app composer init-db
```

Open <http://127.0.0.1:8080>. The runnable page intentionally uses the
FatController baseline in
[`src/Controller/SystemSettingsController.php`](src/Controller/SystemSettingsController.php).
It mixes request parsing, validation, normalization, authorization-like checks,
multi-table writes, audit logging, cache eviction, and GET view shaping in one
controller action.

The focused application-layer tests are separate:

```sh
docker compose exec app composer test
```

To reproduce the official CakePHP startup-guide generation separately, run:

```sh
docker compose run --rm app sh scripts/bootstrap-official.sh /tmp/official-cakephp-app
```

That script follows the CakePHP 4 official `composer create-project` flow and
then pins `cakephp/cakephp` to `4.5.2`. It writes to a separate directory so this
refactoring exercise is not overwritten.

## Questions For The Real System

The answers determine whether this architecture should be kept, simplified, or
extended:

1. Are settings tenant-specific, user-specific, global, or mixed?
2. How is authorization implemented today: Authentication/Authorization
   plugins, a custom component, or ad hoc role checks?
3. Are settings stored in dedicated tables, a generic key-value table, JSON
   columns, or multiple patterns?
4. Which side effects exist in practice: audit logs, cache eviction, file
   writes, external APIs, email, or none?
5. Are concurrent edits possible, and must lost updates be prevented?
6. Is there a shared base controller or component used by many settings screens?
7. What database is used, and how are test databases provisioned?
8. Which setting screen has the highest modification frequency or incident rate?
