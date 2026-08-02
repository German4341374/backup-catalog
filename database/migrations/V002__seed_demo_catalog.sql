INSERT INTO systems (name, owner, criticality, expected_backup_interval_hours) VALUES
    ('Customer CRM', 'Business Applications', 'Critical', 12),
    ('Payroll Platform', 'Finance IT', 'Critical', 24),
    ('Corporate File Server', 'Infrastructure', 'High', 24),
    ('Marketing SharePoint', 'Digital Workplace', 'Medium', 48),
    ('Analytics Warehouse', 'Data Engineering', 'High', 24),
    ('HR Self-Service Portal', 'HR Technology', 'High', 24),
    ('Source Code Hosting', 'Developer Experience', 'Medium', 72),
    ('Email Archive', 'Messaging Team', 'Critical', 24)
ON CONFLICT (name) DO NOTHING;

INSERT INTO backup_jobs (system_id, name, backup_type, schedule_description, max_runtime_minutes)
SELECT id, 'Nightly full', 'Full', 'Every day at 01:00 UTC', 180 FROM systems WHERE name = 'Customer CRM'
ON CONFLICT (system_id, name) DO NOTHING;
INSERT INTO backup_jobs (system_id, name, backup_type, schedule_description, max_runtime_minutes)
SELECT id, 'Daily snapshot', 'Snapshot', 'Every day at 00:30 UTC', 90 FROM systems WHERE name = 'Payroll Platform'
ON CONFLICT (system_id, name) DO NOTHING;
INSERT INTO backup_jobs (system_id, name, backup_type, schedule_description, max_runtime_minutes)
SELECT id, 'Incremental file backup', 'Incremental', 'Every 12 hours', 240 FROM systems WHERE name = 'Corporate File Server'
ON CONFLICT (system_id, name) DO NOTHING;
INSERT INTO backup_jobs (system_id, name, backup_type, schedule_description, max_runtime_minutes)
SELECT id, 'SharePoint snapshot', 'Snapshot', 'Daily at 03:00 UTC', 120 FROM systems WHERE name = 'Marketing SharePoint'
ON CONFLICT (system_id, name) DO NOTHING;
INSERT INTO backup_jobs (system_id, name, backup_type, schedule_description, max_runtime_minutes)
SELECT id, 'Warehouse full export', 'Full', 'Daily at 02:00 UTC', 300 FROM systems WHERE name = 'Analytics Warehouse'
ON CONFLICT (system_id, name) DO NOTHING;
INSERT INTO backup_jobs (system_id, name, backup_type, schedule_description, max_runtime_minutes)
SELECT id, 'Portal database snapshot', 'Snapshot', 'Daily at 02:30 UTC', 120 FROM systems WHERE name = 'HR Self-Service Portal'
ON CONFLICT (system_id, name) DO NOTHING;
INSERT INTO backup_jobs (system_id, name, backup_type, schedule_description, max_runtime_minutes)
SELECT id, 'Repository full backup', 'Full', 'Monday and Thursday at 23:00 UTC', 180 FROM systems WHERE name = 'Source Code Hosting'
ON CONFLICT (system_id, name) DO NOTHING;
INSERT INTO backup_jobs (system_id, name, backup_type, schedule_description, max_runtime_minutes)
SELECT id, 'Archive incremental', 'Incremental', 'Every 8 hours', 150 FROM systems WHERE name = 'Email Archive'
ON CONFLICT (system_id, name) DO NOTHING;

INSERT INTO backup_runs (
    system_id, backup_job_id, external_id, backup_type, started_at, completed_at,
    status, size_bytes, storage_location, error_message
)
SELECT s.id, j.id, seed.external_id, seed.backup_type, NOW() - seed.started_ago,
       CASE WHEN seed.completed_ago IS NULL THEN NULL ELSE NOW() - seed.completed_ago END,
       seed.status, seed.size_bytes, seed.storage_location, seed.error_message
