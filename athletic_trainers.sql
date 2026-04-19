-- AthleteHub - Athletic Trainers table
-- Run inside athletics_db after users table exists.

USE athletics_db;

CREATE TABLE IF NOT EXISTS athletic_trainers (
  at_id         INT AUTO_INCREMENT PRIMARY KEY,
  user_id       INT NOT NULL,
  specialty     VARCHAR(100) NULL,
  certification VARCHAR(100) NULL,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_athletic_trainers_user
    FOREIGN KEY (user_id) REFERENCES users(user_id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT uq_athletic_trainers_user UNIQUE (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
