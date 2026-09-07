-- ============================================================================
-- SkillBridge.lk — Migration: profile photos + client booking location
-- Run this once against an EXISTING database that was created before these
-- columns were added to schema.sql. Safe to run on a fresh DB too (it just
-- won't be needed there, since schema.sql already has these columns).
-- ============================================================================

USE gpss_database;

ALTER TABLE users
  ADD COLUMN photo_path VARCHAR(255) NULL AFTER phone;

ALTER TABLE bookings
  ADD COLUMN latitude  DECIMAL(10,7) NULL AFTER booking_date,
  ADD COLUMN longitude DECIMAL(10,7) NULL AFTER latitude;
