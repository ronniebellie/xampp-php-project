-- calcforadvisors subscriber table for webhook-based tracking
-- Run once in your database (ronbelisle_premium or same DB as includes/db_config.php)

CREATE TABLE IF NOT EXISTS calcforadvisors_subscribers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  stripe_customer_id VARCHAR(255) NOT NULL,
  stripe_subscription_id VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL,
  plan VARCHAR(20) NOT NULL DEFAULT 'monthly',
  status VARCHAR(32) NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_stripe_subscription (stripe_subscription_id),
  INDEX idx_stripe_customer (stripe_customer_id),
  INDEX idx_email (email),
  INDEX idx_status (status)
);
