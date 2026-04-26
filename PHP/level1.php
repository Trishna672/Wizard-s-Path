<?php
session_start();

// Reset option
if (isset($_GET['reset'])) {
    unset($_SESSION['player'], $_SESSION['level'], $_SESSION['lives'], $_SESSION['orbs']);
    header("Location: index.php");
    exit;
}

// Ensure player exists
if (!isset($_SESSION['player'])) {
    $_SESSION['player'] = [
        'x' => 50,
        'y' => 220,
        'score' => 0,
        'sprites' => ['level' => 'assets/player_level.png']
    ];
}

// Ensure level
if (!isset($_SESSION['level']) || $_SESSION['level'] < 1)
    $_SESSION['level'] = 1;

// Lives
if (!isset($_SESSION['lives']))
    $_SESSION['lives'] = 3;

// Orbs
if (!isset($_SESSION['orbs']))
    $_SESSION['orbs'] = [false, false, false, false];

//  damage
if (isset($_GET['damage'])) {
    if ($_SESSION['lives'] > 0)
        $_SESSION['lives']--;
    echo json_encode(['lives' => $_SESSION['lives']]);
    exit;
}

//  score
if (isset($_GET['addscore'])) {
    $points = intval($_GET['addscore']);
    if ($points > 0)
        $_SESSION['player']['score'] += $points;
    echo json_encode(['score' => $_SESSION['player']['score']]);
    exit;
}

//  collect orb
if (isset($_GET['collect'])) {
    $id = intval($_GET['collect']);
    if (isset($_SESSION['orbs'][$id]) && !$_SESSION['orbs'][$id]) {
        $_SESSION['orbs'][$id] = true;
        $_SESSION['player']['score'] += 10;
    }
    echo json_encode(['score' => $_SESSION['player']['score'], 'orbs' => $_SESSION['orbs']]);
    exit;
}

// Level configuration with images
$level = [
    'id' => 1,
    'bg' => "assets/level1_bg.png",
    'obstacles' => [
        ['x' => 200, 'y' => 300, 'w' => 60, 'h' => 30, 'dx' => 0, 'img' => 'assets/obstacles.png'],
        ['x' => 350, 'y' => 270, 'w' => 60, 'h' => 30, 'dx' => 1, 'img' => 'assets/obstacles.png'],
        ['x' => 500, 'y' => 240, 'w' => 60, 'h' => 30, 'dx' => 0, 'img' => 'assets/obstacles.png'],
        ['x' => 600, 'y' => 200, 'w' => 70, 'h' => 30, 'dx' => 2, 'img' => 'assets/obstacles.png']
    ],
    'hazards' => [
        ['x' => 300, 'y' => 320, 'w' => 50, 'h' => 30, 'img' => 'assets/spikes.png'],
        ['x' => 450, 'y' => 320, 'w' => 50, 'h' => 30, 'img' => 'assets/spikes.png']
    ],
    'goal' => ['x' => 780, 'y' => 30, 'w' => 50, 'h' => 100, 'img' => 'assets/portal.png'],
    'next_battle' => 'battle1.php',
    'orbs' => [
        ['x' => 250, 'y' => 200, 'w' => 20, 'h' => 20, 'collected' => $_SESSION['orbs'][0]],
        ['x' => 400, 'y' => 180, 'w' => 20, 'h' => 20, 'collected' => $_SESSION['orbs'][1]],
        ['x' => 520, 'y' => 150, 'w' => 20, 'h' => 20, 'collected' => $_SESSION['orbs'][2]],
        ['x' => 700, 'y' => 100, 'w' => 20, 'h' => 20, 'collected' => $_SESSION['orbs'][3]]
    ]
];

// Final packaged game data
$gameData = [
    'player' => $_SESSION['player'],
    'lives' => $_SESSION['lives'],
    'level' => $level
];
?>
<!DOCTYPE html>
<html>

<head>
    <title>Level 1 — A Wizard's Path</title>
    <style>
        html,
        body {
            margin: 0;
            padding: 0;
            height: 100%;
            overflow: hidden;
        }

        canvas {
            display: block;
            width: 100vw;
            height: 100vh;
            background: url('<?= $level['bg'] ?>') no-repeat center center;
            background-size: cover;
        }

        #gameOverOverlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.8);
            color: gold;
            font-size: 40px;
            display: none;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            z-index: 100;
        }

        #gameOverOverlay button,
        #uiControls button {
            margin: 5px;
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
            border: none;
            border-radius: 8px;
            background: #222;
            color: gold;
        }

        #uiControls {
            position: fixed;
            top: 10px;
            right: 10px;
            z-index: 50;
        }
    </style>
</head>

