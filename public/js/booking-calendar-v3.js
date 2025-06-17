(function($) {
    'use strict';

    class BookingCalendarV3 {
        constructor(container) {
            this.container = container;
            this.currentOffset = 0;
            this.monthsToShow = 2;
            this.selectedDates = [];
            this.pricingData = {};
            this.bookedDates = [];
            this.startDayNumber = 0; // Default to Sunday
            this.minNights = 3; // Minimum 3 nights
            this.selectedCheckIn = null;
            this.selectedCheckOut = null;
            
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
                    <h2>Select Your Stay</h2>
                    <p>Choose your check-in and check-out dates (minimum ${this.minNights} nights)</p>
                </div>
                <div class="br-month-navigation">
                    <button class="br-nav-button prev-month">Previous Month</button>
                    <span class="br-current-months"></span>
                    <button class="br-nav-button next-month">Next Month</button>
                </div>
                <div class="br-calendar-container"></div>
                <div class="br-selected-dates">
                    <h3>Selected Times</h3>
                    <div class="br-selected-dates-info">
                        <p class="br-checkin-info">Check-in: <span class="br-checkin-date">Not selected</span></p>
                        <p class="br-checkout-info">Check-out: <span class="br-checkout-date">Not selected</span></p>
                        <p class="br-nights-info">Nights: <span class="br-nights-count">0</span></p>
                        <p class="br-total-price-info">Total Price: <span class="br-total-amount">€0</span></p>
                    </div>
                </div>
                <div class="br-booking-form">
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
                        
                        <div class="br-additional-services">
                            <h4>Additional Services</h4>
                            <p class="br-services-note">Select all that apply. Prices are provided upon request.</p>
                            <div class="br-service-options">
                                <label class="br-checkbox-label">
                                    <input type="checkbox" name="services[]" value="taxi_service">
                                    <span>Taxi service from and to the airport</span>
                                </label>
                                <label class="br-checkbox-label">
                                    <input type="checkbox" name="services[]" value="greeting_service">
                                    <span>Greeting service – to welcome you to the Villa and overview the region</span>
                                </label>
                                <label class="br-checkbox-label">
                                    <input type="checkbox" name="services[]" value="welcome_hamper">
                                    <span>Welcome hamper on arrival</span>
                                </label>
                                <label class="br-checkbox-label">
                                    <input type="checkbox" name="services[]" value="sailing">
                                    <span>Sailing</span>
                                </label>
                                <label class="br-checkbox-label">
                                    <input type="checkbox" name="services[]" value="motorboat_hire">
                                    <span>Motorboat hire</span>
                                </label>
                                <label class="br-checkbox-label">
                                    <input type="checkbox" name="services[]" value="flight_bookings">
                                    <span>Flight bookings (from the UK)</span>
                                </label>
                                <label class="br-checkbox-label">
                                    <input type="checkbox" name="services[]" value="car_hire">
                                    <span>Car hire booking service</span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="br-submit-wrapper">
                            <button type="submit" class="br-submit-button">Submit Booking Request</button>
                        </div>
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
                        <div class="br-legend-color booked"></div>
                        <span>Booked</span>
                    </div>
                </div>
            `;
            
            calendarContainer.innerHTML = monthsHTML;

            // Add click handlers
            this.attachDayClickHandlers();
            
            // Highlight selected dates
            if (this.selectedCheckIn && this.selectedCheckOut) {
                this.highlightDateRange(this.selectedCheckIn, this.selectedCheckOut);
            } else if (this.selectedCheckIn) {
                const dayElement = this.container.querySelector(`[data-date="${this.selectedCheckIn}"]`);
                if (dayElement) {
                    dayElement.classList.add('br-selected', 'br-checkin');
                }
            }

            // Mark booked dates
            this.bookedDates.forEach(dateInfo => {
                const date = typeof dateInfo === 'object' ? dateInfo.date : dateInfo;
                const dayElement = this.container.querySelector(`[data-date="${date}"]`);
                if (dayElement) {
                    dayElement.classList.add('br-booked');
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
            const isPast = date < today;
            const isBooked = this.bookedDates.includes(dateStr);
            
            let classes = ['br-calendar-day'];
            if (isOtherMonth) classes.push('br-other-month');
            if (isPast) classes.push('br-past');
            if (!isPast && !isBooked && !isOtherMonth) classes.push('br-available');
            
            // Get pricing for this date
            const pricingInfo = this.pricingData[dateStr];
            
            let html = `<div class="${classes.join(' ')}" data-date="${dateStr}">`;
            html += `<div class="br-day-number">${date.getDate()}</div>`;
            
            // Show daily price if available and not past/other month/booked
            if (pricingInfo && !isPast && !isOtherMonth && !isBooked) {
                const dailyPrice = br_calendar.currency_symbol + 
                    new Intl.NumberFormat('de-DE').format(Math.round(pricingInfo.daily_rate));
                html += `<div class="br-day-price">${dailyPrice}</div>`;
            }
            
            html += '</div>';
            return html;
        }

        attachDayClickHandlers() {
            const self = this;
            $(this.container).find('.br-calendar-day.br-available').on('click', function() {
                const dateStr = $(this).data('date');
                self.handleDateSelection(dateStr);
            });
        }

        handleDateSelection(dateStr) {
            // Ensure we're working with the correct date (not shifted)
            const selectedDate = new Date(dateStr + 'T00:00:00');
            const actualDateStr = selectedDate.toISOString().split('T')[0];
            
            if (!this.selectedCheckIn || (this.selectedCheckIn && this.selectedCheckOut)) {
                // First selection or resetting
                this.selectedCheckIn = actualDateStr;
                this.selectedCheckOut = null;
                this.selectedDates = [actualDateStr];
            } else {
                // Second selection
                const checkInDate = new Date(this.selectedCheckIn + 'T00:00:00');
                const checkOutDate = new Date(actualDateStr + 'T00:00:00');
                
                if (checkOutDate <= checkInDate) {
                    // If selected date is before or same as check-in, reset
                    this.selectedCheckIn = actualDateStr;
                    this.selectedCheckOut = null;
                    this.selectedDates = [actualDateStr];
                } else {
                    // Valid check-out date
                    const nights = Math.round((checkOutDate - checkInDate) / (1000 * 60 * 60 * 24));
                    
                    if (nights < this.minNights) {
                        this.showError(`Minimum stay is ${this.minNights} nights. You selected ${nights} night${nights > 1 ? 's' : ''}.`);
                        return;
                    }
                    
                    // Check if any dates in range are booked
                    const datesInRange = this.getDatesInRange(checkInDate, checkOutDate);
                    const hasBookedDates = datesInRange.some(date => this.bookedDates.includes(date));
                    
                    if (hasBookedDates) {
                        this.showError('Some dates in your selection are already booked. Please choose different dates.');
                        return;
                    }
                    
                    this.selectedCheckOut = actualDateStr;
                    this.selectedDates = datesInRange;
                }
            }
            
            this.renderMonths();
            this.updateSelectedDatesInfo();
        }

        getDatesInRange(startDate, endDate) {
            const dates = [];
            const current = new Date(startDate);
            
            while (current < endDate) {
                dates.push(current.toISOString().split('T')[0]);
                current.setDate(current.getDate() + 1);
            }
            
            return dates;
        }

        highlightDateRange(startDateStr, endDateStr) {
            const startDate = new Date(startDateStr + 'T00:00:00');
            const endDate = new Date(endDateStr + 'T00:00:00');
            const current = new Date(startDate);
            
            while (current < endDate) {
                const dateStr = current.toISOString().split('T')[0];
                const dayElement = this.container.querySelector(`[data-date="${dateStr}"]`);
                if (dayElement) {
                    dayElement.classList.add('br-selected');
                    if (dateStr === startDateStr) {
                        dayElement.classList.add('br-checkin');
                    }
                }
                current.setDate(current.getDate() + 1);
            }
            
            // Handle checkout date separately to ensure it's marked correctly
            const checkoutDateStr = endDateStr;
            const checkoutElement = this.container.querySelector(`[data-date="${checkoutDateStr}"]`);
            if (checkoutElement) {
                checkoutElement.classList.add('br-selected', 'br-checkout');
            }
        }

        updateSelectedDatesInfo() {
            const container = $(this.container);
            
            if (this.selectedCheckIn) {
                const checkInDate = new Date(this.selectedCheckIn + 'T00:00:00');
                container.find('.br-checkin-date').text(checkInDate.toLocaleDateString('en-US', { 
                    weekday: 'short', 
                    month: 'short', 
                    day: 'numeric',
                    year: 'numeric'
                }));
            } else {
                container.find('.br-checkin-date').text('Not selected');
            }
            
            if (this.selectedCheckOut) {
                const checkOutDate = new Date(this.selectedCheckOut + 'T00:00:00');
                container.find('.br-checkout-date').text(checkOutDate.toLocaleDateString('en-US', { 
                    weekday: 'short', 
                    month: 'short', 
                    day: 'numeric',
                    year: 'numeric'
                }));
                
                // Calculate nights and price
                const checkInDate = new Date(this.selectedCheckIn + 'T00:00:00');
                const nights = Math.round((checkOutDate - checkInDate) / (1000 * 60 * 60 * 24));
                container.find('.br-nights-count').text(nights);
                
                // Calculate total price
                const totalPrice = this.calculateTotalPrice(this.selectedCheckIn, this.selectedCheckOut);
                container.find('.br-total-amount').text('€' + new Intl.NumberFormat('en-US').format(totalPrice));
            } else {
                container.find('.br-checkout-date').text('Not selected');
                container.find('.br-nights-count').text('0');
                container.find('.br-total-amount').text('€0');
            }
        }

        calculateTotalPrice(checkInStr, checkOutStr) {
            const checkIn = new Date(checkInStr + 'T00:00:00');
            const checkOut = new Date(checkOutStr + 'T00:00:00');
            const nights = Math.round((checkOut - checkIn) / (1000 * 60 * 60 * 24));
            
            let totalPrice = 0;
            let current = new Date(checkIn);
            
            // Sum up daily rates for the stay
            while (current < checkOut) {
                const dateStr = current.toISOString().split('T')[0];
                if (this.pricingData[dateStr] && this.pricingData[dateStr].daily_rate) {
                    totalPrice += parseFloat(this.pricingData[dateStr].daily_rate);
                }
                current.setDate(current.getDate() + 1);
            }
            
            return Math.round(totalPrice);
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
            
            // Prevent double submission
            if (submitButton.prop('disabled')) {
                return;
            }
            
            // Validate dates are selected
            if (!this.selectedCheckIn || !this.selectedCheckOut) {
                this.showError('Please select check-in and check-out dates');
                return;
            }
            
            // Get selected services
            const selectedServices = [];
            form.find('input[name="services[]"]:checked').each(function() {
                selectedServices.push($(this).val());
            });
            
            // Disable submit button and prevent resubmission
            submitButton.prop('disabled', true).text('Processing...');
            form.addClass('submitting');
            form.find('input, textarea, select').prop('readonly', true);
            
            // Prepare data
            const formData = {
                action: 'br_submit_calendar_booking',
                nonce: br_calendar.nonce,
                first_name: form.find('#br-first-name').val(),
                last_name: form.find('#br-last-name').val(),
                email: form.find('#br-email').val(),
                phone: form.find('#br-phone').val(),
                message: form.find('#br-message').val(),
                checkin_date: this.selectedCheckIn,
                checkout_date: this.selectedCheckOut,
                additional_services: selectedServices,
                total_price: this.calculateTotalPrice(this.selectedCheckIn, this.selectedCheckOut)
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
                        form.removeClass('submitting');
                        form.find('input, textarea, select').prop('readonly', false);
                        submitButton.prop('disabled', false).text('Submit Booking Request');
                        self.selectedCheckIn = null;
                        self.selectedCheckOut = null;
                        self.selectedDates = [];
                        self.renderMonths();
                        self.updateSelectedDatesInfo();
                        // Reload calendar data to show new booking
                        setTimeout(() => self.loadCalendarData(), 2000);
                    } else {
                        self.showError(response.data || br_calendar.strings.error);
                        // Re-enable form on error
                        submitButton.prop('disabled', false).text('Submit Booking Request');
                        form.removeClass('submitting');
                        form.find('input, textarea, select').prop('readonly', false);
                    }
                },
                error: function() {
                    self.showError(br_calendar.strings.error);
                    // Re-enable form on error
                    submitButton.prop('disabled', false).text('Submit Booking Request');
                    form.removeClass('submitting');
                    form.find('input, textarea, select').prop('readonly', false);
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
            
            // Append message after the booking form (at the bottom)
            $(this.container).find('.br-booking-form').after(messageHtml);
            
            // Scroll to message
            const messageElement = $(this.container).find('.br-message');
            $('html, body').animate({
                scrollTop: messageElement.offset().top - 100
            }, 300);
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                messageElement.fadeOut(() => {
                    messageElement.remove();
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