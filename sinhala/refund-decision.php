<?php
require_once __DIR__ . '/session.php';
require_login('freelancer');
require_once __DIR__ . '/../config/stripe.php';
require_once __DIR__ . '/notifications-helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: freelancer-refunds.php');
    exit;
}

$submittedToken = $_POST['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $submittedToken)) {
    header('Location: freelancer-refunds.php');
    exit;
}

$refundId = (int) ($_POST['refund_id'] ?? 0);
$action   = $_POST['action'] ?? '';
$myId     = (int) $_SESSION['user_id'];

if (!in_array($action, ['approve', 'reject'], true)) {
    header('Location: freelancer-refunds.php');
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT rr.*, p.gateway_ref, p.gateway
        FROM refund_requests rr
        JOIN payments p ON rr.payment_id = p.payment_id
        WHERE rr.refund_id = :refund_id AND rr.freelancer_id = :freelancer_id AND rr.status = 'pending'
        LIMIT 1
    ");
    $stmt->execute(['refund_id' => $refundId, 'freelancer_id' => $myId]);
    $refund = $stmt->fetch();

    if (!$refund) {
        header('Location: freelancer-refunds.php');
        exit;
    }

    // ---------- REJECT ----------
    if ($action === 'reject') {
        $update = $pdo->prepare("
            UPDATE refund_requests
            SET status = 'rejected', processed_by = 'freelancer', decided_at = NOW()
            WHERE refund_id = :refund_id
        ");
        $update->execute(['refund_id' => $refundId]);

        create_notification($pdo, (int) $refund['client_id'], 'refund_rejected',
            'Your refund request for booking #' . $refund['booking_id'] . ' was declined by the freelancer.', $refundId);
        notify_all_admins($pdo, 'refund_rejected',
            'Refund request #' . $refundId . ' (booking #' . $refund['booking_id'] . ') was rejected by the freelancer.', $refundId);

        header('Location: freelancer-refunds.php?rejected=1');
        exit;
    }

    // ---------- APPROVE → live Stripe refund ----------
    if ($refund['gateway'] !== 'stripe' || empty($refund['gateway_ref'])) {
        header('Location: freelancer-refunds.php?error=unsupported_gateway');
        exit;
    }

    $stripeResponse = stripe_api_request('POST', 'refunds', [
        'payment_intent' => $refund['gateway_ref'],
        'amount'         => (int) round(((float) $refund['amount']) * 100),
    ]);

    if (empty($stripeResponse['id']) || ($stripeResponse['status'] ?? '') === 'failed') {
        error_log('Stripe refund failed for refund_id ' . $refundId . ': ' . json_encode($stripeResponse));
        notify_all_admins($pdo, 'refund_failed',
            'Stripe refund FAILED for booking #' . $refund['booking_id'] . '. Please check manually.', $refundId);
        header('Location: freelancer-refunds.php?error=stripe_failed');
        exit;
    }

    $pdo->beginTransaction();

    $updateRefund = $pdo->prepare("
        UPDATE refund_requests
        SET status = 'refunded', processed_by = 'freelancer', decided_at = NOW(),
            refunded_at = NOW(), stripe_refund_id = :stripe_refund_id
        WHERE refund_id = :refund_id
    ");
    $updateRefund->execute(['stripe_refund_id' => $stripeResponse['id'], 'refund_id' => $refundId]);

    $updatePayment = $pdo->prepare("UPDATE payments SET payment_status = 'refunded' WHERE payment_id = :payment_id");
    $updatePayment->execute(['payment_id' => $refund['payment_id']]);

    $pdo->commit();

    create_notification($pdo, (int) $refund['client_id'], 'refund_completed',
        'Your refund of LKR ' . number_format((float) $refund['amount'], 2) . ' for booking #' . $refund['booking_id'] . ' has been processed to your card.', $refundId);
    notify_all_admins($pdo, 'refund_completed',
        'Refund for booking #' . $refund['booking_id'] . ' (LKR ' . number_format((float) $refund['amount'], 2) . ') was approved and processed by the freelancer.', $refundId);

    header('Location: freelancer-refunds.php?approved=1');
    exit;

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('refund-decision.php error: ' . $e->getMessage());
    header('Location: freelancer-refunds.php?error=1');
    exit;
}