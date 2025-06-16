<?php
/**
 * Booking Details View
 */

$nights = br_calculate_nights($booking->checkin_date, $booking->checkout_date);
$weeks = ceil($nights / 7);
$total_price = $booking->weekly_rate * $weeks;
?>

<div class="wrap">
    <h1>
        <?php _e('Booking Details', 'booking-requests'); ?>
        <a href="<?php echo admin_url('admin.php?page=booking-requests'); ?>" class="page-title-action">
            <?php _e('Back to List', 'booking-requests'); ?>
        </a>
        <a href="#" class="page-title-action br-print-booking">
            <?php _e('Print', 'booking-requests'); ?>
        </a>
    </h1>
    
    <div class="br-booking-details">
        <!-- Guest Information -->
        <div class="br-detail-section">
            <h2><?php _e('Guest Information', 'booking-requests'); ?></h2>
            
            <div class="br-detail-row">
                <span class="br-detail-label"><?php _e('Name:', 'booking-requests'); ?></span>
                <span class="br-detail-value">
                    <?php echo esc_html($booking->first_name . ' ' . $booking->last_name); ?>
                </span>
            </div>
            
            <div class="br-detail-row">
                <span class="br-detail-label"><?php _e('Email:', 'booking-requests'); ?></span>
                <span class="br-detail-value">
                    <a href="mailto:<?php echo esc_attr($booking->email); ?>">
                        <?php echo esc_html($booking->email); ?>
                    </a>
                </span>
            </div>
            
            <div class="br-detail-row">
                <span class="br-detail-label"><?php _e('Phone:', 'booking-requests'); ?></span>
                <span class="br-detail-value">
                    <?php echo esc_html($booking->phone); ?>
                </span>
            </div>
            
            <?php if ($booking->details): ?>
                <div class="br-detail-row">
                    <span class="br-detail-label"><?php _e('Message:', 'booking-requests'); ?></span>
                    <span class="br-detail-value">
                        <?php echo nl2br(esc_html($booking->details)); ?>
                    </span>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Booking Information -->
        <div class="br-detail-section">
            <h2><?php _e('Booking Information', 'booking-requests'); ?></h2>
            
            <div class="br-detail-row">
                <span class="br-detail-label"><?php _e('Status:', 'booking-requests'); ?></span>
                <span class="br-detail-value">
                    <span class="br-status br-status-<?php echo $booking->status; ?>">
                        <?php echo br_get_status_label($booking->status); ?>
                    </span>
                </span>
            </div>
            
            <div class="br-detail-row">
                <span class="br-detail-label"><?php _e('Check-in:', 'booking-requests'); ?></span>
                <span class="br-detail-value">
                    <?php echo date_i18n('l, F j, Y', strtotime($booking->checkin_date)); ?>
                </span>
            </div>
            
            <div class="br-detail-row">
                <span class="br-detail-label"><?php _e('Check-out:', 'booking-requests'); ?></span>
                <span class="br-detail-value">
                    <?php echo date_i18n('l, F j, Y', strtotime($booking->checkout_date)); ?>
                </span>
            </div>
            
            <div class="br-detail-row">
                <span class="br-detail-label"><?php _e('Duration:', 'booking-requests'); ?></span>
                <span class="br-detail-value">
                    <?php printf(_n('%d night', '%d nights', $nights, 'booking-requests'), $nights); ?>
                    (<?php printf(_n('%d week', '%d weeks', $weeks, 'booking-requests'), $weeks); ?>)
                </span>
            </div>
            
            <div class="br-detail-row">
                <span class="br-detail-label"><?php _e('Weekly Rate:', 'booking-requests'); ?></span>
                <span class="br-detail-value">
                    <?php echo br_format_price($booking->weekly_rate); ?>
                </span>
            </div>
            
            <div class="br-detail-row">
                <span class="br-detail-label"><?php _e('Total Price:', 'booking-requests'); ?></span>
                <span class="br-detail-value" style="font-size: 1.2em; font-weight: bold; color: #28a745;">
                    <?php echo br_format_price($total_price); ?>
                </span>
            </div>
            
            <div class="br-detail-row">
                <span class="br-detail-label"><?php _e('Submitted:', 'booking-requests'); ?></span>
                <span class="br-detail-value">
                    <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($booking->submitted_at)); ?>
                </span>
            </div>
            
            <?php if ($booking->form_entry_id && class_exists('GFAPI')): ?>
                <div class="br-detail-row">
                    <span class="br-detail-label"><?php _e('Form Entry:', 'booking-requests'); ?></span>
                    <span class="br-detail-value">
                        <a href="<?php echo admin_url('admin.php?page=gf_entries&view=entry&id=' . $booking->form_entry_id); ?>" target="_blank">
                            <?php _e('View in Gravity Forms', 'booking-requests'); ?> →
                        </a>
                    </span>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Admin Notes -->
        <div class="br-detail-section">
            <h2><?php _e('Admin Notes', 'booking-requests'); ?></h2>
            
            <form method="post" action="">
                <?php wp_nonce_field('br_update_notes'); ?>
                <input type="hidden" name="booking_id" value="<?php echo $booking->id; ?>">
                
                <div class="br-detail-row">
                    <textarea name="admin_notes" rows="5" style="width: 100%;"><?php echo esc_textarea($booking->admin_notes); ?></textarea>
                </div>
                
                <div class="br-detail-row">
                    <button type="submit" name="update_notes" class="button button-primary">
                        <?php _e('Save Notes', 'booking-requests'); ?>
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Actions -->
        <?php if ($booking->status === 'pending'): ?>
            <div class="br-actions">
                <h2><?php _e('Actions', 'booking-requests'); ?></h2>
                
                <?php $nonce = wp_create_nonce('br_admin_action'); ?>
                
                <a href="<?php echo add_query_arg(array(
                    'page' => 'booking-requests',
                    'action' => 'approve',
                    'id' => $booking->id,
                    '_wpnonce' => $nonce
                ), admin_url('admin.php')); ?>" class="button button-primary button-large">
                    <?php _e('Approve Booking', 'booking-requests'); ?>
                </a>
                
                <a href="<?php echo add_query_arg(array(
                    'page' => 'booking-requests',
                    'action' => 'deny',
                    'id' => $booking->id,
                    '_wpnonce' => $nonce
                ), admin_url('admin.php')); ?>" class="button button-large">
                    <?php _e('Deny Booking', 'booking-requests'); ?>
                </a>
                
                <a href="<?php echo add_query_arg(array(
                    'page' => 'booking-requests',
                    'action' => 'delete',
                    'id' => $booking->id,
                    '_wpnonce' => $nonce
                ), admin_url('admin.php')); ?>" class="button button-link-delete" onclick="return confirm('<?php _e('Are you sure you want to delete this booking?', 'booking-requests'); ?>')">
                    <?php _e('Delete', 'booking-requests'); ?>
                </a>
            </div>
        <?php elseif ($booking->status === 'approved'): ?>
            <div class="br-actions">
                <h2><?php _e('Actions', 'booking-requests'); ?></h2>
                
                <p><?php _e('This booking has been approved. You can:', 'booking-requests'); ?></p>
                
                <?php $nonce = wp_create_nonce('br_admin_action'); ?>
                
                <a href="<?php echo add_query_arg(array(
                    'page' => 'booking-requests',
                    'action' => 'deny',
                    'id' => $booking->id,
                    '_wpnonce' => $nonce
                ), admin_url('admin.php')); ?>" class="button">
                    <?php _e('Change to Denied', 'booking-requests'); ?>
                </a>
                
                <a href="#" class="button br-resend-confirmation" data-booking-id="<?php echo $booking->id; ?>">
                    <?php _e('Resend Confirmation Email', 'booking-requests'); ?>
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Handle notes update
if (isset($_POST['update_notes']) && wp_verify_nonce($_POST['_wpnonce'], 'br_update_notes')) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'booking_requests';
    
    $wpdb->update(
        $table_name,
        array('admin_notes' => sanitize_textarea_field($_POST['admin_notes'])),
        array('id' => intval($_POST['booking_id']))
    );
    
    echo '<script>location.reload();</script>';
}
?>

<style type="text/css">
@media print {
    .page-title-action,
    .br-actions,
    #adminmenumain,
    #wpadminbar,
    .notice,
    button {
        display: none !important;
    }
    
    .br-booking-details {
        margin: 0;
        padding: 0;
    }
    
    .br-detail-section {
        page-break-inside: avoid;
    }
}
</style>