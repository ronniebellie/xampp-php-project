-- Creates the scenarios table for save/load/compare premium features.
-- Run this once in your database (e.g. phpMyAdmin or mysql CLI).
-- Database: ronbelisle_premium (or your db name from includes/db_config.php)

CREATE TABLE IF NOT EXISTS scenarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  calculator_type VARCHAR(64) NOT NULL,
  scenario_name VARCHAR(255) NOT NULL,
  scenario_data TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_user_calculator (user_id, calculator_type)
);
