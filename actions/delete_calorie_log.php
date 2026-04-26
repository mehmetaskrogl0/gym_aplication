<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

if (!validate_csrf_token($_POST['csrf_token'] ?? null)) {
    set_flash('danger', 'Invalid request token. Please try again.');
    header('Location: ../index.php');
    exit;
}

if (!($pdo instanceof PDO)) {
    set_flash('danger', 'Database connection is not available.');
    header('Location: ../index.php');
    exit;
}

$user = current_user();
$id = (int) ($_POST['id'] ?? 0);

if ($id <= 0) {
    set_flash('danger', 'Invalid calorie log selected.');
    header('Location: ../index.php');
    exit;
}

$statement = $pdo->prepare('DELETE FROM calorie_logs WHERE id = :id AND user_id = :user_id');
$statement->execute([
    'id' => $id,
    'user_id' => (int) $user['id'],
]);

set_flash('success', 'Calorie log deleted successfully.');
header('Location: ../index.php');
exit;
