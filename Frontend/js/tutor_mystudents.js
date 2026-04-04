const menuBtn = document.getElementById("menuBtn");
const sidebar = document.getElementById("sidebar");

/* SIDEBAR TOGGLE */
menuBtn.addEventListener("click", () => {
    sidebar.classList.toggle("active");
});

/* CLICK OUTSIDE CLOSE */
document.addEventListener("click", (e) => {
    if (!sidebar.contains(e.target) && !menuBtn.contains(e.target)) {
        sidebar.classList.remove("active");
    }
});