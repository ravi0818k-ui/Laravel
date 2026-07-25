-- ============================================================
-- PG A1 Management System — Complete MySQL Schema
-- ============================================================

-- Users table (all roles: super_admin, admin, tenant)
CREATE TABLE `users` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NULL UNIQUE,
    `mobile` VARCHAR(15) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('super_admin', 'admin', 'tenant') NOT NULL DEFAULT 'tenant',
    `is_active` BOOLEAN NOT NULL DEFAULT TRUE,
    `email_verified_at` TIMESTAMP NULL,
    `remember_token` VARCHAR(100) NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- PG Locations
CREATE TABLE `pg_locations` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `address` TEXT NOT NULL,
    `city` VARCHAR(100) NOT NULL,
    `state` VARCHAR(100) NOT NULL DEFAULT 'Haryana',
    `pincode` VARCHAR(10) NOT NULL,
    `latitude` DECIMAL(10, 8) NULL,
    `longitude` DECIMAL(11, 8) NULL,
    `tenant_id_prefix` VARCHAR(10) NOT NULL UNIQUE COMMENT 'e.g. TSN, TJV, TS46',
    `tenant_id_counter` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Last used sequential number',
    `contact_mobile` VARCHAR(15) NULL,
    `contact_email` VARCHAR(255) NULL,
    `description` TEXT NULL,
    `photos` JSON NULL COMMENT 'Array of photo paths',
    `metadata` JSON NULL COMMENT 'Frontend-specific: slug, sharing_type, whatsapp, map_iframe, map_link, videos, amenities, meals, tags, security_deposit',
    `starting_rent` DECIMAL(10, 2) NULL COMMENT 'For public display',
    `is_active` BOOLEAN NOT NULL DEFAULT TRUE,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rooms within a PG location
CREATE TABLE `rooms` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `pg_location_id` BIGINT UNSIGNED NOT NULL,
    `room_number` VARCHAR(50) NOT NULL,
    `floor` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `room_type` ENUM('single', 'double', 'triple', 'quad') NOT NULL,
    `total_beds` TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `has_attached_bathroom` BOOLEAN NOT NULL DEFAULT FALSE,
    `has_ac` BOOLEAN NOT NULL DEFAULT FALSE,
    `has_balcony` BOOLEAN NOT NULL DEFAULT FALSE,
    `description` TEXT NULL,
    `is_active` BOOLEAN NOT NULL DEFAULT TRUE,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    FOREIGN KEY (`pg_location_id`) REFERENCES `pg_locations`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_room_per_pg` (`pg_location_id`, `room_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Beds within a room (first-class entity for billing)
CREATE TABLE `beds` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `room_id` BIGINT UNSIGNED NOT NULL,
    `bed_number` VARCHAR(20) NOT NULL COMMENT 'e.g. A, B, C or 1, 2, 3',
    `monthly_rent` DECIMAL(10, 2) NOT NULL COMMENT 'Rent for this specific bed',
    `status` ENUM('available', 'occupied', 'reserved', 'maintenance') NOT NULL DEFAULT 'available',
    `description` VARCHAR(255) NULL,
    `is_active` BOOLEAN NOT NULL DEFAULT TRUE,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    FOREIGN KEY (`room_id`) REFERENCES `rooms`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_bed_per_room` (`room_id`, `bed_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tenants (extends users with tenant-specific profile data)
