CREATE TABLE `groups`
(
    `id`   int PRIMARY KEY NOT NULL,
    `name` TEXT NOT NULL,
    `permissions` TEXT NOT NULL
);

CREATE TABLE `users`
(
    `id`       int PRIMARY KEY NOT NULL,
    `group_id` int DEFAULT NULL,
    `name`     TEXT NOT NULL,
    `email`    TEXT NOT NULL,
    CONSTRAINT `group_id` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
);
