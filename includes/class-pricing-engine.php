<?php

class BR_Pricing_Engine {
    
    private $pricing_periods;
    
    public function __construct() {
        $this->pricing_periods = $this->get_pricing_periods();
    }
    
    /**
     * Get pricing periods
     */
    private function get_pricing_periods() {
        return array(
            array(
                'start' => '2025-07-05',
                'end' => '2025-09-02',
                'weekly_price' => 8495
            ),
            array(
                'start' => '2025-09-03',
                'end' => '2025-09-09',
                'weekly_price' => 7495
            ),
            array(
                'start' => '2025-09-10',
                'end' => '2025-09-16',
                'weekly_price' => 6495
            ),
            array(
                'start' => '2025-09-17',
                'end' => '2025-10-28',
                'weekly_price' => 5695
            ),
            array(
                'start' => '2025-10-29',
                'end' => '2026-03-23',
                'weekly_price' => 4995
            ),
            array(
                'start' => '2026-03-24',
                'end' => '2026-06-15',
                'weekly_price' => 5695
            ),
            array(
                'start' => '2026-06-16',
                'end' => '2026-06-29',
                'weekly_price' => 6495
            ),
            array(
                'start' => '2026-06-30',
                'end' => '2026-07-06',
                'weekly_price' => 7495
            ),
            array(
                'start' => '2026-07-07',
                'end' => '2026-08-31',
                'weekly_price' => 8395
            ),
            array(
                'start' => '2026-09-01',
                'end' => '2026-09-07',
                'weekly_price' => 7495
            ),
            array(
                'start' => '2026-09-08',
                'end' => '2026-09-14',
                'weekly_price' => 6495
            ),
            array(
                'start' => '2026-09-15',
                'end' => '2026-10-26',
                'weekly_price' => 5695
            ),
            array(
                'start' => '2026-10-27',
                'end' => '2027-03-22',
                'weekly_price' => 5295
            )
        );
    }
    
    /**
     * Calculate total price for date range
     */
    public function calculate_total_price($checkin_date, $checkout_date) {
        $checkin = new DateTime($checkin_date);
        $checkout = new DateTime($checkout_date);
        
        // Calculate number of nights
        $nights = $checkin->diff($checkout)->days;
        
        // For weekly pricing, we need exactly 7 nights
        if ($nights % 7 !== 0) {
            // Find the weekly rate for the check-in date
            $weekly_rate = $this->get_weekly_rate_for_date($checkin_date);
            
            // For 6 nights (Sunday to Saturday), return the weekly rate
            if ($nights === 6) {
                return $weekly_rate;
            }
            
            // For other periods, prorate (not ideal but workable)
            return round(($weekly_rate / 7) * $nights);
        }
        
        // Calculate total for full weeks
        $total = 0;
        $current = clone $checkin;
        
        while ($current < $checkout) {
            $weekly_rate = $this->get_weekly_rate_for_date($current->format('Y-m-d'));
            $total += $weekly_rate;
            
            $current->modify('+7 days');
        }
        
        return $total;
    }
    
    /**
     * Get weekly rate for a specific date
     */
    public function get_weekly_rate_for_date($date) {
        $check_date = new DateTime($date);
        
        foreach ($this->pricing_periods as $period) {
            $period_start = new DateTime($period['start']);
            $period_end = new DateTime($period['end']);
            
            if ($check_date >= $period_start && $check_date <= $period_end) {
                return $period['weekly_price'];
            }
        }
        
        // Default rate if date is outside defined periods
        return 4995;
    }
    
    /**
     * Get pricing for date range
     */
    public function get_pricing_for_dates($start_date, $end_date) {
        $pricing = array();
        $current = new DateTime($start_date);
        $end = new DateTime($end_date);
        
        // Get start day setting
        $start_day = get_option('br_week_start_day', 'saturday');
        $start_day_number = $start_day === 'sunday' ? 0 : 6;
        
        // Move to first start day
        while ($current->format('w') != $start_day_number) {
            $current->modify('+1 day');
        }
        
        while ($current <= $end) {
            $week_end = clone $current;
            $week_end->modify('+6 days');
            
            $pricing[$current->format('Y-m-d')] = array(
                'start' => $current->format('Y-m-d'),
                'end' => $week_end->format('Y-m-d'),
                'rate' => $this->get_weekly_rate_for_date($current->format('Y-m-d'))
            );
            
            $current->modify('+7 days');
        }
        
        return $pricing;
    }
    
    /**
     * Check if date is in valid booking period
     */
    public function is_date_in_valid_period($date) {
        $check_date = new DateTime($date);
        
        foreach ($this->pricing_periods as $period) {
            $period_start = new DateTime($period['start']);
            $period_end = new DateTime($period['end']);
            
            if ($check_date >= $period_start && $check_date <= $period_end) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get all pricing periods
     */
    public function get_all_pricing_periods() {
        return $this->pricing_periods;
    }
}