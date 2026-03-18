<?php
require_once '../config.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_response(['success' => false, 'message' => t('auth.method_not_allowed')], 405);
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $identifier = trim((string) ($data['identifier'] ?? ''));

    if ($identifier === '') {
        json_response(['success' => false, 'message' => t('forgot.invalid_identifier')], 400);
    }

    $normalizedPhone = preg_replace('/\D+/', '', $identifier);
    $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL);

    if ($isEmail) {
        $sql = "SELECT id, name, email FROM users WHERE LOWER(email) = LOWER(:identifier) LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':identifier', $identifier);
    } else {
        $sql = "SELECT id, name, email FROM users WHERE REGEXP_REPLACE(COALESCE(phone, ''), '[^0-9]', '', 'g') = :identifier LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':identifier', $normalizedPhone);
    }

    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    $genericMessage = t('forgot.success');

    if (!$user || empty($user['email'])) {
        json_response(['success' => true, 'message' => $genericMessage]);
    }

    $plainToken = create_password_reset_token($user['id']);
    $resetUrl = app_base_url() . '/reset-password.php?token=' . urlencode($plainToken);
    $sent = send_password_reset_email($user['email'], $user['name'] ?? 'User', $resetUrl);

    if (!$sent) {
        error_log('Password reset email failed for user ID ' . $user['id']);
    }

    json_response(['success' => true, 'message' => $genericMessage]);
} catch (Exception $e) {
    error_log('Forgot password error: ' . $e->getMessage());
    json_response(['success' => false, 'message' => t('auth.reset_request_failed')], 500);
}
?>
