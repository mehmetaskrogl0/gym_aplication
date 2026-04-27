<?php
declare(strict_types=1);

require_once '../includes/session.php';
require_once '../includes/functions.php';
require_once '../config.php';

if (empty($_SESSION['user']) || !($pdo instanceof PDO)) {
    http_response_code(403);
    exit('Unauthorized');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

$userId = (int)($_SESSION['user']['id'] ?? 0);

if ($userId <= 0) {
    http_response_code(403);
    exit('Unauthorized');
}

try {
    $gender = $_POST['gender'] ?? null;
    $age = (int)($_POST['age'] ?? 0);
    $height_cm = (float)($_POST['height_cm'] ?? 0);
    $weight_kg = (float)($_POST['weight_kg'] ?? 0);
    $bodyFatInput = trim((string)($_POST['body_fat_percentage'] ?? ''));
    $body_fat_percentage = $bodyFatInput !== '' ? (float)$bodyFatInput : null;
    $target_physique = $_POST['target_physique'] ?? 'athletic';
    $target_muscles = $_POST['target_muscles'] ?? '[]';

    $allowedMuscles = [
        'deltoids',
        'pectorals',
        'abdominals',
        'obliques',
        'biceps',
        'triceps',
        'forearms',
        'trapezius',
        'lats',
        'lower_back',
        'glutes',
        'quadriceps',
        'hamstrings',
        'calves',
    ];

    $decodedMuscles = json_decode((string)$target_muscles, true);
    if (!is_array($decodedMuscles)) {
        $decodedMuscles = [];
    }

    $normalizedMuscles = array_values(array_unique(array_filter(
        array_map(static fn($item): string => trim((string)$item), $decodedMuscles),
        static fn($item): bool => in_array($item, $allowedMuscles, true)
    )));
    $target_muscles = json_encode($normalizedMuscles, JSON_UNESCAPED_SLASHES);
    
    // Validation
    if (!$gender || $age < 13 || $age > 120 || $height_cm < 100 || $height_cm > 250 || $weight_kg < 30 || $weight_kg > 300) {
        throw new Exception('Invalid input parameters');
    }
    
    if (!in_array($target_physique, ['lean', 'athletic', 'bodybuilder', 'endurance'])) {
        throw new Exception('Invalid target physique');
    }
    
    // Check if profile exists
    $checkStmt = $pdo->prepare('SELECT id FROM user_profiles WHERE user_id = ?');
    $checkStmt->execute([$userId]);
    $profileExists = $checkStmt->fetch();
    
    if ($profileExists) {
        // Update existing profile
        $updateStmt = $pdo->prepare(
            'UPDATE user_profiles SET gender = ?, age = ?, height_cm = ?, weight_kg = ?, 
             body_fat_percentage = ?, target_physique = ?, target_muscles = ? 
             WHERE user_id = ?'
        );
        $updateStmt->execute([
            $gender, $age, $height_cm, $weight_kg, 
            $body_fat_percentage, $target_physique, $target_muscles, $userId
        ]);
    } else {
        // Insert new profile
        $insertStmt = $pdo->prepare(
            'INSERT INTO user_profiles (user_id, gender, age, height_cm, weight_kg, 
             body_fat_percentage, target_physique, target_muscles) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $insertStmt->execute([
            $userId, $gender, $age, $height_cm, $weight_kg, 
            $body_fat_percentage, $target_physique, $target_muscles
        ]);
    }
    
    $_SESSION['success_message'] = 'Profile saved successfully!';
    header('Location: ../physique.php#assessment');
    exit;
    
} catch (Exception $e) {
    $_SESSION['error_message'] = 'Error: ' . $e->getMessage();
    header('Location: ../physique.php#assessment');
    exit;
}
