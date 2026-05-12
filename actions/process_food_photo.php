<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

// CSRF token kontrolü
$csrfToken = $_POST['csrf_token'] ?? '';
if (!hash_equals(csrf_token(), $csrfToken)) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'CSRF token validation failed'
    ]);
    exit;
}

// Fotoğraf kontrolü
if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'No photo uploaded or upload error'
    ]);
    exit;
}

$file = $_FILES['photo'];
$mimeType = mime_content_type($file['tmp_name']);

// Dosya türü kontrolü
if (!in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid file type. Please upload a JPEG, PNG, or WebP image.'
    ]);
    exit;
}

try {
    // Fotoğrafı geçici olarak kaydet
    $tempPath = sys_get_temp_dir() . '/' . uniqid('food_photo_') . '.jpg';
    
    if (!move_uploaded_file($file['tmp_name'], $tempPath)) {
        throw new Exception('Failed to save uploaded file');
    }

    // Yemeği tanımaya çalış ve Nutritionix'den kalori bilgisi al
    $result = analyzeAndGetCalories($tempPath);

    // Geçici dosyayı sil
    @unlink($tempPath);

    echo json_encode($result);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

/**
 * Fotoğrafı analiz et ve Nutritionix'den kalori bilgisi al
 * Bu fonksiyon şu anda basit bir mock, gerçek AI entegrasyonu için upgrade edilebilir
 */
function analyzeAndGetCalories(string $imagePath): array {
    // Fotoğraf içeriğini base64'e çevir
    $imageData = base64_encode(file_get_contents($imagePath));

    // DEMO: Basit Food Recognition
    // Gerçek implementasyon için Google Vision API, Clarifai, veya TensorFlow.js kullanabilirsiniz
    $detectedFood = detectFoodFromImage($imagePath);

    if (!$detectedFood) {
        return [
            'success' => false,
            'message' => 'Could not identify food in the image. Please try another photo or enter manually.'
        ];
    }

    // Nutritionix API'den kalori bilgisi al
    $calorieInfo = searchNutritionix($detectedFood);

    if (!$calorieInfo) {
        return [
            'success' => false,
            'message' => "Food detected as '{$detectedFood}' but nutritional info not found in database."
        ];
    }

    return [
        'success' => true,
        'foodName' => $calorieInfo['name'],
        'calories' => $calorieInfo['calories'],
        'protein' => $calorieInfo['protein'] ?? 0,
        'carbs' => $calorieInfo['carbs'] ?? 0,
        'fat' => $calorieInfo['fat'] ?? 0
    ];
}

/**
 * Basit yemeği tanıma (mock sürüm)
 * Gerçek implementasyon için AI model veya Vision API kullanın
 */
function detectFoodFromImage(string $imagePath): ?string {
    // DEMO: Basit pattern matching veya predefined list
    // Gerçek implementasyon için:
    // 1. Google Cloud Vision API
    // 2. Clarifai Food Recognition
    // 3. TensorFlow.js (client-side)
    // 4. Azure Computer Vision API

    // İlk deneme: Filename'den tahmin et
    $filename = basename($imagePath);
    
    $commonFoods = [
        'chicken' => 'Grilled Chicken',
        'rice' => 'White Rice',
        'pasta' => 'Pasta with Sauce',
        'pizza' => 'Cheese Pizza',
        'salad' => 'Green Salad',
        'burger' => 'Beef Burger',
        'sandwich' => 'Sandwich',
        'fish' => 'Grilled Fish',
        'beef' => 'Beef Steak',
        'turkey' => 'Turkey Breast',
        'bread' => 'Bread',
        'egg' => 'Eggs',
        'apple' => 'Apple',
        'banana' => 'Banana',
        'milk' => 'Milk',
    ];

    // Filename'de yemeğin adı varsa bunu kullan
    foreach ($commonFoods as $key => $value) {
        if (stripos($filename, $key) !== false) {
            return $value;
        }
    }

    // Aksi halde default'tan seç (gerçek API ile değiştir)
    return 'Grilled Chicken';
}

/**
 * Nutritionix API'den yemeği ara ve kalori bilgisini al
 */
function searchNutritionix(string $foodName): ?array {
    $apiUrl = 'https://www.nutritionix.com/api/nutrition/search';
    
    try {
        // cURL ile Nutritionix API'ye istek yap
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl . '?query=' . urlencode($foodName));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'FitBalance/1.0');
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            return null;
        }

        $data = json_decode($response, true);

        // API'den alınan sonuçları işle
        if (!isset($data['hits']) || empty($data['hits'])) {
            return null;
        }

        // İlk sonucu al
        $food = $data['hits'][0]['_source'] ?? null;
        
        if (!$food) {
            return null;
        }

        // Kalori bilgisini çıkart (per serving)
        $calories = (int) ($food['nf_calories'] ?? 0);
        $protein = (int) ($food['nf_protein'] ?? 0);
        $carbs = (int) ($food['nf_total_carbohydrate'] ?? 0);
        $fat = (int) ($food['nf_total_fat'] ?? 0);

        return [
            'name' => $food['food_name'] ?? $foodName,
            'calories' => max(1, $calories), // En az 1 kalori
            'protein' => $protein,
            'carbs' => $carbs,
            'fat' => $fat
        ];

    } catch (Exception $e) {
        error_log('Nutritionix API Error: ' . $e->getMessage());
        return null;
    }
}