CREATE TABLE `tenants` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL UNIQUE,
    `tenant_id` VARCHAR(20) NOT NULL UNIQUE COMMENT 'e.g. TSN0001',
    `pg_location_id` BIGINT UNSIGNED NOT NULL,
    `date_of_birth` DATE NULL,
    `blood_group` ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-') NULL,
    `company_or_college` VARCHAR(255) NULL,
    `company_college_address` TEXT NULL,
    `parent_mobile` VARCHAR(15) NULL,
    `reference_mobile_1` VARCHAR(15) NULL,
    `reference_mobile_2` VARCHAR(15) NULL,
    `emergency_contact_name` VARCHAR(255) NULL,
    `emergency_contact_mobile` VARCHAR(15) NULL,
    `referral_code` VARCHAR(30) NOT NULL UNIQUE COMMENT 'e.g. TSN0001-REF',
    `referred_by_code` VARCHAR(30) NULL COMMENT 'Code used during onboarding',
    `joining_date` DATE NOT NULL,
    `current_rent` DECIMAL(10, 2) NOT NULL,
    `security_deposit` DECIMAL(10, 2) NULL DEFAULT 0,
    `status` ENUM('active', 'offboarded', 'suspended') NOT NULL DEFAULT 'active',
    `offboarded_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`pg_location_id`) REFERENCES `pg_locations`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tenant ↔ Bed allocation (history of room/bed assignments)
CREATE TABLE `tenant_bed_allocations` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` BIGINT UNSIGNED NOT NULL,
    `bed_id` BIGINT UNSIGNED NOT NULL,
    `allocated_at` DATE NOT NULL,
    `vacated_at` DATE NULL,
    `is_current` BOOLEAN NOT NULL DEFAULT TRUE,
    `notes` TEXT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`bed_id`) REFERENCES `beds`(`id`) ON DELETE CASCADE,
    INDEX `idx_current_allocation` (`is_current`, `bed_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tenant rent change history
CREATE TABLE `tenant_rent_history` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` BIGINT UNSIGNED NOT NULL,
    `previous_rent` DECIMAL(10, 2) NOT NULL,
    `new_rent` DECIMAL(10, 2) NOT NULL,
    `effective_date` DATE NOT NULL,
    `reason` VARCHAR(255) NULL,
    `changed_by` BIGINT UNSIGNED NOT NULL COMMENT 'User who made the change',
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`changed_by`) REFERENCES `users`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Onboarding invitations (secure token links)
CREATE TABLE `onboarding_invitations` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `token` VARCHAR(64) NOT NULL UNIQUE,
    `pg_location_id` BIGINT UNSIGNED NULL COMMENT 'Optional pre-assigned PG',
    `created_by` BIGINT UNSIGNED NOT NULL,
    `status` ENUM('pending', 'submitted', 'approved', 'rejected', 'expired') NOT NULL DEFAULT 'pending',
    `expires_at` TIMESTAMP NOT NULL,
    `submitted_at` TIMESTAMP NULL,
    `candidate_name` VARCHAR(255) NULL,
    `candidate_mobile` VARCHAR(15) NULL,
    `candidate_dob` DATE NULL,
    `candidate_blood_group` ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-') NULL,
    `candidate_company_college` VARCHAR(255) NULL,
    `candidate_company_college_address` TEXT NULL,
    `candidate_parent_mobile` VARCHAR(15) NULL,
    `candidate_reference_mobile_1` VARCHAR(15) NULL,
    `candidate_reference_mobile_2` VARCHAR(15) NULL,
    `preferred_pg_location_id` BIGINT UNSIGNED NULL,
    `referral_code_used` VARCHAR(30) NULL,
    `admin_notes` TEXT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    FOREIGN KEY (`pg_location_id`) REFERENCES `pg_locations`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`preferred_pg_location_id`) REFERENCES `pg_locations`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tenant documents (Aadhaar, Voter ID, selfie, company ID)
CREATE TABLE `tenant_documents` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` BIGINT UNSIGNED NULL COMMENT 'NULL if uploaded during onboarding before tenant creation',
    `onboarding_invitation_id` BIGINT UNSIGNED NULL,
    `document_type` ENUM('selfie', 'aadhaar', 'voter_id_front', 'voter_id_back', 'company_college_id', 'other') NOT NULL,
    `file_path` VARCHAR(500) NOT NULL COMMENT 'Private storage path',
    `original_filename` VARCHAR(255) NOT NULL,
    `mime_type` VARCHAR(50) NOT NULL,
    `file_size` INT UNSIGNED NOT NULL COMMENT 'Size in bytes',
    `verification_status` ENUM('pending', 'verified', 'correction_required') NOT NULL DEFAULT 'pending',
    `rejection_reason` TEXT NULL,
    `verified_by` BIGINT UNSIGNED NULL,
    `verified_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`onboarding_invitation_id`) REFERENCES `onboarding_invitations`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`verified_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Monthly rent ledger (per tenant per month)
CREATE TABLE `monthly_rents` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` BIGINT UNSIGNED NOT NULL,
    `billing_month` DATE NOT NULL COMMENT 'First day of billing month (YYYY-MM-01)',
    `base_rent` DECIMAL(10, 2) NOT NULL,
    `discount` DECIMAL(10, 2) NOT NULL DEFAULT 0,
    `additional_charge` DECIMAL(10, 2) NOT NULL DEFAULT 0,
    `total_amount` DECIMAL(10, 2) NOT NULL,
    `paid_amount` DECIMAL(10, 2) NOT NULL DEFAULT 0,
    `due_amount` DECIMAL(10, 2) NOT NULL,
    `status` ENUM('unpaid', 'partially_paid', 'verification_pending', 'paid') NOT NULL DEFAULT 'unpaid',
    `due_date` DATE NULL,
    `notes` TEXT NULL,
    `generated_by` BIGINT UNSIGNED NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`generated_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    UNIQUE KEY `unique_rent_per_month` (`tenant_id`, `billing_month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Payment submissions (screenshot uploads + verification)
