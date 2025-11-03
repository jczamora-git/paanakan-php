document.addEventListener("DOMContentLoaded", function () {
    const calendarContainer = document.getElementById("calendar");
    const selectedDateEl = document.getElementById("selected-date");
    const selectedDayEl = document.getElementById("selected-day");
    const appointmentsList = document.getElementById("appointments-list");
    const prevMonthBtn = document.getElementById("prevMonth");  // Reference to the prev button

    let currentYear = new Date().getFullYear();
    let currentMonth = new Date().getMonth();
    const today = new Date();
    let selectedDate = today;

    // Function to check if the "Previous" button should be shown
    function updatePrevButtonVisibility() {
        // Ensure that the prevMonthBtn exists
        if (prevMonthBtn) {
            // If the current displayed month is the current month, hide the "Previous" button
            if (currentYear === today.getFullYear() && currentMonth === today.getMonth()) {
                prevMonthBtn.style.display = "none"; // Hide the prev button if we're in the current month
            } else {
                prevMonthBtn.style.display = "inline-block"; // Otherwise, show it
            }
        }
    }

    function generateCalendar(year, month) {
        const firstDay = new Date(year, month, 1).getDay();
        const lastDate = new Date(year, month + 1, 0).getDate();
        const prevLastDate = new Date(year, month, 0).getDate();

        let calendarHTML = `
            <div class="calendar-header">
                <span class="month-year">${new Intl.DateTimeFormat('en-US', { month: 'long', year: 'numeric' }).format(new Date(year, month))}</span>
                <div class="nav-buttons">
                    <button id="prevMonth">❮</button>
                    <button id="nextMonth">❯</button>
                </div>
            </div>`;

        calendarHTML += `<div class="calendar-grid">`;

        // Week Days
        const daysOfWeek = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        daysOfWeek.forEach(day => {
            calendarHTML += `<div class="week-day">${day}</div>`;
        });

        // Previous month's dates
        for (let i = firstDay; i > 0; i--) {
            calendarHTML += `<div class="calendar-day disabled">${prevLastDate - i + 1}</div>`;
        }

        // Current month's dates
        for (let i = 1; i <= lastDate; i++) {
            let className = "calendar-day";
            const currentDate = new Date(year, month, i);
            const currentDayOfWeek = currentDate.getDay(); // Get the day of the week (0 = Sunday, 6 = Saturday)

            // Disable Saturdays (6) and Sundays (0), except for the current day if it's a weekend
            if (currentDayOfWeek === 0 || currentDayOfWeek === 6) {
                if (currentDate.toDateString() !== today.toDateString()) {
                    className += " disabled"; // Disable weekends, except today
                }
            }

            // Disable past days
            if (currentDate < today) {
                className += " disabled"; // Disable past days
            }

            if (currentDate.toDateString() === today.toDateString()) {
                className += " today"; // Today's Date (Filled Green)
            }

            if (currentDate.toDateString() === selectedDate.toDateString() && currentDate.toDateString() !== today.toDateString()) {
                className += " selected"; // Selected Date (Outlined Green)
            }

            calendarHTML += `<div class="${className}" data-date="${year}-${(month + 1).toString().padStart(2, "0")}-${i.toString().padStart(2, "0")}">${i}</div>`;
        }

        calendarHTML += `</div>`;
        calendarContainer.innerHTML = calendarHTML;

        // Attach Event Listeners to Month Change Buttons
        document.getElementById("prevMonth").addEventListener("click", () => changeMonth(-1));
        document.getElementById("nextMonth").addEventListener("click", () => changeMonth(1));

        // Add click event listeners to all dates
        document.querySelectorAll(".calendar-day").forEach(day => {
            if (!day.classList.contains("disabled")) {
                day.addEventListener("click", function () {
                    document.querySelectorAll(".calendar-day").forEach(d => d.classList.remove("selected"));
                    this.classList.add("selected");
                    selectedDate = new Date(this.dataset.date);
                    updateSelectedDate(this.dataset.date);
                });
            }
        });

        // Update visibility of the "Previous" button based on current month
        updatePrevButtonVisibility();
    }

    function updateSelectedDate(date) {
        let selectedDateObj = new Date(date);

        // Update the large number
        document.getElementById("selected-date").textContent = selectedDateObj.getDate();

        // Update the Month/Year and Day
        document.getElementById("selected-month").textContent = `${String(selectedDateObj.getMonth() + 1).padStart(2, "0")}/${selectedDateObj.getFullYear()}`;
        document.getElementById("selected-day").textContent = new Intl.DateTimeFormat('en-US', { weekday: 'long' }).format(selectedDateObj);

        // Fetch available slots for the selected date
        fetchAvailableSlots(date);
    }

    function fetchAvailableSlots(date) {
        // Show loading message
        appointmentsList.innerHTML = "<tr><td colspan='4' class='text-center'>Loading...</td></tr>";
        
        // Make AJAX request to fetch available slots for the selected date
        $.ajax({
            url: 'fetch_available_slots.php',
            method: 'GET',
            data: { date: date },
            success: function (response) {
                appointmentsList.innerHTML = response;
            },
            error: function () {
                console.error("Error fetching available slots.");
                appointmentsList.innerHTML = "<tr><td colspan='4' class='text-center'>Error loading available slots.</td></tr>";
            }
        });
    }

    function changeMonth(step) {
        currentMonth += step;
        if (currentMonth < 0) {
            currentMonth = 11;
            currentYear--;
        } else if (currentMonth > 11) {
            currentMonth = 0;
            currentYear++;
        }
        generateCalendar(currentYear, currentMonth);
    }

    // Initialize the calendar
    generateCalendar(currentYear, currentMonth);
});
