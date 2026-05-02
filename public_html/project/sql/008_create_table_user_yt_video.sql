CREATE TABLE `IT202_M3_UserYTVideos` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `yt_video_id` INT NOT NULL,  -- references IT202_M2_YT_Videos.id (your local DB id)
  `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  UNIQUE KEY `uq_user_video` (`user_id`, `yt_video_id`),
  FOREIGN KEY (`user_id`) REFERENCES `Users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`yt_video_id`) REFERENCES `IT202_M2_YT_Videos`(`id`) ON DELETE CASCADE
);