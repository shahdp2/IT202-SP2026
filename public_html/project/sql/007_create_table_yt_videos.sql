CREATE TABLE `IT202_M2_YT_Videos` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `video_id` VARCHAR(20) NOT NULL UNIQUE,            -- e.g., c80ELNJ0LJE
  `channel_id` VARCHAR(40) NOT NULL,                 -- FK-style link to Channels.channel_id
  `title` VARCHAR(150) NOT NULL,
  `channel_name` VARCHAR(120) NOT NULL,
  `length_text` VARCHAR(20) NULL,                    -- "4:37"
  `published_text` VARCHAR(30) NULL,                 -- "1 year ago"
  `views_text` VARCHAR(40) NULL,                     -- "6,965 views"
  `thumbnail_url` VARCHAR(255) NULL,                 -- pick a best/medium thumb url
  `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_api` TINYINT(1) NOT NULL DEFAULT 1
);
