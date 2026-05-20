document.addEventListener("DOMContentLoaded", () => {
    const form = document.querySelector("form[method='POST']");
    const scheduleInput = document.querySelector("input[name='requested_schedule']");
    
    // Get availability data from the embedded JSON
    const availabilityData = document.getElementById("availability-data");
    const availabilitySlots = availabilityData ? JSON.parse(availabilityData.textContent) : [];
    
    if (!form || !scheduleInput) return;
    
    form.addEventListener("submit", (e) => {
        if (availabilitySlots.length === 0) {
            // No availability slots defined, allow submission
            return true;
        }
        
        const scheduleValue = scheduleInput.value;
        if (!scheduleValue) {
            e.preventDefault();
            alert("Please select a schedule.");
            return false;
        }
        
        const requestedDate = new Date(scheduleValue);
        const daysOfWeek = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        const requestedDay = daysOfWeek[requestedDate.getDay()];
        
        // Format time as HH:MM:SS
        const hours = requestedDate.getHours().toString().padStart(2, '0');
        const minutes = requestedDate.getMinutes().toString().padStart(2, '0');
        const seconds = '00';
        const requestedTime = `${hours}:${minutes}:${seconds}`;
        
        let isAvailable = false;
        for (const slot of availabilitySlots) {
            if (slot.day_of_week === requestedDay) {
                if (requestedTime >= slot.start_time && requestedTime <= slot.end_time) {
                    isAvailable = true;
                    break;
                }
            }
        }
        
        if (!isAvailable) {
            e.preventDefault();
            alert("The tutor is not available at the selected time. Please choose from their available schedule.");
            return false;
        }
        
        return true;
    });
});