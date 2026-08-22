CREATE TABLE IF NOT EXISTS creators (
  id VARCHAR(64) PRIMARY KEY,
  name VARCHAR(190) NOT NULL,
  profile_name VARCHAR(190) NOT NULL,
  platform VARCHAR(64) NOT NULL,
  avatar_url TEXT NOT NULL,
  cover_url TEXT NOT NULL,
  description TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS videos (
  id VARCHAR(64) PRIMARY KEY,
  creator_id VARCHAR(64) NOT NULL,
  title VARCHAR(255) NOT NULL,
  thumbnail_url TEXT NOT NULL,
  video_url TEXT NOT NULL,
  duration VARCHAR(32) NOT NULL,
  views VARCHAR(64) NOT NULL,
  description TEXT NOT NULL,
  transcript LONGTEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_videos_creator
    FOREIGN KEY (creator_id) REFERENCES creators(id)
    ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS video_playback_tasks (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  video_id VARCHAR(64) NOT NULL,
  timecode VARCHAR(16) NOT NULL,
  label VARCHAR(255) NOT NULL,
  position INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_tasks_video
    FOREIGN KEY (video_id) REFERENCES videos(id)
    ON DELETE CASCADE,
  INDEX idx_video_position (video_id, position)
);
