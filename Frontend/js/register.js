document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("registerForm");
    const submitBtn = document.getElementById("submitBtn");
    const loader = submitBtn ? submitBtn.querySelector(".loader") : null;
    const btnText = submitBtn ? submitBtn.querySelector("span") : null;
    
    const passwordInput = document.getElementById("password");
    const togglePasswordBtn = document.getElementById("togglePassword");

    const roleGroup = document.getElementById("roleGroup");
    const roleBtn = document.getElementById("roleBtn");
    const roleInput = document.getElementById("role");
    const dropdownContent = roleGroup ? roleGroup.querySelector(".dropdown-content") : null;
    const customOptions = roleGroup ? roleGroup.querySelectorAll(".dropdown-content div") : [];

    // Password Toggle
    if (togglePasswordBtn && passwordInput) {
        togglePasswordBtn.addEventListener("click", () => {
            const isPassword = passwordInput.type === "password";
            passwordInput.type = isPassword ? "text" : "password";
            togglePasswordBtn.textContent = isPassword ? "Hide" : "Show";
        });
    }

    // Role Dropdown
    if (roleBtn && roleGroup) {
        roleBtn.addEventListener("click", (e) => {
            e.preventDefault();
            roleGroup.classList.toggle("active");
        });

        customOptions.forEach((option) => {
            option.addEventListener("click", () => {
                const value = option.dataset.value;
                const label = option.textContent.trim();

                roleInput.value = value;
                roleBtn.textContent = label;
                roleGroup.classList.remove("active");
            });
        });

        document.addEventListener("click", (e) => {
            if (!roleGroup.contains(e.target)) {
                roleGroup.classList.remove("active");
            }
        });
    }

    // Form Submission
    if (form && submitBtn) {
        form.addEventListener("submit", () => {
            if (btnText && loader) {
                btnText.style.visibility = "hidden";
                loader.style.display = "block";
            }
            submitBtn.disabled = true;
        });
    }
});