/**
 * TutorLoop Messaging System - Responsive Logic
 * Handles sidebar toggling, auto-scrolling, and mobile view transitions.
 */

document.addEventListener('DOMContentLoaded', function() {
    
   // --- 1. SIDEBAR TOGGLE (Mobile Menu) ---
const menuBtn = document.getElementById('menuBtn');
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('sidebarOverlay');

if (menuBtn && sidebar) {
    menuBtn.addEventListener('click', function() {
        sidebar.classList.toggle('active');
        if (overlay) overlay.classList.toggle('active');
        document.body.classList.toggle('sidebar-open');
    });

    if (overlay) {
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
            document.body.classList.remove('sidebar-open');
        });
    }

    sidebar.querySelectorAll('nav a').forEach(link => {
        link.addEventListener('click', () => {
            sidebar.classList.remove('active');
            if (overlay) overlay.classList.remove('active');
            document.body.classList.remove('sidebar-open');
        });
    });
}

    // --- 2. AUTO-SCROLL TO LATEST MESSAGE ---
    const chatBody = document.querySelector('.chat-body');
    
    function scrollToBottom() {
        if (chatBody) {
            chatBody.scrollTop = chatBody.scrollHeight;
        }
    }

    // Run on load
    scrollToBottom();

    // --- 3. MOBILE VIEW OPTIMIZATION ---
    // If a user selects a contact on a mobile device, automatically scroll down to the chat
    const urlParams = new URLSearchParams(window.location.search);
    const hasContactSelected = urlParams.has('tutor_id') || urlParams.has('tutee_id');

    if (window.innerWidth <= 768 && hasContactSelected) {
        const chatWindow = document.querySelector('.chat');
        if (chatWindow) {
            // Smooth scroll to the chat area so the user doesn't have to manual scroll past contacts
            setTimeout(() => {
                chatWindow.scrollIntoView({ behavior: 'smooth' });
            }, 300);
        }
    }

    // --- 4. PREVENT EMPTY FORM SUBMISSION ---
    const messageForm = document.querySelector('.chat-input');
    if (messageForm) {
        messageForm.addEventListener('submit', function(e) {
            const input = this.querySelector('input[name="message"]');
            if (!input.value.trim()) {
                e.preventDefault(); // Stop empty messages
            }
        });
    }

    // --- 5. DYNAMIC VIEWPORT HEIGHT (Mobile Fix) ---
    // Fixes the 100vh issue on mobile browsers where the address bar covers the input
    const setVH = () => {
        let vh = window.innerHeight * 0.01;
        document.documentElement.style.setProperty('--vh', `${vh}px`);
    };

    window.addEventListener('resize', setVH);
    setVH();
});