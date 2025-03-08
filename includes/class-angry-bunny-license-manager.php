<?php

/**
 * The class responsible for license management functionality.
 */
class Angry_Bunny_License_Manager {

    /**
     * Generate a new license key
     *
     * @param array $args License arguments (duration, site_limit, etc.)
     * @return string Generated license key
     */
    public static function generate_license_key($args = array()) {
        $prefix = 'AB-';
        $random = bin2hex(random_bytes(8));
        $checksum = substr(md5($random . wp_salt()), 0, 4);
        return $prefix . $random . '-' . $checksum;
    }

    /**
     * Validate a license key format
     *
     * @param string $license_key The license key to validate
     * @return bool Whether the license key format is valid
     */
    public static function validate_license_format($license_key) {
        $pattern = '/^AB-[a-f0-9]{16}-[a-f0-9]{4}$/i';
        return (bool) preg_match($pattern, $license_key);
    }

    /**
     * Store license data in WordPress options
     *
     * @param string $license_key The license key
     * @param array $license_data License data to store
     * @return bool Whether the data was stored successfully
     */
    public static function store_license_data($license_key, $license_data) {
        $licenses = get_option('angry_bunny_licenses', array());
        $licenses[$license_key] = wp_parse_args($license_data, array(
            'status' => 'active',
            'created' => current_time('mysql'),
            'expires' => date('Y-m-d H:i:s', strtotime('+1 year')),
            'site_limit' => 1,
            'sites' => array(),
            'owner_email' => '',
            'owner_name' => ''
        ));
        return update_option('angry_bunny_licenses', $licenses);
    }

    /**
     * Get license data
     *
     * @param string $license_key The license key
     * @return array|false License data or false if not found
     */
    public static function get_license_data($license_key) {
        $licenses = get_option('angry_bunny_licenses', array());
        return isset($licenses[$license_key]) ? $licenses[$license_key] : false;
    }

    /**
     * Activate a license for a site
     *
     * @param string $license_key The license key
     * @param string $site_url The site URL to activate
     * @return array Response with status and message
     */
    public static function activate_license($license_key, $site_url) {
        if (!self::validate_license_format($license_key)) {
            return array(
                'success' => false,
                'message' => 'Invalid license key format'
            );
        }

        $license_data = self::get_license_data($license_key);
        if (!$license_data) {
            return array(
                'success' => false,
                'message' => 'License key not found'
            );
        }

        if ($license_data['status'] !== 'active') {
            return array(
                'success' => false,
                'message' => 'License is not active'
            );
        }

        if (count($license_data['sites']) >= $license_data['site_limit']) {
            return array(
                'success' => false,
                'message' => 'Site limit reached for this license'
            );
        }

        if (!in_array($site_url, $license_data['sites'])) {
            $license_data['sites'][] = $site_url;
            self::store_license_data($license_key, $license_data);
        }

        return array(
            'success' => true,
            'message' => 'License activated successfully',
            'data' => array(
                'expires' => $license_data['expires'],
                'site_limit' => $license_data['site_limit'],
                'sites_active' => count($license_data['sites'])
            )
        );
    }

    /**
     * Deactivate a license for a site
     *
     * @param string $license_key The license key
     * @param string $site_url The site URL to deactivate
     * @return array Response with status and message
     */
    public static function deactivate_license($license_key, $site_url) {
        $license_data = self::get_license_data($license_key);
        if (!$license_data) {
            return array(
                'success' => false,
                'message' => 'License key not found'
            );
        }

        $sites = $license_data['sites'];
        $key = array_search($site_url, $sites);
        
        if ($key !== false) {
            unset($sites[$key]);
            $license_data['sites'] = array_values($sites);
            self::store_license_data($license_key, $license_data);
            
            return array(
                'success' => true,
                'message' => 'License deactivated successfully'
            );
        }

        return array(
            'success' => false,
            'message' => 'Site not found in license activations'
        );
    }

    /**
     * Check if a license is valid and active for a site
     *
     * @param string $license_key The license key
     * @param string $site_url The site URL to check
     * @return bool Whether the license is valid and active
     */
    public static function is_license_valid($license_key, $site_url) {
        $license_data = self::get_license_data($license_key);
        
        if (!$license_data) {
            return false;
        }

        if ($license_data['status'] !== 'active') {
            return false;
        }

        if (strtotime($license_data['expires']) < time()) {
            return false;
        }

        return in_array($site_url, $license_data['sites']);
    }

    /**
     * Revoke a license
     *
     * @param string $license_key The license key to revoke
     * @return array Response with status and message
     */
    public static function revoke_license($license_key) {
        $licenses = get_option('angry_bunny_licenses', array());
        
        if (!isset($licenses[$license_key])) {
            return array(
                'success' => false,
                'message' => 'License key not found'
            );
        }

        $licenses[$license_key]['status'] = 'revoked';
        
        if (update_option('angry_bunny_licenses', $licenses)) {
            return array(
                'success' => true,
                'message' => 'License revoked successfully'
            );
        }

        return array(
            'success' => false,
            'message' => 'Failed to revoke license'
        );
    }
} 