const menuBtn = document.getElementById("menuBtn");
const sidebar = document.getElementById("sidebar");

function setGreeting() {
    const greetingText = document.getElementById("greeting");
    const hour = new Date().getHours();

    let greeting = "";
    let emoji = "";

    if (hour < 12) {
        greeting = "Good Morning";
        emoji = "☀️";
    } else if (hour < 18) {
        greeting = "Good Afternoon";
        emoji = "🌤️";
    } else {
        greeting = "Good Evening";
        emoji = "🌙";
    }

    greetingText.innerText = `${greeting} ${emoji}, Tutor 👩‍🏫`;
}

setGreeting();

/* SIDEBAR TOGGLE */
menuBtn.addEventListener("click", () => {
    sidebar.classList.toggle("active");
});

/* CLICK OUTSIDE TO CLOSE */
document.addEventListener("click", (e) => {
    if (!sidebar.contains(e.target) && !menuBtn.contains(e.target)) {
        sidebar.classList.remove("active");
    }
});