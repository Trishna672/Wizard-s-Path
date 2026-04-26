<?php
session_start();

// ensure player exists
if (!isset($_SESSION['player'])) {
    header("Location: index.php");
    exit;
}

$enemy = [
    "name" => "Death Eater",
    "level" => 4,
    "max_hp" => 80, // buffed from 40 to 80
    "attack" => 10,
    "defense" => 4,
    "sprite" => "assets/deatheater.png",
    "background" => "url('assets/battle_bg.png')"
];

// initialize enemy hp per battle stage (don't override if already fighting)
if (!isset($_SESSION['enemy_hp']) || $_SESSION['current_enemy'] !== 'battle1') {
    $_SESSION['enemy_hp'] = $enemy['max_hp'];
    $_SESSION['current_enemy'] = 'battle1';
    $_SESSION['submenu'] = 'main'; // track menus
}

$player = &$_SESSION['player'];
if (!isset($_SESSION['player_max_hp'])) {
    $_SESSION['player_max_hp'] = $player['hp'];
}

$message = "";

// handle POST actions
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST['action'] ?? '';

    // Back button resets to main menu
    if ($action === "back") {
        $_SESSION['submenu'] = 'main';
    }

    // MAIN MENU
    elseif ($_SESSION['submenu'] === 'main') {
        if ($action === "attack") {
            $_SESSION['submenu'] = 'attack';
        } elseif ($action === "items") {
            $_SESSION['submenu'] = 'items';
        } elseif ($action === "potions") {
            $_SESSION['submenu'] = 'potions';
        } elseif ($action === "run") {
            header("Location: outcome.php?outcome=lose&stage=1&reason=run");
            exit;
        }
    }

    // ATTACK SUBMENU
    elseif ($_SESSION['submenu'] === 'attack') {
        if (in_array($action, ["Expelliarmus", "Stupefy", "Sectumsempra"])) {
            // --- NEW DAMAGE LOGIC ---
            $base = ($player['magic'] * 0.4) - ($enemy['defense'] * 0.3);
            if ($action === "Expelliarmus")
                $damage = rand($base + 2, $base + 6);
            if ($action === "Stupefy")
                $damage = rand($base + 4, $base + 10);
            if ($action === "Sectumsempra")
                $damage = rand($base + 7, $base + 14);

            $damage = max(1, round($damage)); // always at least 1
            $_SESSION['enemy_hp'] -= $damage;
            $message .= "{$player['name']} casts $action and hits {$enemy['name']} for $damage damage!<br>";
            $_SESSION['submenu'] = 'main';
        }
    }

    // ITEMS SUBMENU
    elseif ($_SESSION['submenu'] === 'items') {
        if (!empty($player['inventory']) && in_array($action, $player['inventory'])) {
            $message .= "{$player['name']} uses {$action}!<br>";
            // Example: wand increases magic, chocolate heals
            if ($action === "Wand") {
                $player['magic'] += 2;
                $message .= "Magic power increased!<br>";
            }
            if ($action === "Chocolate Frog") {
                $heal = 5;
                $player['hp'] = min($player['hp'] + $heal, $_SESSION['player_max_hp']);
                $message .= "Healed $heal HP!<br>";
            }
            $i = array_search($action, $player['inventory']);
            if ($i !== false)
                array_splice($player['inventory'], $i, 1);
            $_SESSION['submenu'] = 'main';
        }
    }

    // POTIONS SUBMENU
    elseif ($_SESSION['submenu'] === 'potions') {
        if (in_array($action, ["Healing Potion", "Elixir"])) {
            if ($action === "Healing Potion") {
                $heal = 10;
                $player['hp'] = min($player['hp'] + $heal, $_SESSION['player_max_hp']);
                $message .= "{$player['name']} drinks Healing Potion and heals for $heal HP!<br>";
            }
            if ($action === "Elixir") {
                $player['magic'] += 5;
                $message .= "{$player['name']} drinks Elixir and boosts magic!<br>";
            }
            $_SESSION['submenu'] = 'main';
        }
    }

    // Enemy counterattack if alive and not just pressing back/run
    if ($_SESSION['enemy_hp'] > 0 && !in_array($action, ["back", "run"])) {
        // --- NEW DAMAGE LOGIC ---
        $base = ($enemy['attack'] * 0.5) - ($player['defense'] * 0.3);
        $damage = rand($base + 2, $base + 6);
        $damage = max(1, round($damage));

        $player['hp'] -= $damage;
        $message .= "{$enemy['name']} strikes back for $damage damage!<br>";
    }

    // Check win/lose
    if ($_SESSION['enemy_hp'] <= 0) {
        unset($_SESSION['enemy_hp']);
        unset($_SESSION['current_enemy']);
        $_SESSION['submenu'] = 'main';
        $_SESSION['level'] = max(1, ($_SESSION['level'] ?? 1));
        header("Location: outcome.php?outcome=win&stage=1&next=battle2.php");
        exit;
    }
    if ($player['hp'] <= 0) {
        header("Location: outcome.php?outcome=lose&stage=1");
        exit;
    }

    $_SESSION['player'] = $player;
}

