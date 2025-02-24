document.addEventListener('DOMContentLoaded', function() {
    // Image Gallery Functionality
    const mainImage = document.getElementById('mainImage');
    const thumbnails = document.querySelectorAll('.thumbnail');

    function changeImage(src) {
        mainImage.src = src;
        thumbnails.forEach(thumb => {
            thumb.classList.remove('active');
            if (thumb.src === src) {
                thumb.classList.add('active');
            }
        });
    }

    thumbnails.forEach(thumb => {
        thumb.addEventListener('click', () => changeImage(thumb.src));
    });

    // Size Selection
    const sizeButtons = document.querySelectorAll('.size-btn');
    sizeButtons.forEach(button => {
        button.addEventListener('click', () => {
            sizeButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
        });
    });

    // Date Picker Initialization
    const eventDatePicker = flatpickr("#eventDate", {
        minDate: "today",
        onChange: function(selectedDates) {
            updateRentalDates(selectedDates[0]);
        }
    });

    // Rental Duration and Dates Calculation
    const durationInputs = document.querySelectorAll('input[name="duration"]');
    const startDateInput = document.getElementById('startDate');
    const endDateInput = document.getElementById('endDate');

    function updateRentalDates(eventDate) {
        if (!eventDate) return;

        let duration = 3; // default duration
        durationInputs.forEach(input => {
            if (input.checked) {
                duration = parseInt(input.value);
            }
        });

        // Calculate start date (2 days before event)
        const startDate = new Date(eventDate);
        startDate.setDate(eventDate.getDate() - 2);

        // Calculate end date based on duration
        const endDate = new Date(startDate);
        endDate.setDate(startDate.getDate() + duration - 1);

        // Format dates for display
        startDateInput.value = formatDate(startDate);
        endDateInput.value = formatDate(endDate);
    }

    function formatDate(date) {
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }

    // Update dates when duration changes
    durationInputs.forEach(input => {
        input.addEventListener('change', () => {
            const eventDate = eventDatePicker.selectedDates[0];
            if (eventDate) {
                updateRentalDates(eventDate);
            }
        });
    });

    // Size Chart Functionality
    function showSizeChart() {
        // Implement size chart modal or popup here
        alert('Size Chart will be displayed here');
    }
});
