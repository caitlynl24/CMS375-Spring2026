-- AthleteHub - Messages table (run inside athletics_db)
USE athletics_db;

-- Clean reset for schema upgrade (safe here because existing data is test-only).
DROP TABLE IF EXISTS messages;

CREATE TABLE messages (
  message_id INT AUTO_INCREMENT PRIMARY KEY,
  message_type ENUM('direct', 'announcement') NOT NULL DEFAULT 'direct',
  athlete_id INT NULL,
  sender_user_id INT NOT NULL,
  recipient_user_id INT NULL,
  recipient_group ENUM('team') NULL,
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
  CONSTRAINT fk_messages_recipient_user
    FOREIGN KEY (recipient_user_id) REFERENCES users(user_id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  INDEX idx_messages_athlete_sent (athlete_id, sent_at),
  INDEX idx_messages_type_group_sent (message_type, recipient_group, sent_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SELECT * FROM messages;
