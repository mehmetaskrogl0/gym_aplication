<?php
declare(strict_types=1);

require_once 'includes/session.php';
require_once 'includes/functions.php';
require_once 'config.php';

if (empty($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$userId = (int)($_SESSION['user']['id'] ?? 0);
$errorMessage = '';
$successMessage = '';

if (!empty($_SESSION['success_message'])) {
    $successMessage = (string)$_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if (!empty($_SESSION['error_message'])) {
    $errorMessage = (string)$_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

if ($userId <= 0) {
    header('Location: login.php');
    exit;
}

$profile = false;
$logs = [];

if ($pdo instanceof PDO) {
    // Fetch user profile
    $profileStmt = $pdo->prepare(
        'SELECT * FROM user_profiles WHERE user_id = ?'
    );
    $profileStmt->execute([$userId]);
    $profile = $profileStmt->fetch();

    // Fetch latest physique logs (last 2 entries for comparison)
    $logsStmt = $pdo->prepare(
        'SELECT * FROM physique_logs WHERE user_id = ? ORDER BY log_date DESC LIMIT 2'
    );
    $logsStmt->execute([$userId]);
    $logs = $logsStmt->fetchAll();
} elseif ($errorMessage === '') {
    $errorMessage = 'Database connection is unavailable. Please check your configuration.';
}

// Determine if new profile or existing
$isNewProfile = !$profile;

// Parse target muscles JSON
$targetMuscles = [];
if ($profile && $profile['target_muscles']) {
    $targetMuscles = json_decode($profile['target_muscles'], true) ?? [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Current Physique - FitBalance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .anatomy-card {
            background: #f8f9fa;
            border: 1px solid #e6e9ef;
            border-radius: 12px;
            padding: 1rem;
        }

        .anatomy-label {
            font-size: 0.75rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #6c757d;
            margin-bottom: 0.5rem;
            display: inline-block;
        }

        .anatomy-map {
            width: 100%;
            max-width: 100%;
            height: auto;
            display: block;
        }

        .anatomy-base {
            fill: #edf0f3;
            stroke: #cfd7de;
            stroke-width: 1;
        }

        .muscle-hit {
            fill: #d9dee4;
            stroke: #b4bec8;
            stroke-width: 1;
            cursor: pointer;
            transition: fill 0.2s ease, filter 0.2s ease;
        }

        .muscle-hit:hover {
            fill: #c9d0d8;
            filter: drop-shadow(0 0 4px rgba(40, 167, 69, 0.25));
        }

        .muscle-hit.selected {
            fill: var(--fit-success, #28a745);
            stroke: #1f8336;
            filter: drop-shadow(0 0 5px rgba(40, 167, 69, 0.35));
        }

        .muscle-pill {
            border: 1px solid #d5dde5;
            border-radius: 999px;
            padding: 0.2rem 0.65rem;
            font-size: 0.75rem;
            color: #495057;
            background: #fff;
        }
        
        .measurement-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            border: 1px solid #e0e0e0;
            margin-bottom: 20px;
        }
        
        .progress-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .progress-badge.positive {
            background-color: #d4edda;
            color: #155724;
        }
        
        .progress-badge.negative {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .physique-photo {
            max-width: 100%;
            border-radius: 8px;
            border: 1px solid #ddd;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .tab-content {
            background: white;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            border-top: none;
            padding: 20px;
        }
        
        .goal-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            display: inline-block;
            font-weight: 600;
        }
        
        .goal-badge.lean {
            background: #cfe2ff;
            color: #084298;
        }
        
        .goal-badge.athletic {
            background: #d1e7dd;
            color: #0f5132;
        }
        
        .goal-badge.bodybuilder {
            background: #ffc107;
            color: #000;
        }
        
        .goal-badge.endurance {
            background: #cff4fc;
            color: #055160;
        }
    </style>
</head>
<body class="bg-light">
    <?php include 'includes/header.php'; ?>
    
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-md-3">
                <?php include 'includes/sidebar.php'; ?>
            </div>
            
            <div class="col-md-9">
                <!-- Header -->
                <div class="mb-4">
                    <h1 class="h3 text-dark fw-bold">Current Physique</h1>
                    <p class="text-muted">Track your physical progress and set your fitness goals</p>
                </div>
                
                <!-- Messages -->
                <?php if ($successMessage): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($successMessage); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($errorMessage): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($errorMessage); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Tabs Navigation -->
                <ul class="nav nav-tabs mb-0" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="assessment-tab" data-bs-toggle="tab" 
                                data-bs-target="#assessment" type="button" role="tab">
                            Physical Assessment
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="current-tab" data-bs-toggle="tab" 
                                data-bs-target="#current" type="button" role="tab">
                            Current Status
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="progress-tab" data-bs-toggle="tab" 
                                data-bs-target="#progress" type="button" role="tab">
                            Progress Tracking
                        </button>
                    </li>
                </ul>
                
                <!-- Tabs Content -->
                <div class="tab-content">
                    <!-- Assessment Tab -->
                    <div class="tab-pane fade show active" id="assessment" role="tabpanel">
                        <form id="assessmentForm" method="POST" action="actions/save_physique_profile.php">
                            <div class="row mt-4">
                                <!-- Personal Info -->
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Gender</label>
                                    <select class="form-select" name="gender" required>
                                        <option value="">Select Gender</option>
                                        <option value="male" <?php echo ($profile && $profile['gender'] === 'male') ? 'selected' : ''; ?>>Male</option>
                                        <option value="female" <?php echo ($profile && $profile['gender'] === 'female') ? 'selected' : ''; ?>>Female</option>
                                        <option value="other" <?php echo ($profile && $profile['gender'] === 'other') ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Age</label>
                                    <input type="number" class="form-control" name="age" min="13" max="120" 
                                           value="<?php echo $profile['age'] ?? ''; ?>" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Height (cm)</label>
                                    <input type="number" class="form-control" name="height_cm" step="0.01" min="100" max="250"
                                           value="<?php echo $profile['height_cm'] ?? ''; ?>" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Weight (kg)</label>
                                    <input type="number" class="form-control" name="weight_kg" step="0.1" min="30" max="300"
                                           value="<?php echo $profile['weight_kg'] ?? ''; ?>" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Body Fat % (Optional)</label>
                                    <input type="number" class="form-control" name="body_fat_percentage" step="0.1" min="5" max="60"
                                           value="<?php echo $profile['body_fat_percentage'] ?? ''; ?>">
                                </div>
                            </div>
                            
                            <hr class="my-4">
                            
                            <!-- Target Physique -->
                            <div class="mb-4">
                                <h5 class="fw-semibold mb-3">Target Physique Goal</h5>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="target_physique" 
                                                   id="goalLean" value="lean" 
                                                   <?php echo ($profile && $profile['target_physique'] === 'lean') ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="goalLean">
                                                <span class="goal-badge lean">Lean</span> - Minimal body fat, defined muscles
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="target_physique" 
                                                   id="goalAthletic" value="athletic" 
                                                   <?php echo (!$profile || $profile['target_physique'] === 'athletic') ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="goalAthletic">
                                                <span class="goal-badge athletic">Athletic</span> - Balanced muscle & strength
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="target_physique" 
                                                   id="goalBodybuilder" value="bodybuilder" 
                                                   <?php echo ($profile && $profile['target_physique'] === 'bodybuilder') ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="goalBodybuilder">
                                                <span class="goal-badge bodybuilder">Bodybuilder</span> - Maximum muscle mass
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="target_physique" 
                                                   id="goalEndurance" value="endurance" 
                                                   <?php echo ($profile && $profile['target_physique'] === 'endurance') ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="goalEndurance">
                                                <span class="goal-badge endurance">Endurance</span> - Lean & strong
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <hr class="my-4">
                            
                            <!-- Target Muscle Groups -->
                            <div class="mb-4">
                                <h5 class="fw-semibold mb-3">Priority Muscle Groups</h5>
                                <p class="text-muted small mb-3">Tap muscle groups on the map. Your selected priorities are saved with your profile.</p>

                                <div class="d-flex align-items-center gap-2 mb-3">
                                    <span class="small text-muted fw-semibold">Body Type</span>
                                    <div class="btn-group btn-group-sm" role="group" aria-label="Gender anatomy toggle">
                                        <input type="radio" class="btn-check" name="anatomy_gender_view" id="anatomyMale" value="male" checked>
                                        <label class="btn btn-outline-secondary" for="anatomyMale">Male</label>

                                        <input type="radio" class="btn-check" name="anatomy_gender_view" id="anatomyFemale" value="female">
                                        <label class="btn btn-outline-secondary" for="anatomyFemale">Female</label>
                                    </div>
                                </div>

                                <div id="muscleSelector" class="row g-3" data-active-gender="male">
                                    <div class="col-12 col-lg-6 anatomy-gender" data-gender="male">
                                        <div class="anatomy-card h-100">
                                            <span class="anatomy-label">Male - Front</span>
                                            <svg class="anatomy-map" viewBox="0 0 220 420" role="img" aria-label="Male front anatomy map">
                                                <rect class="anatomy-base" x="86" y="16" width="48" height="50" rx="24"></rect>
                                                <rect class="anatomy-base" x="80" y="66" width="60" height="122" rx="30"></rect>
                                                <rect class="anatomy-base" x="84" y="188" width="52" height="54" rx="24"></rect>
                                                <rect class="anatomy-base" x="20" y="74" width="40" height="138" rx="20"></rect>
                                                <rect class="anatomy-base" x="160" y="74" width="40" height="138" rx="20"></rect>
                                                <rect class="anatomy-base" x="76" y="242" width="28" height="142" rx="14"></rect>
                                                <rect class="anatomy-base" x="116" y="242" width="28" height="142" rx="14"></rect>

                                                <g class="muscle-hit" data-muscle="deltoids">
                                                    <ellipse cx="78" cy="89" rx="18" ry="16"></ellipse>
                                                    <ellipse cx="142" cy="89" rx="18" ry="16"></ellipse>
                                                </g>
                                                <g class="muscle-hit" data-muscle="pectorals">
                                                    <path d="M76 93 Q110 80 144 93 L136 132 L84 132 Z"></path>
                                                </g>
                                                <g class="muscle-hit" data-muscle="abdominals">
                                                    <rect x="90" y="136" width="40" height="54" rx="10"></rect>
                                                </g>
                                                <g class="muscle-hit" data-muscle="obliques">
                                                    <path d="M84 136 L90 192 L76 192 L66 152 Z"></path>
                                                    <path d="M136 136 L154 152 L144 192 L130 192 Z"></path>
                                                </g>
                                                <g class="muscle-hit" data-muscle="biceps">
                                                    <ellipse cx="48" cy="122" rx="11" ry="24"></ellipse>
                                                    <ellipse cx="172" cy="122" rx="11" ry="24"></ellipse>
                                                </g>
                                                <g class="muscle-hit" data-muscle="forearms">
                                                    <ellipse cx="48" cy="170" rx="10" ry="24"></ellipse>
                                                    <ellipse cx="172" cy="170" rx="10" ry="24"></ellipse>
                                                </g>
                                                <g class="muscle-hit" data-muscle="quadriceps">
                                                    <rect x="76" y="246" width="28" height="84" rx="10"></rect>
                                                    <rect x="116" y="246" width="28" height="84" rx="10"></rect>
                                                </g>
                                                <g class="muscle-hit" data-muscle="calves">
                                                    <rect x="76" y="332" width="28" height="50" rx="10"></rect>
                                                    <rect x="116" y="332" width="28" height="50" rx="10"></rect>
                                                </g>
                                            </svg>
                                        </div>
                                    </div>

                                    <div class="col-12 col-lg-6 anatomy-gender" data-gender="male">
                                        <div class="anatomy-card h-100">
                                            <span class="anatomy-label">Male - Back</span>
                                            <svg class="anatomy-map" viewBox="0 0 220 420" role="img" aria-label="Male back anatomy map">
                                                <rect class="anatomy-base" x="86" y="16" width="48" height="50" rx="24"></rect>
                                                <rect class="anatomy-base" x="80" y="66" width="60" height="122" rx="30"></rect>
                                                <rect class="anatomy-base" x="84" y="188" width="52" height="54" rx="24"></rect>
                                                <rect class="anatomy-base" x="20" y="74" width="40" height="138" rx="20"></rect>
                                                <rect class="anatomy-base" x="160" y="74" width="40" height="138" rx="20"></rect>
                                                <rect class="anatomy-base" x="76" y="242" width="28" height="142" rx="14"></rect>
                                                <rect class="anatomy-base" x="116" y="242" width="28" height="142" rx="14"></rect>

                                                <g class="muscle-hit" data-muscle="trapezius">
                                                    <path d="M76 70 Q110 96 144 70 L136 116 L84 116 Z"></path>
                                                </g>
                                                <g class="muscle-hit" data-muscle="deltoids">
                                                    <ellipse cx="78" cy="95" rx="16" ry="18"></ellipse>
                                                    <ellipse cx="142" cy="95" rx="16" ry="18"></ellipse>
                                                </g>
                                                <g class="muscle-hit" data-muscle="lats">
                                                    <path d="M80 100 L98 186 L72 186 L62 126 Z"></path>
                                                    <path d="M140 100 L158 126 L148 186 L122 186 Z"></path>
                                                </g>
                                                <g class="muscle-hit" data-muscle="lower_back">
                                                    <rect x="92" y="138" width="36" height="48" rx="10"></rect>
                                                </g>
                                                <g class="muscle-hit" data-muscle="triceps">
                                                    <ellipse cx="48" cy="128" rx="10" ry="24"></ellipse>
                                                    <ellipse cx="172" cy="128" rx="10" ry="24"></ellipse>
                                                </g>
                                                <g class="muscle-hit" data-muscle="forearms">
                                                    <ellipse cx="48" cy="176" rx="10" ry="24"></ellipse>
                                                    <ellipse cx="172" cy="176" rx="10" ry="24"></ellipse>
                                                </g>
                                                <g class="muscle-hit" data-muscle="glutes">
                                                    <ellipse cx="94" cy="214" rx="16" ry="20"></ellipse>
                                                    <ellipse cx="126" cy="214" rx="16" ry="20"></ellipse>
                                                </g>
                                                <g class="muscle-hit" data-muscle="hamstrings">
                                                    <rect x="76" y="246" width="28" height="84" rx="10"></rect>
                                                    <rect x="116" y="246" width="28" height="84" rx="10"></rect>
                                                </g>
                                                <g class="muscle-hit" data-muscle="calves">
                                                    <rect x="76" y="332" width="28" height="50" rx="10"></rect>
                                                    <rect x="116" y="332" width="28" height="50" rx="10"></rect>
                                                </g>
                                            </svg>
                                        </div>
                                    </div>

                                    <div class="col-12 col-lg-6 anatomy-gender d-none" data-gender="female">
                                        <div class="anatomy-card h-100">
                                            <span class="anatomy-label">Female - Front</span>
                                            <svg class="anatomy-map" viewBox="0 0 220 420" role="img" aria-label="Female front anatomy map">
                                                <rect class="anatomy-base" x="88" y="16" width="44" height="50" rx="22"></rect>
                                                <rect class="anatomy-base" x="82" y="66" width="56" height="126" rx="28"></rect>
                                                <rect class="anatomy-base" x="86" y="192" width="48" height="50" rx="22"></rect>
                                                <rect class="anatomy-base" x="24" y="76" width="34" height="132" rx="17"></rect>
                                                <rect class="anatomy-base" x="162" y="76" width="34" height="132" rx="17"></rect>
                                                <rect class="anatomy-base" x="78" y="242" width="26" height="142" rx="13"></rect>
                                                <rect class="anatomy-base" x="116" y="242" width="26" height="142" rx="13"></rect>

                                                <g class="muscle-hit" data-muscle="deltoids">
                                                    <ellipse cx="80" cy="90" rx="16" ry="14"></ellipse>
                                                    <ellipse cx="140" cy="90" rx="16" ry="14"></ellipse>
                                                </g>
                                                <g class="muscle-hit" data-muscle="pectorals">
                                                    <path d="M82 96 Q110 86 138 96 L132 130 L88 130 Z"></path>
                                                </g>
                                                <g class="muscle-hit" data-muscle="abdominals">
                                                    <rect x="92" y="134" width="36" height="52" rx="10"></rect>
                                                </g>
                                                <g class="muscle-hit" data-muscle="obliques">
                                                    <path d="M86 134 L92 188 L80 188 L70 150 Z"></path>
                                                    <path d="M134 134 L150 150 L140 188 L128 188 Z"></path>
                                                </g>
                                                <g class="muscle-hit" data-muscle="biceps">
                                                    <ellipse cx="46" cy="120" rx="10" ry="22"></ellipse>
                                                    <ellipse cx="174" cy="120" rx="10" ry="22"></ellipse>
                                                </g>
                                                <g class="muscle-hit" data-muscle="forearms">
                                                    <ellipse cx="46" cy="166" rx="9" ry="23"></ellipse>
                                                    <ellipse cx="174" cy="166" rx="9" ry="23"></ellipse>
                                                </g>
                                                <g class="muscle-hit" data-muscle="quadriceps">
                                                    <rect x="78" y="246" width="26" height="84" rx="10"></rect>
                                                    <rect x="116" y="246" width="26" height="84" rx="10"></rect>
                                                </g>
                                                <g class="muscle-hit" data-muscle="calves">
                                                    <rect x="78" y="332" width="26" height="50" rx="10"></rect>
                                                    <rect x="116" y="332" width="26" height="50" rx="10"></rect>
                                                </g>
                                            </svg>
                                        </div>
                                    </div>

                                    <div class="col-12 col-lg-6 anatomy-gender d-none" data-gender="female">
                                        <div class="anatomy-card h-100">
                                            <span class="anatomy-label">Female - Back</span>
                                            <svg class="anatomy-map" viewBox="0 0 220 420" role="img" aria-label="Female back anatomy map">
                                                <rect class="anatomy-base" x="88" y="16" width="44" height="50" rx="22"></rect>
                                                <rect class="anatomy-base" x="82" y="66" width="56" height="126" rx="28"></rect>
                                                <rect class="anatomy-base" x="86" y="192" width="48" height="50" rx="22"></rect>
                                                <rect class="anatomy-base" x="24" y="76" width="34" height="132" rx="17"></rect>
                                                <rect class="anatomy-base" x="162" y="76" width="34" height="132" rx="17"></rect>
                                                <rect class="anatomy-base" x="78" y="242" width="26" height="142" rx="13"></rect>
                                                <rect class="anatomy-base" x="116" y="242" width="26" height="142" rx="13"></rect>

                                                <g class="muscle-hit" data-muscle="trapezius">
                                                    <path d="M82 70 Q110 92 138 70 L130 112 L90 112 Z"></path>
                                                </g>
                                                <g class="muscle-hit" data-muscle="deltoids">
                                                    <ellipse cx="80" cy="96" rx="14" ry="16"></ellipse>
                                                    <ellipse cx="140" cy="96" rx="14" ry="16"></ellipse>
                                                </g>
                                                <g class="muscle-hit" data-muscle="lats">
                                                    <path d="M84 100 L100 186 L78 186 L68 126 Z"></path>
                                                    <path d="M136 100 L152 126 L142 186 L120 186 Z"></path>
                                                </g>
                                                <g class="muscle-hit" data-muscle="lower_back">
                                                    <rect x="94" y="136" width="32" height="48" rx="10"></rect>
                                                </g>
                                                <g class="muscle-hit" data-muscle="triceps">
                                                    <ellipse cx="46" cy="126" rx="9" ry="22"></ellipse>
                                                    <ellipse cx="174" cy="126" rx="9" ry="22"></ellipse>
                                                </g>
                                                <g class="muscle-hit" data-muscle="forearms">
                                                    <ellipse cx="46" cy="172" rx="9" ry="22"></ellipse>
                                                    <ellipse cx="174" cy="172" rx="9" ry="22"></ellipse>
                                                </g>
                                                <g class="muscle-hit" data-muscle="glutes">
                                                    <ellipse cx="96" cy="212" rx="14" ry="18"></ellipse>
                                                    <ellipse cx="124" cy="212" rx="14" ry="18"></ellipse>
                                                </g>
                                                <g class="muscle-hit" data-muscle="hamstrings">
                                                    <rect x="78" y="246" width="26" height="84" rx="10"></rect>
                                                    <rect x="116" y="246" width="26" height="84" rx="10"></rect>
                                                </g>
                                                <g class="muscle-hit" data-muscle="calves">
                                                    <rect x="78" y="332" width="26" height="50" rx="10"></rect>
                                                    <rect x="116" y="332" width="26" height="50" rx="10"></rect>
                                                </g>
                                            </svg>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex flex-wrap gap-2 mt-3" id="selectedMusclePills" aria-live="polite"></div>
                                </div>
                                <input type="hidden" id="targetMuscles" name="target_muscles" value="">
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-success fw-semibold">Save Profile</button>
                                <button type="reset" class="btn btn-outline-secondary fw-semibold">Reset</button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Current Status Tab -->
                    <div class="tab-pane fade" id="current" role="tabpanel">
                        <?php if (!$profile): ?>
                            <div class="alert alert-info mt-4">
                                <strong>Complete your assessment first</strong> to record your current status.
                            </div>
                        <?php else: ?>
                            <form id="currentStatusForm" method="POST" enctype="multipart/form-data" 
                                  action="actions/upload_physique_photo.php">
                                <div class="row mt-4">
                                    <!-- Current Measurements -->
                                    <div class="col-md-6">
                                        <h5 class="fw-semibold mb-3">Current Measurements</h5>
                                        
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Weight (kg)</label>
                                            <input type="number" class="form-control" name="weight_kg" step="0.1" 
                                                   value="<?php echo $profile['weight_kg'] ?? ''; ?>" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Chest (cm)</label>
                                            <input type="number" class="form-control" name="chest_cm" step="0.1">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Waist (cm)</label>
                                            <input type="number" class="form-control" name="waist_cm" step="0.1">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Bicep (cm)</label>
                                            <input type="number" class="form-control" name="bicep_cm" step="0.1">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Thigh (cm)</label>
                                            <input type="number" class="form-control" name="thigh_cm" step="0.1">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Body Fat % (Optional)</label>
                                            <input type="number" class="form-control" name="body_fat_percentage" step="0.1">
                                        </div>
                                    </div>
                                    
                                    <!-- Photo Upload -->
                                    <div class="col-md-6">
                                        <h5 class="fw-semibold mb-3">Progress Photos</h5>
                                        
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Front View</label>
                                            <input type="file" class="form-control" name="photo_front" accept="image/*">
                                            <small class="text-muted">JPG, PNG max 5MB</small>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Side View</label>
                                            <input type="file" class="form-control" name="photo_side" accept="image/*">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Back View</label>
                                            <input type="file" class="form-control" name="photo_back" accept="image/*">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Notes (Optional)</label>
                                            <textarea class="form-control" name="notes" rows="3"></textarea>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="d-flex gap-2 mt-4">
                                    <button type="submit" class="btn btn-success fw-semibold">Record Current Status</button>
                                    <button type="reset" class="btn btn-outline-secondary fw-semibold">Clear</button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Progress Tab -->
                    <div class="tab-pane fade" id="progress" role="tabpanel">
                        <?php if (count($logs) < 1): ?>
                            <div class="alert alert-info mt-4">
                                <strong>No progress records yet.</strong> Record your current status first to track progress.
                            </div>
                        <?php elseif (count($logs) === 1): ?>
                            <div class="mt-4">
                                <h5 class="fw-semibold mb-3">Latest Record</h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="measurement-card">
                                            <h6 class="text-muted mb-3"><?php echo date('F d, Y', strtotime($logs[0]['log_date'])); ?></h6>
                                            <div class="mb-2">
                                                <span class="text-muted">Weight:</span> <strong><?php echo $logs[0]['weight_kg']; ?>kg</strong>
                                            </div>
                                            <div class="mb-2">
                                                <span class="text-muted">Chest:</span> <strong><?php echo $logs[0]['chest_cm'] ?? 'N/A'; ?>cm</strong>
                                            </div>
                                            <div class="mb-2">
                                                <span class="text-muted">Waist:</span> <strong><?php echo $logs[0]['waist_cm'] ?? 'N/A'; ?>cm</strong>
                                            </div>
                                            <div class="mb-2">
                                                <span class="text-muted">Bicep:</span> <strong><?php echo $logs[0]['bicep_cm'] ?? 'N/A'; ?>cm</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="mt-4">
                                <h5 class="fw-semibold mb-4">Progress Comparison</h5>
                                <div class="row">
                                    <?php
                                        $current = $logs[0];
                                        $previous = $logs[1];
                                        
                                        // Calculate changes
                                        $weightChange = $current['weight_kg'] - $previous['weight_kg'];
                                        $chestChange = ($current['chest_cm'] && $previous['chest_cm']) ? $current['chest_cm'] - $previous['chest_cm'] : null;
                                        $waistChange = ($current['waist_cm'] && $previous['waist_cm']) ? $current['waist_cm'] - $previous['waist_cm'] : null;
                                        $bicepChange = ($current['bicep_cm'] && $previous['bicep_cm']) ? $current['bicep_cm'] - $previous['bicep_cm'] : null;
                                    ?>
                                    
                                    <div class="col-md-6 mb-3">
                                        <div class="measurement-card">
                                            <h6 class="text-muted mb-3">Previous: <?php echo date('M d', strtotime($previous['log_date'])); ?></h6>
                                            <div class="mb-2">
                                                <span class="text-muted">Weight:</span> <strong><?php echo $previous['weight_kg']; ?>kg</strong>
                                            </div>
                                            <div class="mb-2">
                                                <span class="text-muted">Chest:</span> <strong><?php echo $previous['chest_cm'] ?? 'N/A'; ?>cm</strong>
                                            </div>
                                            <div class="mb-2">
                                                <span class="text-muted">Waist:</span> <strong><?php echo $previous['waist_cm'] ?? 'N/A'; ?>cm</strong>
                                            </div>
                                            <div class="mb-2">
                                                <span class="text-muted">Bicep:</span> <strong><?php echo $previous['bicep_cm'] ?? 'N/A'; ?>cm</strong>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <div class="measurement-card">
                                            <h6 class="text-muted mb-3">Current: <?php echo date('M d, Y', strtotime($current['log_date'])); ?></h6>
                                            <div class="mb-2">
                                                <span class="text-muted">Weight:</span> <strong><?php echo $current['weight_kg']; ?>kg</strong>
                                                <?php if ($weightChange !== 0): ?>
                                                    <span class="progress-badge <?php echo ($weightChange < 0) ? 'positive' : 'negative'; ?>">
                                                        <?php echo ($weightChange > 0 ? '+' : '') . number_format($weightChange, 1) . 'kg'; ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="mb-2">
                                                <span class="text-muted">Chest:</span> <strong><?php echo $current['chest_cm'] ?? 'N/A'; ?>cm</strong>
                                                <?php if ($chestChange !== null && $chestChange !== 0): ?>
                                                    <span class="progress-badge <?php echo ($chestChange > 0) ? 'positive' : 'negative'; ?>">
                                                        <?php echo ($chestChange > 0 ? '+' : '') . number_format($chestChange, 1) . 'cm'; ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="mb-2">
                                                <span class="text-muted">Waist:</span> <strong><?php echo $current['waist_cm'] ?? 'N/A'; ?>cm</strong>
                                                <?php if ($waistChange !== null && $waistChange !== 0): ?>
                                                    <span class="progress-badge <?php echo ($waistChange < 0) ? 'positive' : 'negative'; ?>">
                                                        <?php echo ($waistChange > 0 ? '+' : '') . number_format($waistChange, 1) . 'cm'; ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="mb-2">
                                                <span class="text-muted">Bicep:</span> <strong><?php echo $current['bicep_cm'] ?? 'N/A'; ?>cm</strong>
                                                <?php if ($bicepChange !== null && $bicepChange !== 0): ?>
                                                    <span class="progress-badge <?php echo ($bicepChange > 0) ? 'positive' : 'negative'; ?>">
                                                        <?php echo ($bicepChange > 0 ? '+' : '') . number_format($bicepChange, 1) . 'cm'; ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const selector = document.getElementById('muscleSelector');
            const targetMusclesInput = document.getElementById('targetMuscles');

            if (!selector || !targetMusclesInput) {
                return;
            }

            const pillsContainer = document.getElementById('selectedMusclePills');
            const genderRadios = document.querySelectorAll('input[name="anatomy_gender_view"]');
            const genderBlocks = selector.querySelectorAll('.anatomy-gender');
            const genderField = document.querySelector('select[name="gender"]');

            const muscleLabels = {
                deltoids: 'Deltoids',
                pectorals: 'Pectorals',
                abdominals: 'Abdominals',
                obliques: 'Obliques',
                biceps: 'Biceps',
                triceps: 'Triceps',
                forearms: 'Forearms',
                trapezius: 'Trapezius',
                lats: 'Latissimus',
                lower_back: 'Lower Back',
                glutes: 'Glutes',
                quadriceps: 'Quadriceps',
                hamstrings: 'Hamstrings',
                calves: 'Calves'
            };

            const existingMuscles = <?php echo json_encode($targetMuscles); ?>;
            const selectedMuscles = new Set(
                Array.isArray(existingMuscles)
                    ? existingMuscles.filter(muscle => Object.prototype.hasOwnProperty.call(muscleLabels, muscle))
                    : []
            );

            function updateTargetMuscles() {
                targetMusclesInput.value = JSON.stringify(Array.from(selectedMuscles));
            }

            function renderPills() {
                if (!pillsContainer) {
                    return;
                }

                pillsContainer.innerHTML = '';

                if (selectedMuscles.size === 0) {
                    const empty = document.createElement('span');
                    empty.className = 'small text-muted';
                    empty.textContent = 'No muscle groups selected yet.';
                    pillsContainer.appendChild(empty);
                    return;
                }

                Array.from(selectedMuscles).forEach(muscleKey => {
                    const pill = document.createElement('span');
                    pill.className = 'muscle-pill';
                    pill.textContent = muscleLabels[muscleKey] || muscleKey;
                    pillsContainer.appendChild(pill);
                });
            }

            function syncVisualSelection() {
                selector.querySelectorAll('.muscle-hit').forEach(node => {
                    const muscleKey = node.getAttribute('data-muscle');
                    node.classList.toggle('selected', selectedMuscles.has(muscleKey));
                });

                updateTargetMuscles();
                renderPills();
            }

            function setActiveGender(gender) {
                selector.setAttribute('data-active-gender', gender);
                genderBlocks.forEach(block => {
                    const isMatch = block.getAttribute('data-gender') === gender;
                    block.classList.toggle('d-none', !isMatch);
                });
            }

            selector.addEventListener('click', function(event) {
                const muscleNode = event.target.closest('.muscle-hit');
                if (!muscleNode || !selector.contains(muscleNode)) {
                    return;
                }

                const muscleKey = muscleNode.getAttribute('data-muscle');
                if (!muscleKey) {
                    return;
                }

                if (selectedMuscles.has(muscleKey)) {
                    selectedMuscles.delete(muscleKey);
                } else {
                    selectedMuscles.add(muscleKey);
                }

                syncVisualSelection();
            });

            genderRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    if (this.checked) {
                        setActiveGender(this.value);
                    }
                });
            });

            if (genderField) {
                genderField.addEventListener('change', function() {
                    const value = this.value === 'female' ? 'female' : 'male';
                    const targetRadio = document.querySelector(`input[name="anatomy_gender_view"][value="${value}"]`);
                    if (targetRadio) {
                        targetRadio.checked = true;
                        setActiveGender(value);
                    }
                });

                if (genderField.value === 'female') {
                    const femaleRadio = document.getElementById('anatomyFemale');
                    if (femaleRadio) {
                        femaleRadio.checked = true;
                        setActiveGender('female');
                    }
                }
            }

            document.getElementById('assessmentForm')?.addEventListener('reset', function() {
                selectedMuscles.clear();
                syncVisualSelection();
            });

            setActiveGender(selector.getAttribute('data-active-gender') || 'male');
            syncVisualSelection();
        });
    </script>
</body>
</html>
