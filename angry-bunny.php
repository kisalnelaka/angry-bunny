<?php
/**
 * Plugin Name: Angry Bunny Security Scanner
 * Plugin URI: https://github.com/socialrabbit/angry-bunny
 * Description: A comprehensive security scanner for WordPress that helps protect your site from vulnerabilities and threats.
 * Version: 1.0.0
 * Author: Kisal Nelaka
 * Author URI: https://github.com/kisalnelaka
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: angry-bunny
 * Domain Path: /languages
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Plugin version and constants
define('ANGRY_BUNNY_VERSION', '1.0.0');
define('ANGRY_BUNNY_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ANGRY_BUNNY_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ANGRY_BUNNY_IS_PRO', false);
define('ANGRY_BUNNY_MIN_PHP_VERSION', '7.2');
define('ANGRY_BUNNY_MIN_WP_VERSION', '5.0');

/**
 * Check system requirements
 */
function angry_bunny_check_requirements() {
    $errors = array();
    
    if (version_compare(PHP_VERSION, ANGRY_BUNNY_MIN_PHP_VERSION, '<')) {
        $errors[] = sprintf(
            __('Angry Bunny Security Scanner requires PHP %s or higher.', 'angry-bunny'),
            ANGRY_BUNNY_MIN_PHP_VERSION
        );
    }

    if (version_compare($GLOBALS['wp_version'], ANGRY_BUNNY_MIN_WP_VERSION, '<')) {
        $errors[] = sprintf(
            __('Angry Bunny Security Scanner requires WordPress %s or higher.', 'angry-bunny'),
            ANGRY_BUNNY_MIN_WP_VERSION
        );
    }

    return $errors;
}

/**
 * Display admin notices for requirements
 */
function angry_bunny_admin_notices() {
    $errors = angry_bunny_check_requirements();
    foreach ($errors as $error) {
        echo '<div class="notice notice-error"><p>' . esc_html($error) . '</p></div>';
    }
}

// Check requirements before loading
$requirement_errors = angry_bunny_check_requirements();
if (!empty($requirement_errors)) {
    add_action('admin_notices', 'angry_bunny_admin_notices');
    return;
}

// Include required files
$required_files = array(
    'includes/class-angry-bunny-loader.php',
    'includes/class-angry-bunny-license-manager.php',
    'includes/class-angry-bunny-license-api.php',
    'includes/class-angry-bunny-license.php',
    'includes/class-angry-bunny-features.php',
    'includes/class-angry-bunny-scanner.php',
    'admin/class-angry-bunny-admin.php',
    'includes/class-angry-bunny.php'
);

foreach ($required_files as $file) {
    require_once ANGRY_BUNNY_PLUGIN_DIR . $file;
}

// Include pro features
if (ANGRY_BUNNY_IS_PRO) {
    $pro_files = array(
        'includes/pro/class-angry-bunny-realtime-scanner.php',
        'includes/pro/class-angry-bunny-advanced-firewall.php',
        'includes/pro/class-angry-bunny-2fa.php'
    );
    foreach ($pro_files as $file) {
        require_once ANGRY_BUNNY_PLUGIN_DIR . $file;
    }
}

/**
 * Handle license AJAX actions
 */
function angry_bunny_handle_license_ajax() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'angry-bunny')));
    }

    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'angry_bunny_nonce')) {
        wp_send_json_error(array('message' => __('Invalid nonce.', 'angry-bunny')));
    }

    $license_key = isset($_POST['license_key']) ? sanitize_text_field($_POST['license_key']) : '';
    $license_action = isset($_POST['license_action']) ? sanitize_text_field($_POST['license_action']) : '';

    if (empty($license_key) && $license_action === 'angry_bunny_activate_license') {
        wp_send_json_error(array('message' => __('Please enter a license key.', 'angry-bunny')));
    }

    $license_manager = new Angry_Bunny_License();
    
    if ($license_action === 'angry_bunny_activate_license') {
        $result = $license_manager->activate_license($license_key);
    } else if ($license_action === 'angry_bunny_deactivate_license') {
        $result = $license_manager->deactivate_license();
    } else {
        wp_send_json_error(array('message' => __('Invalid action.', 'angry-bunny')));
    }

    if ($result['success']) {
        wp_send_json_success(array('message' => $result['message']));
    } else {
        wp_send_json_error(array('message' => $result['message']));
    }
}

/**
 * Initialize the plugin
 */
function angry_bunny_init() {
    $license_api = new Angry_Bunny_License_API();
    add_action('rest_api_init', array($license_api, 'register_routes'));

    if (!get_option('angry_bunny_api_key')) {
        update_option('angry_bunny_api_key', wp_generate_password(32, false));
    }
}

/**
 * Plugin activation
 */
