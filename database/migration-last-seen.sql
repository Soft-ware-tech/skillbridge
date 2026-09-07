-- ============================================================================
-- SkillBridge.lk — Migration: online / last-seen presence
-- Run this once against gpss_database (e.g. via phpMyAdmin -> SQL tab).
-- ============================================================================

ALTER TABLE users
  ADD COLUMN last_seen TIMESTAMP NULL DEFAULT NULL AFTER updated_at;