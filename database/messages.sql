-- AthleteHub - Messages table (run inside athletics_db)
USE athletics_db;

-- Clean reset for schema upgrade (safe here because existing data is test-only).
DROP TABLE IF EXISTS messages;

CREATE TABLE messages (
  message_id INT AUTO_INCREMENT PRIMARY KEY,
  athlete_id INT NOT NULL,
  sender_user_id INT NOT NULL,
  recipient_role ENUM('coach', 'athletic_trainer') NOT NULL,
  content TEXT NOT NULL,
  sent_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_messages_athlete
    FOREIGN KEY (athlete_id) REFERENCES athletes(athlete_id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT fk_messages_sender_user
    FOREIGN KEY (sender_user_id) REFERENCES users(user_id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  INDEX idx_messages_athlete_sent (athlete_id, sent_at),
  INDEX idx_messages_athlete_role_sent (athlete_id, recipient_role, sent_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;