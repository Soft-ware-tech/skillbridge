-- SkillBridge.lk — Concurrency fix for TC-NFR-04
-- ---------------------------------------------------------------------
-- Two clients can currently both end up with a 'confirmed' booking for
-- the same freelancer + date if they pay at nearly the same time,
-- because the only conflict check happens in application code
-- (booking.php) before either payment completes — a classic race
-- condition, not a DB-enforced guarantee.
--
-- This table acts as a hard lock: exactly ONE row can ever exist for a
-- given (freelancer_id, booking_date) pair. Whichever payment tries to
-- INSERT here second gets a duplicate-key error from MySQL itself, so
-- the DB — not application timing — decides who wins.
-- ---------------------------------------------------------------------

CREATE TABLE confirmed_slots (
  slot_id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  freelancer_id  INT UNSIGNED NOT NULL,
  booking_date   DATE         NOT NULL,
  booking_id     INT UNSIGNED NOT NULL,
  created_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_freelancer_date (freelancer_id, booking_date),
  CONSTRAINT fk_confirmed_slots_booking
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- New status so the "loser" of a race is clearly flagged, not silently
-- mixed in with genuine card-decline failures.
ALTER TABLE bookings
  MODIFY status ENUM('pending','confirmed','completed','cancelled','payment_failed','conflict')
  NOT NULL DEFAULT 'pending';
