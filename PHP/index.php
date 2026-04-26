<?php
session_start();

// Single source of truth for characters
$characters = [
    'Harry Potter' => [
        'display' => 'assets/harry.png',
        'level_sprite' => 'assets/harry_level.png',
        'battle_sprite' => 'assets/harry_sprite.png',
        'hp' => 100,
        'magic' => 85,
        'defense' => 70,
        'special' => 'Expecto Patronum'
    ],
    'Hermione Granger' => [
        'display' => 'assets/hermione.png',
        'level_sprite' => 'assets/hermione_level.png',
        'battle_sprite' => 'assets/hermione_sprite.png',
        'hp' => 85,
        'magic' => 100,
        'defense' => 65,
        'special' => 'Wingardium Leviosa'
    ],
    'Ron Weasley' => [
        'display' => 'assets/ron.png',
        'level_sprite' => 'assets/ron_level.png',
        'battle_sprite' => 'assets/ron_sprite.png',
        'hp' => 95,
        'magic' => 75,
        'defense' => 80,
        'special' => "Wizard's Chess"
    ]
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $character = $_POST['character'] ?? '';

    // Validate selection
    if (!array_key_exists($character, $characters)) {
        // invalid selection — redirect back to index with safe fallback
        header("Location: index.php");
        exit;
    }

    $c = $characters[$character];

    // Store all sprites + stats in session in a consistent schema
    $_SESSION['player'] = [
        'name' => $character,
        'hp' => $c['hp'],
        'magic' => $c['magic'],
        'defense' => $c['defense'],
        'special' => $c['special'],
        'xp' => 0,
        'score' => 0,
        'inventory' => ['Potion'], // single potion by default
        'sprites' => [
            'display' => $c['display'],
            'level' => $c['level_sprite'],
            'battle' => $c['battle_sprite']
        ],
        // platformer position defaults
        'x' => 50,
        'y' => 220
    ];

    // Level tracking
    $_SESSION['level'] = 1;
    $_SESSION['enemy_hp'] = null; // reset any enemy hp
    $_SESSION['player_max_hp'] = $c['hp'];

    header("Location: level1.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>A Wizard's Path — Choose Character</title>
    <link rel="stylesheet" href="CSS/index.css">
    <link href="https://fonts.googleapis.com/css2?family=Magic+School+One&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h1 class="glow">A Wizard's Path</h1>
        <h2>Choose Your Character</h2>

        <form method="post" class="character-select" aria-label="Choose character">
            <div class="card-row">
                <?php foreach ($characters as $name => $data): ?>
                <label class="character-card" tabindex="0">
                    <input type="radio" name="character" value="<?= htmlspecialchars($name) ?>" required hidden>
                    <img src="<?= htmlspecialchars($data['display']) ?>" alt="<?= htmlspecialchars($name) ?>">
                    <h3><?= htmlspecialchars($name) ?></h3>
                    <ul>
                        <li><strong>Health:</strong> <?= $data['hp'] ?></li>
                        <li><strong>Magic:</strong> <?= $data['magic'] ?></li>
                        <li><strong>Defense:</strong> <?= $data['defense'] ?></li>
                    </ul>
                    <p class="special"><span><?= htmlspecialchars($data['special']) ?></span></p>
                </label>
                <?php endforeach; ?>
            </div>
            <div class="button-row">
                <button type="submit" class="start-btn">Begin Your Adventure</button>
            </div>
        </form>
    </div>
</body>
</html>
