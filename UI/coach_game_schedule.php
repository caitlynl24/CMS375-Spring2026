<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$role = isset($_SESSION['role']) ? strtolower(trim((string)$_SESSION['role'])) : '';
if ($role !== 'coach') {
    header("Location: index.php");
    exit();
}

require 'db.php';

// Determine selected week start
$weekStartInput = $_GET['week_start'] ?? '';
$weekStartDate = DateTime::createFromFormat('Y-m-d', $weekStartInput);

if ($weekStartDate && $weekStartDate->format('Y-m-d') === $weekStartInput) {
    $weekStart = clone $weekStartDate;
    if ($weekStart->format('N') !== '1') {
        $weekStart->modify('monday this week');
    }
} else {
    $weekStart = new DateTime('monday this week');
}

$weekStart->setTime(0, 0, 0);
$nextWeekStart = (clone $weekStart)->modify('+7 days');
$previousWeekStart = (clone $weekStart)->modify('-7 days');

$weekStartSql = $weekStart->format('Y-m-d H:i:s');
$nextWeekStartSql = $nextWeekStart->format('Y-m-d H:i:s');

$gamesStmt = $conn->prepare(
    "SELECT g.game_id, g.athlete_id, a.full_name AS athlete_name, g.opponent, g.game_datetime, g.location, g.notes
     FROM game_schedule g
     INNER JOIN athletes a ON g.athlete_id = a.athlete_id
     WHERE g.game_datetime >= ? AND g.game_datetime < ?
     ORDER BY g.game_datetime ASC"
);

if (!$gamesStmt) {
    die("Unable to load game schedule.");
}

$gamesStmt->bind_param("ss", $weekStartSql, $nextWeekStartSql);
$gamesStmt->execute();
$gamesResult = $gamesStmt->get_result();

$weekDays = [];
for ($i = 0; $i < 7; $i++) {
    $day = (clone $weekStart)->modify("+$i days");
    $dayKey = $day->format('Y-m-d');
    $weekDays[$dayKey] = [
        'label' => $day->format('l, M j'),
        'games' => []
    ];
}

while ($game = $gamesResult->fetch_assoc()) {
    $dayKey = date('Y-m-d', strtotime($game['game_datetime']));
    if (isset($weekDays[$dayKey])) {
        $weekDays[$dayKey]['games'][] = $game;
    }
}

$weekStartParam = $weekStart->format('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Coach Game Schedule</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        .week-grid {
            display: grid;
            grid-template-columns: repeat(7, minmax(180px, 1fr));
            gap: 12px;
            margin-top: 20px;
        }

        .day-column {
            background: white;
            border-radius: 12px;
            padding: 14px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .day-column.today {
            border: 2px solid #c99700;
            background: #fffdf4;
        }

        .day-header {
            margin: 0 0 12px 0;
            padding-bottom: 10px;
            border-bottom: 1px solid #e8e8e8;
            font-size: 16px;
        }

        .game-item {
            border-top: 1px solid #efefef;
            padding: 8px 0;
        }

        .game-item:first-of-type {
            border-top: none;
            padding-top: 0;
        }

        .game-title {
            margin: 0 0 4px 0;
            font-size: 15px;
            font-weight: 700;
            color: #1f2d3d;
        }

        .game-meta {
            margin: 2px 0;
            font-size: 13px;
            color: #4b5563;
            line-height: 1.35;
        }

        .game-actions {
            display: flex;
            gap: 8px;
            margin-top: 8px;
            align-items: center;
            flex-wrap: wrap;
        }

        @media (max-width: 1200px) {
            .week-grid {
                grid-template-columns: repeat(3, minmax(220px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .week-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<div class="content">
    <div class="topbar" style="border-radius: 12px;">
        <h1>Coach Game Schedule</h1>
        <div class="user">
            <?php echo htmlspecialchars($_SESSION['name']); ?> |
            <a href="coach_dashboard.php?tab=schedule">Dashboard</a> |
            <a href="coach_game_create.php">Add Game</a> |
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="card">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap;">
            <a href="coach_game_schedule.php?week_start=<?php echo urlencode($previousWeekStart->format('Y-m-d')); ?>">Previous Week</a>
            <p style="margin:0;"><strong><?php echo htmlspecialchars($weekStart->format('M j, Y')); ?> - <?php echo htmlspecialchars((clone $weekStart)->modify('+6 days')->format('M j, Y')); ?></strong></p>
            <a href="coach_game_schedule.php?week_start=<?php echo urlencode($nextWeekStart->format('Y-m-d')); ?>">Next Week</a>
        </div>
    </div>

    <div class="week-grid">
        <?php $todayKey = (new DateTime())->format('Y-m-d'); ?>
        <?php foreach ($weekDays as $dayKey => $day): ?>
            <div class="day-column <?php echo ($dayKey === $todayKey) ? 'today' : ''; ?>">
                <h3 class="day-header"><?php echo htmlspecialchars($day['label']); ?></h3>

                <?php if (empty($day['games'])): ?>
                    <p class="game-meta">No games scheduled.</p>
                <?php else: ?>
                    <?php foreach ($day['games'] as $game): ?>
                        <div class="game-item">
                            <p class="game-title"><?php echo htmlspecialchars($game['opponent']); ?></p>
                            <p class="game-meta"><strong>Athlete:</strong> <?php echo htmlspecialchars($game['athlete_name']); ?></p>
                            <p class="game-meta">
                                <strong>Time:</strong>
                                <?php echo htmlspecialchars(date('g:i A', strtotime($game['game_datetime']))); ?>
                            </p>
                            <p class="game-meta"><strong>Location:</strong> <?php echo htmlspecialchars($game['location']); ?></p>
                            <?php if (!empty($game['notes'])): ?>
                                <p class="game-meta"><strong>Notes:</strong> <?php echo nl2br(htmlspecialchars($game['notes'])); ?></p>
                            <?php endif; ?>
                            <div class="game-actions">
                                <a href="coach_game_edit.php?game_id=<?php echo urlencode((string)$game['game_id']); ?>&week_start=<?php echo urlencode($weekStartParam); ?>">
                                    <button>Edit</button>
                                </a>
                                <form method="POST" action="coach_game_delete.php" onsubmit="return confirm('Delete this game? This cannot be undone.');" style="margin:0;">
                                    <input type="hidden" name="game_id" value="<?php echo htmlspecialchars((string)$game['game_id']); ?>">
                                    <input type="hidden" name="athlete_id" value="<?php echo htmlspecialchars((string)$game['athlete_id']); ?>">
                                    <input type="hidden" name="week_start" value="<?php echo htmlspecialchars($weekStartParam); ?>">
                                    <button type="submit">Delete</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

</body>
</html>
