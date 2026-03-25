function showPage(pageId) {
    document.querySelectorAll('.page').forEach(p => p.classList.add('hidden'));
    document.getElementById(pageId).classList.remove('hidden');

    document.querySelectorAll('.sidebar li').forEach(li => li.classList.remove('active'));
    event.target.classList.add('active');
}

//Fake Data
const schedule = [  
    "Practice - 4:00 PM",
    "Team Meeting - 6:00 PM"
];

const announcements = [
    "Game rescheduled to Friday",
    "New workout plan available"
];

const messages = [
    "Coach: Be early today",
    "Trainer: Recovery session tomorrow"
];

// Load Dashboard Data
schedule.forEach(item => {
    document.getElementById("schedule-list").innerHTML += `<p>${item}</p>`;
});

announcements.forEach(item => {
    document.getElementById("announcements").innerHTML += `<li>${item}</li>`;
});

messages.forEach(msg => {
    document.getElementById("messages-preview").innerHTML += `<li>${msg}</li>`;
});

//Chat mock
const chat = document.getElementById("chat");
["Hey team", "Practice at 4", "Don't be late"].forEach(msg => {
    chat.innerHTML += `<p>${msg}</p>`;
});