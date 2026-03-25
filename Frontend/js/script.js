let lampOn = document.querySelector(".container")?.classList.contains("lamp-active") || false;

function toggleLamp() {
    const loginBox = document.getElementById("loginBox");
    const lightBeam = document.getElementById("lightBeam");
    const container = document.querySelector(".container");

    lampOn = !lampOn;

    if (lampOn) {
        loginBox.classList.remove("hidden");
        loginBox.classList.add("glow");
        lightBeam.classList.add("beam-on");
        container.classList.add("lamp-active");
    } else {
        loginBox.classList.add("hidden");
        loginBox.classList.remove("glow");
        lightBeam.classList.remove("beam-on");
        container.classList.remove("lamp-active");
    }
}

function togglePassword(event) {
    event.preventDefault();

    const passwordInput = document.getElementById("password");
    const toggleText = document.querySelector(".toggle-pass");

    if (passwordInput.type === "password") {
        passwordInput.type = "text";
        toggleText.textContent = "Hide";
    } else {
        passwordInput.type = "password";
        toggleText.textContent = "Show";
    }
}