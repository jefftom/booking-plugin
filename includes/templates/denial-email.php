<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Booking Update</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background: #fff;
            padding: 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            background-color: #e74c3c;
            color: #fff;
            padding: 30px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
        }
        .content {
            padding: 40px;
        }
        .message-box {
            background-color: #ffebee;
            border: 1px solid #ffcdd2;
            padding: 20px;
            border-radius: 6px;
            margin: 20px 0;
        }
        .alternatives {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 6px;
            margin: 20px 0;
        }
        .alternatives h3 {
            margin-top: 0;
            color: #2c3e50;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-top: 1px solid #e9ecef;
            border-radius: 0 0 8px 8px;
        }
        .footer p {
            margin: 5px 0;
            color: #666;
            font-size: 14px;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Update on Your Booking Request</h1>
        </div>
        
        <div class="content">
            <p>Dear <?php echo esc_html($guest_name); ?>,</p>
            
            <p>Thank you for your interest in booking with us.</p>
            
            <div class="message-box">
                <p>Unfortunately, we are unable to accommodate your booking request for the dates you selected:</p>
                <p><strong><?php echo esc_html($checkin_formatted); ?> to <?php echo esc_html($checkout_formatted); ?></strong></p>
                
                <p>This may be due to:</p>
                <ul>
                    <li>The requested dates are no longer available</li>
                    <li>The property is undergoing maintenance during this period</li>
                    <li>The booking didn't meet our minimum stay requirements</li>
                </ul>
            </div>
            
            <div class="alternatives">
                <h3>What You Can Do Next</h3>
                <ul>
                    <li>Check our availability calendar for alternative dates</li>
                    <li>Consider booking for a different week that may work for your schedule</li>
                    <li>Contact us directly if you have flexible travel dates</li>
                    <li>Join our mailing list to be notified of future availability</li>
                </ul>
                
                <p style="text-align: center;">
                    <a href="<?php echo home_url(); ?>" class="btn">View Available Dates</a>
                </p>
            </div>
            
            <p>We sincerely apologize for any inconvenience this may cause. We hope to have the opportunity to welcome you at another time.</p>
            
            <p>If you have any questions or would like assistance finding alternative dates, please don't hesitate to contact us.</p>
        </div>
        
        <div class="footer">
            <p><strong><?php echo get_bloginfo('name'); ?></strong></p>
            <p>This is an automated message. For assistance, please contact us directly.</p>
        </div>
    </div>
</body>
</html>