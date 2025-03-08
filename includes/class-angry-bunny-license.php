<?php
/**
 * License management class.
 *
 * @package    AngryBunny
 */

class Angry_Bunny_License {
    /**
     * License API endpoint
     */
    private $api_url = 'https://yourdomain.com/edd-sl';
    
    /**
     * Item ID in the EDD store
     */
    private $item_id = 123; // Replace with your actual item ID
    
    /**
     * Item name in the EDD store
     */
    private $item_name = 'Angry Bunny Security Scanner Pro';

    /**
     * Grace period in days
     */
    private $grace_period_days = 7;

    /**
     * Trial period in days
     */
    private $trial_period_days = 7;

    /**
     * Get license status
     */
    public static function get_license_status() {
        $license_data = get_option('angry_bunny_license_data', array(
            'key' => '',
            'status' => 'inactive',
            'expires' => '',
            'activations_left' => 0,
            'customer_email' => '',
            'customer_name' => '',
            'payment_id' => '',
            'license_limit' => 0,
            'trial_started' => '',
            'trial_ends' => '',
            'grace_period_ends' => '',
            'last_check' => '',
            'last_notification' => ''
        ));

        return $license_data;
    }

    /**
     * Check if license or trial is valid
     */
    public static function is_license_valid() {
        $license_data = self::get_license_status();
        
        // Check if in trial period
        if (self::is_trial_active()) {
            return true;
        }

        // Check if license is valid
        if ($license_data['status'] === 'valid') {
            return true;
        }

        // Check if in grace period
        if (self::is_in_grace_period()) {
            return true;
        }

        return false;
    }

    /**
     * Check if trial is active
     */
    public static function is_trial_active() {
        $license_data = self::get_license_status();
        
        // If trial hasn't started and no valid license exists, start trial
        if (empty($license_data['trial_started']) && $license_data['status'] !== 'valid') {
            self::start_trial();
            return true;
        }

        // If trial has started, check if it's still valid
        if (!empty($license_data['trial_started'])) {
            $trial_end = strtotime($license_data['trial_ends']);
            return $trial_end > time();
        }

        return false;
    }

    /**
     * Start trial period
     */
    public static function start_trial() {
        $license_data = self::get_license_status();
        
        // Only start trial if no valid license exists
        if ($license_data['status'] !== 'valid') {
            $trial_start = current_time('mysql');
            $trial_end = date('Y-m-d H:i:s', strtotime("+{$trial_period_days} days"));
            
            $license_data['trial_started'] = $trial_start;
            $license_data['trial_ends'] = $trial_end;
            
            update_option('angry_bunny_license_data', $license_data);
            update_option('angry_bunny_is_pro_activated', true);
            
            // Send trial start email
            self::send_trial_notification('start');
        }
    }

    /**
     * Check if license is in grace period
     */
    public static function is_in_grace_period() {
        $license_data = self::get_license_status();
        
        if ($license_data['status'] === 'expired' && !empty($license_data['grace_period_ends'])) {
            $grace_end = strtotime($license_data['grace_period_ends']);
            return $grace_end > time();
        }

        return false;
    }

    /**
     * Start grace period
     */
    private function start_grace_period() {
        $license_data = self::get_license_status();
        $grace_end = date('Y-m-d H:i:s', strtotime("+{$this->grace_period_days} days"));
        
        $license_data['grace_period_ends'] = $grace_end;
        update_option('angry_bunny_license_data', $license_data);
        
        // Send grace period notification
        $this->send_expiration_notification('grace_period');
    }

    /**
     * Send license/trial notification
     */
    private function send_notification($type) {
        $license_data = self::get_license_status();
        $admin_email = get_option('admin_email');
        $site_name = get_bloginfo('name');
        
        $subject = '';
        $message = '';
        
        switch($type) {
            case 'expiring_soon':
                $subject = sprintf(__('[%s] Your Angry Bunny Security Pro license is expiring soon', 'angry-bunny'), $site_name);
                $message = sprintf(
                    __('Your Angry Bunny Security Pro license for %s will expire on %s. Please renew your license to continue receiving updates and support.', 'angry-bunny'),
                    home_url(),
                    date_i18n(get_option('date_format'), strtotime($license_data['expires']))
                );
                break;
                
            case 'expired':
                $subject = sprintf(__('[%s] Your Angry Bunny Security Pro license has expired', 'angry-bunny'), $site_name);
                $message = sprintf(
                    __('Your Angry Bunny Security Pro license for %s has expired. You have a %d-day grace period to renew your license. After that, pro features will be disabled.', 'angry-bunny'),
                    home_url(),
                    $this->grace_period_days
                );
                break;
                
            case 'grace_period':
                $subject = sprintf(__('[%s] Grace period started for Angry Bunny Security Pro', 'angry-bunny'), $site_name);
                $message = sprintf(
                    __('Your grace period for Angry Bunny Security Pro on %s has started. You have %d days to renew your license before pro features are disabled.', 'angry-bunny'),
                    home_url(),
                    $this->grace_period_days
                );
                break;
                
            case 'trial_start':
                $subject = sprintf(__('[%s] Your Angry Bunny Security Pro trial has started', 'angry-bunny'), $site_name);
                $message = sprintf(
                    __('Your %d-day trial of Angry Bunny Security Pro has started on %s. Enjoy all pro features during your trial period!', 'angry-bunny'),
                    $this->trial_period_days,
                    home_url()
                );
                break;
                
            case 'trial_ending':
                $subject = sprintf(__('[%s] Your Angry Bunny Security Pro trial is ending soon', 'angry-bunny'), $site_name);
                $message = sprintf(
                    __('Your trial of Angry Bunny Security Pro on %s will end soon. Purchase a license to continue using pro features.', 'angry-bunny'),
                    home_url()
                );
                break;
        }
        
        if (!empty($subject) && !empty($message)) {
            $headers = array('Content-Type: text/html; charset=UTF-8');
            wp_mail($admin_email, $subject, $message, $headers);
            
            // Update last notification time
            $license_data['last_notification'] = current_time('mysql');
            update_option('angry_bunny_license_data', $license_data);
        }
    }

