<?php
session_start();

$outcome = $_GET['outcome'] ?? 'unknown';
$stage   = intval($_GET['stage'] ?? 0);
$reason  = $_GET['reason'] ?? '';
$final   = intval($_GET['final'] ?? 0); // 1 if final game end

// Only destroy session if game is truly over (final win or any lose)
if (($outcome === 'win' && $final === 1) || ($outcome === 'lose')) {
    // destroy and then regenerate to be safe
    session_destroy();
    session_start();
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Battle Result</title>
    <link rel="stylesheet" href="CSS/index.css">
    <style>
        /* trimmed for brevity — keep your original styling here */
        .result { display:flex; flex-direction:column; align-items:center; justify-content:center; height:100vh; background: url('assets/bg.png') no-repeat center center; background-size: cover; text-align:center; color:#fff;}
        .result h1 { font-size:48px; margin-bottom:20px;}
        .result p { font-size:20px; max-width:600px;}
        .result a, .result form button { margin-top:20px; padding:10px 20px; background:#444; color:white; border-radius:10px; text-decoration:none; font-weight:bold; border:none; cursor:pointer;}
        .result a:hover, .result form button:hover { background:#666; }
    </style>
</head>
<body>
    <div class="result">
        <?php if ($outcome === 'win' && $final === 1): ?>
            <h1>🏆 Legendary Victory!</h1>
            <p>You have defeated Voldemort and brought peace to Hogwarts!</p>

        <?php elseif ($outcome === 'win'): ?>
            <h1>✅ Stage Cleared!</h1>
            <p>You defeated your foe and move on to the next challenge...</p>

        <?php elseif ($outcome === 'lose' && $reason === 'run'): ?>
            <h1>🏳 You Escaped!</h1>
            <p>You fled the battle at stage <?= htmlspecialchars($stage); ?>. Sometimes survival is the better part of valor.</p>

        <?php elseif ($outcome === 'lose' && $stage == 1): ?>
            <h1>💀 Defeated by a Death Eater</h1>
            <p>The dark forces proved too strong this time. Regain your courage and try again!</p>

        <?php elseif ($outcome === 'lose' && $stage == 2): ?>
            <h1>💀 Bellatrix Strikes You Down</h1>
            <p>Her curses were too quick to dodge. Perhaps next time you'll be ready.</p>

        <?php elseif ($outcome === 'lose' && $stage == 3): ?>
            <h1>💀 Voldemort Prevails</h1>
            <p>You fought bravely, but the Dark Lord's power was overwhelming.</p>

        <?php else: ?>
            <h1>⚠️ Unknown Outcome</h1>
            <p>Something went wrong.</p>
        <?php endif; ?>

        <?php if ($outcome === 'win' && $final === 0): ?>
            <!-- Continue to next level instead of battle -->
            <form method="get" action="level<?= max(1, $stage + 1); ?>.php">
                <button type="submit">Next Level</button>
            </form>
        <?php else: ?>
            <!-- Restart game -->
            <a href="index.php">Play Again</a>
        <?php endif; ?>
    </div>
</body>
</html>
