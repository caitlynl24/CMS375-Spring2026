document.addEventListener("DOMContentLoaded", function () {

    function showPage(event, pageId) {
        document.querySelectorAll('.page').forEach(p => {
            p.classList.remove('active');
        });

        document.getElementById(pageId).classList.add('active');

        document.querySelectorAll('.sidebar li').forEach(li => {
            li.classList.remove('active');
        });

        event.target.classList.add('active');
    }

    function switchRole() {
        const role = document.getElementById("roleSelect").value;
        const restriction = document.getElementById("coachRestriction");

        if (role === "coach") {
            restriction.classList.remove("hidden");
        } else {
            restriction.classList.add("hidden");
        }
    }

    function sendMessage() {
        const input = document.getElementById("messageInput");
        const chat = document.getElementById("chat");
        const text = input.value.trim();

        if (text === "") return;

        const p = document.createElement("p");
        p.textContent = "You: " + text;
        chat.appendChild(p);

        input.value = "";
        chat.scrollTop = chat.scrollHeight;
    }

    // Expose functions
    window.showPage = showPage;
    window.switchRole = switchRole;
    window.sendMessage = sendMessage;

    // Initialize chat
    const chat = document.getElementById("chat");
    if (chat) {
        ["Coach: Practice at 4PM", "Athlete: Got it!"].forEach(msg => {
            const p = document.createElement("p");
            p.textContent = msg;
            chat.appendChild(p);
        });
    }

});