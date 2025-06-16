<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $subject; ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background-color: #ffffff;
            padding: 30px;
            border: 1px solid #e9ecef;
            border-radius: 0 0 8px 8px;
        }
        .booking-details {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .booking-details h3 {
            margin-top: 0;
            color: #495057;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: bold;
            color: #6c757d;
        }
        .detail-value {
            color: #212529;
        }
        .message-box {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            font-style: italic;
        }
        .action-buttons {
            text-align: center;
            margin: 30px 0;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            margin: 0 10px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 14px;
        }
        .btn-approve {
            background-color: #28a745;
            color: white;
        }
        .btn-deny {
            background-color: #dc3545;
            color: white;
        }
        .btn:hover {
            opacity: 0.9;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
            color: #6c757d;
            font-size: 14px;
        }
        .weekly-rate {
            font-size: 24px;
            color: #28a745;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><?php _e('New Booking Request', 'booking-requests'); ?></h1>
        <p><?php echo get_bloginfo('name'); ?></p>
    </div>
    
    <div class="content">
        <p><?php _e('Hi Admin,', 'booking-requests'); ?></p>
        
        <p><?php _e('A new booking request has been submitted and requires your review.', 'booking-requests'); ?></p>
        
        <div class="booking-details">
            <h3><?php _e('Guest Information', 'booking-requests'); ?></h3>
            <div class="detail-row">
                <span class="detail-label"><?php _e('Name:', 'booking-requests'); ?></span>
                <span class="detail-value"><?php echo esc_html($booking_data['first_name'] . ' ' . $booking_data['last_name']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label"><?php _e('Email:', 'booking-requests'); ?></span>
                <span class="detail-value"><a href="mailto:<?php echo esc_attr($booking_data['email']); ?>"><?php echo esc_html($booking_data['email']); ?></a></span>
            </div>
            <div class="detail-row">
                <span class="detail-label"><?php _e('Phone:', 'booking-requests'); ?></span>
                <span class="detail-value"><?php echo esc_html($booking_data['phone']); ?></span>
            </div>
        </div>
        
        <div class="booking-details">
            <h3><?php _e('Booking Details', 'booking-requests'); ?></h3>
            <div class="detail-row">
                <span class="detail-label"><?php _e('Check-in:', 'booking-requests'); ?></span>
                <span class="detail-value"><?php echo date('l, F j, Y', strtotime($booking_data['checkin_date'])); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label"><?php _e('Check-out:', 'booking-requests'); ?></span>
                <span class="detail-value"><?php echo date('l, F j, Y', strtotime($booking_data['checkout_date'])); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label"><?php _e('Duration:', 'booking-requests'); ?></span>
                <span class="detail-value"><?php printf(_n('%d night', '%d nights', $nights, 'booking-requests'), $nights); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label"><?php _e('Weekly Rate:', 'booking-requests'); ?></span>
                <span class="detail-value weekly-rate">€<?php echo number_format($booking_data['weekly_rate'], 0, ',', '.'); ?></span>
            </div>
        </div>
        
        <?php if (!empty($booking_data['details'])): ?>
            <div class="message-box">
                <h4><?php _e('Guest Message:', 'booking-requests'); ?></h4>
                <p><?php echo nl2br(esc_html($booking_data['details'])); ?></p>
            </div>
        <?php endif; ?>
        
        <div class="action-buttons">
            <a href="<?php echo esc_url($approve_url); ?>" class="btn btn-approve">
                <?php _e('Approve Booking', 'booking-requests'); ?>
            </a>
            <a href="<?php echo esc_url($deny_url); ?>" class="btn btn-deny">
                <?php _e('Deny Booking', 'booking-requests'); ?>
            </a>
        </div>
        
        <p style="text-align: center; color: #6c757d;">
            <?php _e('You can also manage this booking from the', 'booking-requests'); ?> 
            <a href="<?php echo admin_url('admin.php?page=booking-requests&action=view&id=' . $booking_data['id']); ?>">
                <?php _e('admin dashboard', 'booking-requests'); ?>
            </a>
        </p>
    </div>
    
    <div class="footer">
        <p><?php _e('This is an automated message from your booking system.', 'booking-requests'); ?></p>
        <p><?php echo get_bloginfo('name'); ?> | <?php echo home_url(); ?></p>
    </div>
</body>
</html>