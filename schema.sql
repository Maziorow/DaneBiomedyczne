CREATE TABLE IF NOT EXISTS users (
  user_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_fullname VARCHAR(128) NOT NULL,
  user_email VARCHAR(128) NOT NULL UNIQUE,
  user_passwordhash VARCHAR(255) NOT NULL,
  reset_question VARCHAR(255) NOT NULL,
  reset_answer_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS login_attempts (
  attempt_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  email VARCHAR(128) NOT NULL,
  was_successful TINYINT(1) NOT NULL,
  client_ip VARCHAR(45) NOT NULL,
  attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_login_attempts_user
    FOREIGN KEY (user_id) REFERENCES users(user_id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS biomedical_units (
  unit_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  unit_name VARCHAR(80) NOT NULL,
  unit_symbol VARCHAR(20) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS measurement_types (
  type_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  type_name VARCHAR(120) NOT NULL,
  type_slug VARCHAR(80) NOT NULL UNIQUE,
  unit_id INT UNSIGNED NOT NULL,
  has_second_value TINYINT(1) NOT NULL DEFAULT 0,
  value_label VARCHAR(80) NOT NULL,
  second_value_label VARCHAR(80) NULL,
  created_by_user_id INT UNSIGNED NULL,
  CONSTRAINT fk_measurement_types_unit
    FOREIGN KEY (unit_id) REFERENCES biomedical_units(unit_id),
  CONSTRAINT fk_measurement_types_user
    FOREIGN KEY (created_by_user_id) REFERENCES users(user_id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS measurements (
  measurement_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  type_id INT UNSIGNED NOT NULL,
  value_primary DECIMAL(8,2) NOT NULL,
  measured_at DATETIME NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_measurements_user
    FOREIGN KEY (user_id) REFERENCES users(user_id)
    ON DELETE CASCADE,
  CONSTRAINT fk_measurements_type
    FOREIGN KEY (type_id) REFERENCES measurement_types(type_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS measurement_norms (
  norm_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  type_id INT UNSIGNED NOT NULL,
  min_value DECIMAL(8,2) NULL,
  max_value DECIMAL(8,2) NULL,
  source VARCHAR(255) NULL,
  created_by_user_id INT UNSIGNED NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_measurement_norms_type
    FOREIGN KEY (type_id) REFERENCES measurement_types(type_id)
    ON DELETE CASCADE,
  CONSTRAINT fk_measurement_norms_user
    FOREIGN KEY (created_by_user_id) REFERENCES users(user_id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO biomedical_units (unit_id, unit_name, unit_symbol) VALUES
(1, 'Stopnie Celsjusza', '°C'),
(2, 'Milimetry słupa rtęci', 'mmHg'),
(3, 'Kilogramy', 'kg'),
(4, 'Nanogramy na mililitr', 'ng/ml')
ON DUPLICATE KEY UPDATE
  unit_name = VALUES(unit_name),
  unit_symbol = VALUES(unit_symbol);

INSERT INTO measurement_types
  (type_id, type_name, type_slug, unit_id, has_second_value, value_label, second_value_label, created_by_user_id)
VALUES
  (1, 'Temperatura ciała', 'temperatura-ciala', 1, 0, 'Temperatura', NULL, NULL),
  (2, 'Ciśnienie krwi', 'cisnienie-krwi', 2, 0, 'Ciśnienie skurczowe', NULL, NULL),
  (3, 'Waga', 'waga', 3, 0, 'Waga', NULL, NULL),
  (4, 'Poziom witaminy D3', 'witamina-d3', 4, 0, 'Poziom witaminy D3', NULL, NULL)
ON DUPLICATE KEY UPDATE
  type_name = VALUES(type_name),
  unit_id = VALUES(unit_id),
  has_second_value = VALUES(has_second_value),
  value_label = VALUES(value_label),
  second_value_label = VALUES(second_value_label);

INSERT INTO measurement_norms (type_id, min_value, max_value, source, created_by_user_id)
SELECT 1, 36.10, 37.20, 'Mayo Clinic: https://www.mayoclinic.org/first-aid/first-aid-fever/basics/art-20056685', NULL
WHERE NOT EXISTS (SELECT 1 FROM measurement_norms WHERE type_id = 1 AND created_by_user_id IS NULL);

INSERT INTO measurement_norms (type_id, min_value, max_value, source, created_by_user_id)
SELECT 2, 90.00, 120.00, 'American Heart Association: https://www.heart.org/en/health-topics/high-blood-pressure/understanding-blood-pressure-readings', NULL
WHERE NOT EXISTS (SELECT 1 FROM measurement_norms WHERE type_id = 2 AND created_by_user_id IS NULL);

INSERT INTO measurement_norms (type_id, min_value, max_value, source, created_by_user_id)
SELECT 4, 20.00, 50.00, 'NIH Office of Dietary Supplements: https://ods.od.nih.gov/factsheets/VitaminD-HealthProfessional/', NULL
WHERE NOT EXISTS (SELECT 1 FROM measurement_norms WHERE type_id = 4 AND created_by_user_id IS NULL);
