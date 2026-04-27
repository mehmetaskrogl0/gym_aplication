<?php
/**
 * Physique API Integration Example
 * 
 * This file demonstrates how to use the PhysiqueAPI class to send user data
 * to external AI services for generating personalized workout and diet plans.
 * 
 * Usage:
 * php physique-api-example.php --user_id 1 --action generate_plan
 */

declare(strict_types=1);

require_once 'config.php';
require_once 'includes/PhysiqueAPI.php';

// Example 1: Get user profile as JSON
function example_getUserProfile(PDO $pdo, int $userId): void
{
    $api = new PhysiqueAPI($pdo, $userId);
    $profileJSON = $api->getUserPhysiqueJSON();
    
    if ($profileJSON) {
        echo "=== User Profile JSON ===\n";
        echo $profileJSON . "\n\n";
    } else {
        echo "No profile found for user ID: $userId\n";
    }
}

// Example 2: Send to External AI API
function example_sendToAIAPI(PDO $pdo, int $userId): void
{
    $api = new PhysiqueAPI($pdo, $userId);
    $payload = $api->generatePredictionPayload();
    
    if (!$payload) {
        echo "Failed to generate prediction payload\n";
        return;
    }
    
    echo "=== AI API Payload ===\n";
    echo $payload . "\n\n";
    
    // Example API call (configure with real API endpoint)
    $apiEndpoint = 'https://api.example.com/v1/generate-plan';
    $apiKey = getenv('AI_API_KEY') ?: 'your-api-key-here';
    
    echo "=== Sending to API Endpoint ===\n";
    echo "Endpoint: $apiEndpoint\n";
    echo "Method: POST\n";
    echo "Content-Type: application/json\n\n";
    
    // Uncomment to actually send request
    /*
    $response = callAIAPI($apiEndpoint, $payload, $apiKey);
    echo "Response Status: " . ($response['success'] ? 'Success' : 'Failed') . "\n";
    echo "Response Body:\n" . json_encode($response, JSON_PRETTY_PRINT) . "\n";
    */
}

// Example 3: Get Progress Comparison
function example_getProgressComparison(PDO $pdo, int $userId): void
{
    $api = new PhysiqueAPI($pdo, $userId);
    $progress = $api->getProgressComparison();
    
    if (!$progress) {
        echo "Not enough logs for comparison (need at least 2)\n";
        return;
    }
    
    echo "=== Progress Comparison ===\n";
    echo json_encode($progress, JSON_PRETTY_PRINT) . "\n\n";
    
    // Analyze changes
    echo "=== Progress Analysis ===\n";
    echo "Period: {$progress['period']['start_date']} to {$progress['period']['end_date']}\n";
    echo "Days Elapsed: " . (int)$progress['period']['days_elapsed'] . " days\n\n";
    
    if ($progress['weight_change_kg'] != 0) {
        $direction = $progress['weight_change_kg'] > 0 ? 'gained' : 'lost';
        echo "Weight: {$direction} " . abs($progress['weight_change_kg']) . "kg\n";
    }
    
    if ($progress['chest_change_cm'] != 0) {
        $direction = $progress['chest_change_cm'] > 0 ? 'increased' : 'decreased';
        echo "Chest: {$direction} by " . abs($progress['chest_change_cm']) . "cm\n";
    }
    
    if ($progress['waist_change_cm'] != 0) {
        $direction = $progress['waist_change_cm'] > 0 ? 'increased' : 'decreased';
        echo "Waist: {$direction} by " . abs($progress['waist_change_cm']) . "cm\n";
    }
    
    if ($progress['bicep_change_cm'] != 0) {
        $direction = $progress['bicep_change_cm'] > 0 ? 'increased' : 'decreased';
        echo "Bicep: {$direction} by " . abs($progress['bicep_change_cm']) . "cm\n";
    }
}

// Mock AI API call (replace with real implementation)
function callAIAPI(string $endpoint, string $payload, string $apiKey): array
{
    /*
    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ],
        CURLOPT_TIMEOUT => 30,
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'success' => $httpCode === 200,
        'status_code' => $httpCode,
        'body' => json_decode($response, true),
    ];
    */
    
    return [
        'success' => true,
        'status_code' => 200,
        'body' => [
            'plan_id' => 'plan_' . uniqid(),
            'workout_plan' => 'Example workout plan',
            'diet_plan' => 'Example diet plan',
            'duration_weeks' => 12,
        ],
    ];
}

// Main execution
if (php_sapi_name() !== 'cli') {
    echo "This script is designed to run from the command line.\n";
    exit(1);
}

if (!$pdo) {
    echo "Database connection failed.\n";
    exit(1);
}

// Parse command line arguments
$options = getopt('u:', ['user_id:', 'action:']);
$userId = (int)($options['u'] ?? $options['user_id'] ?? 1);
$action = $options['action'] ?? 'profile';

echo "=== FitBalance Physique API Integration Example ===\n";
echo "User ID: $userId\n";
echo "Action: $action\n\n";

try {
    switch ($action) {
        case 'profile':
            example_getUserProfile($pdo, $userId);
            break;
        case 'api':
            example_sendToAIAPI($pdo, $userId);
            break;
        case 'progress':
            example_getProgressComparison($pdo, $userId);
            break;
        default:
            echo "Unknown action: $action\n";
            echo "Available actions: profile, api, progress\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
