ALTER TABLE `tale`
    ADD COLUMN `lang` VARCHAR(10) DEFAULT 'RU' NULL AFTER `is_submitted`;
-- #####
-- #####
-- #####
ALTER TABLE `tale`
    ADD COLUMN `body_txt` TEXT NULL AFTER `body`;

ALTER TABLE `tale`
    ADD KEY `IND_PUBLISH_AT` (`publish_at`);
