<?php
/**
 * Helper Functions
 */

/**
 * Get booking status label
 */
function br_get_status_label($status) {
    $labels = array(
        'pending' => __('Pending', 'booking-requests'),
        'approved' => __('Approved', 'booking-requests'),
        'denied' => __('Denied', 'booking-requests')
    );
    
    return isset($labels[$status]) ? $labels[$status] : ucfirst($status);
}

/**
 * Get booking status color
 */
function br_get_status_color($status) {
    $colors = array(
        'pending' => '#f0ad4e',
        'approved' => '#5cb85c',
        'denied' => '#d9534f'
    );
    
    return isset($colors[$status]) ? $colors[$status] : '#6c757d';
}

/**
 * Format price for display
 */
function br_format_price($price) {
    return '€' . number_format($price, 0, ',', '.');
}

/**
 * Calculate number of nights
 */
function br_calculate_nights($checkin, $checkout) {
    $checkin_date = new DateTime($checkin);
    $checkout_date = new DateTime($checkout);
    return $checkin_date->diff($checkout_date)->days;
}

/**
 * Get admin email addresses
 */
function br_get_admin_emails() {
    $emails = get_option('br_admin_emails', get_option('admin_email'));
    $emails = array_map('trim', explode(',', $emails));
    return array_filter($emails, 'is_email');
}

/**
 * Generate calendar view
 */
function br_generate_calendar($month = null, $year = null) {
    if (!$month) $month = date('n');
    if (!$year) $year = date('Y');
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'booking_requests';
    
    // Get bookings for this month
    $start_date = "$year-$month-01";
    $end_date = date('Y-m-t', strtotime($start_date));
    
    $bookings = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name 
        WHERE status IN ('pending', 'approved') 
        AND (
            (checkin_date >= %s AND checkin_date <= %s) OR
            (checkout_date >= %s AND checkout_date <= %s) OR
            (checkin_date <= %s AND checkout_date >= %s)
        )
        ORDER BY checkin_date",
        $start_date, $end_date,
        $start_date, $end_date,
        $start_date, $end_date
    ));
    
    // Create booking map
    $booking_map = array();
    foreach ($bookings as $booking) {
        $current = new DateTime($booking->checkin_date);
        $end = new DateTime($booking->checkout_date);
        
        while ($current < $end) {
            $date_key = $current->format('Y-m-d');
            if (!isset($booking_map[$date_key])) {
                $booking_map[$date_key] = array();
            }
            $booking_map[$date_key][] = $booking;
            $current->modify('+1 day');
        }
    }
    
    // Generate calendar HTML
    $first_day = mktime(0, 0, 0, $month, 1, $year);
    $days_in_month = date('t', $first_day);
    $day_of_week = date('w', $first_day);
    
    $calendar = '<table class="br-calendar">';
    $calendar .= '<thead><tr>';
    
    $days = array('Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat');
    foreach ($days as $day) {
        $calendar .= '<th>' . __($day, 'booking-requests') . '</th>';
    }
    $calendar .= '</tr></thead><tbody><tr>';
    
    // Empty cells before first day
    for ($i = 0; $i < $day_of_week; $i++) {
        $calendar .= '<td></td>';
    }
    
    // Days of month
    for ($day = 1; $day <= $days_in_month; $day++) {
        $date_key = sprintf('%04d-%02d-%02d', $year, $month, $day);
        $is_today = ($date_key == date('Y-m-d'));
        
        $calendar .= '<td class="br-day' . ($is_today ? ' br-today' : '') . '">';
        $calendar .= '<div class="br-day-number">' . $day . '</div>';
        
        if (isset($booking_map[$date_key])) {
            foreach ($booking_map[$date_key] as $booking) {
                $calendar .= '<div class="br-booking br-booking-' . $booking->status . '">';
                $calendar .= esc_html($booking->first_name . ' ' . substr($booking->last_name, 0, 1) . '.');
                $calendar .= '</div>';
            }
        }
        
        $calendar .= '</td>';
        
        if (($day + $day_of_week) % 7 == 0 && $day != $days_in_month) {
            $calendar .= '</tr><tr>';
        }
    }
    
    // Empty cells after last day
    if (($days_in_month + $day_of_week) % 7 != 0) {
        for ($i = 0; $i < 7 - (($days_in_month + $day_of_week) % 7); $i++) {
            $calendar .= '<td></td>';
        }
    }
    
    $calendar .= '</tr></tbody></table>';
    
    return $calendar;
}

/**
 * Check if dates overlap with existing bookings
 */
function br_check_date_overlap($checkin, $checkout, $exclude_id = null) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'booking_requests';
    
    $query = "SELECT COUNT(*) FROM $table_name 
              WHERE status = 'approved' 
              AND NOT (checkin_date >= %s OR checkout_date <= %s)";
    
    $params = array($checkout, $checkin);
    
    if ($exclude_id) {
        $query .= " AND id != %d";
        $params[] = $exclude_id;
    }
    
    return $wpdb->get_var($wpdb->prepare($query, $params)) > 0;
}

