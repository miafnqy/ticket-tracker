SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `users` (
                                       `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                                       `login` VARCHAR(255) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` VARCHAR(50) NOT NULL DEFAULT 'client',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `statuses` (
                                          `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                                          `code` VARCHAR(50) NOT NULL UNIQUE,
    `name` VARCHAR(50) NOT NULL UNIQUE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `statuses` (`code`, `name`) VALUES
('todo', 'To Do'),
('in_progress', 'In Progress'),
('review', 'Ready For Review'),
('done', 'Done')
ON DUPLICATE KEY UPDATE code=code;

CREATE TABLE IF NOT EXISTS `tickets` (
                                         `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                                         `user_id` INT UNSIGNED NOT NULL,
                                         `status_id` INT UNSIGNED NOT NULL,
                                         `title` VARCHAR(255) NOT NULL,
    `description` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`status_id`) REFERENCES `statuses`(`id`),

    INDEX `idx_status` (`status_id`),
    INDEX `idx_user` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `comments` (
                                          `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                                          `ticket_id` INT UNSIGNED NOT NULL,
                                          `user_id` INT UNSIGNED NOT NULL,
                                          `text` TEXT NOT NULL,
                                          `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

                                          FOREIGN KEY (`ticket_id`) REFERENCES `tickets`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tags` (
                                      `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                                      `name` VARCHAR(50) NOT NULL UNIQUE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ticket_tags` (
                                             `ticket_id` INT UNSIGNED NOT NULL,
                                             `tag_id` INT UNSIGNED NOT NULL,
                                             PRIMARY KEY (`ticket_id`, `tag_id`),
    FOREIGN KEY (`ticket_id`) REFERENCES `tickets`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`tag_id`) REFERENCES `tags`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO users (login, password, role) VALUES
    ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
    ('user', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user')
    ON DUPLICATE KEY UPDATE login=login;

INSERT INTO tags (name) VALUES ('Bug'), ('Feature'), ('Urgent')
    ON DUPLICATE KEY UPDATE name=name;

SET FOREIGN_KEY_CHECKS = 1;