<?php
require_once '../config.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_response(['success' => false, 'message' => t('auth.method_not_allowed')], 405);
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $token = trim((string) ($data['token'] ?? ''));
    $password = (string) ($data['password'] ?? '');
    $confirmPassword = (string) ($data['confirm_password'] ?? '');

    if ($token === '') {
        json_response(['success' => false, 'message' => t('reset.token_required')], 400);
    }

    if ($password !== $confirmPassword) {
        json_response(['success' => false, 'message' => t('reset.password_mismatch')], 400);
    }

    if (strlen($password) < 8 ||
        !preg_match('/[A-Z]/', $password) ||
        !preg_match('/[a-z]/', $password) ||
        !preg_match('/\d/', $password)) {
        json_response(['success' => false, 'message' => t('reset.password_rules')], 400);
    }

    $resetRecord = find_password_reset_token($token);

    if (!$resetRecord) {
        json_response(['success' => false, 'message' => t('auth.reset_invalid_token')], 400);
    }

    $passwordHash = password_hash($password, HASH_ALGO);

    $conn->beginTransaction();

    $updateUser = $conn->prepare("UPDATE users SET password = :password WHERE id = :user_id");
    $updateUser->execute([
        ':password' => $passwordHash,
        ':user_id' => $resetRecord['user_id'],
    ]);

    consume_password_reset_tokens($resetRecord['user_id']);

    $conn->commit();

    json_response(['success' => true, 'message' => t('auth.reset_success')]);
} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log('Reset password error: ' . $e->getMessage());
    json_response(['success' => false, 'message' => t('auth.server_error')], 500);
}
?>
