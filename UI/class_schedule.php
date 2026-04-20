<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require 'db.php';

$userId = $_SESSION['user_id'];

$athleteStmt = $conn->prepare("SELECT athlete_id FROM athletes WHERE user_id = ?");
if (!$athleteStmt) {
    die("Unable to load athlete profile.");
}

$athleteStmt->bind_param("i", $userId);
$athleteStmt->execute();
$athlete = $athleteStmt->get_result()->fetch_assoc();

if (!$athlete) {
    header("Location: athlete_create.php");
    exit();
}

$athleteId = (int)$athlete['athlete_id'];

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

$classStmt = $conn->prepare(
    "SELECT class_id, course_name, day_of_week, start_time, end_time, location, notes
     FROM class_schedule
     WHERE athlete_id = ?
     ORDER BY day_of_week ASC, start_time ASC"
);

if (!$classStmt) {
    die("Unable to load class schedule.");
}

$classStmt->bind_param("i", $athleteId);
$classStmt->execute();
$classesResult = $classStmt->get_result();

$classesByDow = [];
for ($d = 1; $d <= 7; $d++) {
    $classesByDow[$d] = [];
}

while ($row = $classesResult->fetch_assoc()) {
    $dow = (int)$row['day_of_week'];
    if ($dow >= 1 && $dow <= 7) {
        $classesByDow[$dow][] = $row;
    }
}

$weekDays = [];
for ($i = 0; $i < 7; $i++) {
    $day = (clone $weekStart)->modify("+$i days");
    $dayKey = $day->format('Y-m-d');
    $dow = (int)$day->format('N');
    $weekDays[$dayKey] = [
        'label' => $day->format('l, M j'),
        'dow' => $dow,
        'classes' => $classesByDow[$dow],
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Class Schedule</title>
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

        .class-item {
            border-top: 1px solid #efefef;
            padding: 8px 0;
        }

        .class-item:first-of-type {
            border-top: none;
            padding-top: 0;
        }

        .class-title {
            margin: 0 0 4px 0;
            font-size: 15px;
            font-weight: 700;
            color: #1f2d3d;
        }

        .class-meta {
            margin: 2px 0;
            font-size: 13px;
            color: #4b5563;
            line-height: 1.35;
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
        <h1>Class Schedule</h1>
        <div class="user">
            <?php echo htmlspecialchars($_SESSION['name']); ?> |
            <a href="index.php?tab=schedule">Dashboard</a> |
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="card">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap;">
            <a href="class_schedule.php?week_start=<?php echo urlencode($previousWeekStart->format('Y-m-d')); ?>">Previous Week</a>
            <p style="margin:0;"><strong><?php echo htmlspecialchars($weekStart->format('M j, Y')); ?> - <?php echo htmlspecialchars((clone $weekStart)->modify('+6 days')->format('M j, Y')); ?></strong></p>
            <a href="class_schedule.php?week_start=<?php echo urlencode($nextWeekStart->format('Y-m-d')); ?>">Next Week</a>
        </div>
        <p style="margin:12px 0 0 0;color:#4b5563;font-size:13px;">Weekly recurring schedule (same classes each week).</p>
    </div>

    <div class="week-grid">
        <?php $todayKey = (new DateTime())->format('Y-m-d'); ?>
        <?php foreach ($weekDays as $dayKey => $day): ?>
            <div class="day-column <?php echo ($dayKey === $todayKey) ? 'today' : ''; ?>">
                <h3 class="day-header"><?php echo htmlspecialchars($day['label']); ?></h3>

                <?php if (empty($day['classes'])): ?>
                    <p class="class-meta">No classes scheduled.</p>
                <?php else: ?>
                    <?php foreach ($day['classes'] as $class): ?>
                        <div class="class-item">
                            <p class="class-title"><?php echo htmlspecialchars($class['course_name']); ?></p>
                            <p class="class-meta">
                                <strong>Time:</strong>
                                <?php echo htmlspecialchars(date('g:i A', strtotime('2000-01-01 ' . $class['start_time']))); ?>
                                –
                                <?php echo htmlspecialchars(date('g:i A', strtotime('2000-01-01 ' . $class['end_time']))); ?>
                            </p>
                            <?php if (!empty($class['location'])): ?>
                                <p class="class-meta"><strong>Location:</strong> <?php echo htmlspecialchars($class['location']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($class['notes'])): ?>
                                <p class="class-meta"><strong>Notes:</strong> <?php echo nl2br(htmlspecialchars($class['notes'])); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

</body>
</html>