FROM (
    VALUES
        ('Customer CRM', 'Nightly full', 'demo-crm-001', 'Full', INTERVAL '3 hours 40 minutes', INTERVAL '3 hours', 'Successful', 21474836480::BIGINT, 's3://demo-backups/crm/001', NULL),
        ('Customer CRM', 'Nightly full', 'demo-crm-002', 'Full', INTERVAL '15 hours', INTERVAL '14 hours 20 minutes', 'Successful', 20937965568::BIGINT, 's3://demo-backups/crm/002', NULL),
        ('Customer CRM', 'Nightly full', 'demo-crm-003', 'Full', INTERVAL '27 hours', INTERVAL '26 hours 10 minutes', 'Failed', NULL, 's3://demo-backups/crm/003', 'Database connection closed during export.'),
        ('Payroll Platform', 'Daily snapshot', 'demo-payroll-001', 'Snapshot', INTERVAL '31 hours', INTERVAL '30 hours', 'Successful', 8589934592::BIGINT, 'vault://finance/payroll/001', NULL),
        ('Payroll Platform', 'Daily snapshot', 'demo-payroll-002', 'Snapshot', INTERVAL '7 hours', INTERVAL '6 hours 45 minutes', 'Failed', NULL, 'vault://finance/payroll/002', 'Snapshot quota exceeded.'),
        ('Corporate File Server', 'Incremental file backup', 'demo-files-001', 'Incremental', INTERVAL '11 hours', INTERVAL '10 hours', 'Successful', 53687091200::BIGINT, 'nas://backup-01/files/001', NULL),
        ('Corporate File Server', 'Incremental file backup', 'demo-files-002', 'Incremental', INTERVAL '35 hours', INTERVAL '34 hours 15 minutes', 'Successful', 49392123904::BIGINT, 'nas://backup-01/files/002', NULL),
        ('Marketing SharePoint', 'SharePoint snapshot', 'demo-marketing-001', 'Snapshot', INTERVAL '71 hours', INTERVAL '70 hours', 'Successful', 12884901888::BIGINT, 'vault://workplace/sharepoint/001', NULL),
        ('Marketing SharePoint', 'SharePoint snapshot', 'demo-marketing-002', 'Snapshot', INTERVAL '20 hours', INTERVAL '19 hours 45 minutes', 'Cancelled', NULL, 'vault://workplace/sharepoint/002', 'Maintenance window ended.'),
        ('Analytics Warehouse', 'Warehouse full export', 'demo-data-001', 'Full', INTERVAL '29 hours', INTERVAL '27 hours', 'Failed', NULL, 's3://demo-backups/warehouse/001', 'Insufficient temporary disk space.'),
        ('Analytics Warehouse', 'Warehouse full export', 'demo-data-002', 'Full', INTERVAL '5 hours', INTERVAL '3 hours', 'Failed', NULL, 's3://demo-backups/warehouse/002', 'Object storage returned HTTP 503.'),
        ('HR Self-Service Portal', 'Portal database snapshot', 'demo-hr-001', 'Snapshot', INTERVAL '7 hours', NULL, 'Running', NULL, 'vault://hr/portal/001', NULL),
        ('HR Self-Service Portal', 'Portal database snapshot', 'demo-hr-002', 'Snapshot', INTERVAL '29 hours', INTERVAL '28 hours 30 minutes', 'Successful', 6442450944::BIGINT, 'vault://hr/portal/002', NULL),
        ('Source Code Hosting', 'Repository full backup', 'demo-git-001', 'Full', INTERVAL '49 hours', INTERVAL '48 hours', 'Successful', 34359738368::BIGINT, 'nas://backup-02/git/001', NULL),
        ('Source Code Hosting', 'Repository full backup', 'demo-git-002', 'Full', INTERVAL '121 hours', INTERVAL '120 hours', 'Successful', 33285996544::BIGINT, 'nas://backup-02/git/002', NULL),
        ('Email Archive', 'Archive incremental', 'demo-email-001', 'Incremental', INTERVAL '6 hours', INTERVAL '5 hours', 'Successful', 17179869184::BIGINT, 'tape://archive/email/001', NULL),
        ('Email Archive', 'Archive incremental', 'demo-email-002', 'Incremental', INTERVAL '14 hours', INTERVAL '13 hours', 'Successful', 16106127360::BIGINT, 'tape://archive/email/002', NULL),
        ('Email Archive', 'Archive incremental', 'demo-email-003', 'Incremental', INTERVAL '22 hours', INTERVAL '21 hours', 'Successful', 15569256448::BIGINT, 'tape://archive/email/003', NULL)
) AS seed(system_name, job_name, external_id, backup_type, started_ago, completed_ago, status, size_bytes, storage_location, error_message)
JOIN systems s ON s.name = seed.system_name
JOIN backup_jobs j ON j.system_id = s.id AND j.name = seed.job_name
ON CONFLICT (external_id) DO NOTHING;
