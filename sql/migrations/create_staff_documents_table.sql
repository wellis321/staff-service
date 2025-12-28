-- Migration: Create staff_documents table
-- This table stores references to documents related to staff members
-- Documents can be stored locally or referenced from external systems

CREATE TABLE IF NOT EXISTS staff_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    person_id INT NOT NULL,
    document_type VARCHAR(100) NOT NULL COMMENT 'e.g. contract, offer_letter, id_verification, training_certificate',
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    filename VARCHAR(255) NULL COMMENT 'If stored locally',
    file_path VARCHAR(500) NULL COMMENT 'Path to local file',
    external_url VARCHAR(500) NULL COMMENT 'URL if stored in external system',
    external_system VARCHAR(100) NULL COMMENT 'External system name',
    external_id VARCHAR(100) NULL COMMENT 'ID in external system',
    file_type VARCHAR(100) NULL COMMENT 'MIME type',
    file_size INT NULL COMMENT 'Size in bytes',
    expiry_date DATE NULL COMMENT 'If document has expiry date',
    uploaded_by INT NULL COMMENT 'User who uploaded/created reference',
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (person_id) REFERENCES people(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_person (person_id),
    INDEX idx_document_type (document_type),
    INDEX idx_external (external_system, external_id),
    INDEX idx_expiry_date (expiry_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