/**
 * Get booking statistics
 */
function br_get_booking_stats() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'booking_requests';
    
    $stats = array();
    
    // Total bookings by status
    $status_counts = $wpdb->get_results(
        "SELECT status, COUNT(*) as count 
         FROM $table_name 
         GROUP BY status",
        OBJECT_K
    );
    
    $stats['total'] = array_sum(wp_list_pluck($status_counts, 'count'));
    $stats['pending'] = isset($status_counts['pending']) ? $status_counts['pending']->count : 0;
    $stats['approved'] = isset($status_counts['approved']) ? $status_counts['approved']->count : 0;
    $stats['denied'] = isset($status_counts['denied']) ? $status_counts['denied']->count : 0;
    
    // This month's bookings
    $stats['this_month'] = $wpdb->get_var(
        "SELECT COUNT(*) FROM $table_name 
         WHERE MONTH(submitted_at) = MONTH(CURRENT_DATE()) 
         AND YEAR(submitted_at) = YEAR(CURRENT_DATE())"
    );
    
    // Revenue stats (approved bookings only)
    $revenue_data = $wpdb->get_row(
        "SELECT 
            SUM(weekly_rate * CEIL(DATEDIFF(checkout_date, checkin_date) / 7)) as total_revenue,
            AVG(weekly_rate) as avg_rate
         FROM $table_name 
         WHERE status = 'approved'"
    );
    
    $stats['total_revenue'] = $revenue_data->total_revenue ?: 0;
    $stats['avg_rate'] = $revenue_data->avg_rate ?: 0;
    
    return $stats;
}

/**
 * Send test email
 */
function br_send_test_email($email_type, $recipient) {
    $test_booking = (object) array(
        'id' => 999,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john.doe@example.com',
        'phone' => '+1234567890',
        'details' => 'This is a test booking request message.',
        'checkin_date' => date('Y-m-d', strtotime('+7 days')),
        'checkout_date' => date('Y-m-d', strtotime('+14 days')),
        'weekly_rate' => 5695,
        'status' => 'pending'
    );
    
    $email_handler = new BR_Email_Handler();
    
    switch ($email_type) {
        case 'admin_notification':
            $booking_data = (array) $test_booking;
            $booking_data['approval_token'] = 'test-token-123';
            $email_handler->send_admin_notification($booking_data);
            break;
            
        case 'approval':
            $test_booking->status = 'approved';
            $GLOBALS['br_test_booking'] = $test_booking;
            $email_handler->send_approval_email(999);
            break;
            
        case 'denial':
            $test_booking->status = 'denied';
            $GLOBALS['br_test_booking'] = $test_booking;
            $email_handler->send_denial_email(999);
            break;
    }
    
    return true;
}

/**
 * Export bookings to CSV
 */
function br_export_bookings_csv($args = array()) {
    $bookings = BR_Admin_Dashboard::get_bookings($args);
    
    $csv = array();
    $csv[] = array(
        'ID',
        'First Name',
        'Last Name',
        'Email',
        'Phone',
        'Check-in',
        'Check-out',
        'Nights',
        'Weekly Rate',
        'Total Price',
        'Status',
        'Submitted',
        'Details'
    );
    
    foreach ($bookings['bookings'] as $booking) {
        $nights = br_calculate_nights($booking->checkin_date, $booking->checkout_date);
        $weeks = ceil($nights / 7);
        $total = $booking->weekly_rate * $weeks;
        
        $csv[] = array(
            $booking->id,
            $booking->first_name,
            $booking->last_name,
            $booking->email,
            $booking->phone,
            $booking->checkin_date,
            $booking->checkout_date,
            $nights,
            br_format_price($booking->weekly_rate),
            br_format_price($total),
            ucfirst($booking->status),
            $booking->submitted_at,
            $booking->details
        );
    }
    
    return $csv;
}

/**
 * Get next available dates
 */
function br_get_next_available_dates($duration = 7, $limit = 5) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'booking_requests';
    
    $available_periods = array();
    $current_date = new DateTime();
    $current_date->modify('+1 day'); // Start from tomorrow
    
    $max_search_days = 365; // Search up to 1 year ahead
    $days_searched = 0;
    
    while (count($available_periods) < $limit && $days_searched < $max_search_days) {
        $checkin = $current_date->format('Y-m-d');
        $checkout_date = clone $current_date;
        $checkout_date->modify('+' . $duration . ' days');
        $checkout = $checkout_date->format('Y-m-d');
        
        if (!br_check_date_overlap($checkin, $checkout)) {
            $available_periods[] = array(
                'checkin' => $checkin,
                'checkout' => $checkout,
                'rate' => BR_Booking_Requests::get_weekly_rate($checkin)
            );
        }
        
        $current_date->modify('+1 day');
        $days_searched++;
    }
    
    return $available_periods;
}