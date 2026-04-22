// Define functions at top level for inline onclick handlers
let loginBox, stringElement, body;

function toggleLight() {
    // Get elements if not already stored
    if (!loginBox) loginBox = document.getElementById("loginBox");
    if (!body) body = document.body;
    if (!stringElement) stringElement = document.querySelector(".string");
    
    // Ensure elements exist
    if (!loginBox || !body) return;

    // Toggle light and box visibility
    const isTurningOn = !body.classList.contains("light-on");
    
    // Update classes with immediate visual feedback
    body.classList.toggle("light-on", isTurningOn);
    loginBox.classList.toggle("active", isTurningOn);
    
    // Add visual feedback to the string
    if (stringElement) {
        stringElement.style.opacity = isTurningOn ? "0.8" : "1";
    }
}

function togglePassword() {
    const passwordInput = document.getElementById("password");
    const toggleText = document.querySelector(".show-text");

    if (!passwordInput || !toggleText) return;

    if (passwordInput.type === "password") {
        passwordInput.type = "text";
        toggleText.textContent = "Hide";
    } else {
        passwordInput.type = "password";
        toggleText.textContent = "Show";
    }
}

// Additional event listeners (optional enhancements)
document.addEventListener("DOMContentLoaded", () => {
    loginBox = document.getElementById("loginBox");
    stringElement = document.querySelector(".string");
    body = document.body;

    // Attach event listener to string for keyboard support
    if (stringElement) {
        // Keyboard support for accessibility
        stringElement.addEventListener("keydown", function(e) {
            if (e.key === "Enter" || e.key === " ") {
                e.preventDefault();
                toggleLight();
            }
        });
        // Make string focusable for accessibility
        stringElement.setAttribute("role", "button");
        stringElement.setAttribute("tabindex", "0");
    }
});