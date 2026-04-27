<?php
/**
 * Physique Data API Endpoint
 * 
 * Provides REST-like endpoints for retrieving user physique data in JSON format.
 * Suitable for mobile apps, external services, and AI integrations.
 * 
 * Endpoints:
 * GET /api/physique.php?action=profile&user_id=1
 * GET /api/physique.php?action=measurements&user_id=1
 * GET /api/physique.php?action=progress&user_id=1
 * GET /api/physique.php?action=prediction&user_id=1
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Cache-Control: no-cache, no-store, must-revalidate');

require_once '../config.php';
require_once '../includes/PhysiqueAPI.php';

// Response wrapper
function sendResponse(int $code, array $data, ?string $message = null): never
{
    http_response_code($code);
    $response = [
        'success' => $code >= 200 && $code < 300,
        'code' => $code,
        'data' => $data,
    ];
    if ($message) {
        $response['message'] = $message;
    }
    echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

// Validate request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    sendResponse(200, [], 'OK');
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(405, [], 'Method not allowed');
}

// Get parameters
$action = $_GET['action'] ?? $_POST['action'] ?? null;
$userId = (int)($_GET['user_id'] ?? $_POST['user_id'] ?? 0);

// Validate parameters
if (!$action || $userId <= 0) {
    sendResponse(400, [], 'Missing required parameters: action, user_id');
}

if ($pdo === null) {
    sendResponse(503, [], 'Database connection unavailable');
}

try {
    $api = new PhysiqueAPI($pdo, $userId);
    
    match($action) {
        'profile' => sendProfile($api),
        'measurements' => sendMeasurements($api),
        'progress' => sendProgress($api),
        'prediction' => sendPrediction($api),
        'full' => sendFull($api),
        default => sendResponse(400, [], "Unknown action: $action"),
    };
    
} catch (Exception $e) {
    error_log('Physique API Error: ' . $e->getMessage());
    sendResponse(500, [], 'Internal server error: ' . $e->getMessage());
}

/**
 * Send user profile data
 */
function sendProfile(PhysiqueAPI $api): never
{
    $profile = $api->getUserProfile();
    
    if (!$profile) {
        sendResponse(404, [], 'User profile not found');
    }
    
    sendResponse(200, ['profile' => $profile]);
}

/**
 * Send latest measurements
 */
function sendMeasurements(PhysiqueAPI $api): never
{
    $measurements = $api->getLatestPhysiqueLog();
    
    if (!$measurements) {
        sendResponse(404, [], 'No measurement logs found');
    }
    
    sendResponse(200, ['measurements' => $measurements]);
}

/**
 * Send progress comparison
 */
function sendProgress(PhysiqueAPI $api): never
{
    $progress = $api->getProgressComparison();
    
    if (!$progress) {
        sendResponse(404, [], 'Insufficient data for progress comparison (need at least 2 logs)');
    }
    
    sendResponse(200, ['progress' => $progress]);
}

/**
 * Send AI prediction payload
 */
function sendPrediction(PhysiqueAPI $api): never
{
    $predictionJSON = $api->generatePredictionPayload();
    
    if (!$predictionJSON) {
        sendResponse(404, [], 'Cannot generate prediction without complete profile');
    }
    
    $prediction = json_decode($predictionJSON, true);
    sendResponse(200, $prediction);
}

/**
 * Send complete profile with all data
 */
function sendFull(PhysiqueAPI $api): never
{
    $fullJSON = $api->getUserPhysiqueJSON();
    
    if (!$fullJSON) {
        sendResponse(404, [], 'User profile not found');
    }
    
    $fullData = json_decode($fullJSON, true);
    sendResponse(200, $fullData);
}
