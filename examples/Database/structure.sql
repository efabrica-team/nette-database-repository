CREATE TABLE `groups`
(
    `id`          INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    `name`        TEXT NOT NULL,
    `permissions` TEXT NOT NULL
);

CREATE TABLE `users`
(
    `id`       INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    `group_id` INTEGER DEFAULT NULL,
    `name`     TEXT NOT NULL,
    `email`    TEXT NOT NULL,
    CONSTRAINT `group_id` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
);

CREATE TABLE `articles`
(
    `id`      INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    `user_id` INTEGER DEFAULT NULL,
    `title`   TEXT NOT NULL,
    `body`    TEXT NOT NULL,
    CONSTRAINT `user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
);

CREATE TABLE `images`
(
    `id`         INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    `article_id` INTEGER NOT NULL,
    `title`      TEXT NOT NULL,
    CONSTRAINT `article_id` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
);
