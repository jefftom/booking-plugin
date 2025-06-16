(function($) {
    'use strict';

    class BookingCalendarV3 {
        constructor(container) {
            this.container = container;
            this.currentOffset = 0;
            this.monthsToShow = 2;
            this.selectedWeeks = [];
            this.pricingData = {};
            this.bookedDates = [];
            this.startDayNumber = 0; // Default to Sunday
            this.maxWeeks = 8; // Maximum weeks that can be selected
            
            this.init();
        }

        init() {
            // Get configuration from data attributes
            const widgetData = $(this.container).data();
            const startDay = widgetData.startDay || br_calendar.start_day || 'sunday';
            
            // Convert day name to number
            this.startDayNumber = this.getDayNumber(startDay);
            
            console.log('Calendar initialized with start day:', this.startDayNumber, 'from widget:', startDay);
            
            this.loadCalendarData();
            this.bindEvents();
        }

        getDayNumber(dayName) {
            const days = {
                'sunday': 0,
                'monday': 1,
                'tuesday': 2,
                'wednesday': 3,
                'thursday': 4,
                'friday': 5,
                'saturday': 6
            };
            
            if (typeof dayName === 'number') {
                return dayName;
            }
            
            return days[dayName.toLowerCase()] || 0;
        }

        loadCalendarData() {
            const self = this;
            const startDate = new Date();
            const endDate = new Date();
            endDate.setMonth(endDate.getMonth() + 12);

            $.ajax({
                url: br_calendar.ajax_url,
                type: 'POST',
                data: {
                    action: 'br_get_calendar_data',
                    nonce: br_calendar.nonce,
                    start_date: startDate.toISOString().split('T')[0],
                    end_date: endDate.toISOString().split('T')[0]
                },
                success: function(response) {
                    if (response.success) {
                        self.pricingData = response.data.pricing_data || {};
                        self.bookedDates = response.data.booked_dates || [];
                        self.startDayNumber = response.data.start_day || self.startDayNumber;
                        console.log('Calendar data loaded. Start day:', self.startDayNumber);
                        self.renderCalendar();
                    } else {
                        self.showError(response.data || 'Failed to load calendar data');
                    }
                },
                error: function() {
                    self.showError('Failed to load calendar data');
                }
            });
        }

        renderCalendar() {
            const calendarHTML = `
                <div class="br-calendar-header">
                    <h2>${br_calendar.strings.select_week}</h2>
                    <p>Select your check-in date (${this.getDayName(this.startDayNumber)})</p>
                </div>
                <div class="br-month-navigation">
                    <button class="br-nav-button prev-month">Previous Month</button>
                    <span class="br-current-months"></span>
                    <button class="br-nav-button next-month">Next Month</button>
                </div>
                <div class="br-calendar-container"></div>
                <div class="br-selected-weeks" style="display:none;">
                    <h3>Selected Weeks</h3>
                    <div class="br-selected-weeks-list"></div>
                    <div class="br-total-price"></div>
                </div>
                <div class="br-booking-form" style="display:none;">
                    <h3>Guest Information</h3>
                    <form class="br-booking-form-inner">
                        <div class="br-form-row">
                            <div class="br-form-group">
                                <label for="br-first-name">First Name *</label>
                                <input type="text" id="br-first-name" name="first_name" required>
                            </div>
                            <div class="br-form-group">
                                <label for="br-last-name">Last Name *</label>
                                <input type="text" id="br-last-name" name="last_name" required>
                            </div>
                        </div>
                        <div class="br-form-row">
                            <div class="br-form-group">
                                <label for="br-email">Email *</label>
                                <input type="email" id="br-email" name="email" required>
                            </div>
                            <div class="br-form-group">
                                <label for="br-phone">Phone *</label>
                                <input type="tel" id="br-phone" name="phone" required>
                            </div>
                        </div>
                        <div class="br-form-group">
                            <label for="br-message">Message (Optional)</label>
                            <textarea id="br-message" name="message"></textarea>
                        </div>
                        <button type="submit" class="br-submit-button">Submit Booking Request</button>
                    </form>
                </div>
            `;

            $(this.container).html(calendarHTML);
            this.updateMonthDisplay();
        }

        updateMonthDisplay() {
            const today = new Date();
            const startMonth = new Date(today.getFullYear(), today.getMonth() + this.currentOffset, 1);
            const endMonth = new Date(today.getFullYear(), today.getMonth() + this.currentOffset + this.monthsToShow - 1, 1);

            // Update navigation
            const navText = startMonth.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
            const endText = endMonth.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
            
            if (startMonth.getMonth() === endMonth.getMonth() && startMonth.getFullYear() === endMonth.getFullYear()) {
                $(this.container).find('.br-current-months').text(navText);
            } else if (startMonth.getFullYear() === endMonth.getFullYear()) {
                $(this.container).find('.br-current-months').text(
                    `${startMonth.toLocaleDateString('en-US', { month: 'long' })} - ${endText}`
                );
            } else {
                $(this.container).find('.br-current-months').text(`${navText} - ${endText}`);
            }

            // Disable prev button if at start
            $(this.container).find('.prev-month').prop('disabled', this.currentOffset <= 0);

            this.renderMonths();
        }

        renderMonths() {
            const calendarContainer = $(this.container).find('.br-calendar-container')[0];
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            let monthsHTML = '<div class="br-months-wrapper">';

            for (let i = 0; i < this.monthsToShow; i++) {
                const monthOffset = this.currentOffset + i;
                const monthDate = new Date(today.getFullYear(), today.getMonth() + monthOffset, 1);
                monthsHTML += this.renderMonth(monthDate, today);
            }

            monthsHTML += '</div>';
            
            // Add legend
            monthsHTML += `
                <div class="br-calendar-legend">
                    <div class="br-legend-item">
                        <div class="br-legend-color available"></div>
                        <span>Available</span>
                    </div>
                    <div class="br-legend-item">
                        <div class="br-legend-color selected"></div>
                        <span>Selected</span>
                    </div>
                    <div class="br-legend-item">
                        <div class="br-legend-color pending"></div>
                        <span>Pending</span>
                    </div>
                    <div class="br-legend-item">
                        <div class="br-legend-color booked"></div>
                        <span>Booked</span>
                    </div>
                </div>
            `;
            
            calendarContainer.innerHTML = monthsHTML;

            // Add click handlers
            this.attachDayClickHandlers();
            
            // Highlight selected weeks
            this.selectedWeeks.forEach(week => {
                const startDate = new Date(week.start);
                const endDate = new Date(week.end);
                
                // Highlight all 7 days of the selected week
                let currentDate = new Date(startDate);
                while (currentDate <= endDate) {
                    const dayElement = this.container.querySelector(`[data-date="${currentDate.toISOString().split('T')[0]}"]`);
                    if (dayElement) {
                        dayElement.classList.add('br-selected');
                    }
                    currentDate.setDate(currentDate.getDate() + 1);
                }
            });

            // Add pending status styling
            this.bookedDates.forEach(dateInfo => {
                if (typeof dateInfo === 'object' && dateInfo.status === 'pending') {
                    const dayElement = this.container.querySelector(`[data-date="${dateInfo.date}"]`);
                    if (dayElement) {
                        dayElement.classList.remove('br-booked');
                        dayElement.classList.add('br-pending');
                    }
                } else {
                    // Legacy support for simple date strings
                    const dayElement = this.container.querySelector(`[data-date="${dateInfo}"]`);
                    if (dayElement) {
                        dayElement.classList.add('br-booked');
                    }
                }
            });
        }

        renderMonth(monthDate, today) {
            const year = monthDate.getFullYear();
            const month = monthDate.getMonth();
            const firstDay = new Date(year, month, 1);
            const lastDay = new Date(year, month + 1, 0);
            const startingDayOfWeek = firstDay.getDay();
            
            let html = '<div class="br-month">';
            html += `<div class="br-month-header">${monthDate.toLocaleDateString('en-US', { month: 'long', year: 'numeric' })}</div>`;
            html += '<div class="br-calendar-grid">';
            
            // Day headers
            const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            dayNames.forEach(day => {
                html += `<div class="br-day-name">${day}</div>`;
            });
            
            // Empty cells for days before month starts
            for (let i = 0; i < startingDayOfWeek; i++) {
                const prevMonthDay = new Date(year, month, -startingDayOfWeek + i + 1);
                html += this.renderDay(prevMonthDay, today, true);
            }
            
            // Days of the month
            for (let day = 1; day <= lastDay.getDate(); day++) {
                const currentDate = new Date(year, month, day);
                html += this.renderDay(currentDate, today, false);
            }
            
            // Fill remaining cells
            const totalCells = startingDayOfWeek + lastDay.getDate();
            const remainingCells = totalCells % 7 === 0 ? 0 : 7 - (totalCells % 7);
            for (let i = 1; i <= remainingCells; i++) {
                const nextMonthDay = new Date(year, month + 1, i);
                html += this.renderDay(nextMonthDay, today, true);
            }
            
            html += '</div></div>';
            return html;
        }

        renderDay(date, today, isOtherMonth) {
            const dateStr = date.toISOString().split('T')[0];
            const dayOfWeek = date.getDay();
            const isPast = date < today;
            const isStartDay = dayOfWeek === this.startDayNumber;
            const pricingInfo = this.pricingData[dateStr];
            
            let classes = ['br-calendar-day'];
            if (isOtherMonth) classes.push('br-other-month');
            if (isPast) classes.push('br-past');
            if (isStartDay && !isPast && pricingInfo && pricingInfo.available) {
                classes.push('br-week-start', 'br-week-available');
            }
            
            let html = `<div class="${classes.join(' ')}" data-date="${dateStr}">`;
            html += `<div class="br-day-number">${date.getDate()}</div>`;
            
            if (isStartDay && pricingInfo && !isPast && !isOtherMonth) {
                const formattedPrice = br_calendar.currency_symbol + 
                    new Intl.NumberFormat('en-US').format(pricingInfo.rate);
                html += `<div class="br-day-price">${formattedPrice}</div>`;
            }
            
            html += '</div>';
            return html;
        }

        getDayName(dayNumber) {
            const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            return days[dayNumber];
        }

        attachDayClickHandlers() {
            const self = this;
            $(this.container).find('.br-week-start').on('click', function() {
                const dateStr = $(this).data('date');
                self.handleWeekSelection(dateStr);
            });
        }

        handleWeekSelection(startDateStr) {
            const startDate = new Date(startDateStr);
            const endDate = new Date(startDate);
            endDate.setDate(endDate.getDate() + 6); // 6 nights
            
            const weekKey = startDateStr;
            const existingIndex = this.selectedWeeks.findIndex(w => w.start === weekKey);
            
            if (existingIndex !== -1) {
                // Remove week if already selected
                this.selectedWeeks.splice(existingIndex, 1);
            } else {
                // Check if we've reached the maximum
                if (this.selectedWeeks.length >= this.maxWeeks) {
                    this.showError(`You can select a maximum of ${this.maxWeeks} weeks`);
                    return;
                }
                
                // Add week
                const pricingInfo = this.pricingData[startDateStr];
                if (pricingInfo && pricingInfo.available) {
                    this.selectedWeeks.push({
                        start: startDateStr,
                        end: endDate.toISOString().split('T')[0],
                        rate: pricingInfo.rate
                    });
                    
                    // Sort by date
                    this.selectedWeeks.sort((a, b) => new Date(a.start) - new Date(b.start));
                } else {
                    this.showError(br_calendar.strings.week_unavailable);
                    return;
                }
            }
            
            this.renderMonths();
            this.updateSelectedWeeksSummary();
        }

        updateSelectedWeeksSummary() {
            const container = $(this.container).find('.br-selected-weeks');
            const list = container.find('.br-selected-weeks-list');
            
            if (this.selectedWeeks.length === 0) {
                container.hide();
                $(this.container).find('.br-booking-form').hide();
                return;
            }
            
            container.show();
            $(this.container).find('.br-booking-form').show();
            
            let html = '';
            let totalPrice = 0;
            
            this.selectedWeeks.forEach((week, index) => {
                const startDate = new Date(week.start);
                const endDate = new Date(week.end);
                const formattedStart = startDate.toLocaleDateString('en-US', { 
                    weekday: 'short', 
                    month: 'short', 
                    day: 'numeric',
                    year: 'numeric'
                });
                const formattedEnd = endDate.toLocaleDateString('en-US', { 
                    weekday: 'short', 
                    month: 'short', 
                    day: 'numeric',
                    year: 'numeric'
                });
                const formattedPrice = br_calendar.currency_symbol + 
                    new Intl.NumberFormat('en-US').format(week.rate);
                
                html += `
                    <div class="br-week-item">
                        <span class="br-week-dates">${formattedStart} - ${formattedEnd}</span>
                        <span class="br-week-price">${formattedPrice}</span>
                        <button class="br-remove-week" data-index="${index}">×</button>
                    </div>
                `;
                
                totalPrice += week.rate;
            });
            
            list.html(html);
            
            const formattedTotal = br_calendar.currency_symbol + 
                new Intl.NumberFormat('en-US').format(totalPrice);
            container.find('.br-total-price').html(`Total: ${formattedTotal}`);
            
            // Bind remove handlers
            const self = this;
            container.find('.br-remove-week').on('click', function() {
                const index = $(this).data('index');
                self.selectedWeeks.splice(index, 1);
                self.renderMonths();
                self.updateSelectedWeeksSummary();
            });
        }

        bindEvents() {
            const self = this;
            
            // Navigation
            $(this.container).on('click', '.prev-month', function() {
                if (self.currentOffset > 0) {
                    self.currentOffset -= self.monthsToShow;
                    self.updateMonthDisplay();
                }
            });
            
            $(this.container).on('click', '.next-month', function() {
                self.currentOffset += self.monthsToShow;
                self.updateMonthDisplay();
            });
            
            // Form submission
            $(this.container).on('submit', '.br-booking-form-inner', function(e) {
                e.preventDefault();
                self.submitBooking();
            });
        }

        submitBooking() {
            const self = this;
            const form = $(this.container).find('.br-booking-form-inner');
            const submitButton = form.find('.br-submit-button');
            
            // Disable submit button
            submitButton.prop('disabled', true).text('Processing...');
            
            // Get first and last dates from selected weeks
            const firstWeek = this.selectedWeeks[0];
            const lastWeek = this.selectedWeeks[this.selectedWeeks.length - 1];
            
            // Prepare data
            const formData = {
                action: 'br_submit_calendar_booking',
                nonce: br_calendar.nonce,
                first_name: form.find('#br-first-name').val(),
                last_name: form.find('#br-last-name').val(),
                email: form.find('#br-email').val(),
                phone: form.find('#br-phone').val(),
                message: form.find('#br-message').val(),
                checkin_date: firstWeek.start,
                checkout_date: lastWeek.end,
                weeks_data: JSON.stringify(this.selectedWeeks),
                total_price: this.selectedWeeks.reduce((sum, week) => sum + week.rate, 0)
            };
            
            $.ajax({
                url: br_calendar.ajax_url,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        self.showSuccess(response.data.message || br_calendar.strings.booking_submitted);
                        // Reset form and selection
                        form[0].reset();
                        self.selectedWeeks = [];
                        self.renderMonths();
                        self.updateSelectedWeeksSummary();
                        // Reload calendar data to show new booking
                        setTimeout(() => self.loadCalendarData(), 2000);
                    } else {
                        self.showError(response.data || br_calendar.strings.error);
                    }
                },
                error: function() {
                    self.showError(br_calendar.strings.error);
                },
                complete: function() {
                    submitButton.prop('disabled', false).text('Submit Booking Request');
                }
            });
        }

        showError(message) {
            this.showMessage(message, 'error');
        }

        showSuccess(message) {
            this.showMessage(message, 'success');
        }

        showMessage(message, type) {
            // Remove any existing messages
            $(this.container).find('.br-message').remove();
            
            const messageHtml = `<div class="br-message ${type}">${message}</div>`;
            $(this.container).prepend(messageHtml);
            
            // Scroll to message
            $('html, body').animate({
                scrollTop: $(this.container).offset().top - 100
            }, 300);
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                $(this.container).find('.br-message').fadeOut(() => {
                    $(this.container).find('.br-message').remove();
                });
            }, 5000);
        }
    }

    // Initialize all calendar widgets on page
    $(document).ready(function() {
        $('.br-calendar-widget').each(function() {
            new BookingCalendarV3(this);
        });
    });

})(jQuery);