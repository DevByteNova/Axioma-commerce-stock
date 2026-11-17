CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_password_reset_user_created` (`user_id`, `created_at`),
  KEY `idx_password_reset_token` (`token_hash`),
  CONSTRAINT `fk_password_reset_user`
    FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
