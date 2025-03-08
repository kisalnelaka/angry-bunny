<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @since      1.0.0
 * @package    AngryBunny
 * @author     Kisal Nelaka <kisalnelaka6@gmail.com>
 * @link       https://github.com/kisalnelaka
 * @license    GPL-2.0+
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2 or later
 */

class Angry_Bunny_Admin {

    /**
     * Severity levels and their descriptions
     */
    private $severity_levels = array(
        'critical' => array(
            'label' => 'Critical',
            'impact' => 'Immediate action required',
            'description' => 'These issues pose an immediate security risk and should be addressed as soon as possible.'
        ),
        'high' => array(
            'label' => 'High',
            'impact' => 'Action required soon',
            'description' => 'These issues pose a significant security risk and should be addressed promptly.'
        ),
        'medium' => array(
            'label' => 'Medium',
            'impact' => 'Action recommended',
            'description' => 'These issues pose a moderate security risk and should be addressed when possible.'
        ),
        'warning' => array(
            'label' => 'Warning',
            'impact' => 'Action suggested',
            'description' => 'These issues pose a low security risk but should be reviewed.'
        )
    );

    /**
     * Initialize the class and set its properties.
     */
    public function __construct() {
        // Add admin menu and submenu pages
        add_action('admin_menu', array($this, 'add_plugin_admin_menu'));
        
        // Add admin styles and scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Add settings
        add_action('admin_init', array($this, 'register_settings'));
        
        // Add menu icon styles
        add_action('admin_head', array($this, 'add_menu_icon_styles'));
        
        // Add AJAX handlers
        add_action('wp_ajax_angry_bunny_generate_license', array($this, 'handle_generate_license'));
        add_action('wp_ajax_angry_bunny_revoke_license', array($this, 'handle_revoke_license'));

        // Add footer text
        add_action('admin_footer_text', array($this, 'add_admin_footer_text'));
        add_action('wp_footer', array($this, 'add_frontend_footer_text'));

        // Enqueue frontend styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_styles'));
    }

    /**
     * Add custom menu icon styles
     */
    public function add_menu_icon_styles() {
        ?>
        <style>
            .toplevel_page_angry-bunny .wp-menu-image {
                background-repeat: no-repeat;
                background-position: center;
                background-size: 20px;
            }
            .toplevel_page_angry-bunny .wp-menu-image::before {
                content: '';
                background-image: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCI+PHBhdGggZmlsbD0iY3VycmVudENvbG9yIiBkPSJNMTIgMkM2LjQ4IDIgMiA2LjQ4IDIgMTJzNC40OCAxMCAxMCAxMCAxMC00LjQ4IDEwLTEwUzE3LjUyIDIgMTIgMnptMCAxOGMtNC40MSAwLTgtMy41OS04LThzMy41OS04IDgtOCA4IDMuNTkgOCA4LTMuNTkgOC04IDh6Ii8+PC9zdmc+');
                background-repeat: no-repeat;
                background-position: center;
                background-size: 20px;
                opacity: 0.6;
            }
            .toplevel_page_angry-bunny:hover .wp-menu-image::before,
            .toplevel_page_angry-bunny.current .wp-menu-image::before {
                opacity: 1;
            }
            /* Add pirate patch and hat */
            .toplevel_page_angry-bunny .wp-menu-image::after {
                content: '';
                position: absolute;
                top: 8px;
                left: 50%;
                transform: translateX(-50%);
                width: 24px;
                height: 24px;
                background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%23a7aaad"><path d="M12 3c-1.1 0-2 .9-2 2v1h4V5c0-1.1-.9-2-2-2zm-2 4v2h4V7h-4z"/><path d="M8 6c-.55 0-1 .45-1 1v2h2V7c0-.55-.45-1-1-1zm8 0c-.55 0-1 .45-1 1v2h2V7c0-.55-.45-1-1-1z"/></svg>');
                background-repeat: no-repeat;
                background-position: center;
                background-size: 24px;
                pointer-events: none;
            }
            .toplevel_page_angry-bunny:hover .wp-menu-image::after,
            .toplevel_page_angry-bunny.current .wp-menu-image::after {
                background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%23ffffff"><path d="M12 3c-1.1 0-2 .9-2 2v1h4V5c0-1.1-.9-2-2-2zm-2 4v2h4V7h-4z"/><path d="M8 6c-.55 0-1 .45-1 1v2h2V7c0-.55-.45-1-1-1zm8 0c-.55 0-1 .45-1 1v2h2V7c0-.55-.45-1-1-1z"/></svg>');
            }
        </style>
        <?php
    }

