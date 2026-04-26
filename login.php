<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config.php';

if (!empty($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if ($name === '' || $email === '') {
        set_flash('danger', 'Please provide both name and email.');
    } else {
        $userId = 1;

        if ($pdo instanceof PDO) {
            $selectUser = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
            $selectUser->execute(['email' => $email]);
            $existingUser = $selectUser->fetch();

            if ($existingUser) {
                $userId = (int) $existingUser['id'];

                $updateUser = $pdo->prepare('UPDATE users SET name = :name WHERE id = :id');
                $updateUser->execute([
                    'id' => $userId,
                    'name' => $name,
                ]);
            } else {
                $insertUser = $pdo->prepare('INSERT INTO users (name, email) VALUES (:name, :email)');
                $insertUser->execute([
                    'name' => $name,
                    'email' => $email,
                ]);
                $userId = (int) $pdo->lastInsertId();
            }
        }

        $_SESSION['user'] = [
            'id' => $userId,
            'name' => $name,
            'email' => $email,
        ];

        header('Location: index.php');
        exit;
    }
}

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | FitBalance</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="style.css">
</head>
<body class="fit-login-body">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4 p-md-5">
                    <h1 class="h3 mb-3">Welcome to FitBalance</h1>
                    <p class="text-muted mb-4">Sign in to start tracking your meals and workouts.</p>

                    <?php if ($flash): ?>
                        <div class="alert alert-<?php echo escape($flash['type']); ?>" role="alert">
                            <?php echo escape($flash['message']); ?>
                        </div>
                    <?php endif; ?>

                    <form method="post" class="d-grid gap-3">
                        <div>
                            <label for="name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div>
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <button class="btn btn-success btn-fit" type="submit">Start My Dashboard</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
