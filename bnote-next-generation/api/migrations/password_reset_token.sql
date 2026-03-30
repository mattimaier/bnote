-- Next Gen: password reset tokens (opaque token hashed at rest).
-- Optional: the API also creates this table on first use (see password_reset_schema.php).
-- Run this file manually if you want the schema applied before first reset or without auto-DDL.
-- Safe to run multiple times if your MySQL version supports IF NOT EXISTS.

CREATE TABLE IF NOT EXISTS `password_reset_token` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `token_hash` char(64) NOT NULL COMMENT 'hex(SHA-256(opaque token from email link))',
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token_hash` (`token_hash`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `password_reset_token_user_fk` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
