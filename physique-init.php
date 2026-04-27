<?php
/**
 * Physique Module Initialization Script
 * 
 * Sets up database tables, directories, and verifies all components
 * Run once after installation: php physique-init.php
 */

declare(strict_types=1);

require_once 'config.php';

echo "=== FitBalance Physique Module Initialization ===\n\n";

// Color codes for CLI output
const COLOR_GREEN = "\033[92m";
const COLOR_RED = "\033[91m";
const COLOR_YELLOW = "\033[93m";
const COLOR_RESET = "\033[0m";

function checkmark($message): void
{
    echo COLOR_GREEN . "✓" . COLOR_RESET . " $message\n";
}

function cross($message): void
{
    echo COLOR_RED . "✗" . COLOR_RESET . " $message\n";
}

function warning($message): void
{
    echo COLOR_YELLOW . "!" . COLOR_RESET . " $message\n";
}

// 1. Check database connection
echo "1. Checking Database Connection...\n";
if ($pdo !== null) {
    checkmark("Database connected");
} else {
    cross("Database connection failed");
    exit(1);
}

// 2. Verify tables exist
echo "\n2. Verifying Database Tables...\n";

$tables = [
    'user_profiles' => 'user_profiles',
    'physique_logs' => 'physique_logs',
];

foreach ($tables as $table => $name) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            checkmark("Table `$name` exists");
        } else {
            cross("Table `$name` does not exist");
            warning("Run: mysql -u root -p fitbalance < database.sql");
        }
    } catch (PDOException $e) {
        cross("Error checking table `$name`: " . $e->getMessage());
    }
}

// 3. Create upload directories
echo "\n3. Setting Up Upload Directories...\n";

$directories = [
    'uploads',
    'uploads/physique',
];

foreach ($directories as $dir) {
    if (is_dir($dir)) {
        checkmark("Directory `$dir` exists");
    } else {
        if (mkdir($dir, 0755, true)) {
            checkmark("Created directory `$dir`");
        } else {
            cross("Failed to create directory `$dir`");
        }
    }
}

// 4. Check file permissions
echo "\n4. Checking File Permissions...\n";

if (is_writable('uploads/physique')) {
    checkmark("Directory `uploads/physique` is writable");
} else {
    cross("Directory `uploads/physique` is not writable");
    warning("Run: chmod 755 uploads/physique");
}

// 5. Verify required files
echo "\n5. Verifying Required Files...\n";

$requiredFiles = [
    'physique.php' => 'Main module page',
    'includes/PhysiqueAPI.php' => 'API helper class',
    'includes/muscle-map.php' => 'Muscle map component',
    'actions/save_physique_profile.php' => 'Profile save handler',
    'actions/upload_physique_photo.php' => 'Photo upload handler',
    'PHYSIQUE_MODULE.md' => 'Module documentation',
];

foreach ($requiredFiles as $file => $description) {
    if (file_exists($file)) {
        checkmark("$description (`$file`)");
    } else {
        cross("Missing: $description (`$file`)");
    }
}

// 6. Verify database schema
echo "\n6. Verifying Database Schema...\n";

try {
    // Check user_profiles columns
    $stmt = $pdo->query("DESCRIBE user_profiles");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    
    $requiredColumns = ['id', 'user_id', 'gender', 'age', 'height_cm', 'weight_kg', 'target_physique', 'target_muscles'];
    $missingColumns = array_diff($requiredColumns, $columns);
    
    if (empty($missingColumns)) {
        checkmark("user_profiles schema is correct");
    } else {
        cross("user_profiles is missing columns: " . implode(', ', $missingColumns));
    }
} catch (PDOException $e) {
    cross("Error checking schema: " . $e->getMessage());
}

// 7. Test permissions with sample query
echo "\n7. Testing Database Permissions...\n";

try {
    $pdo->query("SELECT 1 FROM user_profiles LIMIT 1");
    checkmark("Can read from user_profiles");
} catch (PDOException $e) {
    cross("Cannot read from user_profiles: " . $e->getMessage());
}

try {
    // This won't actually insert due to constraints, but tests INSERT permission
    $pdo->prepare("INSERT INTO user_profiles (user_id, gender, age, height_cm, weight_kg, target_physique, target_muscles) VALUES (?, ?, ?, ?, ?, ?, ?)")->execute([99999, 'male', 30, 180, 80, 'athletic', '[]']);
    $pdo->query("DELETE FROM user_profiles WHERE user_id = 99999");
    checkmark("Can write to user_profiles");
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate entry') === false) {
        warning("Write test inconclusive: " . $e->getMessage());
    }
}

// 8. Summary
echo "\n" . str_repeat("=", 50) . "\n";
echo "Initialization Complete!\n";
echo str_repeat("=", 50) . "\n\n";

echo "Next Steps:\n";
echo "1. Access the module at: physique.php\n";
echo "2. Complete your physical assessment\n";
echo "3. Start recording measurements\n";
echo "4. View progress in the dashboard\n\n";

echo "For API Integration:\n";
echo "php physique-api-example.php --user_id 1 --action profile\n\n";

echo "For more information, see: PHYSIQUE_MODULE.md\n";
