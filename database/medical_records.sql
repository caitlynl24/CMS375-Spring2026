-- Medical records table 
USE athletics_db;

CREATE TABLE IF NOT EXISTS medical_records (
  medical_record_id INT AUTO_INCREMENT PRIMARY KEY,
  athlete_id INT NOT NULL,
  injury_title VARCHAR(120) NOT NULL,
  injury_details TEXT NULL,
  reported_date DATE NOT NULL,
  status ENUM('active', 'recovering', 'cleared') NOT NULL,
  clearance_status ENUM('not_cleared', 'limited', 'cleared') NOT NULL,
  expected_return_date DATE NULL,
  cleared_date DATE NULL,
  notes TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_medical_records_athlete
    FOREIGN KEY (athlete_id) REFERENCES athletes(athlete_id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  INDEX idx_medical_records_athlete_reported (athlete_id, reported_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Mock data examples for athlete profile.
INSERT INTO medical_records (
  athlete_id,
  injury_title,
  injury_details,
  reported_date,
  status,
  clearance_status,
  expected_return_date,
  cleared_date,
  notes
) VALUES
(
  2,
  'Ankle Sprain',
  'Mild lateral ankle sprain during practice. Swelling present; pain with inversion.',
  '2026-03-28',
  'recovering',
  'limited',
  '2026-04-18',
  NULL,
  'RICE protocol + rehab exercises. Re-evaluate weekly.'
),
(
  3,
  'Shoulder Soreness',
  'Post-lift soreness; no instability reported.',
  '2026-02-12',
  'cleared',
  'cleared',
  NULL,
  '2026-02-20',
  'Cleared for full activity. Continue mobility work.'
);

SELECT athlete_id, user_id, full_name FROM athletes;
