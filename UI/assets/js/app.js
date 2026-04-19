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

    // Expose functions
    window.showPage = showPage;
    window.switchRole = switchRole;
    // Messaging is now server-rendered and database-backed.

});