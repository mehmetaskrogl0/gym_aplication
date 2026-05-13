<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';

require_login();

require_once __DIR__ . '/includes/header.php';
?>
<main class="container-fluid px-3 px-lg-4 py-4">
    <!-- WORKOUT PLANNER - Main Section -->
    <div class="row g-4" id="planner-section">
        <!-- Left Column -->
        <div class="col-lg-6 mx-auto">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">Workout Planner</h5>
                </div>
                <div class="card-body">
                    <ul class="nav nav-pills mb-4" id="plannerTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active btn-fit" id="create-tab" data-bs-toggle="pill" data-bs-target="#create-content" type="button" role="tab" aria-controls="create-content" aria-selected="true">
                                Create New
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="saved-tab" data-bs-toggle="pill" data-bs-target="#saved-content" type="button" role="tab" aria-controls="saved-content" aria-selected="false">
                                My Saved
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="plannerTabContent">
                        <div class="tab-pane fade show active" id="create-content" role="tabpanel" aria-labelledby="create-tab">
                            <!-- Start Custom Workout Card -->
                            <div class="card border-0 bg-light mb-3">
                                <div class="card-body text-center py-5">
                                    <div style="font-size: 3rem; margin-bottom: 1rem;">➕</div>
                                    <h6 class="fw-600 mb-1">START CUSTOM WORKOUT</h6>
                                    <p class="text-muted small mb-4">Build from scratch</p>
                                    <button type="button" class="btn btn-fit w-100" onclick="showCustomWorkout()">
                                        Create New Workout
                                    </button>
                                </div>
                            </div>

                            <!-- Generate AI Workout Card -->
                            <div class="card border-0 bg-light">
                                <div class="card-body text-center py-5">
                                    <div style="font-size: 3rem; margin-bottom: 1rem;">🤖</div>
                                    <h6 class="fw-600 mb-1">GENERATE AI WORKOUT</h6>
                                    <p class="text-muted small mb-4">Personalized for you</p>
                                    <button type="button" class="btn btn-fit w-100" onclick="showGenerateWorkout()">
                                        Generate with AI
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="saved-content" role="tabpanel" aria-labelledby="saved-tab">
                            <p class="text-muted text-center py-5">No saved workouts yet</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- GENERATE MY WORKOUT SECTION (Hidden) -->
    <div id="generate-section" style="display: none;">
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom d-flex align-items-center gap-2">
                        <button class="btn btn-light btn-sm" onclick="backToPlanner()">
                            <i class="fas fa-arrow-left"></i>
                        </button>
                        <h5 class="mb-0">Generate My Workout</h5>
                    </div>
                    <div class="card-body">
                        <form id="generateWorkoutForm">
                            <div class="mb-4">
                                <label class="form-label fw-600">Goal</label>
                                <select class="form-select" name="goal" required>
                                    <option selected disabled>Select your goal</option>
                                    <option>Muscle Gain</option>
                                    <option>Weight Loss</option>
                                    <option>Strength</option>
                                    <option>Endurance</option>
                                    <option>Flexibility</option>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-600">Experience</label>
                                <div class="d-flex gap-2">
                                    <input type="radio" name="experience" value="beginner" id="exp_beginner" class="form-check-input" required>
                                    <label class="form-check-label me-3" for="exp_beginner">Beginner</label>
                                    
                                    <input type="radio" name="experience" value="intermediate" id="exp_intermediate" class="form-check-input">
                                    <label class="form-check-label me-3" for="exp_intermediate">Intermediate</label>
                                    
                                    <input type="radio" name="experience" value="advanced" id="exp_advanced" class="form-check-input">
                                    <label class="form-check-label" for="exp_advanced">Advanced</label>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-600">Days per week</label>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-outline-secondary days-btn" data-days="3">3</button>
                                    <button type="button" class="btn btn-outline-secondary days-btn" data-days="4">4</button>
                                    <button type="button" class="btn btn-outline-secondary days-btn" data-days="5">5</button>
                                    <input type="hidden" name="days_per_week" id="daysPerWeek" required>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-600">Time limit</label>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-outline-secondary time-btn" data-time="30">30 mins</button>
                                    <button type="button" class="btn btn-outline-secondary time-btn" data-time="45">45 mins</button>
                                    <button type="button" class="btn btn-outline-secondary time-btn" data-time="60">60 mins</button>
                                    <button type="button" class="btn btn-outline-secondary time-btn" data-time="90">90 mins</button>
                                    <input type="hidden" name="time_limit" id="timeLimit" required>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-600">Equipment</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="equipment" value="home_gym" id="home_gym">
                                    <label class="form-check-label" for="home_gym">Home Gym</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="equipment" value="commercial_gym" id="commercial_gym">
                                    <label class="form-check-label" for="commercial_gym">Commercial Gym</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="equipment" value="dumbbells" id="dumbbells">
                                    <label class="form-check-label" for="dumbbells">Dumbbells Only</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="equipment" value="bodyweight" id="bodyweight">
                                    <label class="form-check-label" for="bodyweight">Bodyweight</label>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-fit w-100 py-2 fw-600">Generate Workout</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Preview Panel -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm sticky-top" style="top: 20px;">
                    <div class="card-body text-center text-muted">
                        <p>Workout preview will appear here</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MY CUSTOM WORKOUT SECTION (Hidden) -->
    <div id="custom-section" style="display: none;">
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <button class="btn btn-light btn-sm" onclick="backToPlanner()">
                                <i class="fas fa-arrow-left"></i>
                            </button>
                            <h5 class="mb-0">Day 1 - Upper Body (Custom)</h5>
                            <button class="btn btn-light btn-sm">
                                <i class="fas fa-edit"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Exercise 1 -->
                        <div class="border rounded p-4 mb-3">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h6 class="fw-600 mb-1">Exercise 1</h6>
                                    <select class="form-select form-select-sm">
                                        <option selected>Bench Press</option>
                                    </select>
                                </div>
                                <button class="btn btn-light btn-sm">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </div>

                            <div class="row g-3">
                                <div class="col-4">
                                    <label class="form-label small fw-600">Sets</label>
                                    <input type="number" class="form-control" value="3" min="1">
                                </div>
                                <div class="col-4">
                                    <label class="form-label small fw-600">Reps</label>
                                    <input type="number" class="form-control" value="10" min="1">
                                </div>
                                <div class="col-4">
                                    <label class="form-label small fw-600">Rest</label>
                                    <input type="text" class="form-control" value="90s">
                                </div>
                            </div>
                        </div>

                        <!-- Add Exercise Button -->
                        <button type="button" class="btn btn-outline-secondary w-100 py-2">
                            <i class="fas fa-plus me-2"></i>Add Exercise
                        </button>
                    </div>
                </div>
            </div>

            <!-- Right Panel -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white border-bottom">
                        <h6 class="mb-0">Add Day</h6>
                    </div>
                    <div class="card-body">
                        <button type="button" class="btn btn-outline-secondary w-100">
                            <i class="fas fa-plus me-2"></i>Add Day
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- WORKOUT SUMMARY SECTION (Hidden) -->
    <div id="summary-section" style="display: none;">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <h4 class="fw-600 mb-4">Workout complete!</h4>
                
                <div class="row g-4 mb-5">
                    <div class="col-md-6">
                        <div class="bg-light p-4 rounded">
                            <p class="text-muted small mb-2">Session Duration</p>
                            <h5 class="fw-600">1h 05m</h5>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="bg-light p-4 rounded">
                            <p class="text-muted small mb-2">Total Volume</p>
                            <h5 class="fw-600">1,800 kg</h5>
                        </div>
                    </div>
                </div>

                <!-- Chart Placeholder -->
                <div class="bg-light rounded p-4 mb-4" style="height: 300px; display: flex; align-items: center; justify-content: center;">
                    <p class="text-muted">Progress Chart</p>
                </div>

                <div class="row g-2">
                    <div class="col-md-6">
                        <button class="btn btn-outline-secondary w-100 py-2">Save Workout</button>
                    </div>
                    <div class="col-md-6">
                        <button class="btn btn-fit w-100 py-2">Done</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

