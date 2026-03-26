function showPage(pageId) {
    document.querySelectorAll('.page').forEach(p => {
        p.classList.remove('active');
    });

    document.getElementById(pageId).classList.add('active');

    document.querySelectorAll('.sidebar li').forEach(li => {
        li.classList.remove('active');
    });

    event.target.classList.add('active');
}

// Role Switching (Athlete vs Coach)
function switchRole(role) {
    const role = document.getElementById("roleSelect").value;
    const restriction = document.getElementById("coachRestriction");

    if (role === "coach") {
        restriction.classList.remove("hidden");
    } else {
        restriction.classList.add("hidden");
    }
}

//Chat Mock
const chat = document.getElementById("chat");

[
    "Coach: Practice at 4PM",
    "Athlete: Got it!",
].forEach(msg => {
    const p = document.createElement("p");
    p.textContent = msg;
    chat.appendChild(p);
});