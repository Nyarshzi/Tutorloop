// DYNAMIC GREETING
const title = document.querySelector(".topbar h1");

const hour = new Date().getHours();

if (hour < 12) {
  title.textContent = "Good Morning ☀️";
} else if (hour < 18) {
  title.textContent = "Good Afternoon 🌤️";
} else {
  title.textContent = "Good Evening 🌙";
}

// BOTTOM NAV ACTIVE STATE
const navLinks = document.querySelectorAll(".bottom-nav a");

navLinks.forEach(link => {
  link.addEventListener("click", () => {
    navLinks.forEach(l => l.classList.remove("active"));
    link.classList.add("active");
  });
});

// SEARCH FUNCTION (DEMO)
const searchBtn = document.querySelector(".search button");
const searchInput = document.querySelector(".search input");

if (searchBtn) {
  searchBtn.addEventListener("click", () => {
    const value = searchInput.value.trim();

    if (value === "") {
      alert("Please enter a subject!");
    } else {
      alert("Searching for: " + value);
    }
  });
}

// ENTER KEY SEARCH
if (searchInput) {
  searchInput.addEventListener("keypress", function(e) {
    if (e.key === "Enter") {
      searchBtn.click();
    }
  });
}

// SIMPLE TOUCH FEEDBACK (MOBILE)
document.querySelectorAll("button, .card, .box").forEach(el => {
  el.addEventListener("touchstart", () => {
    el.style.transform = "scale(0.97)";
  });

  el.addEventListener("touchend", () => {
    el.style.transform = "scale(1)";
  });
});

// OPTIONAL: VIBRATION (ANDROID)
function vibrate() {
  if (navigator.vibrate) {
    navigator.vibrate(30);
  }
}

// apply vibration on buttons
document.querySelectorAll("button").forEach(btn => {
  btn.addEventListener("click", vibrate);
});

// Show section function for sidebar navigation
function showSection(sectionId) {
    // Hide all sections
    const sections = document.querySelectorAll('.section');
    sections.forEach(section => {
        section.classList.remove('active');
    });
    // Show the selected section
    const activeSection = document.getElementById(sectionId);
    if (activeSection) {
        activeSection.classList.add('active');
    }
    // Update sidebar active state
    const sidebarLinks = document.querySelectorAll('.sidebar ul li');
    sidebarLinks.forEach(link => {
        link.classList.remove('active');
    });
    // Find the link that matches
    const activeLink = Array.from(sidebarLinks).find(link => link.getAttribute('onclick') === `showSection('${sectionId}')`);
    if (activeLink) {
        activeLink.classList.add('active');
    }
}

// Logout function
function logout() {
    window.location.href = 'logout.php';
}

// Placeholder for bookSession
function bookSession(event) {
    event.preventDefault();
    alert('Booking functionality not implemented yet.');
}