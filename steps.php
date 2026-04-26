<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';

require_login();

require_once __DIR__ . '/includes/header.php';
?>
<main class="container-fluid px-3 px-lg-4 py-4">
    <div class="card border-0 shadow-sm fit-hero fit-hero-secondary">
        <div class="hero-overlay"></div>
        <div class="card-body p-4 p-lg-5 position-relative">
            <h1 class="h3 mb-2">Step Counter</h1>
            <p class="mb-0">Placeholder for daily steps, weekly trends, and activity goals.</p>
        </div>
    </div>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