CREATE TABLE `payment_submissions` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `monthly_rent_id` BIGINT UNSIGNED NOT NULL,
    `tenant_id` BIGINT UNSIGNED NOT NULL,
    `claimed_amount` DECIMAL(10, 2) NOT NULL,
    `verified_amount` DECIMAL(10, 2) NULL COMMENT 'Amount admin confirms after reviewing screenshot',
    `payment_method` ENUM('upi', 'phonepe', 'gpay', 'paytm', 'bank_transfer', 'cash', 'other') NOT NULL DEFAULT 'upi',
    `transaction_reference` VARCHAR(255) NULL,
    `screenshot_path` VARCHAR(500) NULL COMMENT 'Private storage path',
    `status` ENUM('submitted', 'verification_pending', 'verified', 'rejected') NOT NULL DEFAULT 'submitted',
    `rejection_reason` TEXT NULL,
    `verified_by` BIGINT UNSIGNED NULL,
    `verified_at` TIMESTAMP NULL,
    `payment_date` DATE NULL COMMENT 'Date tenant claims payment was made',
    `notes` TEXT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    FOREIGN KEY (`monthly_rent_id`) REFERENCES `monthly_rents`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`verified_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Electricity bills (per room per month)
CREATE TABLE `electricity_bills` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `room_id` BIGINT UNSIGNED NOT NULL,
    `billing_month` DATE NOT NULL COMMENT 'First day of billing month',
    `total_units` DECIMAL(10, 2) NOT NULL,
    `rate_per_unit` DECIMAL(8, 2) NOT NULL,
    `total_amount` DECIMAL(10, 2) NOT NULL,
    `active_tenants_count` TINYINT UNSIGNED NOT NULL COMMENT 'Snapshot at time of billing',
    `per_tenant_amount` DECIMAL(10, 2) NOT NULL,
    `entered_by` BIGINT UNSIGNED NOT NULL,
    `notes` TEXT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    FOREIGN KEY (`room_id`) REFERENCES `rooms`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`entered_by`) REFERENCES `users`(`id`) ON DELETE RESTRICT,
    UNIQUE KEY `unique_bill_per_room_month` (`room_id`, `billing_month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Electricity bill allocations (per tenant share)
CREATE TABLE `electricity_bill_allocations` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `electricity_bill_id` BIGINT UNSIGNED NOT NULL,
    `tenant_id` BIGINT UNSIGNED NOT NULL,
    `amount` DECIMAL(10, 2) NOT NULL,
    `status` ENUM('unpaid', 'paid') NOT NULL DEFAULT 'unpaid',
    `paid_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    FOREIGN KEY (`electricity_bill_id`) REFERENCES `electricity_bills`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_allocation` (`electricity_bill_id`, `tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Referrals
CREATE TABLE `referrals` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `referrer_tenant_id` BIGINT UNSIGNED NOT NULL,
    `referred_tenant_id` BIGINT UNSIGNED NULL COMMENT 'NULL until onboarding is approved',
    `onboarding_invitation_id` BIGINT UNSIGNED NULL,
    `referral_code_used` VARCHAR(30) NOT NULL,
    `status` ENUM('pending', 'converted', 'expired') NOT NULL DEFAULT 'pending',
    `reward_type` VARCHAR(50) NULL,
    `reward_amount` DECIMAL(10, 2) NULL,
    `reward_applied` BOOLEAN NOT NULL DEFAULT FALSE,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    FOREIGN KEY (`referrer_tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`referred_tenant_id`) REFERENCES `tenants`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`onboarding_invitation_id`) REFERENCES `onboarding_invitations`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Concerns / Tickets
