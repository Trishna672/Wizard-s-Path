<?php
session_start();
if (!isset($_SESSION['player'])) {
    header("Location: index.php");
    exit;
}

// --- Enemy Setup ---
$enemy = [
    "name" => "Lord Voldemort",
    "level" => 10,
    "max_hp" => 120, // buffed for the final boss
    "attack" => 18,
    "defense" => 8,
    "sprite" => "assets/voldemort.png",
    "background" => "url('assets/battle_bg3.png')"
];

if (!isset($_SESSION['enemy_hp']) || $_SESSION['current_enemy'] !== 'battle3') {
    $_SESSION['enemy_hp'] = $enemy['max_hp'];
    $_SESSION['current_enemy'] = 'battle3';
    $_SESSION['submenu'] = 'main'; // submenu state
}

$player = &$_SESSION['player'];
if (!isset($_SESSION['player_max_hp'])) {
    $_SESSION['player_max_hp'] = $player['hp'];
}
$message = "";

// --- Handle Actions ---
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST['action'] ?? '';

    // Back button
    if ($action === "back") {
        $_SESSION['submenu'] = 'main';
    }
    // Enter submenus
    elseif ($action === "attack" && $_SESSION['submenu'] === 'main') {
        $_SESSION['submenu'] = 'attack';
    } elseif ($action === "items" && $_SESSION['submenu'] === 'main') {
        $_SESSION['submenu'] = 'items';
    } elseif ($action === "potions" && $_SESSION['submenu'] === 'main') {
        $_SESSION['submenu'] = 'potions';
    }
    // Cast spells
    elseif ($_SESSION['submenu'] === 'attack') {
        if (in_array($action, ["Expelliarmus", "Stupefy", "Sectumsempra", "Avada Kedavra"])) {
            $base = ($player['magic'] * 0.5) - ($enemy['defense'] * 0.3);
            if ($action === "Expelliarmus")
                $damage = rand($base + 2, $base + 6);
            if ($action === "Stupefy")
                $damage = rand($base + 4, $base + 10);
            if ($action === "Sectumsempra")
                $damage = rand($base + 7, $base + 14);
            if ($action === "Avada Kedavra")
                $damage = rand($base + 10, $base + 20); // risky but strong

            $damage = max(1, round($damage));
            $_SESSION['enemy_hp'] -= $damage;
            $message .= "{$player['name']} casts $action and strikes {$enemy['name']} for $damage damage!<br>";
            $_SESSION['submenu'] = 'main';
        }
    }
    // Use items
    elseif ($_SESSION['submenu'] === 'items') {
        if ($action === "Chocolate Frog") {
            $heal = 10;
            $player['hp'] = min($player['hp'] + $heal, $_SESSION['player_max_hp']);
            $message .= "{$player['name']} eats a Chocolate Frog and heals $heal HP!<br>";
            $_SESSION['submenu'] = 'main';
        } elseif ($action === "Elixir") {
            $heal = 20;
            $player['hp'] = min($player['hp'] + $heal, $_SESSION['player_max_hp']);
            $message .= "{$player['name']} drinks an Elixir and restores $heal HP!<br>";
            $_SESSION['submenu'] = 'main';
        }
    }
    // Use potions
    elseif ($_SESSION['submenu'] === 'potions') {
        if ($action === "Small Potion") {
            $heal = 12;
            $player['hp'] = min($player['hp'] + $heal, $_SESSION['player_max_hp']);
            $message .= "{$player['name']} drinks a Small Potion and heals $heal HP!<br>";
            $_SESSION['submenu'] = 'main';
        } elseif ($action === "Large Potion") {
            $heal = 25;
            $player['hp'] = min($player['hp'] + $heal, $_SESSION['player_max_hp']);
            $message .= "{$player['name']} drinks a Large Potion and heals $heal HP!<br>";
            $_SESSION['submenu'] = 'main';
        }
    }
    // Run away
    elseif ($action === "run") {
        header("Location: outcome.php?outcome=lose&stage=3&reason=run");
        exit;
    }

    // --- Enemy Counterattack ---
    if ($_SESSION['enemy_hp'] > 0 && !in_array($action, ["back", "run"])) {
        $base = ($enemy['attack'] * 0.5) - ($player['defense'] * 0.3);
        $damage = rand($base + 4, $base + 10);
        $damage = max(1, round($damage));
        $player['hp'] -= $damage;
        $message .= "{$enemy['name']} retaliates with dark magic for $damage damage!<br>";
    }

    // --- Victory / Defeat ---
    if ($_SESSION['enemy_hp'] <= 0) {
        unset($_SESSION['enemy_hp'], $_SESSION['current_enemy']);
        header("Location: outcome.php?outcome=win&stage=3&final=1");
        exit;
    }
    if ($player['hp'] <= 0) {
        header("Location: outcome.php?outcome=lose&stage=3");
        exit;
    }

    $_SESSION['player'] = $player;
}

