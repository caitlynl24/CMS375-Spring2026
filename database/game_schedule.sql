-- Game schedule table
USE athletics_db;

CREATE TABLE IF NOT EXISTS game_schedule (
  game_id INT AUTO_INCREMENT PRIMARY KEY,
  athlete_id INT NOT NULL,
  opponent VARCHAR(120) NOT NULL,
  game_datetime DATETIME NOT NULL,
  location VARCHAR(120) NOT NULL,
  notes TEXT NULL,
  CONSTRAINT fk_game_schedule_athlete
    FOREIGN KEY (athlete_id) REFERENCES athletes(athlete_id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  INDEX idx_game_schedule_athlete_datetime (athlete_id, game_datetime)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Mock data examples for one athlete profile.
INSERT INTO game_schedule (athlete_id, opponent, game_datetime, location, notes) VALUES
(2, 'Winter Park Wildcats', '2026-04-22 18:00:00', 'Rollins Field', 'Conference game'),
(2, 'Stetson Hatters', '2026-04-29 19:30:00', 'Stetson Stadium', 'Away game - arrive 90 minutes early');


SELECT athlete_id, user_id, full_name FROM athletes;
