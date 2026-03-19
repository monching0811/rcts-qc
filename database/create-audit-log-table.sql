-- SQL to create a simple audit log table for admin actions
CREATE TABLE IF NOT EXISTS rcts_audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ts DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actor VARCHAR(100) NOT NULL,
    event VARCHAR(255) NOT NULL,
    details TEXT DEFAULT NULL
);
