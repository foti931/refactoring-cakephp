-- Hypothesis only. Convert this into the project's migration format after
-- confirming the production database and naming conventions.

CREATE TABLE system_settings (
    id INTEGER PRIMARY KEY,
    tenant_id INTEGER NOT NULL,
    notification_enabled BOOLEAN NOT NULL DEFAULT FALSE,
    sender_name VARCHAR(100) NOT NULL,
    support_email VARCHAR(255) NOT NULL,
    maintenance_mode BOOLEAN NOT NULL DEFAULT FALSE,
    allowed_ip_addresses TEXT NOT NULL,
    created TIMESTAMP NULL,
    modified TIMESTAMP NULL,
    UNIQUE (tenant_id)
);

CREATE TABLE system_setting_recipients (
    id INTEGER PRIMARY KEY,
    tenant_id INTEGER NOT NULL,
    email VARCHAR(255) NOT NULL,
    created TIMESTAMP NULL,
    modified TIMESTAMP NULL,
    UNIQUE (tenant_id, email)
);

CREATE TABLE system_feature_flags (
    id INTEGER PRIMARY KEY,
    tenant_id INTEGER NOT NULL,
    feature_key VARCHAR(50) NOT NULL,
    enabled BOOLEAN NOT NULL DEFAULT FALSE,
    created TIMESTAMP NULL,
    modified TIMESTAMP NULL,
    UNIQUE (tenant_id, feature_key)
);

CREATE TABLE audit_logs (
    id INTEGER PRIMARY KEY,
    tenant_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    event VARCHAR(100) NOT NULL,
    created TIMESTAMP NULL,
    modified TIMESTAMP NULL
);
