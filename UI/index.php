<link rel="stylesheet" href="assets/css/styles.css">
<script src="assets/js/app.js"></script>

<!-- Sidebar -->
<div class="sidebar">
    <h2>Rollins Athletics</h2>

    <!-- Role Toggle (for demo) -->
    <select id="roleSelect" onchange="switchRole()">
        <option value="athlete">Athlete</option>
        <option value="coach">Coach</option>
    </select>

    <ul>
        <li onclick="showPage('profile')" class="active">Profile</li>
        <li onclick="showPage('performance')">Performance Tracking</li>
        <li onclick="showPage('schedule')">Schedule</li>
        <li onclick="showPage('communication')">Communication</li>
        <li onclick="showPage('medical')">Medical Records</li>
    </ul>
</div>

<!-- Content -->
<div class="content">

    <!-- Profile -->
    <div id="profile" class="page active">
        <h1>Profile</h1>

        <div class="card">
            <p><strong>Name:</strong> John Doe</p>
            <p><strong>Age:</strong> 20</p>
            <p><strong>Sport:</strong> Basketball</p>
            <p><strong>Position:</strong> Guard</p>

            <button>Edit Profile</button>
        </div>
    </div>

    <!-- Performance -->
    <div id="performance" class="page">
        <h1>Performance Tracking</h1>

        <div class="grid">
            <div class="card">
                <h3>Game Statistics</h3>
                <p>Points: 18</p>
                <p>Assists: 6</p>
            </div>

            <div class="card">
                <h3>Training Metrics</h3>
                <p>Speed: High</p>
                <p>Endurance: Medium</p>
            </div>
        </div>
    </div>

    <!-- Schedule -->
    <div id="schedule" class="page">
        <h1>Schedule</h1>

        <div class="calendar">
            <div class="day">Mon<br><span class="practice">Practice</span></div>
            <div class="day">Tue<br><span class="lift">Lift</span></div>
            <div class="day">Wed<br><span class="practice">Practice</span></div>
            <div class="day">Thu<br><span class="rest">Rest</span></div>
            <div class="day">Fri<br><span class="game">Game</span></div>
            <div class="day">Sat</div>
            <div class="day">Sun</div>
        </div>
    </div>

    <!-- Communication -->
    <div id="communication" class="page">
        <h1>Communication</h1>

        <div class="chat-box" id="chat"></div>

        <div class="chat-input">
            <input type="text" placeholder="Type a message..." />
            <button>Send</button>
        </div>
    </div>

    <!-- Medical -->
    <div id="medical" class="page">
        <h1>Medical Records</h1>

        <div class="card">
            <p><strong>Injury:</strong> Ankle Sprain</p>
            <p class="status recovering">Status: Recovering</p>

            <div id="coachRestriction" class="hidden">
                <p>Access Restricted: Request permission from athlete.</p>
                <button>Request Access</button>
            </div>
        </div>
    </div>

</div>