document.addEventListener("DOMContentLoaded", function () {
  // Image Gallery Functionality
  const mainImage = document.getElementById("mainImage");
  const thumbnails = document.querySelectorAll(".thumbnail");

  function changeImage(src) {
    mainImage.src = src;
    thumbnails.forEach((thumb) => {
      thumb.classList.remove("active");
      if (thumb.src === src) {
        thumb.classList.add("active");
      }
    });
  }

  thumbnails.forEach((thumb) => {
    thumb.addEventListener("click", () => changeImage(thumb.src));
  });

  // Size Selection
  const sizeButtons = document.querySelectorAll(".size-btn");
  sizeButtons.forEach((button) => {
    button.addEventListener("click", () => {
      sizeButtons.forEach((btn) => btn.classList.remove("active"));
      button.classList.add("active");
    });
  });

  // Date Picker Initialization
  const eventDatePicker = flatpickr("#eventDate", {
    minDate: "today",
    onChange: function (selectedDates) {
      updateRentalDates(selectedDates[0]);
    },
  });

  // Rental Duration and Dates Calculation
  const durationInputs = document.querySelectorAll('input[name="duration"]');
  const startDateInput = document.getElementById("startDate");
  const endDateInput = document.getElementById("endDate");

  function updateRentalDates(eventDate) {
    if (!eventDate) return;

    let duration = 3; // default duration
    durationInputs.forEach((input) => {
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
    return date.toLocaleDateString("en-US", {
      year: "numeric",
      month: "long",
      day: "numeric",
    });
  }

  // Update dates when duration changes
  durationInputs.forEach((input) => {
    input.addEventListener("change", () => {
      const eventDate = eventDatePicker.selectedDates[0];
      if (eventDate) {
        updateRentalDates(eventDate);
      }
    });
  });

  // Size Chart Functionality
  function showSizeChart() {
    // Implement size chart modal or popup here
    alert("Size Chart will be displayed here");
  }

  const proceedButton = document.getElementById('proceedButton');
  const heightInput = document.getElementById('height');
  const shoulderInput = document.getElementById('shoulder');
  const bustInput = document.getElementById('bust');
  const waistInput = document.getElementById('waist');
  const outfitIdElement = document.getElementById('outfitId');
  const outfitId = outfitIdElement ? outfitIdElement.value : null;

  // Function to check if all fields are filled
  function checkFields() {
    const allFieldsFilled = 
      heightInput.value && 
      shoulderInput.value && 
      bustInput.value && 
      waistInput.value && 
      startDateInput.value && 
      endDateInput.value;

    proceedButton.disabled = !allFieldsFilled;
  }

  // Add event listeners to all inputs
  [heightInput, shoulderInput, bustInput, waistInput, startDateInput, endDateInput].forEach(input => {
    if (input) {
      input.addEventListener('input', checkFields);
      input.addEventListener('change', checkFields);
    }
  });

  // Handle form submission
  if (proceedButton) {
    proceedButton.addEventListener('click', function(e) {
      e.preventDefault();

      // Create a form
      const form = document.createElement('form');
      form.method = 'POST';
      form.action = 'process_measurements.php';

      // Add all the measurement data
      const formData = {
        outfit_id: outfitId,
        height: heightInput.value,
        shoulder: shoulderInput.value,
        bust: bustInput.value,
        waist: waistInput.value,
        start_date: startDateInput.value,
        end_date: endDateInput.value
      };

      // Add each field to the form
      for (const [key, value] of Object.entries(formData)) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = key;
        input.value = value;
        form.appendChild(input);
      }

      // Submit the form
      document.body.appendChild(form);
      form.submit();
    });
  }
});
