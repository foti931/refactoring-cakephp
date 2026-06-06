<?php
declare(strict_types=1);

use Cake\Datasource\ConnectionManager;

define('DS', DIRECTORY_SEPARATOR);
define('ROOT', dirname(__DIR__));
define('APP_DIR', 'src');
define('APP', ROOT . DS . APP_DIR . DS);
define('CONFIG', ROOT . DS . 'config' . DS);
define('WWW_ROOT', ROOT . DS . 'webroot' . DS);
define('TESTS', ROOT . DS . 'tests' . DS);
define('TMP', ROOT . DS . 'tmp' . DS);
define('LOGS', ROOT . DS . 'logs' . DS);
define('RESOURCES', ROOT . DS . 'resources' . DS);
define('CACHE', TMP . 'cache' . DS);
define('CORE_PATH', ROOT . DS . 'vendor' . DS . 'cakephp' . DS . 'cakephp' . DS);
define('CAKE', CORE_PATH . 'src' . DS);

require CONFIG . 'bootstrap.php';

foreach ([TMP, LOGS, CACHE] as $directory) {
    if (!is_dir($directory)) {
        mkdir($directory, 0777, true);
    }
}

$connection = ConnectionManager::get('default');
$connection->execute('DROP TABLE IF EXISTS system_setting_recipients');
$connection->execute('DROP TABLE IF EXISTS system_feature_flags');
$connection->execute('DROP TABLE IF EXISTS system_settings');
$connection->execute('DROP TABLE IF EXISTS audit_logs');
$connection->execute(
    'CREATE TABLE system_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT NOT NULL UNIQUE,
        notification_enabled TINYINT(1) NOT NULL DEFAULT 0,
        sender_name VARCHAR(100) NOT NULL,
        support_email VARCHAR(255) NOT NULL,
        maintenance_mode TINYINT(1) NOT NULL DEFAULT 0,
        allowed_ip_addresses TEXT NOT NULL,
        created DATETIME,
        modified DATETIME
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
);
$connection->execute(
    'CREATE TABLE system_feature_flags (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT NOT NULL,
        feature_key VARCHAR(50) NOT NULL,
        enabled TINYINT(1) NOT NULL DEFAULT 0,
        created DATETIME,
        modified DATETIME,
        UNIQUE (tenant_id, feature_key)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
);
$connection->execute(
    'CREATE TABLE system_setting_recipients (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT NOT NULL,
        email VARCHAR(255) NOT NULL,
        created DATETIME,
        modified DATETIME,
        UNIQUE (tenant_id, email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
);
$connection->execute(
    'CREATE TABLE audit_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT NOT NULL,
        user_id INT NOT NULL,
        event VARCHAR(100) NOT NULL,
        created DATETIME,
        modified DATETIME
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
);
$connection->execute(
    "INSERT INTO system_settings
        (tenant_id, notification_enabled, sender_name, support_email, maintenance_mode, allowed_ip_addresses, created, modified)
     VALUES
        (1, 1, 'Example Sender', 'support@example.com', 0, '127.0.0.1', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)"
);
$connection->execute(
    "INSERT INTO system_setting_recipients
        (tenant_id, email, created, modified)
     VALUES
        (1, 'first@example.com', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)"
);
$connection->execute(
    "INSERT INTO system_feature_flags
        (tenant_id, feature_key, enabled, created, modified)
     VALUES
        (1, 'new_dashboard', 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
        (1, 'csv_export', 0, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
        (1, 'beta_notice', 0, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)"
);

fwrite(STDOUT, "Initialized MariaDB tables\n");
