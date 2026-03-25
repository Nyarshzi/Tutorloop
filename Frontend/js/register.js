document.addEventListener("DOMContentLoaded", () => {
    const card = document.getElementById("registerCard");
    const fields = document.querySelectorAll(".input-group input");
    const form = document.getElementById("registerForm");
    const btn = document.getElementById("registerBtn");
    const text = btn ? btn.querySelector(".btn-text") : null;
    const loader = btn ? btn.querySelector(".loader") : null;
    const passwordInput = document.getElementById("password");
    const togglePasswordBtn = document.getElementById("togglePassword");

    const roleGroup = document.getElementById("roleGroup");
    const roleInput = document.getElementById("role");
    const roleTrigger = document.getElementById("roleTrigger");
    const roleText = document.getElementById("roleText");
    const customOptions = document.querySelectorAll(".custom-option");

    const updateFilledState = (field) => {
        const group = field.closest(".input-group");
        if (!group) return;

        if (field.value.trim() !== "") {
            group.classList.add("filled");
        } else {
            group.classList.remove("filled");
        }
    };

    fields.forEach((field) => {
        updateFilledState(field);

        field.addEventListener("focus", () => {
            if (card) card.classList.add("active");
        });

        field.addEventListener("input", () => {
            if (card) card.classList.add("active");
            updateFilledState(field);
        });

        field.addEventListener("blur", () => {
            updateFilledState(field);

            const hasInputValue = [...fields].some((item) => item.value.trim() !== "");
            const hasRoleValue = roleInput && roleInput.value.trim() !== "";

            if (!hasInputValue && !hasRoleValue && card) {
                card.classList.remove("active");
            }
        });
    });

    if (togglePasswordBtn && passwordInput) {
        togglePasswordBtn.addEventListener("click", () => {
            const isPassword = passwordInput.type === "password";
            passwordInput.type = isPassword ? "text" : "password";
            togglePasswordBtn.textContent = isPassword ? "Hide" : "Show";
            togglePasswordBtn.setAttribute(
                "aria-label",
                isPassword ? "Hide password" : "Show password"
            );
            passwordInput.focus();
        });
    }

    if (roleTrigger && roleGroup && roleInput && roleText) {
        roleTrigger.addEventListener("click", () => {
            roleGroup.classList.toggle("open");
            roleTrigger.setAttribute(
                "aria-expanded",
                roleGroup.classList.contains("open") ? "true" : "false"
            );
            if (card) card.classList.add("active");
        });

        customOptions.forEach((option) => {
            option.addEventListener("click", () => {
                const value = option.dataset.value;
                const label = option.textContent.trim();

                roleInput.value = value;
                roleText.textContent = label;
                roleGroup.classList.add("filled");
                roleGroup.classList.remove("open");

                customOptions.forEach((opt) => opt.classList.remove("selected"));
                option.classList.add("selected");

                roleTrigger.setAttribute("aria-expanded", "false");
                if (card) card.classList.add("active");
            });
        });

        document.addEventListener("click", (e) => {
            if (!roleGroup.contains(e.target)) {
                roleGroup.classList.remove("open");
                roleTrigger.setAttribute("aria-expanded", "false");
            }
        });
    }

    if (form && btn && text && loader) {
        form.addEventListener("submit", () => {
            text.style.display = "none";
            loader.style.display = "block";
            btn.disabled = true;
        });
    }
});