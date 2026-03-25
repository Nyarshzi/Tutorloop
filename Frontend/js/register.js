document.addEventListener("DOMContentLoaded", () => {

    const password = document.getElementById("password");
    const toggle = document.getElementById("togglePassword");

    const roleGroup = document.getElementById("roleGroup");
    const roleTrigger = document.getElementById("roleTrigger");
    const roleText = document.getElementById("roleText");
    const roleInput = document.getElementById("role");
    const options = document.querySelectorAll(".custom-option");

    // SHOW PASSWORD (FAST TOGGLE)
    toggle.addEventListener("click", () => {
        if (password.type === "password") {
            password.type = "text";
            toggle.textContent = "Hide";
        } else {
            password.type = "password";
            toggle.textContent = "Show";
        }
    });

    // ROLE DROPDOWN
roleTrigger.addEventListener("click", () => {
    roleGroup.classList.toggle("open");
});

options.forEach(option => {
    option.addEventListener("click", () => {
        roleText.textContent = option.textContent;
        roleInput.value = option.dataset.value;

        // ADD THIS LINE 👇 (VERY IMPORTANT)
        roleGroup.classList.add("filled");

        roleGroup.classList.remove("open");
    });
});
});