document.addEventListener('DOMContentLoaded', () => {
    const uploadInput = document.getElementById('pic-upload');
    const addBtn = document.getElementById('add-row-btn');
    const container = document.getElementById('availability-container');
    const subjectSelect = document.getElementById('input_subject');
    const newSubInput = document.getElementById('new-subject-input');

    // 1. PHOTO CHANGE PREVIEW
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

    // 2. SCHEDULE ROW MANAGEMENT
    window.addScheduleRow = function(day = 'Monday', start = '', end = '') {
        const row = document.createElement('div');
        row.className = 'availability-row';
        row.innerHTML = `
            <div class="schedule-inputs">
                <select name="avail_day[]">
                    ${['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'].map(d => 
                        `<option value="${d}" ${d === day ? 'selected' : ''}>${d}</option>`
                    ).join('')}
                </select>
                <div class="time-range">
                    <input type="time" name="avail_start[]" value="${start}" required>
                    <span>to</span>
                    <input type="time" name="avail_end[]" value="${end}" required>
                    <button type="button" class="remove-btn">×</button>
                </div>
            </div>
        `;
        row.querySelector('.remove-btn').onclick = () => row.remove();
        if(container) container.appendChild(row);
    }

    // Initialize schedule from global PHP variable
    if (typeof savedSchedule !== 'undefined' && savedSchedule !== "") {
        const slots = savedSchedule.split("|");
        slots.forEach(slot => {
            const parts = slot.split(",");
            if(parts.length === 3) window.addScheduleRow(parts[0], parts[1], parts[2]);
        });
    } else if (container && container.children.length === 0) {
        window.addScheduleRow(); 
    }

    if(addBtn) addBtn.onclick = () => window.addScheduleRow();

    // 3. NEW SUBJECT TOGGLE
    if (subjectSelect) {
        subjectSelect.addEventListener('change', function() {
            if (newSubInput) {
                newSubInput.style.display = (this.value === 'new') ? 'block' : 'none';
                if (this.value === 'new') newSubInput.focus();
            }
        });
    }

    // 4. POPULATE FIELDS (Global function for the Edit button)
    window.populateFields = function(subjectId, rate) {
        if (subjectSelect) subjectSelect.value = subjectId;
        const rateField = document.getElementById('input_rate');
        if (rateField) rateField.value = rate;
        if (newSubInput) newSubInput.style.display = 'none';
    };
});