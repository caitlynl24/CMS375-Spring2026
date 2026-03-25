<link rel="stylesheet" href="assets/css/styles.css">
<script src="assets/js/app.js"></script>

<!-- Sidebar -->
<div class="sidebar">
    <h2>Rollins Athletics</h2>
    <ul>
        <li onclick="showPage('dashboard')" class="active">Dashboard</li>
        <li onclick="showPage('schedule')">Schedule</li>
        <li onclick="showPage('messages')">Messages</li>
        <li onclick="showPage('performance')">Performance</li>
        <li onclick="showPage('medical')">Medical</li>
    </ul>
</div>

<!-- Main Content -->
<div class="content">

    <!-- Dashboard -->
    <div id="dashboard" class="page">
        <h1>Dashboard</h1>
        <div class="grid">
            <div class="card">
            <h3>Today's Schedule</h3>
            <div id="schedule-list"></div>
        </div>
        <div class="card">
            <h3>Accouncements</h3>
            <ul id="announcements"></ul>
        </div>
        <div class="card">
            <h3>Messages</h3>
            <ul id="messages-preview"></ul>
        </div>
    </div>
</div>

<!-- Schedule -->
<div id="schedule" class="page hidden">
    <h1>Weekly Schedule</h1>
    <div class="card" id="calendar"></div>
</div>

<!-- Messages -->
<div id="messages" class="page hidden">
    <h1>Messages</h1>
    <div class="card chat-box" id="chat"></div>
</div>

<!-- Performance -->
<div id="performance" class="page hidden">
    <h1>Performance</h1>
    <div class="grid">
        <div class="card">Stats: Points, Assists, Rebounds</div>
        <div class="card">Training Metrics</div>
        <div class="card">Performance History</div>
    </div>
</div>

<!-- Medical -->
<div id="medical" class="page hidden">
    <h1>Medical Records</h1>
    <div class="grid">
        <div class="card">Injury Reports</div>
        <div class="card">Clearance Status</div>
        <div class="card">Recovery Tracking</div>
    </div>
</div>

</div>