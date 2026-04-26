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
$logDate = trim($_POST['log_date'] ?? '');
$mealName = trim($_POST['meal_name'] ?? '');
$calories = (int) ($_POST['calories'] ?? 0);
$notes = trim($_POST['notes'] ?? '');

if ($logDate === '' || $mealName === '' || $calories <= 0) {
    set_flash('danger', 'Date, meal name, and calories are required.');
    header('Location: ../index.php');
    exit;
}

$statement = $pdo->prepare(
    'INSERT INTO calorie_logs (user_id, log_date, meal_name, calories, notes)
     VALUES (:user_id, :log_date, :meal_name, :calories, :notes)'
);
$statement->execute([
    'user_id' => (int) $user['id'],
    'log_date' => $logDate,
    'meal_name' => $mealName,
    'calories' => $calories,
    'notes' => $notes,
]);

set_flash('success', 'Calorie log added successfully.');
header('Location: ../index.php');
exit;
