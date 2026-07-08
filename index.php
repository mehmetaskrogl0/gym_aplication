<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';

require_login();

$user = current_user();
$flash = get_flash();
$today = date('Y-m-d');
$dailyTargetCalories = 2200;
$todayCalories = 0;
$recentLogs = [];
$logsPerPage = 12;
$logsOffset = max(0, (int) ($_GET['logs_offset'] ?? 0));
$logsFragment = (($_GET['logs_fragment'] ?? '') === '1');
$totalLogs = 0;
$hasMoreLogs = false;

function render_calorie_log_rows(array $logs): string
{
    ob_start();

    foreach ($logs as $log):
        ?>
        <tr>
            <td><?php echo escape($log['log_date']); ?></td>
            <td><?php echo escape($log['meal_name']); ?></td>
            <td><?php echo (int) $log['calories']; ?> kcal</td>
            <td><?php echo escape($log['notes']); ?></td>
            <td class="text-end">
                <a class="btn btn-sm btn-outline-success" href="edit_calorie_log.php?id=<?php echo (int) $log['id']; ?>">Edit</a>
                <form method="post" action="actions/delete_calorie_log.php" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?php echo escape(csrf_token()); ?>">
                    <input type="hidden" name="id" value="<?php echo (int) $log['id']; ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this calorie log?');">Delete</button>
                </form>
            </td>
        </tr>
        <?php
    endforeach;

    return (string) ob_get_clean();
}

$routines = [
  'Monday' => 'Chest + Triceps',
    'Tuesday' => 'Back + Biceps',
    'Wednesday' => 'Legs + Core',
    'Thursday' => 'off Day',
    'Friday' => 'Shoulders + Arms',
    'Saturday' => 'Legs + Core',
   'Sunday' => 'Off Day'
];
$todayName = date('l');
$todayWorkoutRoutine = $routines[$todayName] ?? 'Stay active with a light walk and stretching.';

if ($pdo instanceof PDO) {
    $sumStatement = $pdo->prepare(
        'SELECT COALESCE(SUM(calories), 0) AS total_calories
         FROM calorie_logs
         WHERE user_id = :user_id AND log_date = :log_date'
    );
    $sumStatement->execute([
        'user_id' => (int) $user['id'],
        'log_date' => $today,
    ]);
    $todayCalories = (int) ($sumStatement->fetch()['total_calories'] ?? 0);

    $countStatement = $pdo->prepare(
        'SELECT COUNT(*) AS total_logs
         FROM calorie_logs
         WHERE user_id = :user_id'
    );
    $countStatement->execute(['user_id' => (int) $user['id']]);
    $totalLogs = (int) ($countStatement->fetch()['total_logs'] ?? 0);

    $logsStatement = $pdo->prepare(
        'SELECT id, meal_name, calories, notes, log_date, created_at
         FROM calorie_logs
         WHERE user_id = :user_id
         ORDER BY log_date DESC, created_at DESC
         LIMIT :limit OFFSET :offset'
    );
    $logsStatement->bindValue(':user_id', (int) $user['id'], PDO::PARAM_INT);
    $logsStatement->bindValue(':limit', $logsPerPage, PDO::PARAM_INT);
    $logsStatement->bindValue(':offset', $logsOffset, PDO::PARAM_INT);
    $logsStatement->execute();
    $recentLogs = $logsStatement->fetchAll();
    $hasMoreLogs = ($logsOffset + count($recentLogs)) < $totalLogs;
}

