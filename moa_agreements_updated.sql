-- Update MOA table to add document upload fields
ALTER TABLE moa_agreements
ADD COLUMN moa_document_path VARCHAR(500) NULL AFTER moa_version,
ADD COLUMN moa_document_name VARCHAR(255) NULL AFTER moa_document_path,
ADD COLUMN moa_uploaded_at TIMESTAMP NULL AFTER moa_document_name,
ADD COLUMN moa_uploaded_by INT NULL AFTER moa_uploaded_at,
ADD COLUMN lawyer_name VARCHAR(255) NULL AFTER moa_uploaded_by,
ADD COLUMN lawyer_license_number VARCHAR(100) NULL AFTER lawyer_name,
ADD COLUMN approval_date DATE NULL AFTER lawyer_license_number,
ADD COLUMN approval_notes TEXT NULL AFTER approval_date,
ADD FOREIGN KEY (moa_uploaded_by) REFERENCES users(id) ON DELETE SET NULL;

-- Or create complete table with document fields
DROP TABLE IF EXISTS moa_agreements;

CREATE TABLE moa_agreements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    work_schedule_id INT NOT NULL,
    user_id INT NOT NULL,
    
    -- MOA Content
    moa_content TEXT NOT NULL,
    moa_version VARCHAR(20) DEFAULT '1.0',
    
    -- MOA Document (approved by lawyer)
    moa_document_path VARCHAR(500) NULL,
    moa_document_name VARCHAR(255) NULL,
    moa_uploaded_at TIMESTAMP NULL,
    moa_uploaded_by INT NULL,
    
    -- Lawyer Approval
    lawyer_name VARCHAR(255) NULL,
    lawyer_license_number VARCHAR(100) NULL,
    approval_date DATE NULL,
    approval_notes TEXT NULL,
    
    -- Agreement Details
    agreement_date DATE NOT NULL,
    start_date DATE NOT NULL,
    department VARCHAR(100) NOT NULL,
    supervisor_name VARCHAR(100) NOT NULL,
    location VARCHAR(100) NOT NULL,
    
    -- Digital Signature
    intern_signature VARCHAR(255) NOT NULL,
    intern_full_name VARCHAR(255) NOT NULL,
    intern_email VARCHAR(255) NOT NULL,
    
    -- Acceptance Details
    accepted_at TIMESTAMP NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    
    -- Agreement Checkboxes (what they agreed to)
    agreed_terms TINYINT(1) DEFAULT 1,
    agreed_confidentiality TINYINT(1) DEFAULT 1,
    agreed_schedule TINYINT(1) DEFAULT 1,
    
    -- Status
    status ENUM('active', 'completed', 'terminated', 'cancelled') DEFAULT 'active',
    termination_date DATE NULL,
    termination_reason TEXT NULL,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign Keys
    FOREIGN KEY (work_schedule_id) REFERENCES work_schedules(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (moa_uploaded_by) REFERENCES users(id) ON DELETE SET NULL,
    
    -- Indexes
    UNIQUE KEY unique_work_schedule (work_schedule_id),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_accepted_at (accepted_at)
);
