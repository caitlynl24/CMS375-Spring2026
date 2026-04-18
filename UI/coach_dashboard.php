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

$activeTab = $_GET['tab'] ?? 'schedule';
$allowedTabs = ['profile', 'schedule', 'communication', 'medical'];
if (!in_array($activeTab, $allowedTabs, true)) {
    $activeTab = 'schedule';
}

require 'db.php';

$coachUserId = (int)$_SESSION['user_id'];
$coachProfileRow = null;

$coachProfileStmt = $conn->prepare(
    "SELECT u.name, u.email, c.sport, c.title
     FROM users u
     LEFT JOIN coaches c ON c.user_id = u.user_id
     WHERE u.user_id = ?
     LIMIT 1"
);

if ($coachProfileStmt) {
    $coachProfileStmt->bind_param("i", $coachUserId);
    $coachProfileStmt->execute();
    $coachProfileRow = $coachProfileStmt->get_result()->fetch_assoc();
}

$upcomingPractices = [];
$upcomingGames = [];

$practicePreviewStmt = $conn->prepare(
    "SELECT p.title, p.start_time, p.end_time, p.location, a.full_name AS athlete_name
     FROM practice_schedule p
     INNER JOIN athletes a ON p.athlete_id = a.athlete_id
     WHERE p.start_time >= NOW()
     ORDER BY p.start_time ASC
     LIMIT 1"
);

if ($practicePreviewStmt) {
    $practicePreviewStmt->execute();
    $practicePreviewResult = $practicePreviewStmt->get_result();

    while ($row = $practicePreviewResult->fetch_assoc()) {
        $upcomingPractices[] = $row;
    }
}

$gamesPreviewStmt = $conn->prepare(
    "SELECT g.opponent, g.game_datetime, g.location, g.notes, a.full_name AS athlete_name
     FROM game_schedule g
     INNER JOIN athletes a ON g.athlete_id = a.athlete_id
     WHERE g.game_datetime >= NOW()
     ORDER BY g.game_datetime ASC
     LIMIT 3"
);

if ($gamesPreviewStmt) {
    $gamesPreviewStmt->execute();
    $gamesPreviewResult = $gamesPreviewStmt->get_result();

    while ($row = $gamesPreviewResult->fetch_assoc()) {
        $upcomingGames[] = $row;
    }
}

$coachMedicalRecords = [];

$coachMedicalStmt = $conn->prepare(
    "SELECT m.injury_title, m.injury_details, m.reported_date, m.status, m.clearance_status,
            m.expected_return_date, m.cleared_date, m.notes, a.full_name AS athlete_name
     FROM medical_records m
     INNER JOIN athletes a ON m.athlete_id = a.athlete_id
     ORDER BY m.reported_date DESC"
);

if ($coachMedicalStmt) {
    $coachMedicalStmt->execute();
    $coachMedicalResult = $coachMedicalStmt->get_result();

    while ($row = $coachMedicalResult->fetch_assoc()) {
        $coachMedicalRecords[] = $row;
    }
}

$coachAthletes = [];
$teamAnnouncementsCoach = [];
$coachDirectMessages = [];
$selectedAthleteId = isset($_GET['athlete_id']) ? (int)$_GET['athlete_id'] : 0;
$selectedAthleteUserId = null;

$coachAthletesStmt = $conn->prepare("SELECT athlete_id, full_name FROM athletes ORDER BY full_name ASC");
if ($coachAthletesStmt) {
    $coachAthletesStmt->execute();
    $coachAthletesResult = $coachAthletesStmt->get_result();
    while ($row = $coachAthletesResult->fetch_assoc()) {
        $coachAthletes[] = $row;
    }
}

$coachAnnStmt = $conn->prepare(
    "SELECT u.name AS sender_name, m.content, m.sent_at
     FROM messages m
     INNER JOIN users u ON m.sender_user_id = u.user_id
     WHERE m.message_type = 'announcement' AND m.recipient_group = 'team'
     ORDER BY m.sent_at DESC"
);