    /**
     * Check for updates
     */
    public function check_for_updates() {
        if (!self::is_license_valid()) {
            return;
        }

        $license_data = self::get_license_status();
        
        // Data to send to the API
        $api_params = array(
            'edd_action' => 'get_version',
            'license'    => $license_data['key'],
            'item_id'    => $this->item_id,
            'version'    => ANGRY_BUNNY_VERSION,
            'url'        => home_url(),
            'beta'       => false
        );

        // Call the API
        $response = wp_remote_post($this->api_url, array(
            'timeout'   => 15,
            'sslverify' => true,
            'body'      => $api_params
        ));

        if (!is_wp_error($response)) {
            $update_data = json_decode(wp_remote_retrieve_body($response));
            
            if (isset($update_data->new_version)) {
                // Store update information
                set_site_transient('angry_bunny_pro_update_data', $update_data);
            }
        }
    }

    /**
     * Activate license
     */
    public function activate_license($license_key) {
        // Data to send to the API
        $api_params = array(
            'edd_action' => 'activate_license',
            'license'    => trim($license_key),
            'item_id'    => $this->item_id,
            'item_name'  => urlencode($this->item_name),
            'url'        => home_url(),
            'environment' => function_exists('wp_get_environment_type') ? wp_get_environment_type() : 'production'
        );

        // Call the API
        $response = wp_remote_post($this->api_url, array(
            'timeout'   => 15,
            'sslverify' => true,
            'body'      => $api_params
        ));

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message()
            );
        }

        $license_data = json_decode(wp_remote_retrieve_body($response), true);

        if ($license_data['success'] === false) {
            if (isset($license_data['error'])) {
                return array(
                    'success' => false,
                    'message' => $this->get_error_message($license_data['error'])
                );
            }
        }

        // Update license data
        update_option('angry_bunny_license_data', array(
            'key' => $license_key,
            'status' => $license_data['license'],
            'expires' => $license_data['expires'],
            'activations_left' => isset($license_data['activations_left']) ? $license_data['activations_left'] : 'unlimited',
            'customer_email' => isset($license_data['customer_email']) ? $license_data['customer_email'] : '',
            'customer_name' => isset($license_data['customer_name']) ? $license_data['customer_name'] : '',
            'payment_id' => isset($license_data['payment_id']) ? $license_data['payment_id'] : '',
            'license_limit' => isset($license_data['license_limit']) ? $license_data['license_limit'] : 0
        ));

        // Enable pro features if license is valid
        update_option('angry_bunny_is_pro_activated', $license_data['license'] === 'valid');

        return array(
            'success' => $license_data['success'],
            'message' => $license_data['license'] === 'valid' ? 
                __('License activated successfully.', 'angry-bunny') : 
                $this->get_error_message($license_data['error'])
        );
    }

    /**
     * Deactivate license
     */
    public function deactivate_license() {
        $license_data = self::get_license_status();
        
        if (empty($license_data['key'])) {
            return array(
                'success' => false,
                'message' => __('No license key found.', 'angry-bunny')
            );
        }

        // Data to send to the API
        $api_params = array(
            'edd_action' => 'deactivate_license',
            'license'    => $license_data['key'],
            'item_id'    => $this->item_id,
            'item_name'  => urlencode($this->item_name),
            'url'        => home_url(),
            'environment' => function_exists('wp_get_environment_type') ? wp_get_environment_type() : 'production'
        );

        // Call the API
        $response = wp_remote_post($this->api_url, array(
            'timeout'   => 15,
            'sslverify' => true,
            'body'      => $api_params
        ));

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message()
            );
        }

        $license_data = json_decode(wp_remote_retrieve_body($response), true);

        // Reset license data regardless of API response
        update_option('angry_bunny_license_data', array(
            'key' => '',
            'status' => 'inactive',
            'expires' => '',
            'activations_left' => 0,
            'customer_email' => '',
            'customer_name' => '',
            'payment_id' => '',
            'license_limit' => 0
        ));

        // Disable pro features
        update_option('angry_bunny_is_pro_activated', false);

        return array(
            'success' => true,
            'message' => __('License deactivated successfully.', 'angry-bunny')
        );
    }

    /**
     * Check license status with the server
     */
    public function check_license() {
        $license_data = self::get_license_status();
        
        if (empty($license_data['key'])) {
            return array(
                'success' => false,
                'message' => __('No license key found.', 'angry-bunny')
            );
        }

        // Data to send to the API
        $api_params = array(
            'edd_action' => 'check_license',
            'license'    => $license_data['key'],
            'item_id'    => $this->item_id,
            'item_name'  => urlencode($this->item_name),
            'url'        => home_url(),
            'environment' => function_exists('wp_get_environment_type') ? wp_get_environment_type() : 'production'
        );

        // Call the API
        $response = wp_remote_post($this->api_url, array(
            'timeout'   => 15,
            'sslverify' => true,
            'body'      => $api_params
        ));

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message()
            );
        }

        $license_data = json_decode(wp_remote_retrieve_body($response), true);
        $current_data = self::get_license_status();

        if ($license_data['success'] === false) {
            // If license was valid before but now expired, start grace period
            if ($current_data['status'] === 'valid' && $license_data['error'] === 'expired') {
                $this->start_grace_period();
            }

            // License is no longer valid
            update_option('angry_bunny_is_pro_activated', false);
            return array(
                'success' => false,
                'message' => $this->get_error_message($license_data['error'])
            );
        }

        // Check for upcoming expiration
        if (!empty($license_data['expires'])) {
            $expiry_date = strtotime($license_data['expires']);
            $days_until_expiry = ceil(($expiry_date - time()) / DAY_IN_SECONDS);
            
            // Send notification 7 days before expiration
            if ($days_until_expiry <= 7 && $days_until_expiry > 0) {
                $last_notification = strtotime($current_data['last_notification']);
                if (empty($last_notification) || (time() - $last_notification) > DAY_IN_SECONDS) {
                    $this->send_notification('expiring_soon');
                }
            }
        }

        // Update license data
        $updated_data = array(
            'key' => $license_data['license_key'],
            'status' => $license_data['license'],
            'expires' => $license_data['expires'],
            'activations_left' => isset($license_data['activations_left']) ? $license_data['activations_left'] : 'unlimited',
            'customer_email' => isset($license_data['customer_email']) ? $license_data['customer_email'] : '',
            'customer_name' => isset($license_data['customer_name']) ? $license_data['customer_name'] : '',
            'payment_id' => isset($license_data['payment_id']) ? $license_data['payment_id'] : '',
            'license_limit' => isset($license_data['license_limit']) ? $license_data['license_limit'] : 0,
            'trial_started' => $current_data['trial_started'],
            'trial_ends' => $current_data['trial_ends'],
            'grace_period_ends' => $current_data['grace_period_ends'],
            'last_check' => current_time('mysql'),
            'last_notification' => $current_data['last_notification']
        );
        
        update_option('angry_bunny_license_data', $updated_data);

        // Update pro activation status
        update_option('angry_bunny_is_pro_activated', $license_data['license'] === 'valid');

        // Check for updates
        $this->check_for_updates();

        return array(
            'success' => true,
            'message' => __('License status checked successfully.', 'angry-bunny')
        );
    }

    /**
     * Get error message
     */
    private function get_error_message($error) {
        switch($error) {
            case 'expired':
                return __('Your license key has expired.', 'angry-bunny');
            case 'disabled':
            case 'revoked':
                return __('Your license key has been disabled.', 'angry-bunny');
            case 'missing':
                return __('Invalid license key.', 'angry-bunny');
            case 'invalid':
            case 'site_inactive':
                return __('Your license is not active for this URL.', 'angry-bunny');
            case 'item_name_mismatch':
                return __('This license key does not belong to this product.', 'angry-bunny');
            case 'no_activations_left':
                return __('Your license key has reached its activation limit.', 'angry-bunny');
            default:
                return __('An error occurred, please try again.', 'angry-bunny');
        }
    }

    /**
     * Schedule daily license check
     */
    public static function schedule_license_check() {
        if (!wp_next_scheduled('angry_bunny_daily_license_check')) {
            wp_schedule_event(time(), 'daily', 'angry_bunny_daily_license_check');
        }
    }

    /**
     * Clear scheduled license check
     */
    public static function clear_license_check() {
        wp_clear_scheduled_hook('angry_bunny_daily_license_check');
    }
} 