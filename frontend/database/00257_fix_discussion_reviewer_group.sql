-- Fix omegaup:discussion-reviewer group if it was created manually through UI
-- This ensures the group has the correct ACL ID (10), Role (10), and Group_Roles associations
--
-- If the group was created manually through the UI, it may have:
-- - A different ACL ID (not 10)
-- - Missing Role entry
-- - Missing or incorrect Group_Roles entry
--
-- This script deletes the manually created group and recreates it properly.

-- Step 1: Get the group_id for the existing group (if it exists)
SET @existing_group_id = (SELECT `group_id` FROM `Groups_` WHERE `alias` = 'omegaup:discussion-reviewer' LIMIT 1);

-- Step 2: Delete Group_Roles entries first (to avoid foreign key constraint)
DELETE FROM `Group_Roles` WHERE `group_id` = @existing_group_id;

-- Step 3: Delete Groups_Identities entries (members of the group)
DELETE FROM `Groups_Identities` WHERE `group_id` = @existing_group_id;

-- Step 4: Now delete the group itself
DELETE FROM `Groups_` WHERE `alias` = 'omegaup:discussion-reviewer';

-- Step 5: Ensure ACL ID 10 exists (if not already created by migration 00255)
INSERT IGNORE INTO `ACLs` (`acl_id`, `owner_id`) VALUES (10, 1);

-- Step 6: Ensure Role ID 10 exists (if not already created by migration 00255)
INSERT IGNORE INTO
  `Roles` (`role_id`, `name`, `description`)
VALUES
  (10, 'DiscussionReviewer', 'Miembro del grupo que tiene privilegios para revisar reportes de discusiones');

-- Step 7: Create the group with correct ACL ID 10
INSERT INTO `Groups_` (`acl_id`, `alias`, `name`, `description`) VALUES (
  10,
  'omegaup:discussion-reviewer',
  'omegaup:discussion-reviewer',
  'Equipo de usuarios con privilegios para revisar reportes de discusiones'
);

SET @discussion_reviewer_group_id = LAST_INSERT_ID();

-- Step 8: Link group to role and ACL
INSERT INTO `Group_Roles` (`group_id`, `role_id`, `acl_id`)
VALUES (@discussion_reviewer_group_id, 10, 10);

