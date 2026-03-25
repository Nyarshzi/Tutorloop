//  GREETING
const headerTitle = document.querySelector(".dashboard-header h1");

const hour = new Date().getHours();

if (hour < 12) {
  headerTitle.textContent = "Good Morning, Tutor ☀️";
} else if (hour < 18) {
  headerTitle.textContent = "Good Afternoon, Tutor 🌤️";
} else {
  headerTitle.textContent = "Good Evening, Tutor 🌙";
}

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