</main>

<style>
    .nav-tabs .nav-link {
        color: var(--fit-gray-600);
        border-bottom: 3px solid transparent;
    }

    .nav-tabs .nav-link.active {
        color: var(--fit-success);
        background-color: transparent;
        border-bottom: 3px solid var(--fit-success);
    }

    .nav-tabs .nav-link:hover {
        border-bottom-color: var(--fit-gray-200);
    }

    .btn-fit {
        background-color: var(--fit-success);
        border-color: var(--fit-success);
        color: white;
        font-weight: 600;
    }

    .btn-fit:hover {
        background-color: var(--fit-success-dark);
        border-color: var(--fit-success-dark);
        color: white;
    }

    .days-btn, .time-btn {
        min-width: 80px;
    }

    .days-btn.active, .time-btn.active {
        background-color: var(--fit-success);
        color: white;
        border-color: var(--fit-success);
    }

    @media (max-width: 991.98px) {
        .card {
            margin-bottom: 1.5rem;
        }
        
        .sticky-top {
            position: static !important;
        }
    }
</style>

<script>
    function showGenerateWorkout() {
        document.getElementById('planner-section').style.display = 'none';
        document.getElementById('generate-section').style.display = 'block';
        window.scrollTo(0, 0);
    }

    function showCustomWorkout() {
        document.getElementById('planner-section').style.display = 'none';
        document.getElementById('custom-section').style.display = 'block';
        window.scrollTo(0, 0);
    }

    function backToPlanner() {
        document.getElementById('planner-section').style.display = 'block';
        document.getElementById('generate-section').style.display = 'none';
        document.getElementById('custom-section').style.display = 'none';
        document.getElementById('summary-section').style.display = 'none';
        window.scrollTo(0, 0);
    }

    // Days per week button handler
    document.querySelectorAll('.days-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelectorAll('.days-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            document.getElementById('daysPerWeek').value = this.dataset.days;
        });
    });

    // Time limit button handler
    document.querySelectorAll('.time-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelectorAll('.time-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            document.getElementById('timeLimit').value = this.dataset.time;
        });
    });

    // Form submission
    if (document.getElementById('generateWorkoutForm')) {
        document.getElementById('generateWorkoutForm').addEventListener('submit', function(e) {
            e.preventDefault();
            // Switch to custom workout section
            document.getElementById('generate-section').style.display = 'none';
            document.getElementById('custom-section').style.display = 'block';
            window.scrollTo(0, 0);
        });
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
