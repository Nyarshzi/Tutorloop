document.addEventListener('DOMContentLoaded', () => {
    const uploadInput = document.getElementById('pic-upload');

    // ===== PHOTO CHANGE PREVIEW =====
    if (uploadInput) {
        uploadInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    const circle = document.querySelector('.avatar-circle');
                    const initials = circle.querySelector('.initials');
                    if (initials) initials.style.display = 'none';

                    let img = circle.querySelector('.profile-img');
                    if (!img) {
                        img = document.createElement('img');
                        img.className = 'profile-img';
                        circle.prepend(img);
                    }
                    img.src = e.target.result;
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    }
});