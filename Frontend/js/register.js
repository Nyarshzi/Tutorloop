const togglePassword = document.getElementById("togglePassword");
const password = document.getElementById("password");

const dropdown = document.querySelector(".dropdown");
const roleBtn = document.getElementById("roleBtn");
let selectedRole = "";

const form = document.getElementById("registerForm");
const submitBtn = document.getElementById("submitBtn");

// Toggle password
togglePassword.addEventListener("click", () => {
    password.type = password.type === "password" ? "text" : "password";
    togglePassword.textContent = password.type === "password" ? "Show" : "Hide";
});

// Dropdown toggle
roleBtn.addEventListener("click", () => {
    dropdown.classList.toggle("active");
});

// Select role
document.querySelectorAll(".dropdown-content div").forEach(item => {
    item.addEventListener("click", () => {
        selectedRole = item.dataset.role;
        roleBtn.textContent = selectedRole;
        dropdown.classList.remove("active");
    });
});

// Close dropdown outside click
document.addEventListener("click", (e) => {
    if (!dropdown.contains(e.target)) {
        dropdown.classList.remove("active");
    }
});

// Form submit
form.addEventListener("submit", (e) => {
    e.preventDefault();

      const name = document.getElementById("name").value;
    const email = document.getElementById("email").value;

    if (!name || !email || !password.value || !selectedRole) {
        alert("Please complete all fields");
        return;
    }

    submitBtn.classList.add("loading");

    setTimeout(() => {
        submitBtn.classList.remove("loading");

        alert("Registered Successfully!");
        form.reset();
        roleBtn.textContent = "Select Role";
        selectedRole = "";
    }, 1500);
});