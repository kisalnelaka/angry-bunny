<?php
/**
 * Feature management class.
 *
 * @package    AngryBunny
 */

class Angry_Bunny_Features {
    /**
     * Features configuration
     */
    private static $features = array(
        // Free Features
        'basic_scan' => array(
            'name' => 'Basic Security Scan',
            'description' => 'Basic vulnerability scanning and security checks',
            'is_pro' => false
        ),
        'file_permissions' => array(
            'name' => 'File Permissions Check',
            'description' => 'Check for insecure file permissions',
            'is_pro' => false
        ),
        'wordpress_updates' => array(
            'name' => 'WordPress Updates Check',
            'description' => 'Check for outdated WordPress core, themes, and plugins',
            'is_pro' => false
        ),
        'basic_malware' => array(
            'name' => 'Basic Malware Detection',
            'description' => 'Basic scanning for common malware signatures',
            'is_pro' => false
        ),
        'security_headers' => array(
            'name' => 'Security Headers Check',
            'description' => 'Basic security headers verification',
            'is_pro' => false
        ),

        // Pro Features
        'realtime_scanning' => array(
            'name' => 'Real-time File Monitoring',
            'description' => 'Monitor file changes in real-time',
            'is_pro' => true
        ),
        'advanced_malware' => array(
            'name' => 'Advanced Malware Detection',
            'description' => 'Deep scanning for sophisticated malware patterns',
            'is_pro' => true
        ),
        'firewall' => array(
            'name' => 'Advanced Firewall',
            'description' => 'Advanced firewall protection with custom rules',
            'is_pro' => true
        ),
        'two_factor' => array(
            'name' => 'Two-Factor Authentication',
            'description' => 'Secure login with 2FA',
            'is_pro' => true
        ),
        'api_security' => array(
            'name' => 'API Security',
            'description' => 'Advanced REST API protection',
            'is_pro' => true
        ),
        'geo_blocking' => array(
            'name' => 'Geographic Blocking',
            'description' => 'Block traffic from specific countries',
            'is_pro' => true
        ),
        'auto_fix' => array(
            'name' => 'Auto-Fix Issues',
            'description' => 'Automatically fix detected security issues',
            'is_pro' => true
        ),
        'advanced_reporting' => array(
            'name' => 'Advanced Reporting',
            'description' => 'Detailed security reports and analytics',
            'is_pro' => true
        ),
        'white_label' => array(
            'name' => 'White Label',
            'description' => 'Customize plugin branding',
            'is_pro' => true
        )
    );

    /**
     * Check if a feature is available
     */
    public static function is_available($feature_key) {
        if (!isset(self::$features[$feature_key])) {
            return false;
        }

        if (self::$features[$feature_key]['is_pro']) {
            return defined('ANGRY_BUNNY_IS_PRO') && ANGRY_BUNNY_IS_PRO;
        }

        return true;
    }

    /**
     * Get all features
     */
    public static function get_all_features() {
        return self::$features;
    }

    /**
     * Get available features
     */
    public static function get_available_features() {
        $available = array();
        foreach (self::$features as $key => $feature) {
            if (self::is_available($key)) {
                $available[$key] = $feature;
            }
        }
        return $available;
    }

    /**
     * Get pro features
     */
    public static function get_pro_features() {
        $pro_features = array();
        foreach (self::$features as $key => $feature) {
            if ($feature['is_pro']) {
                $pro_features[$key] = $feature;
            }
        }
        return $pro_features;
    }

    /**
     * Get free features
     */
    public static function get_free_features() {
        $free_features = array();
        foreach (self::$features as $key => $feature) {
            if (!$feature['is_pro']) {
                $free_features[$key] = $feature;
            }
        }
        return $free_features;
    }
} 