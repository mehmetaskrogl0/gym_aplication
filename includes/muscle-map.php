<!-- Interactive Muscle Map Component -->
<!-- Front View -->
<svg id="muscleFront" viewBox="0 0 200 400" class="muscle-map" style="max-width: 300px; height: auto;">
    <!-- Head -->
    <circle cx="100" cy="30" r="15" class="muscle-group" data-muscle="Head" fill="#FFD5B5"/>
    
    <!-- Neck -->
    <rect x="90" y="45" width="20" height="15" class="muscle-group" data-muscle="Neck" fill="#FFD5B5"/>
    
    <!-- Shoulders -->
    <ellipse cx="140" cy="70" rx="30" ry="20" class="muscle-group" data-muscle="Shoulders" fill="#E6C8A2"/>
    <ellipse cx="60" cy="70" rx="30" ry="20" class="muscle-group" data-muscle="Shoulders" fill="#E6C8A2"/>
    
    <!-- Chest -->
    <path d="M 100 65 Q 140 85 140 130 L 60 130 Q 60 85 100 65" class="muscle-group" data-muscle="Chest" fill="#F4A460"/>
    
    <!-- Abs -->
    <rect x="75" y="130" width="50" height="60" class="muscle-group" data-muscle="Abs" fill="#DAA520"/>
    
    <!-- Biceps (Front) -->
    <ellipse cx="55" cy="100" rx="15" ry="35" class="muscle-group" data-muscle="Biceps" fill="#CD853F"/>
    <ellipse cx="145" cy="100" rx="15" ry="35" class="muscle-group" data-muscle="Biceps" fill="#CD853F"/>
    
    <!-- Forearms -->
    <ellipse cx="50" cy="155" rx="12" ry="30" class="muscle-group" data-muscle="Forearms" fill="#D2691E"/>
    <ellipse cx="150" cy="155" rx="12" ry="30" class="muscle-group" data-muscle="Forearms" fill="#D2691E"/>
    
    <!-- Quadriceps -->
    <ellipse cx="70" cy="230" rx="20" ry="50" class="muscle-group" data-muscle="Quadriceps" fill="#B8860B"/>
    <ellipse cx="130" cy="230" rx="20" ry="50" class="muscle-group" data-muscle="Quadriceps" fill="#B8860B"/>
    
    <!-- Calves -->
    <ellipse cx="70" cy="320" rx="15" ry="35" class="muscle-group" data-muscle="Calves" fill="#A0522D"/>
    <ellipse cx="130" cy="320" rx="15" ry="35" class="muscle-group" data-muscle="Calves" fill="#A0522D"/>
</svg>

<!-- Back View -->
<svg id="muscleBack" viewBox="0 0 200 400" class="muscle-map" style="max-width: 300px; height: auto;">
    <!-- Head -->
    <circle cx="100" cy="30" r="15" class="muscle-group" data-muscle="Traps" fill="#FFD5B5"/>
    
    <!-- Traps/Neck -->
    <path d="M 60 50 Q 100 65 140 50" class="muscle-group" data-muscle="Traps" fill="#DEB887"/>
    
    <!-- Shoulders -->
    <ellipse cx="140" cy="75" rx="28" ry="25" class="muscle-group" data-muscle="Shoulders" fill="#E6C8A2"/>
    <ellipse cx="60" cy="75" rx="28" ry="25" class="muscle-group" data-muscle="Shoulders" fill="#E6C8A2"/>
    
    <!-- Lats -->
    <path d="M 70 85 Q 50 120 55 160 L 90 160 L 90 85" class="muscle-group" data-muscle="Lats" fill="#F4A460"/>
    <path d="M 130 85 L 110 160 L 145 160 Q 150 120 130 85" class="muscle-group" data-muscle="Lats" fill="#F4A460"/>
    
    <!-- Back/Rhomboids -->
    <rect x="75" y="80" width="50" height="50" class="muscle-group" data-muscle="Back" fill="#FF8C00"/>
    
    <!-- Lower Back -->
    <rect x="80" y="130" width="40" height="40" class="muscle-group" data-muscle="Lower Back" fill="#DAA520"/>
    
    <!-- Triceps (Back) -->
    <ellipse cx="50" cy="110" rx="14" ry="35" class="muscle-group" data-muscle="Triceps" fill="#CD853F"/>
    <ellipse cx="150" cy="110" rx="14" ry="35" class="muscle-group" data-muscle="Triceps" fill="#CD853F"/>
    
    <!-- Glutes -->
    <ellipse cx="70" cy="190" rx="22" ry="40" class="muscle-group" data-muscle="Glutes" fill="#B8860B"/>
    <ellipse cx="130" cy="190" rx="22" ry="40" class="muscle-group" data-muscle="Glutes" fill="#B8860B"/>
    
    <!-- Hamstrings -->
    <ellipse cx="70" cy="260" rx="18" ry="45" class="muscle-group" data-muscle="Hamstrings" fill="#8B7355"/>
    <ellipse cx="130" cy="260" rx="18" ry="45" class="muscle-group" data-muscle="Hamstrings" fill="#8B7355"/>
    
    <!-- Calves -->
    <ellipse cx="70" cy="330" rx="14" ry="32" class="muscle-group" data-muscle="Calves" fill="#A0522D"/>
    <ellipse cx="130" cy="330" rx="14" ry="32" class="muscle-group" data-muscle="Calves" fill="#A0522D"/>
</svg>

<style>
    .muscle-map {
        border: 1px solid #ddd;
        border-radius: 8px;
        background: #f9f9f9;
        cursor: pointer;
    }
    
    .muscle-group {
        stroke: #333;
        stroke-width: 1;
        transition: all 0.2s ease;
    }
    
    .muscle-group:hover {
        filter: brightness(0.9);
        stroke-width: 2;
    }
    
    .muscle-group.selected {
        fill: #28a745 !important;
        filter: drop-shadow(0 0 4px rgba(40, 167, 69, 0.6));
        stroke-width: 2;
    }
</style>
