CREATE TABLE IF NOT EXISTS `#__decarodocuments_documents` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NULL,
  `original_name` varchar(255) NOT NULL DEFAULT '',
  `stored_name` varchar(64) NOT NULL DEFAULT '',
  `mime_type` varchar(127) NOT NULL DEFAULT '',
  `file_size` bigint unsigned NOT NULL DEFAULT 0,
  `sha256` char(64) NOT NULL DEFAULT '',
  `state` tinyint NOT NULL DEFAULT 1,
  `access` int unsigned NOT NULL DEFAULT 2,
  `created` datetime NOT NULL,
  `created_by` int unsigned NOT NULL DEFAULT 0,
  `modified` datetime NULL DEFAULT NULL,
  `modified_by` int unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_documents_uuid` (`uuid`),
  KEY `idx_documents_state_access` (`state`, `access`),
  KEY `idx_documents_created` (`created`),
  KEY `idx_documents_sha256` (`sha256`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__decarodocuments_relations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `document_id` int unsigned NOT NULL,
  `target_component` varchar(100) NOT NULL,
  `target_entity` varchar(100) NOT NULL,
  `target_id` varchar(191) NOT NULL,
  `relation_type` varchar(100) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_document_relation_unique` (`document_id`, `target_component`, `target_entity`, `target_id`, `relation_type`),
  KEY `idx_relation_target` (`target_component`, `target_entity`, `target_id`),
  CONSTRAINT `fk_decarodocuments_relation_document`
    FOREIGN KEY (`document_id`) REFERENCES `#__decarodocuments_documents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
