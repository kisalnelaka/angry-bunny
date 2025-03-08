<?php
/**
 * The core plugin class.
 *
 * @since      1.0.0
 * @package    AngryBunny
 */

class Angry_Bunny {

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     */
    protected $loader;

    /**
     * The scanner instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Angry_Bunny_Scanner    $scanner    Handles all security scanning operations.
     */
    protected $scanner;

    /**
     * The admin instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Angry_Bunny_Admin    $admin    Handles all admin-specific functionality.
     */
    protected $admin;

    /**
     * Initialize the class and set its properties.
     */
    public function __construct() {
        $this->loader = new Angry_Bunny_Loader();
        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_scanner_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     */
    private function load_dependencies() {
        $this->scanner = new Angry_Bunny_Scanner();
        $this->admin = new Angry_Bunny_Admin();
    }

    /**
     * Set the locale for internationalization.
     */
    private function set_locale() {
        $this->loader->add_action('plugins_loaded', $this, 'load_plugin_textdomain');
    }

    /**
     * Load the plugin text domain for translation.
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'angry-bunny',
            false,
            dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );
    }

    /**
     * Register all of the hooks related to the admin area functionality.
     */
    private function define_admin_hooks() {
        $this->loader->add_action('admin_enqueue_scripts', $this->admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $this->admin, 'enqueue_scripts');
        $this->loader->add_action('admin_menu', $this->admin, 'add_plugin_admin_menu');
        $this->loader->add_action('admin_init', $this->admin, 'register_settings');
    }

    /**
     * Register all of the hooks related to the scanner functionality.
     */
    private function define_scanner_hooks() {
        // Schedule security scan
        if (!wp_next_scheduled('angry_bunny_security_scan')) {
            wp_schedule_event(time(), 'daily', 'angry_bunny_security_scan');
        }
        
        $this->loader->add_action('angry_bunny_security_scan', $this->scanner, 'run_security_scan');
        $this->loader->add_action('wp_ajax_run_manual_scan', $this, 'handle_manual_scan');
    }

    /**
     * Handle manual scan AJAX request
     */
    public function handle_manual_scan() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'angry_bunny_nonce')) {
            wp_send_json_error('Invalid security token');
        }

        // Verify user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        try {
            $this->scanner->run_security_scan();
            wp_send_json_success('Scan completed successfully');
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * Run the plugin.
     */
    public function run() {
        $this->loader->run();
    }
} 