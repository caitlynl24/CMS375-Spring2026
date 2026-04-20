-- Performance records table 
USE athletics_db;

CREATE TABLE IF NOT EXISTS performance_records (
  performance_id INT AUTO_INCREMENT PRIMARY KEY,
  athlete_id INT NOT NULL,
  category ENUM('fitness', 'match') NOT NULL,
  metric_name VARCHAR(50) NOT NULL,
  metric_value DECIMAL(10,2) NOT NULL,
  record_date DATE NOT NULL,
  notes VARCHAR(255) NULL,
  CONSTRAINT fk_performance_records_athlete
    FOREIGN KEY (athlete_id) REFERENCES athletes(athlete_id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  INDEX idx_performance_athlete_category_date (athlete_id, category, record_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Mock data for one athlete profile.
INSERT INTO performance_records (athlete_id, category, metric_name, metric_value, record_date, notes) VALUES
(2, 'fitness', 'beep_test_score', 11.40, '2026-04-01', 'Pre-season conditioning test'),
(2, 'fitness', 'deac_test_score', 8.20, '2026-04-01', 'Agility benchmark'),
(2, 'fitness', 'max_push_ups', 52.00, '2026-04-03', 'Upper body endurance'),
(2, 'fitness', 'bench_press', 185.00, '2026-04-05', 'One-rep max in lbs'),
(2, 'fitness', 'trapbar_deadlift', 315.00, '2026-04-05', 'One-rep max in lbs'),
(2, 'match', 'wins', 12.00, '2026-04-10', 'Season totals'),
(2, 'match', 'losses', 4.00, '2026-04-10', 'Season totals'),
(2, 'match', 'unfinished', 1.00, '2026-04-10', 'Stopped due to weather'),
(2, 'match', 'matches_2_sets', 7.00, '2026-04-10', 'Completed in two sets'),
(2, 'match', 'matches_3_sets', 8.00, '2026-04-10', 'Went to three sets');

SELECT * FROM athletes;
SELECT * FROM performance_records;