// --- Update Stats ---
$enemy['hp'] = max(0, $_SESSION['enemy_hp']);
$player['hp'] = max(0, $player['hp']);
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Final Battle — <?= htmlspecialchars($enemy['name']) ?></title>
    <link rel="stylesheet" href="CSS/battle.css">
</head>

<body style="background: <?= $enemy['background']; ?> no-repeat center center; background-size: cover;">
    <div class="battle-container">
        <!-- Enemy Box -->
        <div class="enemy-box"><?= htmlspecialchars($enemy['name']); ?> Lv<?= $enemy['level']; ?>
            <div class="hp-bar">
                <div class="hp-fill <?= ($enemy['hp'] / $enemy['max_hp']) * 100 < 30 ? 'low' : (($enemy['hp'] / $enemy['max_hp']) * 100 < 60 ? 'medium' : '') ?>"
                    style="width: <?= round(($enemy['hp'] / $enemy['max_hp']) * 100, 1) ?>%"></div>
            </div>
            <?= $enemy['hp']; ?>/<?= $enemy['max_hp']; ?> HP
        </div>

        <!-- Player Box -->
        <div class="player-box"><?= htmlspecialchars($player['name']); ?>
            <?php $hp_pct = ($player['hp'] / $_SESSION['player_max_hp']) * 100; ?>
            <div class="hp-bar">
                <div class="hp-fill <?= $hp_pct < 30 ? 'low' : ($hp_pct < 60 ? 'medium' : '') ?>"
                    style="width: <?= round($hp_pct, 1) ?>%"></div>
            </div>
            <?= htmlspecialchars($player['hp']); ?>/<?= $_SESSION['player_max_hp']; ?> HP
        </div>

        <!-- Battle Graphics -->
        <div id="game-container">
            <img id="character"
                src="<?= htmlspecialchars($_SESSION['player']['sprites']['battle'] ?? $_SESSION['player']['sprites']['display'] ?? 'assets/player_battle.png') ?>"
                alt="Player">
            <img id="enemy" src="<?= htmlspecialchars($enemy['sprite']) ?>" alt="Enemy">
        </div>

        <!-- Battle Log -->
        <div class="battle-log"><?= $message ?: "Lord Voldemort emerges. The final duel begins..."; ?></div>

        <!-- Battle Menu -->
        <?php if ($player['hp'] > 0 && $enemy['hp'] > 0): ?>
            <form method="post">
                <?php if ($_SESSION['submenu'] === 'main'): ?>
                    <button name="action" value="attack">CAST SPELLS</button>
                    <button name="action" value="items">ITEMS</button>
                    <button name="action" value="potions">POTIONS</button>
                    <button name="action" value="run">RUN</button>
                <?php elseif ($_SESSION['submenu'] === 'attack'): ?>
                    <button name="action" value="Expelliarmus">Expelliarmus</button>
                    <button name="action" value="Stupefy">Stupefy</button>
                    <button name="action" value="Sectumsempra">Sectumsempra</button>
                    <button name="action" value="Avada Kedavra">Avada Kedavra</button>
                    <button name="action" value="back">Back</button>
                <?php elseif ($_SESSION['submenu'] === 'items'): ?>
                    <button name="action" value="Chocolate Frog">Chocolate Frog</button>
                    <button name="action" value="Elixir">Elixir</button>
                    <button name="action" value="back">Back</button>
                <?php elseif ($_SESSION['submenu'] === 'potions'): ?>
                    <button name="action" value="Small Potion">Small Potion</button>
                    <button name="action" value="Large Potion">Large Potion</button>
                    <button name="action" value="back">Back</button>
                <?php endif; ?>
            </form>
        <?php endif; ?>
    </div>
</body>

</html>