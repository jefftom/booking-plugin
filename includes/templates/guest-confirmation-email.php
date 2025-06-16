<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Booking Request Received - <?php echo esc_html($guest_name); ?></title>
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
            background-color: #2c3e50;
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
        .booking-details {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 6px;
            margin: 20px 0;
        }
        .booking-details h3 {
            margin-top: 0;
            color: #2c3e50;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin: 10px 0;
            padding: 8px 0;
            border-bottom: 1px dotted #ddd;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: bold;
            color: #555;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Booking Request Received</h1>
        </div>
        
        <div class="content">
            <p>Dear <?php echo esc_html($guest_name); ?>,</p>
            
            <p>Thank you for your booking request! We have received your information and will review it shortly. You will receive another email once your booking has been processed.</p>
            
            <div class="booking-details">
                <h3>Your Booking Details</h3>
                
                <div class="detail-row">
                    <span class="detail-label">Check-in:</span>
                    <span><?php echo esc_html($checkin_formatted); ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Check-out:</span>
                    <span><?php echo esc_html($checkout_formatted); ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Total Nights:</span>
                    <span><?php echo $nights; ?> nights</span>
                </div>
            </div>
            
            <p><strong>What happens next?</strong></p>
            <p>Our team will review your booking request and you will receive an email within 24-48 hours with the status of your booking. If approved, you will receive payment instructions at that time.</p>
            
            <p>If you have any questions in the meantime, please don't hesitate to contact us.</p>
        </div>
        
        <div class="footer">
            <p><strong><?php echo get_bloginfo('name'); ?></strong></p>
            <p>This is an automated message. Please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>