document.addEventListener("DOMContentLoaded", function () {
    const calendarContainer = document.getElementById("calendar");
    const selectedDateEl = document.getElementById("selected-date");
    const selectedMonthEl = document.getElementById("selected-month");
    const selectedDayEl = document.getElementById("selected-day");
    const appointmentsList = document.getElementById("appointments-list");

    let currentYear = new Date().getFullYear();
    let currentMonth = new Date().getMonth();
    const today = new Date();
    let selectedDate = today;

    // Generate the calendar
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

            // Disable past dates
            const todayMidnight = new Date(today.getFullYear(), today.getMonth(), today.getDate());
            // Disable weekends (Saturday=6, Sunday=0)
            const isWeekend = currentDate.getDay() === 0 || currentDate.getDay() === 6;
            if (currentDate < todayMidnight || isWeekend) {
                className += " disabled";
            }
            if (currentDate.toDateString() === today.toDateString()) {
                className += " today"; // Highlight today's date
            }
            if (currentDate.toDateString() === selectedDate.toDateString() && currentDate.toDateString() !== today.toDateString()) {
                className += " selected"; // Highlight selected date
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
    }

    function updateSelectedDate(date) {
        let selectedDateObj = new Date(date);

        // Update displayed date format
        selectedDateEl.textContent = selectedDateObj.getDate().toString().padStart(2, "0");
        selectedMonthEl.textContent = `${(selectedDateObj.getMonth() + 1).toString().padStart(2, "0")}/${selectedDateObj.getFullYear()}`;
        selectedDayEl.textContent = new Intl.DateTimeFormat('en-US', { weekday: 'long' }).format(selectedDateObj);

        // Fetch new appointments for selected date
        fetchAppointments(date);

        // Dispatch custom event for external listeners
        const event = new CustomEvent('calendarDateChanged', { detail: { date: date } });
        document.dispatchEvent(event);
    }
    window.updateSelectedDate = updateSelectedDate;

    function fetchAppointments(date) {
        console.log("Fetching appointments for date: " + date); // Debug: Check the date being passed
        appointmentsList.innerHTML = "<tr><td colspan='3' class='text-center'>Loading...</td></tr>";
        $.ajax({
            url: 'fetch_appointments.php',
            method: 'GET',
            data: { date: date },
            success: function (response) {
                console.log("Appointments Data: ", response); // Debug: Log the response
                appointmentsList.innerHTML = response;
            },
            error: function () {
                console.error("Error fetching appointments.");
                appointmentsList.innerHTML = "<tr><td colspan='3' class='text-center'>Error loading appointments.</td></tr>";
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