$enemy['hp'] = max(0, $_SESSION['enemy_hp']);
$player['hp'] = max(0, $player['hp']);
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Battle 1 — <?= htmlspecialchars($enemy['name']) ?></title>
    <link rel="stylesheet" href="CSS/battle.css">
</head>

<body style="background: <?= $enemy['background']; ?> no-repeat center center; background-size: cover;">
    <div class="battle-container">
        <div class="enemy-box">
            <?= htmlspecialchars($enemy['name']); ?> Lv<?= $enemy['level']; ?>
            <div class="hp-bar">
                <div class="hp-fill <?= ($_SESSION['enemy_hp'] / $enemy['max_hp']) * 100 < 30 ? 'low' : (($_SESSION['enemy_hp'] / $enemy['max_hp']) * 100 < 60 ? 'medium' : '') ?>"
                    style="width: <?= round(($_SESSION['enemy_hp'] / $enemy['max_hp']) * 100, 1) ?>%"></div>
            </div>
            <?= $_SESSION['enemy_hp']; ?>/<?= $enemy['max_hp']; ?> HP
        </div>

        <div class="player-box">
            <?= htmlspecialchars($player['name']); ?>
            <?php $hp_pct = ($_SESSION['player']['hp'] / $_SESSION['player_max_hp']) * 100; ?>
            <div class="hp-bar">
                <div class="hp-fill <?= $hp_pct < 30 ? 'low' : ($hp_pct < 60 ? 'medium' : '') ?>"
                    style="width: <?= round($hp_pct, 1) ?>%"></div>
            </div>
            <?= htmlspecialchars($player['hp']); ?>/<?= $_SESSION['player_max_hp']; ?> HP
        </div>

        <div id="game-container">
            <img id="character"
                src="<?= htmlspecialchars($_SESSION['player']['sprites']['battle'] ?? $_SESSION['player']['sprites']['display'] ?? 'assets/player_battle.png') ?>"
                alt="Player">
            <img id="enemy" src="<?= htmlspecialchars($enemy['sprite']) ?>" alt="Enemy">
        </div>

        <div class="battle-log"><?= $message ?: "{$enemy['name']} appears! What will you do?"; ?></div>

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
                    <button name="action" value="back">⬅ Back</button>

                <?php elseif ($_SESSION['submenu'] === 'items'): ?>
                    <?php if (!empty($player['inventory'])): ?>
                        <?php foreach ($player['inventory'] as $it): ?>
                            <button name="action" value="<?= htmlspecialchars($it) ?>"><?= htmlspecialchars($it) ?></button>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No items available!</p>
                    <?php endif; ?>
                    <button name="action" value="back">⬅ Back</button>

                <?php elseif ($_SESSION['submenu'] === 'potions'): ?>
                    <button name="action" value="Healing Potion">Healing Potion</button>
                    <button name="action" value="Elixir">Elixir</button>
                    <button name="action" value="back">⬅ Back</button>
                <?php endif; ?>
            </form>
        <?php endif; ?>
    </div>
</body>

</html>