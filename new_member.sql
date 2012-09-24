-- Modification for table `nova_applications`
ALTER TABLE `nova_applications` ADD `new_member` TEXT NOT NULL;

-- Modification for table `nova_characters`
ALTER TABLE `nova_characters` ADD `new_member` ENUM( 'Yes', 'No' ) NOT NULL DEFAULT 'No';
