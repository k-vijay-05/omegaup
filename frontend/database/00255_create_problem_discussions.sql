-- Problem Discussions table for storing main comments
CREATE TABLE `Problem_Discussions` (
  `discussion_id` int NOT NULL AUTO_INCREMENT,
  `problem_id` int NOT NULL COMMENT 'El problema al que pertenece esta discusión',
  `identity_id` int NOT NULL COMMENT 'Identidad del usuario que creó el comentario',
  `content` mediumtext NOT NULL COMMENT 'Contenido del comentario en formato markdown',
  `upvotes` int NOT NULL DEFAULT 0 COMMENT 'Número de votos positivos',
  `downvotes` int NOT NULL DEFAULT 0 COMMENT 'Número de votos negativos',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de creación del comentario',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha de última actualización',
  PRIMARY KEY (`discussion_id`),
  KEY `idx_problem_id` (`problem_id`),
  KEY `idx_identity_id` (`identity_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_upvotes` (`upvotes`),
  CONSTRAINT `fk_pd_problem_id` FOREIGN KEY (`problem_id`) REFERENCES `Problems` (`problem_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pd_identity_id` FOREIGN KEY (`identity_id`) REFERENCES `Identities` (`identity_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Comentarios principales en la sección de discusión de problemas';

-- Problem Discussion Replies table for storing replies to comments
CREATE TABLE `Problem_Discussion_Replies` (
  `reply_id` int NOT NULL AUTO_INCREMENT,
  `discussion_id` int NOT NULL COMMENT 'El comentario principal al que responde',
  `identity_id` int NOT NULL COMMENT 'Identidad del usuario que creó la respuesta',
  `content` mediumtext NOT NULL COMMENT 'Contenido de la respuesta en formato markdown',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de creación de la respuesta',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha de última actualización',
  PRIMARY KEY (`reply_id`),
  KEY `idx_discussion_id` (`discussion_id`),
  KEY `idx_identity_id` (`identity_id`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_pdr_discussion_id` FOREIGN KEY (`discussion_id`) REFERENCES `Problem_Discussions` (`discussion_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pdr_identity_id` FOREIGN KEY (`identity_id`) REFERENCES `Identities` (`identity_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Respuestas a comentarios en la sección de discusión de problemas';

-- Problem Discussion Votes table for tracking individual votes (prevents duplicate votes)
CREATE TABLE `Problem_Discussion_Votes` (
  `vote_id` int NOT NULL AUTO_INCREMENT,
  `discussion_id` int NOT NULL COMMENT 'El comentario que fue votado',
  `identity_id` int NOT NULL COMMENT 'Identidad del usuario que votó',
  `vote_type` enum('upvote','downvote') NOT NULL COMMENT 'Tipo de voto',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha del voto',
  PRIMARY KEY (`vote_id`),
  UNIQUE KEY `unique_vote` (`discussion_id`, `identity_id`),
  KEY `idx_discussion_id` (`discussion_id`),
  KEY `idx_identity_id` (`identity_id`),
  CONSTRAINT `fk_pdv_discussion_id` FOREIGN KEY (`discussion_id`) REFERENCES `Problem_Discussions` (`discussion_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pdv_identity_id` FOREIGN KEY (`identity_id`) REFERENCES `Identities` (`identity_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Votos individuales en comentarios de discusión';

-- Problem Discussion Reports table for reporting inappropriate comments
CREATE TABLE `Problem_Discussion_Reports` (
  `report_id` int NOT NULL AUTO_INCREMENT,
  `discussion_id` int NOT NULL COMMENT 'El comentario que fue reportado',
  `identity_id` int NOT NULL COMMENT 'Identidad del usuario que reportó',
  `reason` text COMMENT 'Razón del reporte',
  `status` enum('open','resolved','dismissed') NOT NULL DEFAULT 'open' COMMENT 'Estado del reporte',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha del reporte',
  PRIMARY KEY (`report_id`),
  KEY `idx_discussion_id` (`discussion_id`),
  KEY `idx_identity_id` (`identity_id`),
  KEY `idx_status` (`status`),
  KEY `idx_discussion_identity` (`discussion_id`, `identity_id`),
  CONSTRAINT `fk_pdre_discussion_id` FOREIGN KEY (`discussion_id`) REFERENCES `Problem_Discussions` (`discussion_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pdre_identity_id` FOREIGN KEY (`identity_id`) REFERENCES `Identities` (`identity_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Reportes de comentarios inapropiados en discusiones';

-- omegaup:discussion-reviewer ACL and Group
-- Role ID 10: Discussion Reviewer
INSERT INTO
  `Roles` (`role_id`,`name`,`description`)
VALUES
  (10,'DiscussionReviewer','Miembro del grupo que tiene privilegios para revisar reportes de discusiones');

-- ACL ID 10: Discussion Reviewer ACL
INSERT INTO `ACLs` (`acl_id`, `owner_id`) VALUES (10, 1);

-- Group: omegaup:discussion-reviewer
INSERT INTO `Groups_` (`acl_id`, `alias`, `name`, `description`) VALUES (
  10,
  'omegaup:discussion-reviewer',
  'omegaup:discussion-reviewer',
  'Equipo de usuarios con privilegios para revisar reportes de discusiones'
);

SET @discussion_reviewer_group_id = LAST_INSERT_ID();

-- Link group to role
INSERT INTO `Group_Roles` VALUES(@discussion_reviewer_group_id, 10, 10);