function angry_bunny_activate() {
    $errors = angry_bunny_check_requirements();
    if (!empty($errors)) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(implode('<br>', $errors), 'Plugin Activation Error', array('back_link' => true));
    }

    // Initialize options with default values
    $default_options = array(
        'angry_bunny_last_scan' => '',
        'angry_bunny_scan_frequency' => 'daily',
        'angry_bunny_email_notifications' => '1',
        'angry_bunny_is_pro_activated' => ANGRY_BUNNY_IS_PRO,
        'angry_bunny_licenses' => array()
    );

    foreach ($default_options as $option => $value) {
        add_option($option, $value);
    }

    // Schedule events
    wp_clear_scheduled_hook('angry_bunny_security_scan');
    wp_clear_scheduled_hook('angry_bunny_daily_license_check');
    
    wp_schedule_event(time(), 'daily', 'angry_bunny_security_scan');
    wp_schedule_event(time(), 'daily', 'angry_bunny_daily_license_check');
}

/**
 * Deactivation hook
 */
function angry_bunny_deactivate() {
    // Clear scheduled events
    wp_clear_scheduled_hook('angry_bunny_security_scan');
    wp_clear_scheduled_hook('angry_bunny_daily_license_check');
}
register_deactivation_hook(__FILE__, 'angry_bunny_deactivate');

/**
 * Uninstall hook
 */
function angry_bunny_uninstall() {
    // Remove plugin data
    delete_option('angry_bunny_last_scan');
    delete_option('angry_bunny_scan_frequency');
    delete_option('angry_bunny_email_notifications');
    delete_option('angry_bunny_is_pro_activated');
    delete_option('angry_bunny_licenses');
    delete_option('angry_bunny_api_key');
}
register_uninstall_hook(__FILE__, 'angry_bunny_uninstall');

/**
 * Check for updates when WordPress checks for plugin updates
 */
function angry_bunny_check_for_update($transient) {
    if (empty($transient->checked)) {
        return $transient;
    }

    // Get the update data
    $update_data = get_site_transient('angry_bunny_pro_update_data');
    
    if ($update_data && version_compare(ANGRY_BUNNY_VERSION, $update_data->new_version, '<')) {
        $plugin_slug = plugin_basename(__FILE__);
        
        $transient->response[$plugin_slug] = (object) array(
            'new_version' => $update_data->new_version,
            'package'     => $update_data->package,
            'slug'        => dirname($plugin_slug),
            'url'         => $update_data->url
        );
    }

    return $transient;
}

/**
 * Filter the plugins API to include our custom update data
 */
function angry_bunny_plugins_api_filter($result, $action, $args) {
    if ($action !== 'plugin_information') {
        return $result;
    }

    if (!isset($args->slug) || $args->slug !== dirname(plugin_basename(__FILE__))) {
        return $result;
    }

    $update_data = get_site_transient('angry_bunny_pro_update_data');
    
    if ($update_data) {
        $result = (object) array(
            'name'              => 'Angry Bunny Security Scanner Pro',
            'slug'              => $args->slug,
            'version'           => $update_data->new_version,
            'author'           => 'socialrabbit',
            'requires'         => '5.0',
            'tested'           => $update_data->tested,
            'last_updated'     => $update_data->last_updated,
            'homepage'         => $update_data->homepage,
            'sections'         => array(
                'description'  => $update_data->sections->description,
                'changelog'    => $update_data->sections->changelog
            ),
            'download_link'    => $update_data->package
        );
    }

    return $result;
}

// Initialize plugin
add_action('plugins_loaded', function() {
    try {
        // Initialize the plugin
        $plugin = new Angry_Bunny();
        $plugin->run();

        // Add init hooks after plugin is loaded
        add_action('init', 'angry_bunny_init');
        
        // Add AJAX handlers
        add_action('wp_ajax_angry_bunny_activate_license', 'angry_bunny_handle_license_ajax');
        add_action('wp_ajax_angry_bunny_deactivate_license', 'angry_bunny_handle_license_ajax');
        
        // Add update hooks
        if (ANGRY_BUNNY_IS_PRO) {
            add_filter('pre_set_site_transient_update_plugins', 'angry_bunny_check_for_update');
            add_filter('plugins_api', 'angry_bunny_plugins_api_filter', 10, 3);
        }
    } catch (Exception $e) {
        error_log('Angry Bunny Plugin - Initialization error: ' . $e->getMessage());
        add_action('admin_notices', function() use ($e) {
            echo '<div class="notice notice-error"><p>' . 
                esc_html__('Error initializing Angry Bunny Security Scanner: ', 'angry-bunny') . 
                esc_html($e->getMessage()) . '</p></div>';
        });
    }
});

// Register activation/deactivation hooks
register_activation_hook(__FILE__, 'angry_bunny_activate');
register_deactivation_hook(__FILE__, 'angry_bunny_deactivate');
register_uninstall_hook(__FILE__, 'angry_bunny_uninstall');