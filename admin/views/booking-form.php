<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Display messages
settings_errors('br_booking');

if (isset($_GET['message']) && $_GET['message'] === 'created') {
    echo '<div class="notice notice-success is-dismissible"><p>Booking created successfully!</p></div>';
}

$services = array(
    'taxi_service' => 'Taxi service from and to the airport',
    'greeting_service' => 'Greeting service – to welcome you to the Villa and overview the region',
    'welcome_hamper' => 'Welcome hamper on arrival',
    'sailing' => 'Sailing',
    'motorboat_hire' => 'Motorboat hire',
    'flight_bookings' => 'Flight bookings (from the UK)',
    'car_hire' => 'Car hire booking service'
);

$selected_services = array();
if ($booking && !empty($booking->additional_services)) {
    $selected_services = is_array($booking->additional_services) ? $booking->additional_services : maybe_unserialize($booking->additional_services);
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <?php echo $action === 'edit' ? 'Edit Booking' : 'Add New Booking'; ?>
    </h1>
    
    <?php if ($action === 'edit'): ?>
        <a href="<?php echo admin_url('admin.php?page=booking-requests-add'); ?>" class="page-title-action">Add New</a>
    <?php endif; ?>
    
    <form method="post" action="">
        <?php wp_nonce_field('br_save_booking', 'br_booking_nonce'); ?>
        
        <table class="form-table">
            <tr>
                <th scope="row"><label for="guest_name">Guest Name *</label></th>
                <td>
                    <input type="text" id="guest_name" name="guest_name" class="regular-text" 
                           value="<?php echo $booking ? esc_attr($booking->guest_name) : ''; ?>" required>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><label for="email">Email *</label></th>
                <td>
                    <input type="email" id="email" name="email" class="regular-text" 
                           value="<?php echo $booking ? esc_attr($booking->email) : ''; ?>" required>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><label for="phone">Phone *</label></th>
                <td>
                    <input type="tel" id="phone" name="phone" class="regular-text" 
                           value="<?php echo $booking ? esc_attr($booking->phone) : ''; ?>" required>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><label for="checkin_date">Check-in Date *</label></th>
                <td>
                    <input type="text" id="checkin_date" name="checkin_date" class="datepicker" 
                           value="<?php echo $booking ? esc_attr($booking->checkin_date) : ''; ?>" required>
                    <p class="description">Minimum stay is 3 nights</p>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><label for="checkout_date">Check-out Date *</label></th>
                <td>
                    <input type="text" id="checkout_date" name="checkout_date" class="datepicker" 
                           value="<?php echo $booking ? esc_attr($booking->checkout_date) : ''; ?>" required>
                    <span id="availability-status"></span>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><label>Total Price</label></th>
                <td>
                    <span id="total-price-display">
                        <?php if ($booking): ?>
                            €<?php echonumber_format($booking->total_price, 0, '.', ','); ?>
                        <?php else: ?>
                            €0
                        <?php endif; ?>
                    </span>
                    <button type="button" id="calculate-price" class="button">Calculate Price</button>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><label for="status">Status</label></th>
                <td>
                    <select id="status" name="status">
                        <option value="pending" <?php echo ($booking && $booking->status === 'pending') ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?php echo ($booking && $booking->status === 'approved') ? 'selected' : ''; ?>>Approved</option>
                        <option value="denied" <?php echo ($booking && $booking->status === 'denied') ? 'selected' : ''; ?>>Denied</option>
                    </select>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><label>Additional Services</label></th>
                <td>
                    <fieldset>
                        <?php foreach ($services as $value => $label): ?>
                            <label style="display: block; margin-bottom: 8px;">
                                <input type="checkbox" name="additional_services[]" value="<?php echo esc_attr($value); ?>"
                                       <?php echo in_array($value, $selected_services) ? 'checked' : ''; ?>>
                                <?php echo esc_html($label); ?>
                            </label>
                        <?php endforeach; ?>
                    </fieldset>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><label for="message">Guest Message</label></th>
                <td>
                    <textarea id="message" name="message" rows="5" cols="50" class="large-text"><?php 
                        echo $booking ? esc_textarea($booking->message) : ''; 
                    ?></textarea>
                </td>
            </tr>
            
            <?php if ($booking && !empty($booking->booking_data)): ?>
                <?php $booking_data = is_array($booking->booking_data) ? $booking->booking_data : maybe_unserialize($booking->booking_data); ?>
                <tr>
                    <th scope="row">Booking Information</th>
                    <td>
                        <p><strong>Created:</strong> <?php echo date('F j, Y g:i a', strtotime($booking->created_at)); ?></p>
                        <?php if (!empty($booking->updated_at)): ?>
                            <p><strong>Last Updated:</strong> <?php echo date('F j, Y g:i a', strtotime($booking->updated_at)); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($booking_data['form_source'])): ?>
                            <p><strong>Source:</strong> <?php echo esc_html($booking_data['form_source']); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($booking_data['nights'])): ?>
                            <p><strong>Nights:</strong> <?php echo intval($booking_data['nights']); ?></p>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endif; ?>
        </table>
        
        <p class="submit">
            <input type="submit" name="submit" id="submit" class="button button-primary" 
                   value="<?php echo $action === 'edit' ? 'Update Booking' : 'Create Booking'; ?>">
            <a href="<?php echo admin_url('admin.php?page=booking-requests'); ?>" class="button">Cancel</a>
        </p>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    // Initialize datepickers
    $('.datepicker').datepicker({
        dateFormat: 'yy-mm-dd',
        minDate: 0,
        onSelect: function() {
            checkAvailability();
        }
    });
    
    // Check availability
    function checkAvailability() {
        var checkin = $('#checkin_date').val();
        var checkout = $('#checkout_date').val();
        
        if (!checkin || !checkout) return;
        
        var checkinDate = new Date(checkin);
        var checkoutDate = new Date(checkout);
        var nights = Math.round((checkoutDate - checkinDate) / (1000 * 60 * 60 * 24));
        
        if (nights < 3) {
            $('#availability-status').html('<span style="color: red;">Minimum stay is 3 nights</span>');
            return;
        }
        
        $('#availability-status').html('<span style="color: #666;">Checking availability...</span>');
        
        $.post(br_admin.ajax_url, {
            action: 'br_check_availability',
            nonce: br_admin.nonce,
            checkin_date: checkin,
            checkout_date: checkout,
            exclude_id: <?php echo $booking ? $booking->id : 'null'; ?>
        }, function(response) {
            if (response.success && response.data.available) {
                $('#availability-status').html('<span style="color: green;">✓ Available</span>');
            } else {
                $('#availability-status').html('<span style="color: red;">✗ Not Available</span>');
            }
        });
    }
    
    // Calculate price
    $('#calculate-price').click(function() {
        var checkin = $('#checkin_date').val();
        var checkout = $('#checkout_date').val();
        
        if (!checkin || !checkout) {
            alert('Please select both check-in and check-out dates');
            return;
        }
        
        $('#total-price-display').text(br_admin.strings.calculating);
        
        $.post(br_admin.ajax_url, {
            action: 'br_calculate_price',
            nonce: br_admin.nonce,
            checkin_date: checkin,
            checkout_date: checkout
        }, function(response) {
            if (response.success) {
                $('#total-price-display').text(response.data.formatted_price);
            } else {
                $('#total-price-display').text('Error calculating price');
            }
        });
    });
});
</script>