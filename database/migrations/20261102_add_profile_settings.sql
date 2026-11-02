ALTER TABLE users
    ADD COLUMN avatar_source ENUM('initials','upload','github') NOT NULL DEFAULT 'initials',
    ADD COLUMN profile_image_path VARCHAR(100) NULL,
    ADD COLUMN github_id BIGINT UNSIGNED NULL,
    ADD COLUMN github_login VARCHAR(39) NULL,
    ADD COLUMN github_avatar_path VARCHAR(100) NULL,
    ADD UNIQUE KEY uq_users_github_id (github_id);
