ALTER TABLE `#__decarodocuments_documents`
  ADD COLUMN `document_type` varchar(64) NOT NULL DEFAULT 'generic' AFTER `description`,
  ADD COLUMN `lifecycle_status` varchar(32) NOT NULL DEFAULT 'draft' AFTER `document_type`,
  ADD COLUMN `confidentiality` varchar(32) NOT NULL DEFAULT 'internal' AFTER `lifecycle_status`,
  ADD COLUMN `reference_code` varchar(191) NOT NULL DEFAULT '' AFTER `confidentiality`,
  ADD COLUMN `document_date` date NULL DEFAULT NULL AFTER `reference_code`,
  ADD COLUMN `valid_from` date NULL DEFAULT NULL AFTER `document_date`,
  ADD COLUMN `expires_at` datetime NULL DEFAULT NULL AFTER `valid_from`,
  ADD COLUMN `language` varchar(7) NOT NULL DEFAULT '*' AFTER `expires_at`,
  ADD COLUMN `current_version` int unsigned NOT NULL DEFAULT 1 AFTER `language`,
  ADD KEY `idx_documents_lifecycle` (`lifecycle_status`),
  ADD KEY `idx_documents_type` (`document_type`),
  ADD KEY `idx_documents_confidentiality` (`confidentiality`),
  ADD KEY `idx_documents_expiry` (`expires_at`),
  ADD KEY `idx_documents_language` (`language`);

UPDATE `#__decarodocuments_documents`
SET `lifecycle_status` = CASE
  WHEN `state` = 1 THEN 'published'
  WHEN `state` = -2 THEN 'archived'
  ELSE 'draft'
END;

CREATE TABLE IF NOT EXISTS `#__decarodocuments_versions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `document_id` int unsigned NOT NULL,
  `version_number` int unsigned NOT NULL,
  `version_uuid` char(36) NOT NULL,
  `original_name` varchar(255) NOT NULL DEFAULT '',
  `stored_name` varchar(64) NOT NULL DEFAULT '',
  `mime_type` varchar(127) NOT NULL DEFAULT '',
  `file_size` bigint unsigned NOT NULL DEFAULT 0,
  `sha256` char(64) NOT NULL DEFAULT '',
  `note` varchar(500) NOT NULL DEFAULT '',
  `is_current` tinyint(1) NOT NULL DEFAULT 0,
  `created` datetime NOT NULL,
  `created_by` int unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_document_version_number` (`document_id`, `version_number`),
  UNIQUE KEY `idx_document_version_uuid` (`version_uuid`),
  UNIQUE KEY `idx_document_version_stored` (`stored_name`),
  KEY `idx_document_version_current` (`document_id`, `is_current`),
  CONSTRAINT `fk_decarodocuments_version_document`
    FOREIGN KEY (`document_id`) REFERENCES `#__decarodocuments_documents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

INSERT INTO `#__decarodocuments_versions`
  (`document_id`, `version_number`, `version_uuid`, `original_name`, `stored_name`, `mime_type`, `file_size`, `sha256`, `note`, `is_current`, `created`, `created_by`)
SELECT
  d.`id`, 1, LOWER(UUID()), d.`original_name`, d.`stored_name`, d.`mime_type`, d.`file_size`, d.`sha256`, '', 1, d.`created`, d.`created_by`
FROM `#__decarodocuments_documents` AS d
WHERE d.`stored_name` <> ''
  AND NOT EXISTS (
    SELECT 1 FROM `#__decarodocuments_versions` AS v WHERE v.`document_id` = d.`id`
  );

CREATE TABLE IF NOT EXISTS `#__decarodocuments_audit` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `document_id` int unsigned NULL DEFAULT NULL,
  `document_uuid` char(36) NOT NULL,
  `version_id` bigint unsigned NULL DEFAULT NULL,
  `action` varchar(64) NOT NULL,
  `actor_user_id` int unsigned NOT NULL DEFAULT 0,
  `context_json` text NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_audit_document_uuid` (`document_uuid`),
  KEY `idx_audit_document_id` (`document_id`),
  KEY `idx_audit_action` (`action`),
  KEY `idx_audit_created` (`created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
