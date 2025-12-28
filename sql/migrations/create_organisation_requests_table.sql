-- Organisation Access Requests Table
-- Stores requests from organisations wanting to use the Staff Service

CREATE TABLE IF NOT EXISTS organisation_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organisation_name VARCHAR(255) NOT NULL COMMENT 'Name of the requesting organisation',
    organisation_domain VARCHAR(255) NOT NULL COMMENT 'Email domain for the organisation',
    contact_name VARCHAR(255) NOT NULL COMMENT 'Name of the person making the request',
    contact_email VARCHAR(255) NOT NULL COMMENT 'Email address of the contact person',
    contact_phone VARCHAR(50) NULL COMMENT 'Phone number of the contact person',
    seats_requested INT NOT NULL COMMENT 'Number of seats requested',
    description TEXT NULL COMMENT 'Description of the organisation',
    use_case TEXT NULL COMMENT 'Intended use case for the service',
    status ENUM('pending', 'approved', 'rejected', 'contacted') DEFAULT 'pending' COMMENT 'Status of the request',
    reviewed_by INT NULL COMMENT 'User ID of the superadmin who reviewed this',
    reviewed_at TIMESTAMP NULL COMMENT 'When the request was reviewed',
    review_notes TEXT NULL COMMENT 'Notes from the reviewer',
    ip_address VARCHAR(45) NULL COMMENT 'IP address of the requester',
    submitted_from VARCHAR(255) NULL COMMENT 'Host/domain the request was submitted from',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    INDEX idx_organisation_domain (organisation_domain),
    INDEX idx_contact_email (contact_email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


