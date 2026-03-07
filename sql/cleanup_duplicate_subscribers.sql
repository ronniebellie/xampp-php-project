-- Clean up duplicate calcforadvisors_subscribers (same email, multiple rows).
-- Keeps the most recent row per email (highest id), deletes the rest.
--
-- Run on server: mysql -u root -p ronbelisle_premium < sql/cleanup_duplicate_subscribers.sql
--
-- Preview first (safe, read-only):
--   SELECT email, COUNT(*) as cnt FROM calcforadvisors_subscribers GROUP BY email HAVING cnt > 1;

-- Delete duplicates, keeping only the row with the highest id per email
DELETE s1 FROM calcforadvisors_subscribers s1
INNER JOIN calcforadvisors_subscribers s2
  ON s1.email = s2.email AND s1.id < s2.id;
