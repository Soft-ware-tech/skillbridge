<?php
require_once __DIR__ . '/session.php';
require_login('admin');
require_once __DIR__ . '/../config/stripe.php';
require_once __DIR__ . '/notifications-helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin-refunds.php');
    exit;
}

$submittedToken = $_POST['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $submittedToken)) {
    header('Location: admin-refunds.php');
    exit;
}

$refundId = (int) ($_POST['refund_id'] ?? 0);

try {
    $stmt = $pdo->prepare("
        SELECT rr.*, p.gateway_ref, p.gateway
        FROM refund_requests rr
        JOIN payments p ON rr.payment_id = p.payment_id
        WHERE rr.refund_id = :refund_id AND rr.status = 'pending'
        LIMIT 1
    ");
    $stmt->execute(['refund_id' => $refundId]);
    $refund = $stmt->fetch();

    if (!$refund) {
        header('Location: admin-refunds.php');
        exit;
    }

    if ($refund['gateway'] !== 'stripe' || empty($refund['gateway_ref'])) {
        header('Location: admin-refunds.php?error=unsupported_gateway');
        exit;
    }

    $stripeResponse = stripe_api_request('POST', 'refunds', [
        'payment_intent' => $refund['gateway_ref'],
        'amount'         => (int) round(((float) $refund['amount']) * 100),
    ]);

    if (empty($stripeResponse['id']) || ($stripeResponse['status'] ?? '') === 'failed') {
        error_log('Admin-forced Stripe refund failed for refund_id ' . $refundId . ': ' . json_encode($stripeResponse));
        header('Location: admin-refunds.php?error=stripe_failed');
        exit;
    }

    $pdo->beginTransaction();

    $updateRefund = $pdo->prepare("
        UPDATE refund_requests
        SET status = 'refunded', processed_by = 'admin', decided_at = NOW(),
            refunded_at = NOW(), stripe_refund_id = :stripe_refund_id
        WHERE refund_id = :refund_id
    ");
    $updateRefund->execute(['stripe_refund_id' => $stripeResponse['id'], 'refund_id' => $refundId]);

    $updatePayment = $pdo->prepare("UPDATE payments SET payment_status = 'refunded' WHERE payment_id = :payment_id");
    $updatePayment->execute(['payment_id' => $refund['payment_id']]);

    $pdo->commit();

    create_notification($pdo, (int) $refund['client_id'], 'refund_completed',
        'Your refund of LKR ' . number_format((float) $refund['amount'], 2) . ' for booking #' . $refund['booking_id'] . ' has been processed by SkillBridge admin.', $refundId);
    create_notification($pdo, (int) $refund['freelancer_id'], 'refund_force_processed',
        'The refund for booking #' . $refund['booking_id'] . ' was overdue, so it was processed by an admin.', $refundId);

    header('Location: admin-refunds.php?forced=1');
    exit;

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('admin-refund-force.php error: ' . $e->getMessage());
    header('Location: admin-refunds.php?error=1');
    exit;
}