<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>New Booking Request</title>
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
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h2 {
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
        .booking-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
        }
        .info-row {
            margin: 10px 0;
            display: flex;
            align-items: flex-start;
        }
        .info-label {
            font-weight: bold;
            min-width: 120px;
            color: #555;
        }
        .info-value {
            flex: 1;
        }
        .weeks-list {
            list-style: none;
            padding: 0;
            margin: 10px 0;
        }
        .weeks-list li {
            background: #e9ecef;
            padding: 8px 12px;
            margin: 5px 0;
            border-radius: 4px;
        }
        .message-box {
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
        }
        .action-buttons {
            margin: 30px 0;
            text-align: center;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
            margin: 0 10px;
            color: white;
            text-transform: uppercase;
            font-size: 14px;
        }
        .btn-approve {
            background-color: #27ae60;
        }
        .btn-approve:hover {
            background-color: #229954;
        }
        .btn-deny {
            background-color: #e74c3c;
        }
        .btn-deny:hover {
            background-color: #c0392b;
        }
        .total-price {
            font-size: 20px;
            color: #27ae60;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>New Booking Request #<?php echo $id; ?></h2>
        
        <div class="booking-info">
            <div class="info-row">
                <span class="info-label">Guest Name:</span>
                <span class="info-value"><?php echo esc_html($guest_name); ?></span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Email:</span>
                <span class="info-value">
                    <a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a>
                </span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Phone:</span>
                <span class="info-value"><?php echo esc_html($phone); ?></span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Check-in:</span>
                <span class="info-value"><?php echo esc_html($checkin_formatted); ?></span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Check-out:</span>
                <span class="info-value"><?php echo esc_html($checkout_formatted); ?></span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Total Nights:</span>
                <span class="info-value"><?php echo $nights; ?> nights</span>
            </div>
            
            <?php if (!empty($weeks_info)): ?>
                <div class="info-row">
                    <span class="info-label">Selected Weeks:</span>
                    <span class="info-value"><?php echo $weeks_info; ?></span>
                </div>
            <?php endif; ?>
            
            <div class="info-row">
                <span class="info-label">Total Price:</span>
                <span class="info-value total-price"><?php echo esc_html($total_price_formatted); ?></span>
            </div>
        </div>
        
        <?php if (!empty($message)): ?>
            <div class="message-box">
                <strong>Guest Message:</strong><br>
                <?php echo nl2br(esc_html($message)); ?>
            </div>
        <?php endif; ?>
        
        <div class="action-buttons">
            <a href="<?php echo esc_url($approve_url); ?>" class="btn btn-approve">Approve Booking</a>
            <a href="<?php echo esc_url($deny_url); ?>" class="btn btn-deny">Deny Booking</a>
        </div>
        
        <p style="text-align: center; color: #666; font-size: 14px;">
            You can also manage this booking from the 
            <a href="<?php echo admin_url('admin.php?page=booking-requests'); ?>">WordPress Dashboard</a>
        </p>
    </div>
</body>
</html>