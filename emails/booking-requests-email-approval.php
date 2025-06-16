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
            background-color: #f4f4f4;
        }
        .container {
            background-color: #ffffff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .header {
            background-color: #28a745;
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
        }
        .header p {
            margin: 10px 0 0 0;
            font-size: 18px;
            opacity: 0.9;
        }
        .content {
            padding: 40px;
        }
        .greeting {
            font-size: 20px;
            color: #212529;
            margin-bottom: 20px;
        }
        .confirmation-box {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: center;
        }
        .confirmation-box h2 {
            margin: 0 0 10px 0;
            font-size: 24px;
        }
        .booking-summary {
            background-color: #f8f9fa;
            padding: 25px;
            border-radius: 8px;
            margin: 30px 0;
        }
        .booking-summary h3 {
            margin-top: 0;
            color: #495057;
            font-size: 20px;
            border-bottom: 2px solid #dee2e6;
            padding-bottom: 10px;
        }
        .booking-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .booking-row:last-child {
            border-bottom: none;
        }
        .booking-label {
            font-weight: 600;
            color: #6c757d;
        }
        .booking-value {
            color: #212529;
            font-weight: 500;
        }
        .price-highlight {
            font-size: 24px;
            color: #28a745;
            font-weight: bold;
        }
        .next-steps {
            background-color: #e9ecef;
            padding: 25px;
            border-radius: 8px;
            margin: 30px 0;
        }
        .next-steps h3 {
            margin-top: 0;
            color: #495057;
            font-size: 20px;
        }
        .next-steps ol {
            margin: 15px 0;
            padding-left: 20px;
        }
        .next-steps li {
            margin: 10px 0;
            color: #495057;
        }
        .important-info {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .important-info h4 {
            margin-top: 0;
            font-size: 18px;
        }
        .contact-info {
            text-align: center;
            padding: 30px;
            background-color: #f8f9fa;
            border-top: 1px solid #dee2e6;
        }
        .contact-info h4 {
            margin-bottom: 15px;
            color: #495057;
        }
        .contact-info p {
            margin: 5px 0;
            color: #6c757d;
        }
        .footer {
            text-align: center;
            padding: 20px;
            color: #6c757d;
            font-size: 14px;
            background-color: #f8f9fa;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin: 20px 0;
        }
        .button:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?php _e('Booking Confirmed!', 'booking-requests'); ?></h1>
            <p><?php echo get_bloginfo('name'); ?></p>
        </div>
        
        <div class="content">
            <p class="greeting">
                <?php printf(__('Dear %s,', 'booking-requests'), esc_html($booking->first_name)); ?>
            </p>
            
            <div class="confirmation-box">
                <h2>✓ <?php _e('Your Booking is Confirmed', 'booking-requests'); ?></h2>
                <p><?php _e('We\'re excited to welcome you!', 'booking-requests'); ?></p>
            </div>
            
            <p><?php _e('Thank you for choosing to stay with us. Your booking request has been approved and we\'re looking forward to hosting you.', 'booking-requests'); ?></p>
            
            <div class="booking-summary">
                <h3><?php _e('Booking Details', 'booking-requests'); ?></h3>
                
                <div class="booking-row">
                    <span class="booking-label"><?php _e('Guest Name:', 'booking-requests'); ?></span>
                    <span class="booking-value"><?php echo esc_html($booking->first_name . ' ' . $booking->last_name); ?></span>
                </div>
                
                <div class="booking-row">
                    <span class="booking-label"><?php _e('Check-in Date:', 'booking-requests'); ?></span>
                    <span class="booking-value"><?php echo date('l, F j, Y', strtotime($booking->checkin_date)); ?></span>
                </div>
                
                <div class="booking-row">
                    <span class="booking-label"><?php _e('Check-out Date:', 'booking-requests'); ?></span>
                    <span class="booking-value"><?php echo date('l, F j, Y', strtotime($booking->checkout_date)); ?></span>
                </div>
                
                <div class="booking-row">
                    <span class="booking-label"><?php _e('Duration:', 'booking-requests'); ?></span>
                    <span class="booking-value"><?php printf(_n('%d night', '%d nights', $nights, 'booking-requests'), $nights); ?></span>
                </div>
                
                <div class="booking-row">
                    <span class="booking-label"><?php _e('Weekly Rate:', 'booking-requests'); ?></span>
                    <span class="booking-value price-highlight">€<?php echo number_format($booking->weekly_rate, 0, ',', '.'); ?></span>
                </div>
            </div>
            
            <div class="next-steps">
                <h3><?php _e('What Happens Next?', 'booking-requests'); ?></h3>
                <ol>
                    <li><?php _e('You will receive payment instructions via email within 24 hours', 'booking-requests'); ?></li>
                    <li><?php _e('Once payment is confirmed, we\'ll send you detailed check-in instructions', 'booking-requests'); ?></li>
                    <li><?php _e('7 days before arrival, you\'ll receive your access codes and final details', 'booking-requests'); ?></li>
                    <li><?php _e('Our team will be available to assist you throughout your stay', 'booking-requests'); ?></li>
                </ol>
            </div>
            
            <div class="important-info">
                <h4><?php _e('Important Information', 'booking-requests'); ?></h4>
                <ul>
                    <li><?php _e('Check-in time: 3:00 PM', 'booking-requests'); ?></li>
                    <li><?php _e('Check-out time: 11:00 AM', 'booking-requests'); ?></li>
                    <li><?php _e('Payment must be completed within 7 days to secure your booking', 'booking-requests'); ?></li>
                    <li><?php _e('Cancellation policy will be provided with payment instructions', 'booking-requests'); ?></li>
                </ul>
            </div>
            
            <p style="text-align: center;">
                <a href="<?php echo home_url(); ?>" class="button">
                    <?php _e('Visit Our Website', 'booking-requests'); ?>
                </a>
            </p>
        </div>
        
        <div class="contact-info">
            <h4><?php _e('Questions?', 'booking-requests'); ?></h4>
            <p><?php _e('We\'re here to help! Contact us:', 'booking-requests'); ?></p>
            <p><strong><?php _e('Email:', 'booking-requests'); ?></strong> <?php echo get_option('br_email_from', get_option('admin_email')); ?></p>
            <p><strong><?php _e('Website:', 'booking-requests'); ?></strong> <?php echo home_url(); ?></p>
        </div>
        
        <div class="footer">
            <p><?php _e('Thank you for choosing', 'booking-requests'); ?> <?php echo get_bloginfo('name'); ?></p>
            <p style="font-size: 12px; color: #adb5bd;">
                <?php _e('This email was sent to', 'booking-requests'); ?> <?php echo esc_html($booking->email); ?> 
                <?php _e('regarding your booking request.', 'booking-requests'); ?>
            </p>
        </div>
    </div>
</body>
</html>