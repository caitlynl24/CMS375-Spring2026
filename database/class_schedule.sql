-- AthleteHub - Recurring weekly class schedule (semester-style), per athlete.
-- Run inside athletics_db after athletes table exists.

USE athletics_db;

CREATE TABLE IF NOT EXISTS class_schedule (
  class_id INT AUTO_INCREMENT PRIMARY KEY,
  athlete_id INT NOT NULL,
  course_name VARCHAR(120) NOT NULL,
  day_of_week TINYINT NOT NULL COMMENT '1=Monday ... 7=Sunday (ISO)',
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  location VARCHAR(120) NULL,
  CONSTRAINT fk_class_schedule_athlete
    FOREIGN KEY (athlete_id) REFERENCES athletes(athlete_id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  INDEX idx_class_schedule_athlete_day_start (athlete_id, day_of_week, start_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Demo rows for one athlete. Replace 2 with a real athlete_id from your athletes table.
INSERT INTO class_schedule (athlete_id, course_name, day_of_week, start_time, end_time, location) VALUES
(2, 'CMS 375 Database Systems', 1, '10:00:00', '10:50:00', 'Bush 202'),
(2, 'CMS 375 Database Systems', 3, '10:00:00', '10:50:00', 'Bush 202'),
(2, 'CMS 375 Database Systems', 5, '10:00:00', '10:50:00', 'Bush 202'),
(2, 'MATH 200 Statistics', 1, '13:00:00', '14:15:00', 'Bush 305 110'),
(2, 'MATH 200 Statistics', 3, '13:00:00', '14:15:00', 'Bush 305 110'),
(2, 'ENG 150 Composition', 2, '09:30:00', '10:45:00', 'Orlando 108'),
(2, 'ENG 150 Composition', 4, '09:30:00', '10:45:00', 'Orlando 108'),
(2, 'BIO 101 Lab', 5, '14:00:00', '17:00:00', 'Science Lab B');
