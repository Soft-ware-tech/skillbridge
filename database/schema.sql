-- ============================================================================
-- SkillBridge.lk — Database Schema
-- Shared by all 3 language folders (tamil / english / sinhala)
-- Only UI text differs per language — the data itself is language-neutral.
-- Charset: utf8mb4 throughout, required for Sinhala + Tamil script storage
-- (e.g. freelancer bios, service descriptions, chat messages).
-- ============================================================================

CREATE DATABASE IF NOT EXISTS gpss_database
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE gpss_database;

-- ----------------------------------------------------------------------------
-- USERS
-- Base account for every person on the platform: Freelancer, Client, Admin.
-- ----------------------------------------------------------------------------
CREATE TABLE users (
  user_id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name      VARCHAR(120)        NOT NULL,
  email          VARCHAR(150)        NOT NULL,
  password_hash  VARCHAR(255)        NOT NULL,
  role           ENUM('freelancer','client','admin') NOT NULL,
  language_pref  ENUM('tamil','english','sinhala')    NOT NULL DEFAULT 'english',
  phone          VARCHAR(20)         NULL,
  photo_path     VARCHAR(255)        NULL,   -- profile photo, relative to the language folder (e.g. assets/uploads/avatars/user_12.jpg)
  is_active      TINYINT(1)          NOT NULL DEFAULT 1,
  created_at     TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP
                                      ON UPDATE CURRENT_TIMESTAMP,
                
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- FREELANCER_PROFILES
-- 1:1 extension of USERS for the 'freelancer' role.
-- ----------------------------------------------------------------------------
CREATE TABLE freelancer_profiles (
  profile_id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id         INT UNSIGNED        NOT NULL,
  skill_category  VARCHAR(100)        NULL,
  bio             TEXT                NULL,
  verified_badge  TINYINT(1)          NOT NULL DEFAULT 0,
  latitude        DECIMAL(10,7)       NULL,
  longitude       DECIMAL(10,7)       NULL,
  created_at      TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP
                                       ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_freelancer_user (user_id),
  CONSTRAINT fk_freelancer_user
    FOREIGN KEY (user_id) REFERENCES users(user_id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- CATEGORIES
-- Lookup table for service categories (e.g. Tutoring, Design, Repairs).
-- Category *names* are looked up per-language in the language files, not
-- stored per-language here — this table just holds a stable slug/key.
-- ----------------------------------------------------------------------------
CREATE TABLE categories (
  category_id    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_key   VARCHAR(60)         NOT NULL,   -- e.g. 'tutoring', 'photography'
  category_name  VARCHAR(100)        NOT NULL,   -- fallback/admin display name
  UNIQUE KEY uq_category_key (category_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- SERVICES
-- A service listing published by a freelancer under a category.
-- ----------------------------------------------------------------------------
CREATE TABLE services (
  service_id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  profile_id     INT UNSIGNED        NOT NULL,
  category_id    INT UNSIGNED        NOT NULL,
  title          VARCHAR(150)        NOT NULL,
  price          DECIMAL(10,2)       NOT NULL,
  description    TEXT                NULL,
  is_active      TINYINT(1)          NOT NULL DEFAULT 1,
  created_at     TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP
                                      ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_services_profile (profile_id),
  KEY idx_services_category (category_id),
  CONSTRAINT fk_services_profile
    FOREIGN KEY (profile_id) REFERENCES freelancer_profiles(profile_id)
    ON DELETE CASCADE,
  CONSTRAINT fk_services_category
    FOREIGN KEY (category_id) REFERENCES categories(category_id)
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- BOOKINGS
-- A client's booking request against a service.
-- ----------------------------------------------------------------------------
CREATE TABLE bookings (
  booking_id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  service_id     INT UNSIGNED        NOT NULL,
  client_id      INT UNSIGNED        NOT NULL,
  booking_date   DATE                NOT NULL,
  latitude       DECIMAL(10,7)       NULL,   -- client's location for this booking (optional, set on booking.php)
  longitude      DECIMAL(10,7)       NULL,
  status         ENUM('pending','confirmed','completed','cancelled','payment_failed')
                                      NOT NULL DEFAULT 'pending',
  created_at     TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP
                                      ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_bookings_service (service_id),
  KEY idx_bookings_client (client_id),
  CONSTRAINT fk_bookings_service
    FOREIGN KEY (service_id) REFERENCES services(service_id)
    ON DELETE RESTRICT,
  CONSTRAINT fk_bookings_client
    FOREIGN KEY (client_id) REFERENCES users(user_id)
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- PAYMENTS
-- 1:1 with BOOKINGS. Records the PayHere / Genie Pay transaction outcome.
-- ----------------------------------------------------------------------------
CREATE TABLE payments (
  payment_id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  booking_id     INT UNSIGNED        NOT NULL,
  amount         DECIMAL(10,2)       NOT NULL,
  gateway        ENUM('payhere','geniepay') NOT NULL,
  payment_status ENUM('pending','completed','failed','refunded')
                                      NOT NULL DEFAULT 'pending',
  gateway_ref    VARCHAR(150)        NULL,   -- transaction ID returned by the gateway
  paid_at        TIMESTAMP           NULL,
  created_at     TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_payment_booking (booking_id),
  CONSTRAINT fk_payments_booking
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- REVIEWS
-- Tied uniquely to a completed booking (one review per booking).
-- ----------------------------------------------------------------------------
CREATE TABLE reviews (
  review_id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  booking_id     INT UNSIGNED        NOT NULL,
  rating         TINYINT UNSIGNED    NOT NULL,   -- 1 to 5
  comment        TEXT                NULL,
  created_at     TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_review_booking (booking_id),
  CONSTRAINT fk_reviews_booking
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id)
    ON DELETE CASCADE,
  CONSTRAINT chk_review_rating CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- MESSAGES
-- Independent of the booking lifecycle — pre-booking and post-booking chat.
-- ----------------------------------------------------------------------------
CREATE TABLE messages (
  message_id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sender_id      INT UNSIGNED        NOT NULL,
  receiver_id    INT UNSIGNED        NOT NULL,
  content        TEXT                NOT NULL,
  is_read        TINYINT(1)          NOT NULL DEFAULT 0,
  sent_at        TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_messages_sender (sender_id),
  KEY idx_messages_receiver (receiver_id),
  CONSTRAINT fk_messages_sender
    FOREIGN KEY (sender_id) REFERENCES users(user_id)
    ON DELETE CASCADE,
  CONSTRAINT fk_messages_receiver
    FOREIGN KEY (receiver_id) REFERENCES users(user_id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Seed data — starter categories (extend as needed)
-- ----------------------------------------------------------------------------
INSERT INTO categories (category_key, category_name) VALUES
  ('tutoring',      'Tutoring'),
  ('photography',   'Photography'),
  ('web_design',    'Web Design'),
  ('electrical',    'Electrical Repair'),
  ('plumbing',      'Plumbing'),
  ('graphic_design','Graphic Design'),
  ('writing',       'Content Writing'),
  ('event_services','Event Services');

ALTER TABLE payments MODIFY gateway ENUM('payhere','geniepay','stripe') NOT NULL;