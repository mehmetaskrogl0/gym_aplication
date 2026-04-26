<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';

require_login();

$user = current_user();
$id = (int) ($_GET['id'] ?? 0);

if (!($pdo instanceof PDO)) {
    set_flash('danger', 'Database connection is not available.');
    header('Location: index.php');
    exit;
}

$statement = $pdo->prepare('SELECT id, meal_name, calories, notes, log_date FROM calorie_logs WHERE id = :id AND user_id = :user_id');
$statement->execute([
    'id' => $id,
    'user_id' => (int) $user['id'],
]);
$log = $statement->fetch();

if (!$log) {
    set_flash('danger', 'Calorie log not found.');
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/includes/header.php';
?>
<main class="container py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h1 class="h4 mb-3">Edit Calorie Log</h1>
                    <form method="post" action="actions/update_calorie_log.php" class="row g-3">
                        <input type="hidden" name="csrf_token" value="<?php echo escape(csrf_token()); ?>">
                        <input type="hidden" name="id" value="<?php echo (int) $log['id']; ?>">
                        <div class="col-12 col-md-6">
                            <label for="log_date" class="form-label">Date</label>
                            <input type="date" class="form-control" id="log_date" name="log_date" value="<?php echo escape($log['log_date']); ?>" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="meal_name" class="form-label">Meal Name</label>
                            <input type="text" class="form-control" id="meal_name" name="meal_name" value="<?php echo escape($log['meal_name']); ?>" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="calories" class="form-label">Calories</label>
                            <input type="number" class="form-control" id="calories" name="calories" min="1" value="<?php echo (int) $log['calories']; ?>" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="notes" class="form-label">Notes</label>
                            <input type="text" class="form-control" id="notes" name="notes" value="<?php echo escape($log['notes']); ?>">
                        </div>
                        <div class="col-12 d-flex gap-2 justify-content-end">
                            <a class="btn btn-light" href="index.php">Cancel</a>
                            <button type="submit" class="btn btn-success btn-fit">Update Log</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
