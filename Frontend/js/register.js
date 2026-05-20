document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("registerForm");
    const submitBtn = document.getElementById("submitBtn");
    const loader = submitBtn ? submitBtn.querySelector(".loader") : null;
    const btnText = submitBtn ? submitBtn.querySelector("span") : null;
    
    const passwordInput = document.getElementById("password");
    const confirmInput = document.getElementById("confirm_password");
    const togglePasswordBtn = document.getElementById("togglePassword");
    const toggleConfirmPasswordBtn = document.getElementById("toggleConfirmPassword");

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

    // Confirm Password Toggle
    if (toggleConfirmPasswordBtn && confirmInput) {
        toggleConfirmPasswordBtn.addEventListener("click", () => {
            const isPassword = confirmInput.type === "password";
            confirmInput.type = isPassword ? "text" : "password";
            toggleConfirmPasswordBtn.textContent = isPassword ? "Hide" : "Show";
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

    // Form Submission with password validation
    if (form && submitBtn) {
        form.addEventListener("submit", (e) => {
            const password = passwordInput.value;
            const confirmPassword = confirmInput.value;

            if (password !== confirmPassword) {
                e.preventDefault();
                alert("Passwords do not match. Please make sure both password fields are identical.");
                return false;
            }

            if (btnText && loader) {
                btnText.style.visibility = "hidden";
                loader.style.display = "block";
            }
            submitBtn.disabled = true;
        });
    }
});