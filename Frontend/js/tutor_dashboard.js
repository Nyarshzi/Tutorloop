//  GREETING
document.addEventListener("DOMContentLoaded", function () {

    console.log("JS WORKING");

    const headerTitle = document.getElementById("greetingText");

    if (!headerTitle) {
        console.log("greetingText not found");
        return;
    }

    const tutorName = headerTitle.dataset.name;

    const hour = new Date().getHours();

    let greeting = "";
    let emoji = "";

    if (hour >= 5 && hour < 12) {
        greeting = "Good Morning";
        emoji = "☀️";
    } else if (hour >= 12 && hour < 18) {
        greeting = "Good Afternoon";
        emoji = "🌤️";
    } else {
        greeting = "Good Evening";
        emoji = "🌙";
    }

    headerTitle.innerHTML = `${greeting} ${emoji}, ${tutorName}`;

});

//  TOUCH FEEDBACK
document.querySelectorAll(".stat-card, .action-card").forEach(el => {
  el.addEventListener("touchstart", () => {
    el.style.transform = "scale(0.97)";
  });

  el.addEventListener("touchend", () => {
    el.style.transform = "scale(1)";
  });
});

//  VIBRATION (ANDROID)
function vibrate() {
  if (navigator.vibrate) {
    navigator.vibrate(30);
  }
}

document.querySelectorAll(".action-card").forEach(btn => {
  btn.addEventListener("click", vibrate);
});

// ☰ MENU BUTTON (for future sidebar)
const menuBtn = document.querySelector(".topbar-right");

if (menuBtn) {
  menuBtn.addEventListener("click", () => {
    alert("Menu coming soon 🚀");
  });
}