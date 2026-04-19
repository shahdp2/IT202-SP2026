CREATE TABLE `IT202_M2_YT_Channels` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,

  `channel_id` VARCHAR(40) NOT NULL UNIQUE,          -- e.g., UChPvQ8hfrSW1EAbtBWjis0g
  `title` VARCHAR(120) NOT NULL,                     -- channel title
  `vanity_url` VARCHAR(255) NULL,                    -- vanityChannelUrl
  `verified` TINYINT(1) NOT NULL DEFAULT 0,          -- true/false
  `subscribers_text` VARCHAR(40) NULL,               -- "3.54K subscribers" (string)
  `avatar_url` VARCHAR(255) NULL,                    -- store the highest-res thumbnail url

  `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  `is_api` TINYINT(1) NOT NULL DEFAULT 1
);