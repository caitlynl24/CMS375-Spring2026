-- Practice schedule table 
USE athletics_db;

CREATE TABLE IF NOT EXISTS practice_schedule (
  practice_id INT AUTO_INCREMENT PRIMARY KEY,
  athlete_id INT NOT NULL,
  title VARCHAR(120) NOT NULL,
  start_time DATETIME NOT NULL,
  end_time DATETIME NULL,
  location VARCHAR(120) NULL,
  notes TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_practice_schedule_athlete
    FOREIGN KEY (athlete_id) REFERENCES athletes(athlete_id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  INDEX idx_practice_schedule_athlete_start (athlete_id, start_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SELECT * FROM practice_schedule;
SELECT * FROM practice_schedule ORDER BY start_time;