CREATE TABLE `concerns` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` BIGINT UNSIGNED NOT NULL,
    `pg_location_id` BIGINT UNSIGNED NOT NULL,
    `category` ENUM('electricity', 'food', 'room', 'cleaning', 'payment', 'other') NOT NULL,
    `subject` VARCHAR(255) NOT NULL,
    `description` TEXT NOT NULL,
    `status` ENUM('open', 'in_progress', 'resolved', 'closed') NOT NULL DEFAULT 'open',
    `priority` ENUM('low', 'medium', 'high') NOT NULL DEFAULT 'medium',
    `resolved_by` BIGINT UNSIGNED NULL,
    `resolved_at` TIMESTAMP NULL,
    `admin_notes` TEXT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`pg_location_id`) REFERENCES `pg_locations`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`resolved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Concern attachments (images)
CREATE TABLE `concern_attachments` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `concern_id` BIGINT UNSIGNED NOT NULL,
    `file_path` VARCHAR(500) NOT NULL,
    `original_filename` VARCHAR(255) NOT NULL,
    `mime_type` VARCHAR(50) NOT NULL,
    `file_size` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    FOREIGN KEY (`concern_id`) REFERENCES `concerns`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Offboarding requests
CREATE TABLE `offboarding_requests` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` BIGINT UNSIGNED NOT NULL,
    `initiated_by` BIGINT UNSIGNED NOT NULL COMMENT 'Tenant or Admin user_id',
    `reason` TEXT NOT NULL,
    `expected_leaving_date` DATE NOT NULL,
    `actual_leaving_date` DATE NULL,
    `feedback` TEXT NULL,
    `outstanding_rent` DECIMAL(10, 2) NOT NULL DEFAULT 0,
    `outstanding_electricity` DECIMAL(10, 2) NOT NULL DEFAULT 0,
    `security_deposit_refund` DECIMAL(10, 2) NULL,
    `status` ENUM('requested', 'pending_dues', 'approved', 'completed', 'cancelled') NOT NULL DEFAULT 'requested',
    `completed_by` BIGINT UNSIGNED NULL,
    `completed_at` TIMESTAMP NULL,
    `notes` TEXT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`initiated_by`) REFERENCES `users`(`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`completed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin ↔ PG Location assignment (scoped access)
CREATE TABLE `admin_pg_assignments` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL COMMENT 'Admin user',
    `pg_location_id` BIGINT UNSIGNED NOT NULL,
    `assigned_by` BIGINT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`pg_location_id`) REFERENCES `pg_locations`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`assigned_by`) REFERENCES `users`(`id`) ON DELETE RESTRICT,
    UNIQUE KEY `unique_admin_pg` (`user_id`, `pg_location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notifications
CREATE TABLE `notifications` (
    `id` CHAR(36) PRIMARY KEY COMMENT 'UUID',
    `type` VARCHAR(255) NOT NULL,
    `notifiable_type` VARCHAR(255) NOT NULL,
    `notifiable_id` BIGINT UNSIGNED NOT NULL,
    `data` JSON NOT NULL,
    `read_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    INDEX `idx_notifiable` (`notifiable_type`, `notifiable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Activity / Audit logs
CREATE TABLE `activity_logs` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NULL COMMENT 'Who performed the action',
    `impersonated_by` BIGINT UNSIGNED NULL COMMENT 'If action was during impersonation',
    `action` VARCHAR(100) NOT NULL COMMENT 'e.g. rent_changed, payment_verified, document_verified',
    `model_type` VARCHAR(255) NULL COMMENT 'Eloquent model class',
    `model_id` BIGINT UNSIGNED NULL,
    `before` JSON NULL COMMENT 'Previous state',
    `after` JSON NULL COMMENT 'New state',
    `description` TEXT NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` VARCHAR(500) NULL,
    `created_at` TIMESTAMP NULL,
    INDEX `idx_user_action` (`user_id`, `action`),
    INDEX `idx_model` (`model_type`, `model_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`impersonated_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Laravel personal access tokens (Sanctum)
CREATE TABLE `personal_access_tokens` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tokenable_type` VARCHAR(255) NOT NULL,
    `tokenable_id` BIGINT UNSIGNED NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `token` VARCHAR(64) NOT NULL UNIQUE,
    `abilities` TEXT NULL,
    `last_used_at` TIMESTAMP NULL,
    `expires_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    INDEX `idx_tokenable` (`tokenable_type`, `tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
