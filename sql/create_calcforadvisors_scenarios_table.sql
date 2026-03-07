-- Scenarios for calcforadvisors paid subscribers (save/load).
-- Run once: mysql -u root -p ronbelisle_premium < sql/create_calcforadvisors_scenarios_table.sql

CREATE TABLE IF NOT EXISTS calcforadvisors_scenarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  subscriber_id INT NOT NULL,
  calculator_type VARCHAR(64) NOT NULL,
  scenario_name VARCHAR(255) NOT NULL,
  scenario_data TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_sub_calculator (subscriber_id, calculator_type),
  FOREIGN KEY (subscriber_id) REFERENCES calcforadvisors_subscribers(id) ON DELETE CASCADE
);
