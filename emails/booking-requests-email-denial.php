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
            background-color: #dc3545;
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
        .message-box {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .message-box h2 {
            margin: 0 0 10px 0;
            font-size: 20px;
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
        .alternatives {
            background-color: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
            padding: 25px;
            border-radius: 8px;
            margin: 30px 0;
        }
        .alternatives h3 {
            margin-top: 0;
            color: #0c5460;
            font-size: 20px;
        }
        .alternative-dates {
            margin: 15px 0;
        }
        .alternative-option {
            background: #ffffff;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            border: 1px solid #bee5eb;
        }
        .alternative-option strong {
            color: #0c5460;
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
        .next-steps p {
            margin: 10px 0;
            color: #495057;
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
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin: 20px 0;
        }
        .button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?php _e('Booking Request Update', 'booking-requests'); ?></h1>
            <p><?php echo get_bloginfo('name'); ?></p>
        </div>
        
        <div class="content">
            <p class="greeting">
                <?php printf(__('Dear %s,', 'booking-requests'), esc_html($booking->first_name)); ?>
            </p>
            
            <div class="message-box">
                <h2><?php _e('Booking Not Available', 'booking-requests'); ?></h2>
                <p><?php _e('We regret to inform you that we are unable to accommodate your booking request for the requested dates.', 'booking-requests'); ?></p>
            </div>
            
            <p><?php _e('Thank you for your interest in staying with us. Unfortunately, the dates you requested are not available at this time.', 'booking-requests'); ?></p>
            
            <div class="booking-summary">
                <h3><?php _e('Your Requested Dates', 'booking-requests'); ?></h3>
                
                <div class="booking-row">
                    <span class="booking-label"><?php _e('Check-in:', 'booking-requests'); ?></span>
                    <span class="booking-value"><?php echo date('l, F j, Y', strtotime($booking->checkin_date)); ?></span>
                </div>
                
                <div class="booking-row">
                    <span class="booking-label"><?php _e('Check-out:', 'booking-requests'); ?></span>
                    <span class="booking-value"><?php echo date('l, F j, Y', strtotime($booking->checkout_date)); ?></span>
                </div>
                
                <div class="booking-row">
                    <span class="booking-label"><?php _e('Duration:', 'booking-requests'); ?></span>
                    <span class="booking-value"><?php 
                        $nights = br_calculate_nights($booking->checkin_date, $booking->checkout_date);
                        printf(_n('%d night', '%d nights', $nights, 'booking-requests'), $nights); 
                    ?></span>
                </div>
            </div>
            
            <?php if (!empty($alternative_dates)): ?>
                <div class="alternatives">
                    <h3><?php _e('Alternative Dates Available', 'booking-requests'); ?></h3>
                    <p><?php _e('We have the following alternative dates available for a similar duration:', 'booking-requests'); ?></p>
                    
                    <div class="alternative-dates">
                        <?php foreach ($alternative_dates as $alternative): ?>
                            <div class="alternative-option">
                                <strong><?php echo $alternative['checkin']; ?> - <?php echo $alternative['checkout']; ?></strong><br>
                                <?php 
                                $alt_rate = BR_Booking_Requests::get_weekly_rate(date('Y-m-d', strtotime(str_replace('/', '-', $alternative['checkin']))));
                                echo __('Weekly rate:', 'booking-requests') . ' ' . br_format_price($alt_rate);
                                ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <p><?php _e('If any of these dates work for you, please submit a new booking request.', 'booking-requests'); ?></p>
                </div>
            <?php endif; ?>
            
            <div class="next-steps">
                <h3><?php _e('What You Can Do Next', 'booking-requests'); ?></h3>
                <p>• <?php _e('Check our availability calendar for other dates that might work for you', 'booking-requests'); ?></p>
                <p>• <?php _e('Submit a new booking request for different dates', 'booking-requests'); ?></p>
                <p>• <?php _e('Contact us directly if you have flexible dates or special requirements', 'booking-requests'); ?></p>
                <p>• <?php _e('Join our waiting list in case of cancellations for your preferred dates', 'booking-requests'); ?></p>
            </div>
            
            <p><?php _e('We appreciate your understanding and hope to have the opportunity to welcome you on alternative dates. Our property calendar is regularly updated, so please check back for new availability.', 'booking-requests'); ?></p>
            
            <p style="text-align: center;">
                <a href="<?php echo home_url(); ?>" class="button">
                    <?php _e('View Available Dates', 'booking-requests'); ?>
                </a>
            </p>
        </div>
        
        <div class="contact-info">
            <h4><?php _e('Need Assistance?', 'booking-requests'); ?></h4>
            <p><?php _e('If you have any questions or would like to discuss alternative arrangements, please don\'t hesitate to contact us.', 'booking-requests'); ?></p>
            <p><strong><?php _e('Email:', 'booking-requests'); ?></strong> <?php echo get_option('br_email_from', get_option('admin_email')); ?></p>
            <p><strong><?php _e('Website:', 'booking-requests'); ?></strong> <?php echo home_url(); ?></p>
        </div>
        
        <div class="footer">
            <p><?php _e('Thank you for considering', 'booking-requests'); ?> <?php echo get_bloginfo('name'); ?></p>
            <p style="font-size: 12px; color: #adb5bd;">
                <?php _e('This email was sent to', 'booking-requests'); ?> <?php echo esc_html($booking->email); ?> 
                <?php _e('regarding your booking request.', 'booking-requests'); ?>
            </p>
        </div>
    </div>
</body>
</html>