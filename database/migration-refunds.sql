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