    /**
     * Register the stylesheets for the admin area.
     */
    public function enqueue_styles($hook) {
        // Only load on plugin pages
        if (strpos($hook, 'angry-bunny') === false) {
            return;
        }

        wp_enqueue_style(
            'angry-bunny-admin',
            ANGRY_BUNNY_PLUGIN_URL . 'admin/css/angry-bunny-admin.css',
            array(),
            ANGRY_BUNNY_VERSION,
            'all'
        );
    }

    /**
     * Register the JavaScript for the admin area.
     */
    public function enqueue_scripts($hook) {
        // Only load on plugin pages
        if (strpos($hook, 'angry-bunny') === false) {
            return;
        }

        wp_enqueue_script(
            'angry-bunny-admin',
            ANGRY_BUNNY_PLUGIN_URL . 'admin/js/angry-bunny-admin.js',
            array('jquery'),
            ANGRY_BUNNY_VERSION,
            true
        );

        wp_localize_script('angry-bunny-admin', 'angryBunnyAdmin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('angry_bunny_admin_nonce'),
            'i18n' => array(
                'error' => __('Error', 'angry-bunny'),
                'success' => __('Success', 'angry-bunny')
            )
        ));
    }

    /**
     * Add plugin admin menu
     */
    public function add_plugin_admin_menu() {
        // Add main menu
        add_menu_page(
            __('Angry Bunny Security', 'angry-bunny'),
            __('Angry Bunny Security', 'angry-bunny'),
            'manage_options',
            'angry-bunny',
            array($this, 'display_plugin_admin_page'),
            'dashicons-shield'
        );

        // Remove duplicate submenu
        remove_submenu_page('angry-bunny', 'angry-bunny');

        // Add submenus
        add_submenu_page(
            'angry-bunny',
            __('Dashboard', 'angry-bunny'),
            __('Dashboard', 'angry-bunny'),
            'manage_options',
            'angry-bunny',
            array($this, 'display_plugin_admin_page')
        );

        add_submenu_page(
            'angry-bunny',
            __('Features', 'angry-bunny'),
            __('Features', 'angry-bunny'),
            'manage_options',
            'angry-bunny-features',
            array($this, 'display_features_page')
        );

        // Only show license management for pro version or during trial
        if (defined('ANGRY_BUNNY_IS_PRO') && ANGRY_BUNNY_IS_PRO || get_option('angry_bunny_trial_active')) {
            add_submenu_page(
                'angry-bunny',
                __('License Management', 'angry-bunny'),
                __('License', 'angry-bunny'),
                'manage_options',
                'angry-bunny-licenses',
                array($this, 'display_license_management_page')
            );
        }
    }

    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting('angry_bunny_options', 'angry_bunny_scan_frequency');
        register_setting('angry_bunny_options', 'angry_bunny_email_notifications');
        register_setting('angry_bunny_options', 'angry_bunny_api_key');
        register_setting('angry_bunny_options', 'angry_bunny_show_footer_badge');
    }

    /**
     * Admin page callback
     */
    public function display_plugin_admin_page() {
        $last_scan = get_option('angry_bunny_last_scan');
        $scan_results = get_option('angry_bunny_last_scan_results', array());
        $security_score = get_option('angry_bunny_security_score', 100);
        $score_info = Angry_Bunny_Scanner::get_score_grade($security_score);

        // Group issues by severity
        $issues_by_severity = array();
        foreach ($this->severity_levels as $severity => $info) {
            $issues_by_severity[$severity] = array();
        }
        foreach ($scan_results as $issue) {
            if (isset($issues_by_severity[$issue['severity']])) {
                $issues_by_severity[$issue['severity']][] = $issue;
            }
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="angry-bunny-controls">
                <div class="scan-controls">
                    <button type="button" id="run-scan" class="button button-primary">
                        <?php _e('Run Security Scan', 'angry-bunny'); ?>
                    </button>
                    
                    <?php if ($last_scan): ?>
                        <p class="last-scan">
                            <?php printf(
                                __('Last scan: %s', 'angry-bunny'),
                                date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($last_scan))
                            ); ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="security-score">
                <div class="score-circle" style="border-color: <?php echo esc_attr($score_info['color']); ?>">
                    <div class="score"><?php echo esc_html($security_score); ?>%</div>
                    <div class="grade"><?php echo esc_html($score_info['grade']); ?></div>
                </div>
                <div class="score-text" style="color: <?php echo esc_attr($score_info['color']); ?>">
                    <?php echo esc_html($score_info['text']); ?>
                </div>
                <p class="score-description">
                    <?php
                    if ($security_score == 100) {
                        _e('Congratulations! Your site has passed all security checks.', 'angry-bunny');
                    } else {
                        printf(
                            __('Your site has a security score of %d%%. Address the issues below to improve your score.', 'angry-bunny'),
                            $security_score
                        );
                    }
                    ?>
                </p>
            </div>

            <?php if (!empty($scan_results)): ?>
                <div class="severity-overview">
                    <?php foreach ($this->severity_levels as $severity => $info): 
                        $count = count($issues_by_severity[$severity]);
                        $has_issues = $count > 0;
                        ?>
                        <div class="severity-count severity-<?php echo esc_attr($severity); ?><?php echo $has_issues ? ' has-issues' : ''; ?>">
                            <h3><?php echo esc_html($info['label']); ?></h3>
                            <div class="count"><?php echo esc_html($count); ?></div>
                            <div class="impact"><?php echo esc_html($info['impact']); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="post" action="options.php">
                <?php
                settings_fields('angry_bunny_options');
                do_settings_sections('angry_bunny_options');
                ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Scan Frequency', 'angry-bunny'); ?></th>
                        <td>
                            <select name="angry_bunny_scan_frequency">
                                <option value="daily" <?php selected(get_option('angry_bunny_scan_frequency'), 'daily'); ?>>
                                    <?php _e('Daily', 'angry-bunny'); ?>
                                </option>
                                <option value="weekly" <?php selected(get_option('angry_bunny_scan_frequency'), 'weekly'); ?>>
                                    <?php _e('Weekly', 'angry-bunny'); ?>
                                </option>
                                <option value="monthly" <?php selected(get_option('angry_bunny_scan_frequency'), 'monthly'); ?>>
                                    <?php _e('Monthly', 'angry-bunny'); ?>
                                </option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Email Notifications', 'angry-bunny'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="angry_bunny_email_notifications" value="1"
                                    <?php checked(get_option('angry_bunny_email_notifications'), '1'); ?>>
                                <?php _e('Send email notifications when issues are found', 'angry-bunny'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Footer Badge', 'angry-bunny'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="angry_bunny_show_footer_badge" value="1"
                                    <?php checked(get_option('angry_bunny_show_footer_badge'), '1'); ?>>
                                <?php _e('Show "Secured by Angry Bunny Security" badge in footer', 'angry-bunny'); ?>
                            </label>
                            <p class="description">
                                <?php _e('This will display a small security badge in the admin and frontend footer areas.', 'angry-bunny'); ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>

            <div id="scan-results">
                <?php if (!empty($scan_results)): ?>
                    <?php foreach ($this->severity_levels as $severity => $info): 
                        if (empty($issues_by_severity[$severity])) continue;
                        ?>
                        <div class="security-issues-by-severity severity-<?php echo esc_attr($severity); ?>">
                            <h3>
                                <?php echo esc_html($info['label']); ?> Issues
                                <small style="font-weight: normal; margin-left: 10px;">
                                    <?php echo esc_html($info['description']); ?>
                                </small>
                            </h3>
                            <div class="issues-list">
                                <?php foreach ($issues_by_severity[$severity] as $issue): ?>
                                    <div class="security-issue">
                                        <h4><?php echo esc_html($issue['description']); ?></h4>
                                        <p class="solution">
                                            <strong><?php _e('Solution:', 'angry-bunny'); ?></strong>
                                            <?php echo esc_html($issue['solution']); ?>
                                        </p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Display features comparison page
     */
    public function display_features_page() {
        $features = Angry_Bunny_Features::get_all_features();
        $is_pro = defined('ANGRY_BUNNY_IS_PRO') && ANGRY_BUNNY_IS_PRO;
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <?php if (!$is_pro): ?>
            <div class="notice notice-info">
                <p>
                    <strong>Upgrade to Pro!</strong> Get access to advanced security features and protect your site like never before.
                    <a href="https://yourdomain.com/angry-bunny-pro" target="_blank" class="button button-primary">Learn More</a>
                </p>
            </div>
            <?php endif; ?>

            <div class="angry-bunny-features-grid">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Feature</th>
                            <th>Description</th>
                            <th>Free</th>
                            <th>Pro</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($features as $key => $feature): ?>
                        <tr>
                            <td><strong><?php echo esc_html($feature['name']); ?></strong></td>
                            <td><?php echo esc_html($feature['description']); ?></td>
                            <td>
                                <?php if (!$feature['is_pro']): ?>
                                    <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
                                <?php else: ?>
                                    <span class="dashicons dashicons-no-alt" style="color: #dc3232;"></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if (!$is_pro): ?>
            <div class="angry-bunny-upgrade-cta" style="margin-top: 20px; text-align: center;">
                <h2>Ready to upgrade?</h2>
                <p>Get all these amazing features and more with Angry Bunny Pro!</p>
                <a href="https://yourdomain.com/angry-bunny-pro" target="_blank" class="button button-primary button-hero">
                    Upgrade to Pro
                </a>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Display license management page
     */
    public function display_license_management_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $licenses = get_option('angry_bunny_licenses', array());
        $api_key = get_option('angry_bunny_api_key');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <div class="angry-bunny-admin-section">
                <h2>API Settings</h2>
                <p>Use this API key to authenticate license validation requests from client sites.</p>
                <div class="angry-bunny-api-key">
                    <input type="text" readonly value="<?php echo esc_attr($api_key); ?>" class="large-text" />
                    <button class="button button-secondary copy-api-key">Copy API Key</button>
                </div>
            </div>

            <div class="angry-bunny-admin-section">
                <h2>Generate New License</h2>
                <form id="generate-license-form" method="post">
                    <?php wp_nonce_field('angry_bunny_generate_license', 'angry_bunny_nonce'); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="site_limit">Site Limit</label></th>
                            <td>
                                <input type="number" id="site_limit" name="site_limit" min="1" value="1" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="owner_email">Owner Email</label></th>
                            <td>
                                <input type="email" id="owner_email" name="owner_email" class="regular-text" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="owner_name">Owner Name</label></th>
                            <td>
                                <input type="text" id="owner_name" name="owner_name" class="regular-text" />
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <input type="submit" name="generate_license" class="button button-primary" value="Generate License" />
                    </p>
                </form>
            </div>

            <div class="angry-bunny-admin-section">
                <h2>Active Licenses</h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>License Key</th>
                            <th>Owner</th>
                            <th>Status</th>
                            <th>Sites</th>
                            <th>Expires</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($licenses as $key => $license) : ?>
                            <tr>
                                <td><?php echo esc_html($key); ?></td>
                                <td><?php echo esc_html($license['owner_name']); ?><br/><small><?php echo esc_html($license['owner_email']); ?></small></td>
                                <td><?php echo esc_html(ucfirst($license['status'])); ?></td>
                                <td><?php echo count($license['sites']); ?> / <?php echo esc_html($license['site_limit']); ?></td>
                                <td><?php echo esc_html(date('Y-m-d', strtotime($license['expires']))); ?></td>
                                <td>
                                    <button class="button button-small revoke-license" data-license="<?php echo esc_attr($key); ?>">Revoke</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    public function handle_generate_license() {
        check_ajax_referer('angry_bunny_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        $site_limit = isset($_POST['site_limit']) ? intval($_POST['site_limit']) : 1;
        $owner_email = isset($_POST['owner_email']) ? sanitize_email($_POST['owner_email']) : '';
        $owner_name = isset($_POST['owner_name']) ? sanitize_text_field($_POST['owner_name']) : '';

        $license_key = Angry_Bunny_License_Manager::generate_license_key();
        $license_data = array(
            'status' => 'active',
            'site_limit' => $site_limit,
            'owner_email' => $owner_email,
            'owner_name' => $owner_name,
            'sites' => array()
        );

        if (Angry_Bunny_License_Manager::store_license_data($license_key, $license_data)) {
            wp_send_json_success(array(
                'license_key' => $license_key,
                'message' => 'License generated successfully'
            ));
        } else {
            wp_send_json_error('Failed to generate license');
        }
    }

    /**
     * Handle license revocation
     */
    public function handle_revoke_license() {
        check_ajax_referer('angry_bunny_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        $license_key = isset($_POST['license_key']) ? sanitize_text_field($_POST['license_key']) : '';
        
        if (empty($license_key)) {
            wp_send_json_error('License key is required');
        }

        $result = Angry_Bunny_License_Manager::revoke_license($license_key);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }

    /**
     * Add security badge to admin footer if enabled
     */
    public function add_admin_footer_text($text) {
        if (!get_option('angry_bunny_show_footer_badge', false)) {
            return $text;
        }

        $screen = get_current_screen();
        $is_plugin_page = ($screen && strpos($screen->id, 'angry-bunny') !== false);
        
        if ($is_plugin_page) {
            return sprintf(
                '<span class="angry-bunny-footer">%s %s</span>',
                esc_html__('Secured by', 'angry-bunny'),
                esc_html__('Angry Bunny Security', 'angry-bunny')
            );
        }

        // Add a small shield icon to the footer on all admin pages
        $shield_icon = '<span class="dashicons dashicons-shield" style="color: #82878c; vertical-align: text-bottom;"></span>';
        return $text . ' | ' . $shield_icon . ' ' . sprintf(
            '<span class="angry-bunny-footer-badge">%s %s</span>',
            esc_html__('Secured by', 'angry-bunny'),
            esc_html__('Angry Bunny', 'angry-bunny')
        );
    }

    /**
     * Add security badge to frontend footer if enabled
     */
    public function add_frontend_footer_text() {
        if (!get_option('angry_bunny_show_footer_badge', false)) {
            return;
        }

        $shield_icon = '<span class="dashicons dashicons-shield"></span>';
        echo '<div class="angry-bunny-footer-badge" style="text-align: center; margin: 20px 0; color: #666;">' . 
            $shield_icon . ' ' . sprintf(
                '%s %s',
                esc_html__('Secured by', 'angry-bunny'),
                esc_html__('Angry Bunny Security', 'angry-bunny')
            ) . 
        '</div>';
    }

    /**
     * Enqueue frontend styles
     */
    public function enqueue_frontend_styles() {
        wp_enqueue_style('dashicons');
    }
} 