if ($coachAnnStmt) {
    $coachAnnStmt->execute();
    $coachAnnResult = $coachAnnStmt->get_result();
    while ($row = $coachAnnResult->fetch_assoc()) {
        $teamAnnouncementsCoach[] = $row;
    }
}

if ($selectedAthleteId > 0) {
    $selUserStmt = $conn->prepare("SELECT user_id FROM athletes WHERE athlete_id = ? LIMIT 1");
    if ($selUserStmt) {
        $selUserStmt->bind_param("i", $selectedAthleteId);
        $selUserStmt->execute();
        $selUserRow = $selUserStmt->get_result()->fetch_assoc();
        if ($selUserRow && !empty($selUserRow['user_id'])) {
            $selectedAthleteUserId = (int)$selUserRow['user_id'];

            $dmStmt = $conn->prepare(
                "SELECT u.name AS sender_name, m.content, m.sent_at
                 FROM messages m
                 INNER JOIN users u ON m.sender_user_id = u.user_id
                 WHERE m.message_type = 'direct'
                   AND m.athlete_id = ?
                   AND (
                       (m.sender_user_id = ? AND m.recipient_user_id = ?)
                       OR
                       (m.sender_user_id = ? AND m.recipient_user_id = ?)
                   )
                 ORDER BY m.sent_at ASC"
            );

            if ($dmStmt) {
                $dmStmt->bind_param(
                    "iiiii",
                    $selectedAthleteId,
                    $coachUserId,
                    $selectedAthleteUserId,
                    $selectedAthleteUserId,
                    $coachUserId
                );
                $dmStmt->execute();
                $dmResult = $dmStmt->get_result();
                while ($row = $dmResult->fetch_assoc()) {
                    $coachDirectMessages[] = $row;
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rollins Athletics Dashboard</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>

<div class="app">

    <!-- Sidebar -->
    <div class="sidebar">
        <h2>Rollins Athletics</h2>

        <ul>
            <li onclick="showPage(event, 'profile')" class="<?php echo ($activeTab === 'profile') ? 'active' : ''; ?>">Profile</li>
            <li onclick="showPage(event, 'schedule')" class="<?php echo ($activeTab === 'schedule') ? 'active' : ''; ?>">Schedule</li>
            <li onclick="showPage(event, 'communication')" class="<?php echo ($activeTab === 'communication') ? 'active' : ''; ?>">Communication</li>
            <li onclick="showPage(event, 'medical')" class="<?php echo ($activeTab === 'medical') ? 'active' : ''; ?>">Medical</li>
        </ul>
    </div>

    <!-- Main -->
    <div class="main">

        <!-- Topbar -->
        <div class="topbar">
            <h1>Rollins Athletics Dashboard</h1>

            <div class="user">
                Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>
                |
                <a href="logout.php">Logout</a>
            </div>
        </div>

        <!-- Content -->
        <div class="content">

            <!-- Profile -->
            <div id="profile" class="page <?php echo ($activeTab === 'profile') ? 'active' : ''; ?>">
                <h1>Profile</h1>

                <div class="card">
                    <?php if ($coachProfileRow): ?>
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($coachProfileRow['name']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($coachProfileRow['email']); ?></p>
                        <p><strong>Sport:</strong> <?php echo (isset($coachProfileRow['sport']) && trim((string)$coachProfileRow['sport']) !== '') ? htmlspecialchars($coachProfileRow['sport']) : 'Not set'; ?></p>
                        <p><strong>Title:</strong> <?php echo (isset($coachProfileRow['title']) && trim((string)$coachProfileRow['title']) !== '') ? htmlspecialchars($coachProfileRow['title']) : 'Not set'; ?></p>
                    <?php else: ?>
                        <p>Unable to load profile.</p>
                    <?php endif; ?>

                    <div style="margin-top:15px;">
                        <a href="coach_profile_edit.php"><button>Edit Profile</button></a>
                    </div>
                </div>
            </div>

            <!-- Schedule -->
            <div id="schedule" class="page <?php echo ($activeTab === 'schedule') ? 'active' : ''; ?>">
                <h1>Schedule</h1>

                <div class="card">
                    <div style="display:flex; gap:10px; margin-bottom:15px; flex-wrap:wrap;">
                        <a href="coach_practice_schedule.php"><button>View Full Practice Schedule</button></a>
                        <a href="coach_practice_create.php"><button>Add Practice</button></a>
                        <a href="coach_practice_schedule.php"><button>Manage Practices</button></a>
                    </div>

                    <h3>Next Practice</h3>
                    <?php if (empty($upcomingPractices)): ?>
                        <p>No upcoming practices scheduled.</p>
                    <?php else: ?>
                        <?php foreach ($upcomingPractices as $practice): ?>
                            <p><strong><?php echo htmlspecialchars($practice['title']); ?></strong></p>
                            <p><strong>Athlete:</strong> <?php echo htmlspecialchars($practice['athlete_name'] ?? ''); ?></p>
                            <p>
                                <?php echo htmlspecialchars($practice['start_time']); ?>
                                <?php if (!empty($practice['end_time'])): ?>
                                    - <?php echo htmlspecialchars($practice['end_time']); ?>
                                <?php endif; ?>
                            </p>
                            <p><strong>Location:</strong> <?php echo htmlspecialchars($practice['location'] ?? ''); ?></p>
                            <hr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="card">
                    <div style="display:flex; gap:10px; margin-bottom:15px; flex-wrap:wrap;">
                        <a href="coach_game_schedule.php"><button>View Full Game Schedule</button></a>
                        <a href="coach_game_create.php"><button>Add Game</button></a>
                        <a href="coach_game_schedule.php"><button>Manage Games</button></a>
                    </div>

                    <h3>Next 3 Upcoming Games</h3>

                    <?php if (empty($upcomingGames)): ?>
                        <p>No upcoming games scheduled.</p>
                    <?php else: ?>
                        <?php foreach ($upcomingGames as $game): ?>
                            <p><strong>Opponent:</strong> <?php echo htmlspecialchars($game['opponent']); ?></p>
                            <p><strong>Athlete:</strong> <?php echo htmlspecialchars($game['athlete_name'] ?? ''); ?></p>
                            <p><strong>Date/Time:</strong> <?php echo htmlspecialchars(date('M j, Y g:i A', strtotime($game['game_datetime']))); ?></p>
                            <p><strong>Location:</strong> <?php echo htmlspecialchars($game['location']); ?></p>
                            <?php if (!empty($game['notes'])): ?>
                                <p><strong>Notes:</strong> <?php echo nl2br(htmlspecialchars($game['notes'])); ?></p>
                            <?php endif; ?>
                            <hr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Communication -->
            <div id="communication" class="page <?php echo ($activeTab === 'communication') ? 'active' : ''; ?>">
                <h1>Communication</h1>

                <div class="card">
                    <h3>Team announcements</h3>
                    <p style="margin-top:0;color:#4b5563;font-size:13px;">One-way posts visible to all athletes.</p>
                    <?php if (empty($teamAnnouncementsCoach)): ?>
                        <p>No team announcements yet.</p>
                    <?php else: ?>
                        <?php foreach ($teamAnnouncementsCoach as $ann): ?>
                            <p>
                                <strong><?php echo htmlspecialchars($ann['sender_name']); ?></strong>
                                <small><?php echo htmlspecialchars(date('M j, Y g:i A', strtotime($ann['sent_at']))); ?></small><br>
                                <?php echo nl2br(htmlspecialchars($ann['content'])); ?>
                            </p>
                            <hr>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <form method="POST" action="coach_message_handler.php" style="margin-top:15px;">
                        <input type="hidden" name="action" value="announcement">
                        <textarea name="content" rows="3" placeholder="Post a team announcement..." required style="width:100%; margin:10px 0; padding:10px; border:1px solid #ccc; border-radius:6px;"></textarea>
                        <button type="submit">Post announcement</button>
                    </form>
                </div>

                <div class="card">
                    <h3>Direct messages</h3>
                    <form method="get" action="coach_dashboard.php" style="margin-bottom:15px;">
                        <input type="hidden" name="tab" value="communication">
                        <label for="coach_athlete_select">Athlete</label>
                        <select id="coach_athlete_select" name="athlete_id" onchange="this.form.submit()" style="width:100%; max-width:360px; margin:8px 0;">
                            <option value="">Select an athlete…</option>
                            <?php foreach ($coachAthletes as $ca): ?>
                                <option value="<?php echo htmlspecialchars((string)$ca['athlete_id']); ?>" <?php echo ($selectedAthleteId === (int)$ca['athlete_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($ca['full_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>

                    <?php if ($selectedAthleteId <= 0 || $selectedAthleteUserId === null): ?>
                        <p>Select an athlete to view and send direct messages.</p>
                    <?php else: ?>
                        <div class="chat-box" id="coach-chat">
                            <?php if (empty($coachDirectMessages)): ?>
                                <p>No messages yet.</p>
                            <?php else: ?>
                                <?php foreach ($coachDirectMessages as $msg): ?>
                                    <p>
                                        <strong><?php echo htmlspecialchars($msg['sender_name']); ?></strong>:
                                        <?php echo htmlspecialchars($msg['content']); ?>
                                        <br>
                                        <small><?php echo htmlspecialchars(date('M j, Y g:i A', strtotime($msg['sent_at']))); ?></small>
                                    </p>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <form class="chat-input" method="POST" action="coach_message_handler.php">
                            <input type="hidden" name="action" value="direct">
                            <input type="hidden" name="athlete_id" value="<?php echo htmlspecialchars((string)$selectedAthleteId); ?>">
                            <input type="text" name="content" placeholder="Message this athlete..." required>
                            <button type="submit">Send</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Medical -->
            <div id="medical" class="page <?php echo ($activeTab === 'medical') ? 'active' : ''; ?>">
                <h1>Medical Records</h1>

                <?php if (empty($coachMedicalRecords)): ?>
                    <div class="card">
                        <p>No medical records on file for any athlete.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($coachMedicalRecords as $record): ?>
                        <div class="card">
                            <p><strong>Athlete:</strong> <?php echo htmlspecialchars($record['athlete_name'] ?? ''); ?></p>
                            <p><strong>Injury:</strong> <?php echo htmlspecialchars($record['injury_title']); ?></p>
                            <p><strong>Reported:</strong> <?php echo htmlspecialchars(date('M j, Y', strtotime($record['reported_date']))); ?></p>
                            <p><strong>Status:</strong> <?php echo htmlspecialchars(ucwords($record['status'])); ?></p>
                            <p><strong>Clearance:</strong> <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $record['clearance_status']))); ?></p>

                            <?php if (!empty($record['expected_return_date'])): ?>
                                <p><strong>Expected Return:</strong> <?php echo htmlspecialchars(date('M j, Y', strtotime($record['expected_return_date']))); ?></p>
                            <?php endif; ?>

                            <?php if (!empty($record['cleared_date'])): ?>
                                <p><strong>Cleared Date:</strong> <?php echo htmlspecialchars(date('M j, Y', strtotime($record['cleared_date']))); ?></p>
                            <?php endif; ?>

                            <?php if (!empty($record['injury_details'])): ?>
                                <p><strong>Details:</strong> <?php echo nl2br(htmlspecialchars($record['injury_details'])); ?></p>
                            <?php endif; ?>

                            <?php if (!empty($record['notes'])): ?>
                                <p><strong>Notes:</strong> <?php echo nl2br(htmlspecialchars($record['notes'])); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </div>
    </div>

</div>

<script src="assets/js/app.js"></script>
</body>
</html>
