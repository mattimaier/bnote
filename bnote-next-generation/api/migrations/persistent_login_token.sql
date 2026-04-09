-- Next Gen: persistent login tokens (long-lived auth cookies, hashed at rest).
-- Optional: the API also creates this table on first use (see persistent_login_token_schema.php).
-- Run this manually if you want schema applied before first login flow.

CREATE TABLE IF NOT EXISTS `persistent_login_token` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `token_hash` char(64) NOT NULL COMMENT 'hex(SHA-256(opaque cookie token))',
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_used_at` datetime NULL DEFAULT NULL,
  `revoked_at` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `plt_token_hash` (`token_hash`),
  KEY `plt_user_id` (`user_id`),
  KEY `plt_expires_at` (`expires_at`),
  CONSTRAINT `plt_user_fk` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

