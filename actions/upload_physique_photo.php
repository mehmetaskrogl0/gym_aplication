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
$uploadDir = '../uploads/physique/';

// Create upload directory if it doesn't exist
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

try {
    // Validate measurements
    $weight_kg = (float)($_POST['weight_kg'] ?? 0);
    $chestInput = trim((string)($_POST['chest_cm'] ?? ''));
    $waistInput = trim((string)($_POST['waist_cm'] ?? ''));
    $bicepInput = trim((string)($_POST['bicep_cm'] ?? ''));
    $thighInput = trim((string)($_POST['thigh_cm'] ?? ''));
    $bodyFatInput = trim((string)($_POST['body_fat_percentage'] ?? ''));

    $chest_cm = $chestInput !== '' ? (float)$chestInput : null;
    $waist_cm = $waistInput !== '' ? (float)$waistInput : null;
    $bicep_cm = $bicepInput !== '' ? (float)$bicepInput : null;
    $thigh_cm = $thighInput !== '' ? (float)$thighInput : null;
    $body_fat_percentage = $bodyFatInput !== '' ? (float)$bodyFatInput : null;
    $notes = $_POST['notes'] ?? null;
    
    if ($weight_kg < 30 || $weight_kg > 300) {
        throw new Exception('Invalid weight value');
    }
    
    // Handle photo uploads
    $photoPaths = ['front' => null, 'side' => null, 'back' => null];
    $photoFields = ['photo_front', 'photo_side', 'photo_back'];
    
    foreach ($photoFields as $field) {
        if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES[$field];
            $fileType = mime_content_type($file['tmp_name']);
            $fileSize = filesize($file['tmp_name']);
            
            // Validation
            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
            if (!in_array($fileType, $allowedMimes)) {
                throw new Exception("Invalid file type for $field. Only JPG, PNG, WebP allowed.");
            }
            
            if ($fileSize > 5 * 1024 * 1024) { // 5MB
                throw new Exception("File too large for $field. Max 5MB.");
            }
            
            // Generate safe filename
            $ext = match($fileType) {
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
            };
            
            $filename = 'user_' . $userId . '_' . str_replace('photo_', '', $field) . '_' . time() . '.' . $ext;
            $filepath = $uploadDir . $filename;
            
            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                throw new Exception("Failed to upload $field");
            }
            
            $photoPaths[str_replace('photo_', '', $field)] = 'uploads/physique/' . $filename;
        }
    }
    
    // Insert physique log
    $logDate = date('Y-m-d');
    
    $stmt = $pdo->prepare(
        'INSERT INTO physique_logs 
         (user_id, log_date, weight_kg, chest_cm, waist_cm, bicep_cm, thigh_cm, 
          body_fat_percentage, photo_front_path, photo_side_path, photo_back_path, notes)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
         weight_kg = ?, chest_cm = ?, waist_cm = ?, bicep_cm = ?, thigh_cm = ?, 
         body_fat_percentage = ?, photo_front_path = COALESCE(?, photo_front_path),
         photo_side_path = COALESCE(?, photo_side_path), 
         photo_back_path = COALESCE(?, photo_back_path), notes = ?'
    );
    
    $stmt->execute([
        $userId, $logDate, $weight_kg, $chest_cm, $waist_cm, $bicep_cm, $thigh_cm,
        $body_fat_percentage, $photoPaths['front'], $photoPaths['side'], $photoPaths['back'], $notes,
        $weight_kg, $chest_cm, $waist_cm, $bicep_cm, $thigh_cm,
        $body_fat_percentage, $photoPaths['front'], $photoPaths['side'], $photoPaths['back'], $notes
    ]);
    
    $_SESSION['success_message'] = 'Current status recorded successfully!';
    header('Location: ../physique.php#current');
    exit;
    
} catch (Exception $e) {
    $_SESSION['error_message'] = 'Error: ' . $e->getMessage();
    header('Location: ../physique.php#current');
    exit;
}
