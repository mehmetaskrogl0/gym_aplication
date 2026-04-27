<?php
/**
 * Physique API Helper
 * 
 * Structures user physique data into standardized JSON format
 * for future AI Diet/Workout API integration
 */

declare(strict_types=1);

class PhysiqueAPI
{
    private PDO $pdo;
    private int $userId;
    
    public function __construct(PDO $pdo, int $userId)
    {
        $this->pdo = $pdo;
        $this->userId = $userId;
    }
    
    /**
     * Get complete user physique profile as JSON
     * Suitable for sending to external AI API
     */
    public function getUserPhysiqueJSON(): ?string
    {
        try {
            $profile = $this->getUserProfile();
            if (!$profile) {
                return null;
            }
            
            $latestLog = $this->getLatestPhysiqueLog();
            $userData = [
                'user_id' => $this->userId,
                'profile' => $profile,
                'current_measurements' => $latestLog,
                'api_version' => '1.0',
                'generated_at' => date('c')
            ];
            
            return json_encode($userData, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        } catch (Exception $e) {
            error_log('PhysiqueAPI Error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get structured profile data
     */
    public function getUserProfile(): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, gender, age, height_cm, weight_kg, body_fat_percentage, 
                    target_physique, target_muscles, created_at 
             FROM user_profiles WHERE user_id = ?'
        );
        $stmt->execute([$this->userId]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$profile) {
            return null;
        }
        
        $profile['target_muscles'] = $profile['target_muscles'] 
            ? json_decode($profile['target_muscles'], true) 
            : [];
        
        return [
            'gender' => $profile['gender'],
            'age' => (int)$profile['age'],
            'height_cm' => (float)$profile['height_cm'],
            'weight_kg' => (float)$profile['weight_kg'],
            'body_fat_percentage' => $profile['body_fat_percentage'] ? (float)$profile['body_fat_percentage'] : null,
            'target_physique' => $profile['target_physique'],
            'target_muscle_groups' => $profile['target_muscles'],
            'bmi' => $this->calculateBMI((float)$profile['height_cm'], (float)$profile['weight_kg']),
            'lean_body_weight_kg' => $this->calculateLeanBodyWeight(
                (float)$profile['weight_kg'], 
                $profile['body_fat_percentage'] ? (float)$profile['body_fat_percentage'] : null
            ),
            'profile_created_at' => $profile['created_at']
        ];
    }
    
    /**
     * Get latest physique measurements
     */
    public function getLatestPhysiqueLog(): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, log_date, weight_kg, chest_cm, waist_cm, bicep_cm, thigh_cm, 
                    body_fat_percentage, photo_front_path, photo_side_path, photo_back_path, notes
             FROM physique_logs WHERE user_id = ? ORDER BY log_date DESC LIMIT 1'
        );
        $stmt->execute([$this->userId]);
        $log = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$log) {
            return null;
        }
        
        return [
            'log_id' => (int)$log['id'],
            'log_date' => $log['log_date'],
            'measurements' => [
                'weight_kg' => (float)$log['weight_kg'],
                'chest_cm' => $log['chest_cm'] ? (float)$log['chest_cm'] : null,
                'waist_cm' => $log['waist_cm'] ? (float)$log['waist_cm'] : null,
                'bicep_cm' => $log['bicep_cm'] ? (float)$log['bicep_cm'] : null,
                'thigh_cm' => $log['thigh_cm'] ? (float)$log['thigh_cm'] : null,
                'body_fat_percentage' => $log['body_fat_percentage'] ? (float)$log['body_fat_percentage'] : null
            ],
            'photos' => [
                'front' => $log['photo_front_path'],
                'side' => $log['photo_side_path'],
                'back' => $log['photo_back_path']
            ],
            'notes' => $log['notes']
        ];
    }
    
    /**
     * Get progress between two dates
     */
    public function getProgressComparison(?string $fromDate = null, ?string $toDate = null): ?array
    {
        $query = 'SELECT * FROM physique_logs WHERE user_id = ?';
        $params = [$this->userId];
        
        if ($fromDate && $toDate) {
            $query .= ' AND log_date BETWEEN ? AND ?';
            $params[] = $fromDate;
            $params[] = $toDate;
        }
        
        $query .= ' ORDER BY log_date ASC';
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($logs) < 2) {
            return null;
        }
        
        $first = $logs[0];
        $last = $logs[count($logs) - 1];
        
        return [
            'period' => [
                'start_date' => $first['log_date'],
                'end_date' => $last['log_date'],
                'days_elapsed' => (strtotime($last['log_date']) - strtotime($first['log_date'])) / 86400
            ],
            'weight_change_kg' => (float)$last['weight_kg'] - (float)$first['weight_kg'],
            'chest_change_cm' => ($first['chest_cm'] && $last['chest_cm']) ? (float)$last['chest_cm'] - (float)$first['chest_cm'] : null,
            'waist_change_cm' => ($first['waist_cm'] && $last['waist_cm']) ? (float)$last['waist_cm'] - (float)$first['waist_cm'] : null,
            'bicep_change_cm' => ($first['bicep_cm'] && $last['bicep_cm']) ? (float)$last['bicep_cm'] - (float)$first['bicep_cm'] : null,
            'thigh_change_cm' => ($first['thigh_cm'] && $last['thigh_cm']) ? (float)$last['thigh_cm'] - (float)$first['thigh_cm'] : null,
            'body_fat_change_percentage' => ($first['body_fat_percentage'] && $last['body_fat_percentage']) ? (float)$last['body_fat_percentage'] - (float)$first['body_fat_percentage'] : null,
            'log_count' => count($logs)
        ];
    }
    
    /**
     * Calculate BMI (Body Mass Index)
     */
    private function calculateBMI(float $heightCm, float $weightKg): float
    {
        $heightM = $heightCm / 100;
        return round($weightKg / ($heightM * $heightM), 2);
    }
    
    /**
     * Calculate Lean Body Weight
     */
    private function calculateLeanBodyWeight(float $weightKg, ?float $bodyFatPercentage): ?float
    {
        if ($bodyFatPercentage === null) {
            return null;
        }
        return round($weightKg * (1 - ($bodyFatPercentage / 100)), 2);
    }
    
    /**
     * Generate AI-ready prediction payload
     * (Placeholder for future AI API integration)
     */
    public function generatePredictionPayload(): ?string
    {
        try {
            $profile = $this->getUserProfile();
            $measurements = $this->getLatestPhysiqueLog();
            
            if (!$profile || !$measurements) {
                return null;
            }
            
            $payload = [
                'request_type' => 'generate_workout_diet_plan',
                'user_profile' => $profile,
                'current_measurements' => $measurements['measurements'],
                'target_goal' => $profile['target_physique'],
                'priority_muscles' => $profile['target_muscle_groups'],
                'request_date' => date('c'),
                'api_version' => '1.0'
            ];
            
            return json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        } catch (Exception $e) {
            error_log('PhysiqueAPI Prediction Error: ' . $e->getMessage());
            return null;
        }
    }
}
