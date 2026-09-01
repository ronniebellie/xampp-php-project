-- Stage A reference SQL. Execute only through the fixed CLI migration runner,
-- which verifies absence/compatibility before and structure/FK afterward.
CREATE TABLE calcforadvisors_scenarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  subscriber_id INT NOT NULL,
  calculator_type VARCHAR(64) NOT NULL,
  scenario_name VARCHAR(255) NOT NULL,
  scenario_data TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_sub_calculator (subscriber_id, calculator_type),
  CONSTRAINT fk_cfa_scenarios_subscriber
    FOREIGN KEY (subscriber_id) REFERENCES calcforadvisors_subscribers(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
