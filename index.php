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

$routines = [
    'Monday' => 'Upper Body Strength + 15 min Cardio',
    'Tuesday' => 'Core Stability + 30 min Brisk Walk',
    'Wednesday' => 'Lower Body Strength + Stretching',
    'Thursday' => 'HIIT 20 min + Mobility',
    'Friday' => 'Full Body Circuit + Light Jog',
    'Saturday' => 'Active Recovery: Yoga + Walk',
    'Sunday' => 'Rest and Meal Prep',
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

    $logsStatement = $pdo->prepare(
        'SELECT id, meal_name, calories, notes, log_date, created_at
         FROM calorie_logs
         WHERE user_id = :user_id
         ORDER BY log_date DESC, created_at DESC
         LIMIT 12'
    );
    $logsStatement->execute(['user_id' => (int) $user['id']]);
    $recentLogs = $logsStatement->fetchAll();
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

                    <form method="post" action="actions/save_calorie_log.php" class="row g-3">
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
                                    <?php foreach ($recentLogs as $log): ?>
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
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>

        <div class="col-12 col-xl-3">
            <?php require __DIR__ . '/includes/sidebar.php'; ?>
        </div>
    </div>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
