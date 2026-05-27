document.addEventListener("DOMContentLoaded", () => {
    // FIX: Removed availability alert() check from JS entirely.
    // Availability is already validated server-side by PHP and shown
    // via the in-page .message.error div — no browser alert() needed.

    // JS only handles duration validation (already in inline script in PHP).
    // This file is kept for any future JS enhancements.
});
