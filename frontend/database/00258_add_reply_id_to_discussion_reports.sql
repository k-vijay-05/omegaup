-- Add reply_id column to Problem_Discussion_Reports table
-- This allows reporting both main discussions (reply_id is NULL) and replies (reply_id is set)
-- Use stored procedure to make migration idempotent
DROP PROCEDURE IF EXISTS `add_reply_id_column_if_not_exists`;
DELIMITER //
CREATE PROCEDURE `add_reply_id_column_if_not_exists`()
BEGIN
  DECLARE column_exists INT DEFAULT 0;
  DECLARE index_exists INT DEFAULT 0;
  DECLARE constraint_exists INT DEFAULT 0;

  -- Check if column exists
  SELECT COUNT(*) INTO column_exists
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'Problem_Discussion_Reports'
    AND COLUMN_NAME = 'reply_id';

  -- Add column if it doesn't exist
  IF column_exists = 0 THEN
    ALTER TABLE `Problem_Discussion_Reports`
      ADD COLUMN `reply_id` int NULL COMMENT 'El reply que fue reportado (NULL si es un comentario principal)' AFTER `discussion_id`;
  END IF;

  -- Check if index exists
  SELECT COUNT(*) INTO index_exists
  FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'Problem_Discussion_Reports'
    AND INDEX_NAME = 'idx_reply_id';

  -- Add index if it doesn't exist
  IF index_exists = 0 THEN
    ALTER TABLE `Problem_Discussion_Reports` ADD KEY `idx_reply_id` (`reply_id`);
  END IF;

  -- Check if foreign key constraint exists
  SELECT COUNT(*) INTO constraint_exists
  FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'Problem_Discussion_Reports'
    AND CONSTRAINT_NAME = 'fk_pdre_reply_id';

  -- Add foreign key constraint if it doesn't exist
  IF constraint_exists = 0 THEN
    ALTER TABLE `Problem_Discussion_Reports`
      ADD CONSTRAINT `fk_pdre_reply_id` FOREIGN KEY (`reply_id`) REFERENCES `Problem_Discussion_Replies` (`reply_id`) ON DELETE CASCADE;
  END IF;
END//
DELIMITER ;

CALL `add_reply_id_column_if_not_exists`();
DROP PROCEDURE `add_reply_id_column_if_not_exists`;

-- Update the unique constraint to include reply_id to prevent duplicate reports
-- A user can report the same discussion OR the same reply, but not the same discussion+reply combination twice
-- Note: MySQL allows multiple NULLs in a UNIQUE constraint, so:
-- - Multiple users can report the same discussion (reply_id = NULL for all)
-- - A user can report a discussion once (reply_id = NULL) and each reply once (reply_id = specific value)
-- - The unique constraint (discussion_id, reply_id, identity_id) handles this correctly
-- Drop the old index if it exists (using a stored procedure to handle conditional drop)
DROP PROCEDURE IF EXISTS `drop_idx_if_exists`;
DELIMITER //
CREATE PROCEDURE `drop_idx_if_exists`()
BEGIN
  DECLARE index_exists INT DEFAULT 0;
  SELECT COUNT(*) INTO index_exists
  FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'Problem_Discussion_Reports'
    AND INDEX_NAME = 'idx_discussion_identity';

  IF index_exists > 0 THEN
    ALTER TABLE `Problem_Discussion_Reports` DROP INDEX `idx_discussion_identity`;
  END IF;
END//
DELIMITER ;

CALL `drop_idx_if_exists`();
DROP PROCEDURE `drop_idx_if_exists`;

-- Add the unique constraint (idempotent)
DROP PROCEDURE IF EXISTS `add_unique_constraint_if_not_exists`;
DELIMITER //
CREATE PROCEDURE `add_unique_constraint_if_not_exists`()
BEGIN
  DECLARE constraint_exists INT DEFAULT 0;

  -- Check if unique constraint exists
  SELECT COUNT(*) INTO constraint_exists
  FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'Problem_Discussion_Reports'
    AND CONSTRAINT_NAME = 'unique_report';

  -- Add unique constraint if it doesn't exist
  IF constraint_exists = 0 THEN
    ALTER TABLE `Problem_Discussion_Reports`
      ADD UNIQUE KEY `unique_report` (`discussion_id`, `reply_id`, `identity_id`);
  END IF;
END//
DELIMITER ;

CALL `add_unique_constraint_if_not_exists`();
DROP PROCEDURE `add_unique_constraint_if_not_exists`;

