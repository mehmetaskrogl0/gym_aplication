<aside class="fit-sidebar card border-0 shadow-sm h-100">
    <div class="card-body p-4">
        <h2 class="h6 text-uppercase text-muted mb-3">Quick Access</h2>
        <div class="d-grid gap-2">
            <a class="btn btn-success btn-fit" href="#meal-log-form">Log Meal</a>
            <a class="btn btn-outline-success" href="workout.php">Start Workout</a>
            <a class="btn btn-outline-success" href="steps.php">Step Counter</a>
            <a class="btn btn-outline-success" href="physique.php">Current Physique</a>
        </div>

        <hr class="my-4">

        <h3 class="h6 text-uppercase text-muted mb-3">Weekly Focus</h3>
        <?php
            $schedule = [
                'Monday' => 'Chest + Triceps',
                'Tuesday' => 'Back + Biceps',
                'Wednesday' => 'Legs + Core',
                'Thursday' => 'off Day',
                'Friday' => 'Shoulders + Arms',
                'Saturday' => 'Legs + Core',
                'Sunday' => 'Off Day'
            ];
            
            $today = date('l');
            
            foreach ($schedule as $day => $focus) {
                $isToday = ($day === $today) ? true : false;
                $isOffDay = (strpos(strtolower($focus), 'off') !== false);
                
                $badgeClass = $isOffDay ? 'bg-light border border-secondary text-dark' : 'bg-success text-white';
                $dayClass = $isToday ? 'border-3 border-success ps-3' : '';
        ?>
            <div class="d-flex justify-content-between align-items-center mb-2 p-2 rounded <?php echo $dayClass; ?>" 
                 style="<?php echo $isToday ? 'background-color: rgba(40, 167, 69, 0.08);' : ''; ?>">
                <small class="fw-semibold text-dark"><?php echo $day; ?></small>
                <span class="badge <?php echo $badgeClass; ?>" style="font-size: 0.75rem;">
                    <?php echo $focus; ?>
                </span>
            </div>
        <?php } ?>

        <hr class="my-4">

        <h3 class="h6 text-uppercase text-muted mb-3">Motivation</h3>
        <p class="mb-0 text-secondary small">Small choices each day become your strongest habits. Keep your nutrition and movement balanced.</p>
    </div>
</aside>
