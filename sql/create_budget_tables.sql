-- Manual-first budget app (v1) — run once in your app database.
-- See budget/index.php after tables exist.

CREATE TABLE IF NOT EXISTS budget_accounts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  name VARCHAR(128) NOT NULL,
  account_type ENUM('checking', 'savings', 'cash', 'credit') NOT NULL DEFAULT 'checking',
  cleared_balance DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_budget_accounts_user (user_id)
);

CREATE TABLE IF NOT EXISTS budget_category_groups (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  name VARCHAR(128) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_budget_groups_user (user_id)
);

CREATE TABLE IF NOT EXISTS budget_categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  group_id INT NULL,
  name VARCHAR(128) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_hidden TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_budget_categories_user (user_id),
  INDEX idx_budget_categories_group (group_id),
  FOREIGN KEY (group_id) REFERENCES budget_category_groups(id) ON DELETE SET NULL
);

-- One row per category per calendar month (YYYY-MM-01).
CREATE TABLE IF NOT EXISTS budget_monthly_targets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  category_id INT NOT NULL,
  month_date DATE NOT NULL,
  target_amount DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_user_category_month (user_id, category_id, month_date),
  INDEX idx_budget_targets_user_month (user_id, month_date),
  FOREIGN KEY (category_id) REFERENCES budget_categories(id) ON DELETE CASCADE
);

-- Signed amount: outflows negative, inflows positive (matches Fidelity / YNAB register math).
CREATE TABLE IF NOT EXISTS budget_transactions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  account_id INT NOT NULL,
  category_id INT NULL,
  txn_date DATE NOT NULL,
  payee VARCHAR(255) NOT NULL DEFAULT '',
  memo VARCHAR(512) NOT NULL DEFAULT '',
  amount DECIMAL(12, 2) NOT NULL,
  is_cleared TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_budget_txn_user_date (user_id, txn_date),
  INDEX idx_budget_txn_account (account_id),
  INDEX idx_budget_txn_category (category_id),
  FOREIGN KEY (account_id) REFERENCES budget_accounts(id) ON DELETE CASCADE,
  FOREIGN KEY (category_id) REFERENCES budget_categories(id) ON DELETE SET NULL
);
