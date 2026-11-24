CREATE DATABASE IF NOT EXISTS nested_forum_lab
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE nested_forum_lab;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  email VARCHAR(120) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NULL,
  avatar_path VARCHAR(255) NOT NULL DEFAULT '/assets/default-avatar.svg',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE discussions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  title VARCHAR(200) NOT NULL,
  body TEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_discussions_user
    FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE comments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  discussion_id INT UNSIGNED NOT NULL,
  parent_id INT UNSIGNED NULL,
  user_id INT UNSIGNED NOT NULL,
  body TEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_comments_discussion_id (discussion_id, id),
  CONSTRAINT fk_comments_discussion
    FOREIGN KEY (discussion_id) REFERENCES discussions (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_comments_user
    FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_comments_parent_same_discussion
    FOREIGN KEY (discussion_id, parent_id) REFERENCES comments (discussion_id, id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

INSERT INTO users (id, username, email, password_hash) VALUES
  (1, 'Alice', 'alice@example.invalid', NULL),
  (2, 'Ben',   'ben@example.invalid',   NULL),
  (3, 'Cara',  'cara@example.invalid',  NULL),
  (4, 'Dee',   'dee@example.invalid',   NULL),
  (5, 'Eli',   'eli@example.invalid',   NULL);

INSERT INTO discussions (id, user_id, title, body, created_at) VALUES
  (1, 1, 'How do I learn PHP?', 'Share your learning tips.', '2026-11-01 08:00:00');

INSERT INTO comments (id, discussion_id, parent_id, user_id, body, created_at) VALUES
  (1, 1, NULL, 1, 'Start with the PHP manual.',     '2026-11-01 08:05:00'),
  (2, 1, 1,    2, 'Try the examples as you read.',  '2026-11-01 08:10:00'),
  (3, 1, 2,    3, 'Build a small project too.',     '2026-11-01 08:15:00'),
  (4, 1, 1,    4, 'Which manual section first?',   '2026-11-01 08:20:00'),
  (5, 1, NULL, 5, 'Practice SQL as well.',          '2026-11-01 08:25:00'),
  (6, 1, 3,    2, 'Start with a forum.',            '2026-11-01 08:30:00'),
  (7, 1, 6,    5, 'Add replies next.',              '2026-11-01 08:35:00');