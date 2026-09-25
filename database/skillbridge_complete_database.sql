-- ============================================================================
-- SkillBridge.lk — COMPLETE DATABASE (Merged)
-- ============================================================================
-- Combines schema + all migrations + 100 freelancer demo data + 1 admin account.
-- Demo password for freelancer accounts: Passw0rd!
-- Admin login: guruaprashath / Guru@gmail.com / Guru@2004
--
-- freelancer_profiles.latitude / longitude contain Sri Lankan coordinates.
-- Kandy has multiple demo freelancers intentionally placed within 5 km and
-- 10 km, so the existing Nearby / radius filter can be demonstrated.
-- ============================================================================

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
  last_seen      TIMESTAMP           NULL DEFAULT NULL,
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
  language       ENUM('tamil','english','sinhala') NOT NULL DEFAULT 'english',
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

-- ============================================================================
-- SkillBridge.lk — Refund Requests + Notifications
-- Run once against gpss_database
-- ============================================================================
USE gpss_database;

CREATE TABLE refund_requests (
  refund_id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  booking_id       INT UNSIGNED        NOT NULL,
  payment_id       INT UNSIGNED        NOT NULL,
  client_id        INT UNSIGNED        NOT NULL,
  freelancer_id    INT UNSIGNED        NOT NULL,
  amount           DECIMAL(10,2)       NOT NULL,
  reason           TEXT                NULL,
  account_number   VARCHAR(50)         NULL,
  status           ENUM('pending','refunded','rejected') NOT NULL DEFAULT 'pending',
  processed_by     ENUM('freelancer','admin') NULL,
  stripe_refund_id VARCHAR(150)        NULL,
  requested_at     TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
  decided_at       TIMESTAMP           NULL,
  refunded_at      TIMESTAMP           NULL,
  KEY idx_refund_booking (booking_id),
  KEY idx_refund_client (client_id),
  KEY idx_refund_freelancer (freelancer_id),
  CONSTRAINT fk_refund_booking FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE,
  CONSTRAINT fk_refund_payment FOREIGN KEY (payment_id) REFERENCES payments(payment_id) ON DELETE CASCADE,
  CONSTRAINT fk_refund_client FOREIGN KEY (client_id) REFERENCES users(user_id) ON DELETE CASCADE,
  CONSTRAINT fk_refund_freelancer FOREIGN KEY (freelancer_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notifications (
  notification_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id          INT UNSIGNED        NOT NULL,
  type             VARCHAR(50)         NOT NULL,
  message          VARCHAR(255)        NOT NULL,
  related_id       INT UNSIGNED        NULL,
  is_read          TINYINT(1)          NOT NULL DEFAULT 0,
  created_at       TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_notifications_user (user_id, is_read),
  CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

USE gpss_database;

SET @demo_hash = '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu';

INSERT INTO users
  (user_id, full_name, email, password_hash, role, language_pref, phone, is_active)
VALUES
(1, 'guruaprashath', 'Guru@gmail.com', '$2y$12$NkkUdLUDenobLX2i0fniLOWtRcZUDFqIEenxryeAqWOn96xm/JuPe', 'admin', 'english', NULL, 1),
(2, 'Kavindu Silva', 'kavindu.silva.2@example.com', @demo_hash, 'freelancer', 'sinhala', '+94710000002', 1),
(3, 'Abirami Raj', 'abirami.raj.3@example.com', @demo_hash, 'freelancer', 'tamil', '+94710000003', 1),
(4, 'Dilshan Fernando', 'dilshan.fernando.4@example.com', @demo_hash, 'freelancer', 'english', '+94710000004', 1),
(5, 'Sanduni Wickrama', 'sanduni.wickrama.5@example.com', @demo_hash, 'freelancer', 'sinhala', '+94710000005', 1),
(6, 'Karthik Selvam', 'karthik.selvam.6@example.com', @demo_hash, 'freelancer', 'tamil', '+94710000006', 1),
(7, 'Ruwan Jayasuriya', 'ruwan.jayasuriya.7@example.com', @demo_hash, 'freelancer', 'english', '+94710000007', 1),
(8, 'Ishara Bandara', 'ishara.bandara.8@example.com', @demo_hash, 'freelancer', 'sinhala', '+94710000008', 1),
(9, 'Priya Kumaran', 'priya.kumaran.9@example.com', @demo_hash, 'freelancer', 'tamil', '+94710000009', 1),
(10, 'Chamara Gunawardena', 'chamara.gunawardena.10@example.com', @demo_hash, 'freelancer', 'english', '+94710000010', 1),
(11, 'Nuwan Rathnayake', 'nuwan.rathnayake.11@example.com', @demo_hash, 'freelancer', 'sinhala', '+94710000011', 1),
(12, 'Suresh Kandiah', 'suresh.kandiah.12@example.com', @demo_hash, 'freelancer', 'tamil', '+94710000012', 1),
(13, 'Lasantha Peiris', 'lasantha.peiris.13@example.com', @demo_hash, 'freelancer', 'english', '+94710000013', 1),
(14, 'Tharindu Madushan', 'tharindu.madushan.14@example.com', @demo_hash, 'freelancer', 'sinhala', '+94710000014', 1),
(15, 'Vignesh Ramasamy', 'vignesh.ramasamy.15@example.com', @demo_hash, 'freelancer', 'tamil', '+94710000015', 1),
(16, 'Yasodha Wijeratne', 'yasodha.wijeratne.16@example.com', @demo_hash, 'freelancer', 'english', '+94710000016', 1),
(17, 'Hasitha Karunaratne', 'hasitha.karunaratne.17@example.com', @demo_hash, 'freelancer', 'sinhala', '+94710000017', 1),
(18, 'Divya Chandran', 'divya.chandran.18@example.com', @demo_hash, 'freelancer', 'tamil', '+94710000018', 1),
(19, 'Ashan Dissanayake', 'ashan.dissanayake.19@example.com', @demo_hash, 'freelancer', 'english', '+94710000019', 1),
(20, 'Menaka Rajapaksha', 'menaka.rajapaksha.20@example.com', @demo_hash, 'freelancer', 'sinhala', '+94710000020', 1),
(21, 'Deepan Murugesan', 'deepan.murugesan.21@example.com', @demo_hash, 'freelancer', 'tamil', '+94710000021', 1),
(22, 'Chathura Amarasinghe', 'chathura.amarasinghe.22@example.com', @demo_hash, 'freelancer', 'english', '+94710000022', 1),
(23, 'Iresha Senanayake', 'iresha.senanayake.23@example.com', @demo_hash, 'freelancer', 'sinhala', '+94710000023', 1),
(24, 'Kirushanth Pillai', 'kirushanth.pillai.24@example.com', @demo_hash, 'freelancer', 'tamil', '+94710000024', 1),
(25, 'Malith Samarasinghe', 'malith.samarasinghe.25@example.com', @demo_hash, 'freelancer', 'english', '+94710000025', 1),
(26, 'Hiruni Herath', 'hiruni.herath.26@example.com', @demo_hash, 'freelancer', 'sinhala', '+94710000026', 1),
(27, 'Dinesh Gunasekara', 'dinesh.gunasekara.27@example.com', @demo_hash, 'freelancer', 'tamil', '+94710000027', 1),
(28, 'Sachini Ekanayake', 'sachini.ekanayake.28@example.com', @demo_hash, 'freelancer', 'english', '+94710000028', 1),
(29, 'Kasun Weerasinghe', 'kasun.weerasinghe.29@example.com', @demo_hash, 'freelancer', 'sinhala', '+94710000029', 1),
(30, 'Piumi Jayawardena', 'piumi.jayawardena.30@example.com', @demo_hash, 'freelancer', 'tamil', '+94710000030', 1),
(31, 'Tharuka De Silva', 'tharuka.de.silva.31@example.com', @demo_hash, 'freelancer', 'english', '+94710000031', 1),
(32, 'Imesha Karunathilaka', 'imesha.karunathilaka.32@example.com', @demo_hash, 'freelancer', 'sinhala', '+94710000032', 1),
(33, 'Supun Pathirana', 'supun.pathirana.33@example.com', @demo_hash, 'freelancer', 'tamil', '+94710000033', 1),
(34, 'Nimasha Abeysekera', 'nimasha.abeysekera.34@example.com', @demo_hash, 'freelancer', 'english', '+94710000034', 1),
(35, 'Roshan Wijesinghe', 'roshan.wijesinghe.35@example.com', @demo_hash, 'freelancer', 'sinhala', '+94710000035', 1),
(36, 'Amaya Hettiarachchi', 'amaya.hettiarachchi.36@example.com', @demo_hash, 'freelancer', 'tamil', '+94710000036', 1),
(37, 'Janith Dias', 'janith.dias.37@example.com', @demo_hash, 'freelancer', 'english', '+94710000037', 1),
(38, 'Shenali Mendis', 'shenali.mendis.38@example.com', @demo_hash, 'freelancer', 'sinhala', '+94710000038', 1),
(39, 'Pasindu Fonseka', 'pasindu.fonseka.39@example.com', @demo_hash, 'freelancer', 'tamil', '+94710000039', 1),
(40, 'Ayesha Fernando', 'ayesha.fernando.40@example.com', @demo_hash, 'freelancer', 'english', '+94710000040', 1),
(41, 'Ravindu Arul', 'ravindu.arul.41@example.com', @demo_hash, 'freelancer', 'sinhala', '+94710000041', 1),
(42, 'Madhavi Nadarajah', 'madhavi.nadarajah.42@example.com', @demo_hash, 'freelancer', 'tamil', '+94710000042', 1),
(43, 'Dulshan Thiruchelvam', 'dulshan.thiruchelvam.43@example.com', @demo_hash, 'freelancer', 'english', '+94710000043', 1),
(44, 'Anjali Rajan', 'anjali.rajan.44@example.com', @demo_hash, 'freelancer', 'sinhala', '+94710000044', 1),
(45, 'Sahan Kumar', 'sahan.kumar.45@example.com', @demo_hash, 'freelancer', 'tamil', '+94710000045', 1),
(46, 'Dinuka Sivakumar', 'dinuka.sivakumar.46@example.com', @demo_hash, 'freelancer', 'english', '+94710000046', 1),
(47, 'Isuru Manoharan', 'isuru.manoharan.47@example.com', @demo_hash, 'freelancer', 'sinhala', '+94710000047', 1),
(48, 'Thilini Sureshkumar', 'thilini.sureshkumar.48@example.com', @demo_hash, 'freelancer', 'tamil', '+94710000048', 1),
(49, 'Gihan Jegan', 'gihan.jegan.49@example.com', @demo_hash, 'freelancer', 'english', '+94710000049', 1),
(50, 'Navoda Balasingam', 'navoda.balasingam.50@example.com', @demo_hash, 'freelancer', 'sinhala', '+94710000050', 1),
(51, 'Sewmini Perera', 'sewmini.perera.51@example.com', @demo_hash, 'freelancer', 'tamil', '+94710000051', 1),
(52, 'Akila Silva', 'akila.silva.52@example.com', @demo_hash, 'freelancer', 'english', '+94710000052', 1),
(53, 'Harini Raj', 'harini.raj.53@example.com', @demo_hash, 'freelancer', 'sinhala', '+94710000053', 1),
(54, 'Lakshan Fernando', 'lakshan.fernando.54@example.com', @demo_hash, 'freelancer', 'tamil', '+94710000054', 1),
(55, 'Yuvani Wickrama', 'yuvani.wickrama.55@example.com', @demo_hash, 'freelancer', 'english', '+94710000055', 1),
(56, 'Prabath Selvam', 'prabath.selvam.56@example.com', @demo_hash, 'freelancer', 'sinhala', '+94710000056', 1),
(57, 'Sandaru Jayasuriya', 'sandaru.jayasuriya.57@example.com', @demo_hash, 'freelancer', 'tamil', '+94710000057', 1),
(58, 'Nethmi Bandara', 'nethmi.bandara.58@example.com', @demo_hash, 'freelancer', 'english', '+94710000058', 1),
(59, 'Vihanga Kumaran', 'vihanga.kumaran.59@example.com', @demo_hash, 'freelancer', 'sinhala', '+94710000059', 1),
(60, 'Rashmi Gunawardena', 'rashmi.gunawardena.60@example.com', @demo_hash, 'freelancer', 'tamil', '+94710000060', 1),
(61, 'Chathurika Rathnayake', 'chathurika.rathnayake.61@example.com', @demo_hash, 'freelancer', 'english', '+94710000061', 1),
(62, 'Sajith Kandiah', 'sajith.kandiah.62@example.com', @demo_hash, 'freelancer', 'sinhala', '+94710000062', 1),
(63, 'Udara Peiris', 'udara.peiris.63@example.com', @demo_hash, 'freelancer', 'tamil', '+94710000063', 1),
(64, 'Heshan Madushan', 'heshan.madushan.64@example.com', @demo_hash, 'freelancer', 'english', '+94710000064', 1),
(65, 'Nadeeka Ramasamy', 'nadeeka.ramasamy.65@example.com', @demo_hash, 'freelancer', 'sinhala', '+94710000065', 1),
(66, 'Rukshan Wijeratne', 'rukshan.wijeratne.66@example.com', @demo_hash, 'freelancer', 'tamil', '+94710000066', 1),
(67, 'Shalini Karunaratne', 'shalini.karunaratne.67@example.com', @demo_hash, 'freelancer', 'english', '+94710000067', 1),
(68, 'Arjun Chandran', 'arjun.chandran.68@example.com', @demo_hash, 'freelancer', 'sinhala', '+94710000068', 1),
(69, 'Fathima Dissanayake', 'fathima.dissanayake.69@example.com', @demo_hash, 'freelancer', 'tamil', '+94710000069', 1),
(70, 'Mohan Rajapaksha', 'mohan.rajapaksha.70@example.com', @demo_hash, 'freelancer', 'english', '+94710000070', 1),
(71, 'Sivani Murugesan', 'sivani.murugesan.71@example.com', @demo_hash, 'freelancer', 'sinhala', '+94710000071', 1),
(72, 'Tharshan Amarasinghe', 'tharshan.amarasinghe.72@example.com', @demo_hash, 'freelancer', 'tamil', '+94710000072', 1),
(73, 'Vimal Senanayake', 'vimal.senanayake.73@example.com', @demo_hash, 'freelancer', 'english', '+94710000073', 1),
(74, 'Keshan Pillai', 'keshan.pillai.74@example.com', @demo_hash, 'freelancer', 'sinhala', '+94710000074', 1),
(75, 'Rithika Samarasinghe', 'rithika.samarasinghe.75@example.com', @demo_hash, 'freelancer', 'tamil', '+94710000075', 1),
(76, 'Pradeep Herath', 'pradeep.herath.76@example.com', @demo_hash, 'freelancer', 'english', '+94710000076', 1),
(77, 'Nirasha Gunasekara', 'nirasha.gunasekara.77@example.com', @demo_hash, 'freelancer', 'sinhala', '+94710000077', 1),
(78, 'Amith Ekanayake', 'amith.ekanayake.78@example.com', @demo_hash, 'freelancer', 'tamil', '+94710000078', 1),
(79, 'Bhagya Weerasinghe', 'bhagya.weerasinghe.79@example.com', @demo_hash, 'freelancer', 'english', '+94710000079', 1),
(80, 'Ramesh Jayawardena', 'ramesh.jayawardena.80@example.com', @demo_hash, 'freelancer', 'sinhala', '+94710000080', 1),
(81, 'Tharindu2 De Silva', 'tharindu2.de.silva.81@example.com', @demo_hash, 'freelancer', 'tamil', '+94710000081', 1),
(82, 'Gayani Karunathilaka', 'gayani.karunathilaka.82@example.com', @demo_hash, 'freelancer', 'english', '+94710000082', 1),
(83, 'Chandima Pathirana', 'chandima.pathirana.83@example.com', @demo_hash, 'freelancer', 'sinhala', '+94710000083', 1),
(84, 'Maneesha Abeysekera', 'maneesha.abeysekera.84@example.com', @demo_hash, 'freelancer', 'tamil', '+94710000084', 1),
(85, 'Kusal Wijesinghe', 'kusal.wijesinghe.85@example.com', @demo_hash, 'freelancer', 'english', '+94710000085', 1),
(86, 'Nuwangi Hettiarachchi', 'nuwangi.hettiarachchi.86@example.com', @demo_hash, 'freelancer', 'sinhala', '+94710000086', 1),
(87, 'Praveen Dias', 'praveen.dias.87@example.com', @demo_hash, 'freelancer', 'tamil', '+94710000087', 1),
(88, 'Ishani Mendis', 'ishani.mendis.88@example.com', @demo_hash, 'freelancer', 'english', '+94710000088', 1),
(89, 'Roshan2 Fonseka', 'roshan2.fonseka.89@example.com', @demo_hash, 'freelancer', 'sinhala', '+94710000089', 1),
(90, 'Tharushi Fernando', 'tharushi.fernando.90@example.com', @demo_hash, 'freelancer', 'tamil', '+94710000090', 1),
(91, 'Dhanush Arul', 'dhanush.arul.91@example.com', @demo_hash, 'freelancer', 'english', '+94710000091', 1),
(92, 'Sithum Nadarajah', 'sithum.nadarajah.92@example.com', @demo_hash, 'freelancer', 'sinhala', '+94710000092', 1),
(93, 'Kavisha Thiruchelvam', 'kavisha.thiruchelvam.93@example.com', @demo_hash, 'freelancer', 'tamil', '+94710000093', 1),
(94, 'Dineth Rajan', 'dineth.rajan.94@example.com', @demo_hash, 'freelancer', 'english', '+94710000094', 1),
(95, 'Mihiri Kumar', 'mihiri.kumar.95@example.com', @demo_hash, 'freelancer', 'sinhala', '+94710000095', 1),
(96, 'Sanjaya Sivakumar', 'sanjaya.sivakumar.96@example.com', @demo_hash, 'freelancer', 'tamil', '+94710000096', 1),
(97, 'Yasiru Manoharan', 'yasiru.manoharan.97@example.com', @demo_hash, 'freelancer', 'english', '+94710000097', 1),
(98, 'Lakmali Sureshkumar', 'lakmali.sureshkumar.98@example.com', @demo_hash, 'freelancer', 'sinhala', '+94710000098', 1),
(99, 'Rashan Jegan', 'rashan.jegan.99@example.com', @demo_hash, 'freelancer', 'tamil', '+94710000099', 1),
(100, 'Oshini Balasingam', 'oshini.balasingam.100@example.com', @demo_hash, 'freelancer', 'english', '+94710000100', 1),
(101, 'Nadeesha Perera', 'nadeesha.perera.101@example.com', @demo_hash, 'freelancer', 'english', '+94710000101', 1);

INSERT INTO freelancer_profiles
  (profile_id, user_id, skill_category, bio, verified_badge, latitude, longitude)
VALUES
(1, 101, 'Tutoring', 'Experienced tutoring professional based in Kandy, Sri Lanka, ready to help with your next project.', 1, 7.3165669, 80.6378481),
(2, 2, 'Photography', 'Experienced photography professional based in Kandy, Sri Lanka, ready to help with your next project.', 1, 7.2933545, 80.6500420),
(3, 3, 'Web Design', 'Experienced web design professional based in Kandy, Sri Lanka, ready to help with your next project.', 1, 7.2777493, 80.6075880),
(4, 4, 'Electrical Repair', 'Experienced electrical repair professional based in Kandy, Sri Lanka, ready to help with your next project.', 0, 7.3189020, 80.6510467),
(5, 5, 'Plumbing', 'Experienced plumbing professional based in Kandy, Sri Lanka, ready to help with your next project.', 1, 7.3106556, 80.6375303),
(6, 6, 'Graphic Design', 'Experienced graphic design professional based in Kandy, Sri Lanka, ready to help with your next project.', 1, 7.2341884, 80.6317857),
(7, 7, 'Content Writing', 'Experienced content writing professional based in Kandy, Sri Lanka, ready to help with your next project.', 1, 7.3065198, 80.6818948),
(8, 8, 'Event Services', 'Experienced event services professional based in Kandy, Sri Lanka, ready to help with your next project.', 0, 7.2233325, 80.6140247),
(9, 9, 'Tutoring', 'Experienced tutoring professional based in Kandy, Sri Lanka, ready to help with your next project.', 1, 7.2427560, 80.6034010),
(10, 10, 'Photography', 'Experienced photography professional based in Kandy, Sri Lanka, ready to help with your next project.', 1, 7.3656096, 80.6367895),
(11, 11, 'Web Design', 'Experienced web design professional based in Kandy, Sri Lanka, ready to help with your next project.', 1, 7.2463755, 80.5017505),
(12, 12, 'Electrical Repair', 'Experienced electrical repair professional based in Kandy, Sri Lanka, ready to help with your next project.', 0, 7.3538260, 80.7281031),
(13, 13, 'Plumbing', 'Experienced plumbing professional based in Kandy, Sri Lanka, ready to help with your next project.', 1, 7.2148508, 80.7599253),
(14, 14, 'Graphic Design', 'Experienced graphic design professional based in Kandy, Sri Lanka, ready to help with your next project.', 1, 7.3723752, 80.6910367),
(15, 15, 'Content Writing', 'Experienced content writing professional based in Kandy, Sri Lanka, ready to help with your next project.', 1, 7.1789729, 80.5478414),
(16, 16, 'Event Services', 'Experienced event services professional based in Colombo, Sri Lanka, ready to help with your next project.', 0, 6.9167207, 79.7795425),
(17, 17, 'Tutoring', 'Experienced tutoring professional based in Colombo, Sri Lanka, ready to help with your next project.', 1, 6.9836020, 79.8514931),
(18, 18, 'Photography', 'Experienced photography professional based in Colombo, Sri Lanka, ready to help with your next project.', 1, 6.8862730, 79.8472515),
(19, 19, 'Web Design', 'Experienced web design professional based in Colombo, Sri Lanka, ready to help with your next project.', 1, 6.8655329, 79.8040356),
(20, 20, 'Electrical Repair', 'Experienced electrical repair professional based in Colombo, Sri Lanka, ready to help with your next project.', 0, 6.8504927, 79.8204320),
(21, 21, 'Plumbing', 'Experienced plumbing professional based in Colombo, Sri Lanka, ready to help with your next project.', 1, 6.9966002, 79.8819340),
(22, 22, 'Graphic Design', 'Experienced graphic design professional based in Colombo, Sri Lanka, ready to help with your next project.', 1, 6.9198637, 79.8900510),
(23, 23, 'Content Writing', 'Experienced content writing professional based in Colombo, Sri Lanka, ready to help with your next project.', 1, 6.9288480, 79.8774213),
(24, 24, 'Event Services', 'Experienced event services professional based in Colombo, Sri Lanka, ready to help with your next project.', 0, 6.9239336, 79.8791604),
(25, 25, 'Tutoring', 'Experienced tutoring professional based in Colombo, Sri Lanka, ready to help with your next project.', 1, 6.8833221, 79.9113279),
(26, 26, 'Photography', 'Experienced photography professional based in Colombo, Sri Lanka, ready to help with your next project.', 1, 6.9377612, 79.9024965),
(27, 27, 'Web Design', 'Experienced web design professional based in Colombo, Sri Lanka, ready to help with your next project.', 1, 6.9575768, 79.8482918),
(28, 28, 'Electrical Repair', 'Experienced electrical repair professional based in Negombo, Sri Lanka, ready to help with your next project.', 0, 7.1561409, 79.7927878),
(29, 29, 'Plumbing', 'Experienced plumbing professional based in Negombo, Sri Lanka, ready to help with your next project.', 1, 7.2051056, 79.8113899),
(30, 30, 'Graphic Design', 'Experienced graphic design professional based in Negombo, Sri Lanka, ready to help with your next project.', 1, 7.1910573, 79.8522332),
(31, 31, 'Content Writing', 'Experienced content writing professional based in Negombo, Sri Lanka, ready to help with your next project.', 1, 7.1457334, 79.7595677),
(32, 32, 'Event Services', 'Experienced event services professional based in Negombo, Sri Lanka, ready to help with your next project.', 0, 7.1846634, 79.7811079),
(33, 33, 'Tutoring', 'Experienced tutoring professional based in Gampaha, Sri Lanka, ready to help with your next project.', 1, 7.1011146, 79.9299440),
(34, 34, 'Photography', 'Experienced photography professional based in Gampaha, Sri Lanka, ready to help with your next project.', 1, 7.1163431, 80.0203842),
(35, 35, 'Web Design', 'Experienced web design professional based in Gampaha, Sri Lanka, ready to help with your next project.', 1, 7.0831365, 80.0518823),
(36, 36, 'Electrical Repair', 'Experienced electrical repair professional based in Gampaha, Sri Lanka, ready to help with your next project.', 0, 7.1135332, 80.0044887),
(37, 37, 'Plumbing', 'Experienced plumbing professional based in Gampaha, Sri Lanka, ready to help with your next project.', 1, 7.0525299, 80.0958193),
(38, 38, 'Graphic Design', 'Experienced graphic design professional based in Kurunegala, Sri Lanka, ready to help with your next project.', 1, 7.4323588, 80.4065559),
(39, 39, 'Content Writing', 'Experienced content writing professional based in Kurunegala, Sri Lanka, ready to help with your next project.', 1, 7.3979371, 80.3882694),
(40, 40, 'Event Services', 'Experienced event services professional based in Kurunegala, Sri Lanka, ready to help with your next project.', 0, 7.4869965, 80.3978473),
(41, 41, 'Tutoring', 'Experienced tutoring professional based in Kurunegala, Sri Lanka, ready to help with your next project.', 1, 7.4815350, 80.4246025),
(42, 42, 'Photography', 'Experienced photography professional based in Kurunegala, Sri Lanka, ready to help with your next project.', 1, 7.5356951, 80.3274521),
(43, 43, 'Web Design', 'Experienced web design professional based in Kurunegala, Sri Lanka, ready to help with your next project.', 1, 7.4949190, 80.4092374),
(44, 44, 'Electrical Repair', 'Experienced electrical repair professional based in Dambulla, Sri Lanka, ready to help with your next project.', 0, 7.7743998, 80.7658289),
(45, 45, 'Plumbing', 'Experienced plumbing professional based in Dambulla, Sri Lanka, ready to help with your next project.', 1, 7.8895509, 80.7768653),
(46, 46, 'Graphic Design', 'Experienced graphic design professional based in Dambulla, Sri Lanka, ready to help with your next project.', 1, 7.8599515, 80.7581120),
(47, 47, 'Content Writing', 'Experienced content writing professional based in Dambulla, Sri Lanka, ready to help with your next project.', 1, 7.8021545, 80.8099173),
(48, 48, 'Event Services', 'Experienced event services professional based in Anuradhapura, Sri Lanka, ready to help with your next project.', 0, 8.3005586, 80.4137810),
(49, 49, 'Tutoring', 'Experienced tutoring professional based in Anuradhapura, Sri Lanka, ready to help with your next project.', 1, 8.2142980, 80.3855455),
(50, 50, 'Photography', 'Experienced photography professional based in Anuradhapura, Sri Lanka, ready to help with your next project.', 1, 8.3732708, 80.3288667),
(51, 51, 'Web Design', 'Experienced web design professional based in Anuradhapura, Sri Lanka, ready to help with your next project.', 1, 8.3095628, 80.3937213),
(52, 52, 'Electrical Repair', 'Experienced electrical repair professional based in Anuradhapura, Sri Lanka, ready to help with your next project.', 0, 8.2428671, 80.3873157),
(53, 53, 'Plumbing', 'Experienced plumbing professional based in Jaffna, Sri Lanka, ready to help with your next project.', 1, 9.6405891, 79.9995416),
(54, 54, 'Graphic Design', 'Experienced graphic design professional based in Jaffna, Sri Lanka, ready to help with your next project.', 1, 9.6440199, 80.0332042),
(55, 55, 'Content Writing', 'Experienced content writing professional based in Jaffna, Sri Lanka, ready to help with your next project.', 1, 9.7092994, 80.0110212),
(56, 56, 'Event Services', 'Experienced event services professional based in Jaffna, Sri Lanka, ready to help with your next project.', 0, 9.6541128, 80.1143642),
(57, 57, 'Tutoring', 'Experienced tutoring professional based in Jaffna, Sri Lanka, ready to help with your next project.', 1, 9.6849514, 80.0749620),
(58, 58, 'Photography', 'Experienced photography professional based in Trincomalee, Sri Lanka, ready to help with your next project.', 1, 8.6500660, 81.1481500),
(59, 59, 'Web Design', 'Experienced web design professional based in Trincomalee, Sri Lanka, ready to help with your next project.', 1, 8.5643370, 81.1873815),
(60, 60, 'Electrical Repair', 'Experienced electrical repair professional based in Trincomalee, Sri Lanka, ready to help with your next project.', 0, 8.6240148, 81.2681277),
(61, 61, 'Plumbing', 'Experienced plumbing professional based in Trincomalee, Sri Lanka, ready to help with your next project.', 1, 8.5120627, 81.1959539),
(62, 62, 'Graphic Design', 'Experienced graphic design professional based in Trincomalee, Sri Lanka, ready to help with your next project.', 1, 8.5096797, 81.2000249),
(63, 63, 'Content Writing', 'Experienced content writing professional based in Batticaloa, Sri Lanka, ready to help with your next project.', 1, 7.7269295, 81.6828689),
(64, 64, 'Event Services', 'Experienced event services professional based in Batticaloa, Sri Lanka, ready to help with your next project.', 0, 7.7407131, 81.6700194),
(65, 65, 'Tutoring', 'Experienced tutoring professional based in Batticaloa, Sri Lanka, ready to help with your next project.', 1, 7.7742840, 81.5971774),
(66, 66, 'Photography', 'Experienced photography professional based in Batticaloa, Sri Lanka, ready to help with your next project.', 1, 7.7653082, 81.6878887),
(67, 67, 'Web Design', 'Experienced web design professional based in Nuwara Eliya, Sri Lanka, ready to help with your next project.', 1, 7.0329594, 80.7600584),
(68, 68, 'Electrical Repair', 'Experienced electrical repair professional based in Nuwara Eliya, Sri Lanka, ready to help with your next project.', 0, 6.9330392, 80.7905812),
(69, 69, 'Plumbing', 'Experienced plumbing professional based in Nuwara Eliya, Sri Lanka, ready to help with your next project.', 1, 6.9507148, 80.7737769),
(70, 70, 'Graphic Design', 'Experienced graphic design professional based in Nuwara Eliya, Sri Lanka, ready to help with your next project.', 1, 7.0036687, 80.8458361),
(71, 71, 'Content Writing', 'Experienced content writing professional based in Matale, Sri Lanka, ready to help with your next project.', 1, 7.4181896, 80.6073089),
(72, 72, 'Event Services', 'Experienced event services professional based in Matale, Sri Lanka, ready to help with your next project.', 0, 7.4903772, 80.5995705),
(73, 73, 'Tutoring', 'Experienced tutoring professional based in Matale, Sri Lanka, ready to help with your next project.', 1, 7.4787041, 80.6695700),
(74, 74, 'Photography', 'Experienced photography professional based in Matale, Sri Lanka, ready to help with your next project.', 1, 7.4602568, 80.5657743),
(75, 75, 'Web Design', 'Experienced web design professional based in Badulla, Sri Lanka, ready to help with your next project.', 1, 6.9831411, 81.0803046),
(76, 76, 'Electrical Repair', 'Experienced electrical repair professional based in Badulla, Sri Lanka, ready to help with your next project.', 0, 6.9353467, 80.9746272),
(77, 77, 'Plumbing', 'Experienced plumbing professional based in Badulla, Sri Lanka, ready to help with your next project.', 1, 6.9452178, 81.0496174),
(78, 78, 'Graphic Design', 'Experienced graphic design professional based in Badulla, Sri Lanka, ready to help with your next project.', 1, 6.9965520, 81.0748066),
(79, 79, 'Content Writing', 'Experienced content writing professional based in Ratnapura, Sri Lanka, ready to help with your next project.', 1, 6.6492539, 80.3782655),
(80, 80, 'Event Services', 'Experienced event services professional based in Ratnapura, Sri Lanka, ready to help with your next project.', 0, 6.6883328, 80.4286208),
(81, 81, 'Tutoring', 'Experienced tutoring professional based in Ratnapura, Sri Lanka, ready to help with your next project.', 1, 6.6723328, 80.3878203),
(82, 82, 'Photography', 'Experienced photography professional based in Ratnapura, Sri Lanka, ready to help with your next project.', 1, 6.7073539, 80.3824942),
(83, 83, 'Web Design', 'Experienced web design professional based in Galle, Sri Lanka, ready to help with your next project.', 1, 6.1109262, 80.2542384),
(84, 84, 'Electrical Repair', 'Experienced electrical repair professional based in Galle, Sri Lanka, ready to help with your next project.', 0, 6.0180591, 80.1900624),
(85, 85, 'Plumbing', 'Experienced plumbing professional based in Galle, Sri Lanka, ready to help with your next project.', 1, 6.0519775, 80.2378326),
(86, 86, 'Graphic Design', 'Experienced graphic design professional based in Galle, Sri Lanka, ready to help with your next project.', 1, 5.9487512, 80.1763026),
(87, 87, 'Content Writing', 'Experienced content writing professional based in Galle, Sri Lanka, ready to help with your next project.', 1, 6.0440340, 80.1661432),
(88, 88, 'Event Services', 'Experienced event services professional based in Matara, Sri Lanka, ready to help with your next project.', 0, 5.9848007, 80.6315054),
(89, 89, 'Tutoring', 'Experienced tutoring professional based in Matara, Sri Lanka, ready to help with your next project.', 1, 5.9387975, 80.5624882),
(90, 90, 'Photography', 'Experienced photography professional based in Matara, Sri Lanka, ready to help with your next project.', 1, 5.9087396, 80.5647559),
(91, 91, 'Web Design', 'Experienced web design professional based in Matara, Sri Lanka, ready to help with your next project.', 1, 5.9203087, 80.4884422),
(92, 92, 'Electrical Repair', 'Experienced electrical repair professional based in Hambantota, Sri Lanka, ready to help with your next project.', 0, 6.2224855, 81.1781491),
(93, 93, 'Plumbing', 'Experienced plumbing professional based in Hambantota, Sri Lanka, ready to help with your next project.', 1, 6.1188049, 81.1597595),
(94, 94, 'Graphic Design', 'Experienced graphic design professional based in Hambantota, Sri Lanka, ready to help with your next project.', 1, 6.1436314, 81.2083344),
(95, 95, 'Content Writing', 'Experienced content writing professional based in Kegalle, Sri Lanka, ready to help with your next project.', 1, 7.2265060, 80.3547624),
(96, 96, 'Event Services', 'Experienced event services professional based in Kegalle, Sri Lanka, ready to help with your next project.', 0, 7.2429125, 80.3930352),
(97, 97, 'Tutoring', 'Experienced tutoring professional based in Kegalle, Sri Lanka, ready to help with your next project.', 1, 7.2792217, 80.3316700),
(98, 98, 'Photography', 'Experienced photography professional based in Kalutara, Sri Lanka, ready to help with your next project.', 1, 6.6169081, 79.9230153),
(99, 99, 'Web Design', 'Experienced web design professional based in Kalutara, Sri Lanka, ready to help with your next project.', 1, 6.6410531, 79.9791321),
(100, 100, 'Electrical Repair', 'Experienced electrical repair professional based in Puttalam, Sri Lanka, ready to help with your next project.', 0, 8.0917620, 79.7536410);

INSERT INTO services
  (service_id, profile_id, category_id, title, language, price, description)
VALUES
(1, 1, (SELECT category_id FROM categories WHERE category_key = 'tutoring'), 'One-on-One Tutoring Session', 'english', 2500, 'One-on-one tutoring for school and professional subjects.'),
(2, 2, (SELECT category_id FROM categories WHERE category_key = 'photography'), 'උත්සව සහ ඡායාරූප සේවාව', 'sinhala', 15000, 'Event, portrait and product photography services.'),
(3, 3, (SELECT category_id FROM categories WHERE category_key = 'web_design'), 'தனிப்பயன் இணையதள வடிவமைப்பு', 'tamil', 35000, 'Custom responsive website design and development.'),
(4, 4, (SELECT category_id FROM categories WHERE category_key = 'electrical'), 'Home Electrical Repair', 'english', 4000, 'Home electrical inspection, installation and repair.'),
(5, 5, (SELECT category_id FROM categories WHERE category_key = 'plumbing'), 'ජල නල සවි කිරීම සහ අලුත්වැඩියා', 'sinhala', 3500, 'Plumbing installation, maintenance and repair.'),
(6, 6, (SELECT category_id FROM categories WHERE category_key = 'graphic_design'), 'லோகோ மற்றும் பிராண்டிங் வடிவமைப்பு', 'tamil', 8000, 'Logo, branding and marketing graphic design.'),
(7, 7, (SELECT category_id FROM categories WHERE category_key = 'writing'), 'SEO Content Writing', 'english', 3000, 'SEO-friendly website and marketing content writing.'),
(8, 8, (SELECT category_id FROM categories WHERE category_key = 'event_services'), 'සම්පූර්ණ උත්සව සම්බන්ධීකරණය', 'sinhala', 25000, 'Event planning, coordination and on-site support.'),
(9, 9, (SELECT category_id FROM categories WHERE category_key = 'tutoring'), 'தனிநபர் பயிற்சி வகுப்பு', 'tamil', 2500, 'One-on-one tutoring for school and professional subjects.'),
(10, 10, (SELECT category_id FROM categories WHERE category_key = 'photography'), 'Event & Portrait Photography', 'english', 15000, 'Event, portrait and product photography services.'),
(11, 11, (SELECT category_id FROM categories WHERE category_key = 'web_design'), 'අභිරුචි වෙබ් අඩවි නිර්මාණය', 'sinhala', 35000, 'Custom responsive website design and development.'),
(12, 12, (SELECT category_id FROM categories WHERE category_key = 'electrical'), 'வீட்டு மின் பழுது', 'tamil', 4000, 'Home electrical inspection, installation and repair.'),
(13, 13, (SELECT category_id FROM categories WHERE category_key = 'plumbing'), 'Plumbing Installation & Repair', 'english', 3500, 'Plumbing installation, maintenance and repair.'),
(14, 14, (SELECT category_id FROM categories WHERE category_key = 'graphic_design'), 'ලාංඡන සහ බ්‍රෑන්ඩින් නිර්මාණය', 'sinhala', 8000, 'Logo, branding and marketing graphic design.'),
(15, 15, (SELECT category_id FROM categories WHERE category_key = 'writing'), 'SEO உள்ளடக்க எழுத்து', 'tamil', 3000, 'SEO-friendly website and marketing content writing.'),
(16, 16, (SELECT category_id FROM categories WHERE category_key = 'event_services'), 'Full Event Coordination', 'english', 25000, 'Event planning, coordination and on-site support.'),
(17, 17, (SELECT category_id FROM categories WHERE category_key = 'tutoring'), 'පුද්ගලික ටියුෂන් පන්තිය', 'sinhala', 2500, 'One-on-one tutoring for school and professional subjects.'),
(18, 18, (SELECT category_id FROM categories WHERE category_key = 'photography'), 'நிகழ்வு மற்றும் உருவப்பட புகைப்படம்', 'tamil', 15000, 'Event, portrait and product photography services.'),
(19, 19, (SELECT category_id FROM categories WHERE category_key = 'web_design'), 'Custom Website Design', 'english', 35000, 'Custom responsive website design and development.'),
(20, 20, (SELECT category_id FROM categories WHERE category_key = 'electrical'), 'නිවාස විදුලි අලුත්වැඩියා', 'sinhala', 4000, 'Home electrical inspection, installation and repair.'),
(21, 21, (SELECT category_id FROM categories WHERE category_key = 'plumbing'), 'குழாய் பொருத்துதல் மற்றும் பழுது', 'tamil', 3500, 'Plumbing installation, maintenance and repair.'),
(22, 22, (SELECT category_id FROM categories WHERE category_key = 'graphic_design'), 'Logo & Branding Design', 'english', 8000, 'Logo, branding and marketing graphic design.'),
(23, 23, (SELECT category_id FROM categories WHERE category_key = 'writing'), 'SEO අන්තර්ගත රචනය', 'sinhala', 3000, 'SEO-friendly website and marketing content writing.'),
(24, 24, (SELECT category_id FROM categories WHERE category_key = 'event_services'), 'முழு நிகழ்வு ஒருங்கிணைப்பு', 'tamil', 25000, 'Event planning, coordination and on-site support.'),
(25, 25, (SELECT category_id FROM categories WHERE category_key = 'tutoring'), 'One-on-One Tutoring Session', 'english', 2500, 'One-on-one tutoring for school and professional subjects.'),
(26, 26, (SELECT category_id FROM categories WHERE category_key = 'photography'), 'උත්සව සහ ඡායාරූප සේවාව', 'sinhala', 15000, 'Event, portrait and product photography services.'),
(27, 27, (SELECT category_id FROM categories WHERE category_key = 'web_design'), 'தனிப்பயன் இணையதள வடிவமைப்பு', 'tamil', 35000, 'Custom responsive website design and development.'),
(28, 28, (SELECT category_id FROM categories WHERE category_key = 'electrical'), 'Home Electrical Repair', 'english', 4000, 'Home electrical inspection, installation and repair.'),
(29, 29, (SELECT category_id FROM categories WHERE category_key = 'plumbing'), 'ජල නල සවි කිරීම සහ අලුත්වැඩියා', 'sinhala', 3500, 'Plumbing installation, maintenance and repair.'),
(30, 30, (SELECT category_id FROM categories WHERE category_key = 'graphic_design'), 'லோகோ மற்றும் பிராண்டிங் வடிவமைப்பு', 'tamil', 8000, 'Logo, branding and marketing graphic design.'),
(31, 31, (SELECT category_id FROM categories WHERE category_key = 'writing'), 'SEO Content Writing', 'english', 3000, 'SEO-friendly website and marketing content writing.'),
(32, 32, (SELECT category_id FROM categories WHERE category_key = 'event_services'), 'සම්පූර්ණ උත්සව සම්බන්ධීකරණය', 'sinhala', 25000, 'Event planning, coordination and on-site support.'),
(33, 33, (SELECT category_id FROM categories WHERE category_key = 'tutoring'), 'தனிநபர் பயிற்சி வகுப்பு', 'tamil', 2500, 'One-on-one tutoring for school and professional subjects.'),
(34, 34, (SELECT category_id FROM categories WHERE category_key = 'photography'), 'Event & Portrait Photography', 'english', 15000, 'Event, portrait and product photography services.'),
(35, 35, (SELECT category_id FROM categories WHERE category_key = 'web_design'), 'අභිරුචි වෙබ් අඩවි නිර්මාණය', 'sinhala', 35000, 'Custom responsive website design and development.'),
(36, 36, (SELECT category_id FROM categories WHERE category_key = 'electrical'), 'வீட்டு மின் பழுது', 'tamil', 4000, 'Home electrical inspection, installation and repair.'),
(37, 37, (SELECT category_id FROM categories WHERE category_key = 'plumbing'), 'Plumbing Installation & Repair', 'english', 3500, 'Plumbing installation, maintenance and repair.'),
(38, 38, (SELECT category_id FROM categories WHERE category_key = 'graphic_design'), 'ලාංඡන සහ බ්‍රෑන්ඩින් නිර්මාණය', 'sinhala', 8000, 'Logo, branding and marketing graphic design.'),
(39, 39, (SELECT category_id FROM categories WHERE category_key = 'writing'), 'SEO உள்ளடக்க எழுத்து', 'tamil', 3000, 'SEO-friendly website and marketing content writing.'),
(40, 40, (SELECT category_id FROM categories WHERE category_key = 'event_services'), 'Full Event Coordination', 'english', 25000, 'Event planning, coordination and on-site support.'),
(41, 41, (SELECT category_id FROM categories WHERE category_key = 'tutoring'), 'පුද්ගලික ටියුෂන් පන්තිය', 'sinhala', 2500, 'One-on-one tutoring for school and professional subjects.'),
(42, 42, (SELECT category_id FROM categories WHERE category_key = 'photography'), 'நிகழ்வு மற்றும் உருவப்பட புகைப்படம்', 'tamil', 15000, 'Event, portrait and product photography services.'),
(43, 43, (SELECT category_id FROM categories WHERE category_key = 'web_design'), 'Custom Website Design', 'english', 35000, 'Custom responsive website design and development.'),
(44, 44, (SELECT category_id FROM categories WHERE category_key = 'electrical'), 'නිවාස විදුලි අලුත්වැඩියා', 'sinhala', 4000, 'Home electrical inspection, installation and repair.'),
(45, 45, (SELECT category_id FROM categories WHERE category_key = 'plumbing'), 'குழாய் பொருத்துதல் மற்றும் பழுது', 'tamil', 3500, 'Plumbing installation, maintenance and repair.'),
(46, 46, (SELECT category_id FROM categories WHERE category_key = 'graphic_design'), 'Logo & Branding Design', 'english', 8000, 'Logo, branding and marketing graphic design.'),
(47, 47, (SELECT category_id FROM categories WHERE category_key = 'writing'), 'SEO අන්තර්ගත රචනය', 'sinhala', 3000, 'SEO-friendly website and marketing content writing.'),
(48, 48, (SELECT category_id FROM categories WHERE category_key = 'event_services'), 'முழு நிகழ்வு ஒருங்கிணைப்பு', 'tamil', 25000, 'Event planning, coordination and on-site support.'),
(49, 49, (SELECT category_id FROM categories WHERE category_key = 'tutoring'), 'One-on-One Tutoring Session', 'english', 2500, 'One-on-one tutoring for school and professional subjects.'),
(50, 50, (SELECT category_id FROM categories WHERE category_key = 'photography'), 'උත්සව සහ ඡායාරූප සේවාව', 'sinhala', 15000, 'Event, portrait and product photography services.'),
(51, 51, (SELECT category_id FROM categories WHERE category_key = 'web_design'), 'தனிப்பயன் இணையதள வடிவமைப்பு', 'tamil', 35000, 'Custom responsive website design and development.'),
(52, 52, (SELECT category_id FROM categories WHERE category_key = 'electrical'), 'Home Electrical Repair', 'english', 4000, 'Home electrical inspection, installation and repair.'),
(53, 53, (SELECT category_id FROM categories WHERE category_key = 'plumbing'), 'ජල නල සවි කිරීම සහ අලුත්වැඩියා', 'sinhala', 3500, 'Plumbing installation, maintenance and repair.'),
(54, 54, (SELECT category_id FROM categories WHERE category_key = 'graphic_design'), 'லோகோ மற்றும் பிராண்டிங் வடிவமைப்பு', 'tamil', 8000, 'Logo, branding and marketing graphic design.'),
(55, 55, (SELECT category_id FROM categories WHERE category_key = 'writing'), 'SEO Content Writing', 'english', 3000, 'SEO-friendly website and marketing content writing.'),
(56, 56, (SELECT category_id FROM categories WHERE category_key = 'event_services'), 'සම්පූර්ණ උත්සව සම්බන්ධීකරණය', 'sinhala', 25000, 'Event planning, coordination and on-site support.'),
(57, 57, (SELECT category_id FROM categories WHERE category_key = 'tutoring'), 'தனிநபர் பயிற்சி வகுப்பு', 'tamil', 2500, 'One-on-one tutoring for school and professional subjects.'),
(58, 58, (SELECT category_id FROM categories WHERE category_key = 'photography'), 'Event & Portrait Photography', 'english', 15000, 'Event, portrait and product photography services.'),
(59, 59, (SELECT category_id FROM categories WHERE category_key = 'web_design'), 'අභිරුචි වෙබ් අඩවි නිර්මාණය', 'sinhala', 35000, 'Custom responsive website design and development.'),
(60, 60, (SELECT category_id FROM categories WHERE category_key = 'electrical'), 'வீட்டு மின் பழுது', 'tamil', 4000, 'Home electrical inspection, installation and repair.'),
(61, 61, (SELECT category_id FROM categories WHERE category_key = 'plumbing'), 'Plumbing Installation & Repair', 'english', 3500, 'Plumbing installation, maintenance and repair.'),
(62, 62, (SELECT category_id FROM categories WHERE category_key = 'graphic_design'), 'ලාංඡන සහ බ්‍රෑන්ඩින් නිර්මාණය', 'sinhala', 8000, 'Logo, branding and marketing graphic design.'),
(63, 63, (SELECT category_id FROM categories WHERE category_key = 'writing'), 'SEO உள்ளடக்க எழுத்து', 'tamil', 3000, 'SEO-friendly website and marketing content writing.'),
(64, 64, (SELECT category_id FROM categories WHERE category_key = 'event_services'), 'Full Event Coordination', 'english', 25000, 'Event planning, coordination and on-site support.'),
(65, 65, (SELECT category_id FROM categories WHERE category_key = 'tutoring'), 'පුද්ගලික ටියුෂන් පන්තිය', 'sinhala', 2500, 'One-on-one tutoring for school and professional subjects.'),
(66, 66, (SELECT category_id FROM categories WHERE category_key = 'photography'), 'நிகழ்வு மற்றும் உருவப்பட புகைப்படம்', 'tamil', 15000, 'Event, portrait and product photography services.'),
(67, 67, (SELECT category_id FROM categories WHERE category_key = 'web_design'), 'Custom Website Design', 'english', 35000, 'Custom responsive website design and development.'),
(68, 68, (SELECT category_id FROM categories WHERE category_key = 'electrical'), 'නිවාස විදුලි අලුත්වැඩියා', 'sinhala', 4000, 'Home electrical inspection, installation and repair.'),
(69, 69, (SELECT category_id FROM categories WHERE category_key = 'plumbing'), 'குழாய் பொருத்துதல் மற்றும் பழுது', 'tamil', 3500, 'Plumbing installation, maintenance and repair.'),
(70, 70, (SELECT category_id FROM categories WHERE category_key = 'graphic_design'), 'Logo & Branding Design', 'english', 8000, 'Logo, branding and marketing graphic design.'),
(71, 71, (SELECT category_id FROM categories WHERE category_key = 'writing'), 'SEO අන්තර්ගත රචනය', 'sinhala', 3000, 'SEO-friendly website and marketing content writing.'),
(72, 72, (SELECT category_id FROM categories WHERE category_key = 'event_services'), 'முழு நிகழ்வு ஒருங்கிணைப்பு', 'tamil', 25000, 'Event planning, coordination and on-site support.'),
(73, 73, (SELECT category_id FROM categories WHERE category_key = 'tutoring'), 'One-on-One Tutoring Session', 'english', 2500, 'One-on-one tutoring for school and professional subjects.'),
(74, 74, (SELECT category_id FROM categories WHERE category_key = 'photography'), 'උත්සව සහ ඡායාරූප සේවාව', 'sinhala', 15000, 'Event, portrait and product photography services.'),
(75, 75, (SELECT category_id FROM categories WHERE category_key = 'web_design'), 'தனிப்பயன் இணையதள வடிவமைப்பு', 'tamil', 35000, 'Custom responsive website design and development.'),
(76, 76, (SELECT category_id FROM categories WHERE category_key = 'electrical'), 'Home Electrical Repair', 'english', 4000, 'Home electrical inspection, installation and repair.'),
(77, 77, (SELECT category_id FROM categories WHERE category_key = 'plumbing'), 'ජල නල සවි කිරීම සහ අලුත්වැඩියා', 'sinhala', 3500, 'Plumbing installation, maintenance and repair.'),
(78, 78, (SELECT category_id FROM categories WHERE category_key = 'graphic_design'), 'லோகோ மற்றும் பிராண்டிங் வடிவமைப்பு', 'tamil', 8000, 'Logo, branding and marketing graphic design.'),
(79, 79, (SELECT category_id FROM categories WHERE category_key = 'writing'), 'SEO Content Writing', 'english', 3000, 'SEO-friendly website and marketing content writing.'),
(80, 80, (SELECT category_id FROM categories WHERE category_key = 'event_services'), 'සම්පූර්ණ උත්සව සම්බන්ධීකරණය', 'sinhala', 25000, 'Event planning, coordination and on-site support.'),
(81, 81, (SELECT category_id FROM categories WHERE category_key = 'tutoring'), 'தனிநபர் பயிற்சி வகுப்பு', 'tamil', 2500, 'One-on-one tutoring for school and professional subjects.'),
(82, 82, (SELECT category_id FROM categories WHERE category_key = 'photography'), 'Event & Portrait Photography', 'english', 15000, 'Event, portrait and product photography services.'),
(83, 83, (SELECT category_id FROM categories WHERE category_key = 'web_design'), 'අභිරුචි වෙබ් අඩවි නිර්මාණය', 'sinhala', 35000, 'Custom responsive website design and development.'),
(84, 84, (SELECT category_id FROM categories WHERE category_key = 'electrical'), 'வீட்டு மின் பழுது', 'tamil', 4000, 'Home electrical inspection, installation and repair.'),
(85, 85, (SELECT category_id FROM categories WHERE category_key = 'plumbing'), 'Plumbing Installation & Repair', 'english', 3500, 'Plumbing installation, maintenance and repair.'),
(86, 86, (SELECT category_id FROM categories WHERE category_key = 'graphic_design'), 'ලාංඡන සහ බ්‍රෑන්ඩින් නිර්මාණය', 'sinhala', 8000, 'Logo, branding and marketing graphic design.'),
(87, 87, (SELECT category_id FROM categories WHERE category_key = 'writing'), 'SEO உள்ளடக்க எழுத்து', 'tamil', 3000, 'SEO-friendly website and marketing content writing.'),
(88, 88, (SELECT category_id FROM categories WHERE category_key = 'event_services'), 'Full Event Coordination', 'english', 25000, 'Event planning, coordination and on-site support.'),
(89, 89, (SELECT category_id FROM categories WHERE category_key = 'tutoring'), 'පුද්ගලික ටියුෂන් පන්තිය', 'sinhala', 2500, 'One-on-one tutoring for school and professional subjects.'),
(90, 90, (SELECT category_id FROM categories WHERE category_key = 'photography'), 'நிகழ்வு மற்றும் உருவப்பட புகைப்படம்', 'tamil', 15000, 'Event, portrait and product photography services.'),
(91, 91, (SELECT category_id FROM categories WHERE category_key = 'web_design'), 'Custom Website Design', 'english', 35000, 'Custom responsive website design and development.'),
(92, 92, (SELECT category_id FROM categories WHERE category_key = 'electrical'), 'නිවාස විදුලි අලුත්වැඩියා', 'sinhala', 4000, 'Home electrical inspection, installation and repair.'),
(93, 93, (SELECT category_id FROM categories WHERE category_key = 'plumbing'), 'குழாய் பொருத்துதல் மற்றும் பழுது', 'tamil', 3500, 'Plumbing installation, maintenance and repair.'),
(94, 94, (SELECT category_id FROM categories WHERE category_key = 'graphic_design'), 'Logo & Branding Design', 'english', 8000, 'Logo, branding and marketing graphic design.'),
(95, 95, (SELECT category_id FROM categories WHERE category_key = 'writing'), 'SEO අන්තර්ගත රචනය', 'sinhala', 3000, 'SEO-friendly website and marketing content writing.'),
(96, 96, (SELECT category_id FROM categories WHERE category_key = 'event_services'), 'முழு நிகழ்வு ஒருங்கிணைப்பு', 'tamil', 25000, 'Event planning, coordination and on-site support.'),
(97, 97, (SELECT category_id FROM categories WHERE category_key = 'tutoring'), 'One-on-One Tutoring Session', 'english', 2500, 'One-on-one tutoring for school and professional subjects.'),
(98, 98, (SELECT category_id FROM categories WHERE category_key = 'photography'), 'උත්සව සහ ඡායාරූප සේවාව', 'sinhala', 15000, 'Event, portrait and product photography services.'),
(99, 99, (SELECT category_id FROM categories WHERE category_key = 'web_design'), 'தனிப்பயன் இணையதள வடிவமைப்பு', 'tamil', 35000, 'Custom responsive website design and development.'),
(100, 100, (SELECT category_id FROM categories WHERE category_key = 'electrical'), 'Home Electrical Repair', 'english', 4000, 'Home electrical inspection, installation and repair.');
