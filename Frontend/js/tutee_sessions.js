const menuBtn = document.getElementById("menuBtn");
const sidebar = document.getElementById("sidebar");

// toggle sidebar
menuBtn.addEventListener("click", () => {
    sidebar.classList.toggle("active");
});

// OPTIONAL: click outside to close
document.addEventListener("click", (e) => {
    if (
        !sidebar.contains(e.target) &&
        !menuBtn.contains(e.target)
    ) {
        sidebar.classList.remove("active");
    }
});