<body>
    <canvas id="gameCanvas" aria-label="Level 1 game canvas" tabindex="0"></canvas>

    <div id="uiControls">
        <button onclick="togglePause()">Pause</button>
        <button onclick="window.location.href='?reset=1'">Restart</button>
    </div>

    <div id="gameOverOverlay">
        <div>Game Over</div>
        <div id="finalScore"></div>
        <button onclick="window.location.href='?reset=1'">Restart</button>
    </div>

    <script>
        const GAME_DATA = <?= json_encode($gameData) ?>;

        let canvas = document.getElementById("gameCanvas"), ctx = canvas.getContext("2d");
        const BASE_WIDTH = 900, BASE_HEIGHT = 360;
        let scale = 1, offsetX = 0, offsetY = 0;
        function resizeCanvas() { canvas.width = window.innerWidth; canvas.height = window.innerHeight; let scaleX = canvas.width / BASE_WIDTH, scaleY = canvas.height / BASE_HEIGHT; scale = Math.min(scaleX, scaleY); offsetX = (canvas.width - BASE_WIDTH * scale) / 2; offsetY = (canvas.height - BASE_HEIGHT * scale) / 2; }
        window.addEventListener("resize", resizeCanvas); resizeCanvas();

        let player = { x: GAME_DATA.player.x, y: GAME_DATA.player.y, width: 36, height: 54, dx: 0, dy: 0, jumping: false, score: GAME_DATA.player.score };
        let lives = GAME_DATA.lives;
        let playerSprite = new Image(); playerSprite.src = GAME_DATA.player.sprites.level;

        function loadImg(src) { let img = new Image(); img.src = src; return img; }
        let obstacles = GAME_DATA.level.obstacles.map(o => ({ ...o, img: loadImg(o.img) }));
        let hazards = GAME_DATA.level.hazards.map(h => ({ ...h, img: loadImg(h.img) }));
        let goal = { ...GAME_DATA.level.goal, img: loadImg(GAME_DATA.level.goal.img) };
        let orbs = GAME_DATA.level.orbs.map(o => ({ ...o }));

        let invulnerable = false, paused = false;
        document.addEventListener("keydown", e => {
            if (e.code === "ArrowRight") player.dx = 4;
            if (e.code === "ArrowLeft") player.dx = -4;
            if (e.code === "Space" && !player.jumping) { player.dy = -10; player.jumping = true; }
            if (e.key === "p") togglePause();
            if (e.key === "r") window.location.href = "?reset=1";
        });
        document.addEventListener("keyup", e => { if (e.code === "ArrowRight" || e.code === "ArrowLeft") player.dx = 0; });

        function togglePause() { paused = !paused; document.querySelector("#uiControls button").innerText = paused ? "Resume" : "Pause"; }

        function update() {
            if (paused) { requestAnimationFrame(update); return; }
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            ctx.save(); ctx.translate(offsetX, offsetY); ctx.scale(scale, scale);
            player.dy += 0.6; player.y += player.dy; player.x += player.dx;
            if (player.y + player.height > BASE_HEIGHT - 20) { player.y = BASE_HEIGHT - 20 - player.height; player.dy = 0; player.jumping = false; }
            if (player.y > BASE_HEIGHT) triggerDamage();

            for (let obs of obstacles) {
                if (obs.dx) { obs.x += obs.dx; if (obs.x < 100 || obs.x + obs.w > BASE_WIDTH - 50) obs.dx *= -1; }
                if (obs.img.complete) ctx.drawImage(obs.img, obs.x, obs.y, obs.w, obs.h);
                if (player.x < obs.x + obs.w && player.x + player.width > obs.x && player.y < obs.y + obs.h && player.y + player.height > obs.y) {
                    if (player.dy > 0 && player.y + player.height - player.dy <= obs.y) { player.y = obs.y - player.height; player.dy = 0; player.jumping = false; }
                    else { if (player.dx > 0) player.x = obs.x - player.width - 1; if (player.dx < 0) player.x = obs.x + obs.w + 1; }
                }
            }

            for (let hz of hazards) {
                if (hz.img.complete) ctx.drawImage(hz.img, hz.x, hz.y, hz.w, hz.h);
                if (!invulnerable && player.x < hz.x + hz.w && player.x + player.width > hz.x && player.y < hz.y + hz.h && player.y + player.height > hz.y) triggerDamage();
            }

            orbs.forEach((orb, i) => {
                if (!orb.collected) {
                    let orbImg = new Image(); orbImg.src = "assets/orb.png";
                    if (orbImg.complete) ctx.drawImage(orbImg, orb.x, orb.y, orb.w, orb.h);
                }
                if (!orb.collected && player.x < orb.x + orb.w && player.x + player.width > orb.x && player.y < orb.y + orb.h && player.y + player.height > orb.y) {
                    orb.collected = true; fetch("?collect=" + i).then(r => r.json()).then(data => player.score = data.score);
                }
            });

            if (goal.img.complete) ctx.drawImage(goal.img, goal.x, goal.y, goal.w, goal.h);
            if (player.x < goal.x + goal.w && player.x + player.width > goal.x && player.y < goal.y + goal.h && player.y + player.height > goal.y) { window.location.href = GAME_DATA.level.next_battle; return; }

            ctx.drawImage(playerSprite, player.x, player.y, player.width, player.height);

            ctx.fillStyle = "rgba(99, 17, 8, 0.58)"; ctx.fillRect(10, 10, 180, 40); ctx.fillStyle = "gold"; ctx.font = "16px Arial";
            ctx.fillText("Score: " + player.score, 20, 30); ctx.fillText("Lives: " + lives, 100, 30);

            ctx.restore(); requestAnimationFrame(update);
        }

        function triggerDamage() {
            if (invulnerable) return; invulnerable = true; setTimeout(() => invulnerable = false, 1000);
            fetch("?damage=1").then(r => r.json()).then(data => {
                lives = data.lives;
                if (lives <= 0) { paused = true; document.getElementById("finalScore").innerText = "Final Score: " + player.score; document.getElementById("gameOverOverlay").style.display = "flex"; }
                else { player.x = 50; player.y = 220; player.dy = 0; player.dx = 0; }
            });
        }

        update();
    </script>
</body>

</html>`