if ($logsFragment) {
    header('Content-Type: application/json; charset=UTF-8');

    echo json_encode([
        'success' => true,
        'rowsHtml' => render_calorie_log_rows($recentLogs),
        'hasMore' => $hasMoreLogs,
        'nextOffset' => $logsOffset + count($recentLogs),
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$calorieProgress = (int) min(100, round(($todayCalories / max($dailyTargetCalories, 1)) * 100));

require_once __DIR__ . '/includes/header.php';
?>
<main class="container-fluid px-3 px-lg-4 py-4">
    <div class="row g-4 align-items-stretch">
        <div class="col-12 col-xl-9">
            <section class="fit-hero card border-0 overflow-hidden">
                <div class="hero-overlay"></div>
                <div class="card-body p-4 p-lg-5 position-relative">
                    <h1 class="display-6 fw-bold mb-3">Daily Snapshot</h1>
                    <p class="lead mb-4">Track nutrition and training in one clean workspace designed for consistency.</p>
                    <div class="d-flex flex-wrap gap-2">
                        <a class="btn btn-success btn-fit" href="#meal-log-form">Log Meal</a>
                        <a class="btn btn-light" href="workout.php">Start Workout</a>
                        <a class="btn btn-light" href="steps.php">Step Counter</a>
                    </div>
                </div>
            </section>

            <?php if (isset($dbConnectionError)): ?>
                <div class="alert alert-warning mt-4" role="alert">
                    Database is not connected yet. Update credentials in config.php. Error: <?php echo escape($dbConnectionError); ?>
                </div>
            <?php endif; ?>

            <?php if ($flash): ?>
                <div class="alert alert-<?php echo escape($flash['type']); ?> mt-4" role="alert">
                    <?php echo escape($flash['message']); ?>
                </div>
            <?php endif; ?>

            <section class="row g-3 mt-1">
                <div class="col-12 col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body p-4">
                            <h2 class="h6 text-uppercase text-muted mb-3">Calorie Intake</h2>
                            <p class="display-6 fw-bold mb-2"><?php echo $todayCalories; ?> <span class="fs-6 text-muted">/ <?php echo $dailyTargetCalories; ?> kcal</span></p>
                            <div class="progress" role="progressbar" aria-label="Daily calories progress" aria-valuenow="<?php echo $calorieProgress; ?>" aria-valuemin="0" aria-valuemax="100">
                                <div class="progress-bar bg-success" style="width: <?php echo $calorieProgress; ?>%"></div>
                            </div>
                            <p class="small text-muted mt-2 mb-0">Progress for <?php echo escape($today); ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body p-4">
                            <h2 class="h6 text-uppercase text-muted mb-3">Today's Workout Routine</h2>
                            <p class="mb-0 fw-semibold"><?php echo escape($todayWorkoutRoutine); ?></p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="card border-0 shadow-sm mt-4" id="meal-log-form">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <h2 class="h5 mb-0">Daily Calorie Logs</h2>
                        <span class="badge text-bg-light">Simple CRUD Starter</span>
                    </div>

                    <!-- Mobile: Tablar -->
                    <ul class="nav nav-tabs d-block d-lg-none mb-4" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="photo-tab" data-bs-toggle="tab" data-bs-target="#photo-panel" type="button" role="tab">📸 Scan Food</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="manual-tab" data-bs-toggle="tab" data-bs-target="#manual-panel" type="button" role="tab">✏️ Manual Entry</button>
                        </li>
                    </ul>

                    <!-- Fotoğraf Çekme Bölümü (Mobil) -->
                    <div class="tab-content d-block d-lg-none">
                        <div class="tab-pane fade show active" id="photo-panel" role="tabpanel">
                            <div class="card bg-light border-0 mb-4">
                                <div class="card-body text-center py-5">
                                    <p class="text-muted mb-3">Take a photo of your meal to automatically calculate calories</p>
                                    
                                    <!-- Kamera Erişim -->
                                    <div class="mb-3">
                                        <button type="button" class="btn btn-success btn-fit btn-lg" id="cameraBtn">
                                            📷 Take Photo
                                        </button>
                                    </div>

                                    <!-- Gizli Input -->
                                    <input type="file" id="photoInput" accept="image/*" capture="environment" style="display: none;">
                                    
                                    <!-- Fotoğraf Preview -->
                                    <div id="photoPreview" style="display: none;" class="mb-3">
                                        <img id="previewImage" src="" alt="Food preview" class="img-fluid rounded mb-3" style="max-height: 300px;">
                                        <div id="loadingSpinner" style="display: none;" class="text-center mb-3">
                                            <div class="spinner-border text-success" role="status">
                                                <span class="visually-hidden">Analyzing...</span>
                                            </div>
                                            <p class="text-muted mt-2">Analyzing food...</p>
                                        </div>
                                        <div id="analysisResult" style="display: none;" class="alert alert-info mb-3"></div>
                                        <div class="d-flex gap-2 justify-content-center">
                                            <button type="button" class="btn btn-success" id="confirmPhotoBtn">✓ Confirm</button>
                                            <button type="button" class="btn btn-outline-secondary" id="retakePhotoBtn">↻ Retake</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Manual Entry Tab -->
                        <div class="tab-pane fade" id="manual-panel" role="tabpanel">
                            <form method="post" action="actions/save_calorie_log.php" class="row g-3" id="manualFormMobile">
                                <input type="hidden" name="csrf_token" value="<?php echo escape(csrf_token()); ?>">
                                <input type="hidden" name="log_date" value="<?php echo escape($today); ?>">
                                <div class="col-12">
                                    <label for="meal_name_mobile" class="form-label">Meal</label>
                                    <input type="text" class="form-control" id="meal_name_mobile" name="meal_name" placeholder="Lunch" required>
                                </div>
                                <div class="col-12">
                                    <label for="calories_mobile" class="form-label">Calories</label>
                                    <input type="number" class="form-control" id="calories_mobile" name="calories" min="1" step="1" required>
                                </div>
                                <div class="col-12">
                                    <label for="notes_mobile" class="form-label">Notes</label>
                                    <input type="text" class="form-control" id="notes_mobile" name="notes" placeholder="Optional">
                                </div>
                                <div class="col-12 d-grid">
                                    <button type="submit" class="btn btn-success btn-fit">Add Calorie Log</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <!-- /Mobile Tabs End -->

                    <!-- Desktop: Normal Form -->
                    <form method="post" action="actions/save_calorie_log.php" class="row g-3 d-none d-lg-flex">
                        <input type="hidden" name="csrf_token" value="<?php echo escape(csrf_token()); ?>">
                        <div class="col-12 col-md-3">
                            <label for="log_date" class="form-label">Date</label>
                            <input type="date" class="form-control" id="log_date" name="log_date" value="<?php echo escape($today); ?>" required>
                        </div>
                        <div class="col-12 col-md-3">
                            <label for="meal_name" class="form-label">Meal</label>
                            <input type="text" class="form-control" id="meal_name" name="meal_name" placeholder="Lunch" required>
                        </div>
                        <div class="col-12 col-md-3">
                            <label for="calories" class="form-label">Calories</label>
                            <input type="number" class="form-control" id="calories" name="calories" min="1" step="1" required>
                        </div>
                        <div class="col-12 col-md-3">
                            <label for="notes" class="form-label">Notes</label>
                            <input type="text" class="form-control" id="notes" name="notes" placeholder="Protein-rich meal">
                        </div>
                        <div class="col-12 d-grid d-md-flex justify-content-md-end">
                            <button type="submit" class="btn btn-success btn-fit">Add Calorie Log</button>
                        </div>
                    </form>

                    <div class="table-responsive mt-4">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Meal</th>
                                    <th>Calories</th>
                                    <th>Notes</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!$recentLogs): ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">No logs yet. Add your first meal above.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php echo render_calorie_log_rows($recentLogs); ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($hasMoreLogs): ?>
                        <div class="text-center mt-3">
                            <button type="button"
                                    class="btn btn-outline-success"
                                    id="loadMoreLogsBtn"
                                    data-next-offset="<?php echo $logsOffset + count($recentLogs); ?>">
                                Load More
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>

        <?php if (!is_mobile()): ?>
        <div class="col-12 col-xl-3">
            <?php require __DIR__ . '/includes/sidebar.php'; ?>
        </div>
        <?php endif; ?>
    </div>
</main>

<!-- Food Photo Recognition Script -->
<script>
    // Kamera butonuna tıklanınca dosya input'u aç
    document.getElementById('cameraBtn').addEventListener('click', function() {
        document.getElementById('photoInput').click();
    });

    // Fotoğraf seçildiğinde
    document.getElementById('photoInput').addEventListener('change', async function(e) {
        const file = e.target.files[0];
        if (!file) return;

        // Fotoğrafı göster
        const reader = new FileReader();
        reader.onload = function(event) {
            const img = document.getElementById('previewImage');
            img.src = event.target.result;
            document.getElementById('photoPreview').style.display = 'block';
            
            // Analiz etmeye başla
            analyzeFood(event.target.result, file);
        };
        reader.readAsDataURL(file);
    });

    // Yemeği Nutritionix API ile analiz et
    async function analyzeFood(imageData, file) {
        const spinner = document.getElementById('loadingSpinner');
        const resultDiv = document.getElementById('analysisResult');
        
        spinner.style.display = 'block';
        resultDiv.style.display = 'none';

        try {
            // Fotoğrafı backend'e gönder
            const formData = new FormData();
            formData.append('photo', file);
            formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);

            const response = await fetch('actions/process_food_photo.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            spinner.style.display = 'none';

            if (data.success) {
                // Sonuçları göster
                resultDiv.innerHTML = `
                    <strong>Detected:</strong> ${data.foodName}<br>
                    <strong>Estimated Calories:</strong> ${data.calories} kcal
                `;
                resultDiv.style.display = 'block';

                // Form'a değerleri doldur
                document.getElementById('meal_name_mobile').value = data.foodName;
                document.getElementById('calories_mobile').value = data.calories;
                document.getElementById('notes_mobile').value = 'Detected from photo';
            } else {
                resultDiv.innerHTML = `<strong>Error:</strong> ${data.message || 'Could not analyze food'}`;
                resultDiv.classList.add('alert-danger');
                resultDiv.classList.remove('alert-info');
                resultDiv.style.display = 'block';
            }
        } catch (error) {
            spinner.style.display = 'none';
            resultDiv.innerHTML = `<strong>Error:</strong> ${error.message}`;
            resultDiv.classList.add('alert-danger');
            resultDiv.classList.remove('alert-info');
            resultDiv.style.display = 'block';
        }
    }

    // Fotoğrafı tekrar çek
    document.getElementById('retakePhotoBtn').addEventListener('click', function() {
        document.getElementById('photoInput').value = '';
        document.getElementById('photoPreview').style.display = 'none';
        document.getElementById('analysisResult').style.display = 'none';
        document.getElementById('loadingSpinner').style.display = 'none';
        document.getElementById('cameraBtn').click();
    });

    // Fotoğrafı onayla ve form'u submit et
    document.getElementById('confirmPhotoBtn').addEventListener('click', function() {
        document.getElementById('manualFormMobile').submit();
    });

    const loadMoreLogsBtn = document.getElementById('loadMoreLogsBtn');
    if (loadMoreLogsBtn) {
        loadMoreLogsBtn.addEventListener('click', async function() {
            const nextOffset = Number(loadMoreLogsBtn.dataset.nextOffset || '0');
            loadMoreLogsBtn.disabled = true;
            loadMoreLogsBtn.textContent = 'Loading...';

            try {
                const response = await fetch(`index.php?logs_fragment=1&logs_offset=${nextOffset}`);
                const data = await response.json();

                if (!data.success) {
                    throw new Error('Could not load more logs');
                }

                const tbody = document.querySelector('.table-responsive tbody');
                if (tbody && data.rowsHtml) {
                    tbody.insertAdjacentHTML('beforeend', data.rowsHtml);
                }

                if (data.hasMore) {
                    loadMoreLogsBtn.dataset.nextOffset = String(data.nextOffset);
                    loadMoreLogsBtn.disabled = false;
                    loadMoreLogsBtn.textContent = 'Load More';
                } else {
                    loadMoreLogsBtn.remove();
                }
            } catch (error) {
                loadMoreLogsBtn.disabled = false;
                loadMoreLogsBtn.textContent = 'Load More';
                alert(error.message || 'Could not load more logs');
            }
        });
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
