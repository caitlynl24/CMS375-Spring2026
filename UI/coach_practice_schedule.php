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

// Determine selected week start (Monday). Falls back to current week.
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

$practiceStmt = $conn->prepare(
    "SELECT p.practice_id, p.athlete_id, a.full_name AS athlete_name, p.title, p.start_time, p.end_time, p.location, p.notes
     FROM practice_schedule p
     INNER JOIN athletes a ON p.athlete_id = a.athlete_id
     WHERE p.start_time >= ? AND p.start_time < ?
     ORDER BY p.start_time ASC"
);

if (!$practiceStmt) {
    die("Unable to load practice schedule.");
}

$practiceStmt->bind_param("ss", $weekStartSql, $nextWeekStartSql);
$practiceStmt->execute();
$practices = $practiceStmt->get_result();

$weekDays = [];
for ($i = 0; $i < 7; $i++) {
    $day = (clone $weekStart)->modify("+$i days");
    $dayKey = $day->format('Y-m-d');
    $weekDays[$dayKey] = [
        'label' => $day->format('l, M j'),
        'practices' => []
    ];
}

while ($practice = $practices->fetch_assoc()) {
    $dayKey = date('Y-m-d', strtotime($practice['start_time']));
    if (isset($weekDays[$dayKey])) {
        $weekDays[$dayKey]['practices'][] = $practice;
    }
}

$weekStartParam = $weekStart->format('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Coach Practice Schedule</title>
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

        .practice-item {
            border-top: 1px solid #efefef;
            padding: 8px 0;
        }

        .practice-title {
            margin: 0 0 4px 0;
            font-size: 15px;
            font-weight: 700;
            color: #1f2d3d;
        }

        .practice-meta {
            margin: 2px 0;
            font-size: 13px;
            color: #4b5563;
            line-height: 1.35;
        }

        .practice-actions {
            display: flex;
            gap: 8px;
            margin-top: 8px;
            align-items: center;
            flex-wrap: wrap;
        }

        .practice-item:first-of-type {
            border-top: none;
            padding-top: 0;
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
        <h1>Coach Practice Schedule</h1>
        <div class="user">
            <?php echo htmlspecialchars($_SESSION['name']); ?> |
            <a href="coach_dashboard.php?tab=schedule">Dashboard</a> |
            <a href="coach_practice_create.php">Add Practice</a> |
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="card">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap;">
            <a href="coach_practice_schedule.php?week_start=<?php echo urlencode($previousWeekStart->format('Y-m-d')); ?>">Previous Week</a>
            <p style="margin:0;"><strong><?php echo htmlspecialchars($weekStart->format('M j, Y')); ?> - <?php echo htmlspecialchars((clone $weekStart)->modify('+6 days')->format('M j, Y')); ?></strong></p>
            <a href="coach_practice_schedule.php?week_start=<?php echo urlencode($nextWeekStart->format('Y-m-d')); ?>">Next Week</a>
        </div>
    </div>

    <div class="week-grid">
        <?php $todayKey = (new DateTime())->format('Y-m-d'); ?>
        <?php foreach ($weekDays as $dayKey => $day): ?>
            <div class="day-column <?php echo ($dayKey === $todayKey) ? 'today' : ''; ?>">
                <h3 class="day-header"><?php echo htmlspecialchars($day['label']); ?></h3>

                <?php if (empty($day['practices'])): ?>
                    <p class="practice-meta">No practices scheduled.</p>
                <?php else: ?>
                    <?php foreach ($day['practices'] as $practice): ?>
                        <div class="practice-item">
                            <p class="practice-title"><?php echo htmlspecialchars($practice['title']); ?></p>
                            <p class="practice-meta"><strong>Athlete:</strong> <?php echo htmlspecialchars($practice['athlete_name']); ?></p>
                            <p class="practice-meta">
                                <strong>Time:</strong>
                                <?php echo htmlspecialchars(date('g:i A', strtotime($practice['start_time']))); ?>
                                <?php if (!empty($practice['end_time'])): ?>
                                    - <?php echo htmlspecialchars(date('g:i A', strtotime($practice['end_time']))); ?>
                                <?php endif; ?>
                            </p>
                            <p class="practice-meta"><strong>Location:</strong> <?php echo htmlspecialchars($practice['location'] ?? ''); ?></p>
                            <p class="practice-meta"><strong>Notes:</strong> <?php echo nl2br(htmlspecialchars($practice['notes'] ?? '')); ?></p>
                            <div class="practice-actions">
                                <a href="coach_practice_edit.php?practice_id=<?php echo urlencode((string)$practice['practice_id']); ?>&week_start=<?php echo urlencode($weekStartParam); ?>">
                                    <button>Edit</button>
                                </a>
                                <form method="POST" action="coach_practice_delete.php" onsubmit="return confirm('Delete this practice? This cannot be undone.');" style="margin:0;">
                                    <input type="hidden" name="practice_id" value="<?php echo htmlspecialchars((string)$practice['practice_id']); ?>">
                                    <input type="hidden" name="athlete_id" value="<?php echo htmlspecialchars((string)$practice['athlete_id']); ?>">
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
