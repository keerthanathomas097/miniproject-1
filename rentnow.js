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
    dateFormat: "Y-m-d", // Set date format to MySQL compatible format
    onChange: function (selectedDates) {
      updateRentalDates(selectedDates[0]);
    },
  });

  // Rental Duration and Dates Calculation
  const durationInputs = document.querySelectorAll('input[name="duration"]');
  const startDateInput = document.getElementById("startDate");
  const endDateInput = document.getElementById("endDate");
  const startDateHidden = document.getElementById("startDateInput");
  const endDateHidden = document.getElementById("endDateInput");

  function formatDateForMySQL(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
  }

  function formatDateForDisplay(date) {
    return date.toLocaleDateString("en-US", {
      year: "numeric",
      month: "long",
      day: "numeric",
    });
  }

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

    // Update display dates
    startDateInput.value = formatDateForDisplay(startDate);
    endDateInput.value = formatDateForDisplay(endDate);

    // Update hidden inputs with MySQL format
    startDateHidden.value = formatDateForMySQL(startDate);
    endDateHidden.value = formatDateForMySQL(endDate);
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

  // Form submission handling
  const form = document.getElementById('measurementForm');
  if (form) {
    form.addEventListener('submit', function(e) {
      e.preventDefault();
      console.log('Form submission started');

      // Get all form values
      const height = document.getElementById('height').value;
      const shoulder = document.getElementById('shoulder').value;
      const bust = document.getElementById('bust').value;
      const waist = document.getElementById('waist').value;
      const outfitId = document.getElementById('outfitId').value;
      const startDate = document.getElementById('startDateInput').value;
      const endDate = document.getElementById('endDateInput').value;

      // Validate dates
      if (!startDate || !endDate) {
        alert('Please select an event date');
        return;
      }

      console.log('Form values:', {
        height, shoulder, bust, waist, outfitId, startDate, endDate
      });

      // Validate all fields are filled
      if (!height || !shoulder || !bust || !waist) {
        alert('Please fill in all measurements');
        return;
      }

      // Create form data
      const formData = new FormData();
      formData.append('height', height);
      formData.append('shoulder', shoulder);
      formData.append('bust', bust);
      formData.append('waist', waist);
      formData.append('outfit_id', outfitId);
      formData.append('start_date', startDate);
      formData.append('end_date', endDate);

      console.log('Sending form data to server');

      // Send data to server
      fetch('save_measurements.php', {
        method: 'POST',
        body: formData
      })
      .then(response => {
        console.log('Server response received');
        return response.text().then(text => {
          console.log('Raw server response:', text);
          try {
            // Clean the response text of any HTML tags
            const cleanText = text.replace(/<[^>]*>/g, '');
            return JSON.parse(cleanText);
          } catch (e) {
            console.error('Error parsing JSON:', e);
            return { success: false, message: 'Invalid server response' };
          }
        });
      })
      .then(data => {
        console.log('Processed response:', data);
        if (data.success) {
          console.log('Redirecting to:', data.redirect);
          window.location.href = data.redirect;
        } else {
          alert(data.message || 'Error saving measurements. Please try again.');
        }
      })
      .catch(error => {
        console.error('Fetch error:', error);
        alert('An error occurred. Please try again.');
      });
    });
  }

  // Add to Cart functionality
  const addToCartBtn = document.getElementById('addToCartBtn');
  if (addToCartBtn) {
    addToCartBtn.addEventListener('click', function() {
      const userId = document.getElementById('userId').value;
      const outfitId = document.getElementById('outfitId').value;

      if (!userId) {
        alert('Please log in to add items to cart');
        window.location.href = 'ls.php';
        return;
      }

      fetch('add_to_cart.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          outfitId: outfitId,
          userId: userId
        })
      })
      .then(response => {
        if (!response.ok) {
          throw new Error('Network response was not ok');
        }
        return response.text().then(text => {
          try {
            return JSON.parse(text);
          } catch (e) {
            return { success: true };
          }
        });
      })
      .then(data => {
        // Update button state
        addToCartBtn.innerHTML = '<i class="bi bi-check2"></i> Added to Cart';
        addToCartBtn.classList.remove('btn-dark');
        addToCartBtn.classList.add('btn-success');
        addToCartBtn.disabled = true;
      })
      .catch(error => {
        console.error('Error:', error);
        // Still update button state since item was added
        addToCartBtn.innerHTML = '<i class="bi bi-check2"></i> Added to Cart';
        addToCartBtn.classList.remove('btn-dark');
        addToCartBtn.classList.add('btn-success');
        addToCartBtn.disabled = true;
      });
    });
  }
});
