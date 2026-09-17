CREATE TABLE IF NOT EXISTS settings (
  setting_key VARCHAR(100) PRIMARY KEY,
  setting_value TEXT NOT NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS admins (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(80) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  last_login_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS advertisements (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(180) NOT NULL,
  description TEXT NULL,
  image_url VARCHAR(500) NULL,
  button_text VARCHAR(80) NULL,
  target_url VARCHAR(500) NULL,
  placement ENUM('banner','popup','both') NOT NULL DEFAULT 'banner',
  active TINYINT(1) NOT NULL DEFAULT 1,
  start_at DATETIME NULL,
  end_at DATETIME NULL,
  popup_delay INT UNSIGNED NOT NULL DEFAULT 5,
  popup_frequency VARCHAR(30) NOT NULL DEFAULT '24h',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS page_views (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  visited_at DATETIME NOT NULL,
  path VARCHAR(500) NOT NULL,
  referrer VARCHAR(500) NULL,
  device VARCHAR(30) NOT NULL,
  browser VARCHAR(50) NULL,
  visitor_hash CHAR(64) NULL,
  session_hash CHAR(64) NULL,
  consent TINYINT(1) NOT NULL DEFAULT 0,
  INDEX idx_visited_at (visited_at),
  INDEX idx_path (path),
  INDEX idx_visitor_hash (visitor_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS live_visitors (
  session_hash CHAR(64) PRIMARY KEY,
  path VARCHAR(500) NOT NULL,
  device VARCHAR(30) NOT NULL,
  browser VARCHAR(50) NULL,
  referrer VARCHAR(500) NULL,
  last_seen DATETIME NOT NULL,
  INDEX idx_last_seen (last_seen)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS admin_log (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  admin_id INT UNSIGNED NULL,
  action VARCHAR(120) NOT NULL,
  details TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO settings (setting_key, setting_value) VALUES
('site_online','1'),
('maintenance_message','NAVTOOL ist momentan wegen Wartungsarbeiten offline.'),
('maintenance_title','Kurz offline'),
('analytics_enabled','1'),
('analytics_retention_days','180'),
('module_map','1'),
('module_colreg','1'),
('module_navtext','1'),
('module_calc','1'),
('module_weather','1'),
('module_crew','1')
ON DUPLICATE KEY UPDATE setting_key=VALUES(setting_key);
