INSERT INTO `groups` (`id`, `name`, `permissions`)
VALUES (1, 'Admin', '["manage_users", "manage_posts"]'),
       (2, 'Editor', '["manage_posts"]');

INSERT INTO `users` (`id`, `group_id`, `name`, `email`)
VALUES (1, 1, 'Admin', 'admin@admin.com'),
       (2, 2, 'Editor #1', 'editor1@editor.com'),
       (3, 2, 'Editor #2', 'editor2@editor.com'),
       (4, 2, 'Editor #3', 'editor3@editor.com'),
       (8, NULL, 'User #1', 'user1@user.com'),
       (9, NULL, 'User #2', 'user2@editor.com'),
       (10, NULL, 'User #3', 'user3@editor.com'),
       (11, NULL, 'User #4', 'user4@editor.com'),
       (12, NULL, 'User #5', 'user5@editor.com');
