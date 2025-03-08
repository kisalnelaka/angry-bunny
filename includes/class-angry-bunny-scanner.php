<?php
/**
 * The security scanner functionality of the plugin.
 *
 * @since      1.0.0
 * @package    AngryBunny
 */

class Angry_Bunny_Scanner {

    /**
     * Array to store scan results
     */
    private $scan_results = array();

    /**
     * Security score weights by severity
     */
    private $severity_weights = array(
        'critical' => 25,
        'high' => 15,
        'medium' => 10,
        'warning' => 5
    );

    /**
     * Run the security scan
     */
    public function run_security_scan() {
        try {
            $this->scan_results = array();
            
            // Run various security checks with error handling
            $checks = array(
                'check_wordpress_version',
                'check_plugin_updates',
                'check_theme_updates',
                'check_file_permissions',
                'check_admin_user',
                'check_ssl_status',
                'check_debug_status',
                'check_database_prefix',
                'check_file_editor'
            );

            foreach ($checks as $check) {
                try {
                    if (method_exists($this, $check)) {
                        $this->$check();
                    }
                } catch (Exception $e) {
                    error_log('Angry Bunny Scanner - Error in ' . $check . ': ' . $e->getMessage());
                    // Add as a scan result instead of throwing
                    $this->add_issue(
                        'scan_error_' . $check,
                        'warning',
                        sprintf(__('Error during %s check', 'angry-bunny'), $check),
                        $e->getMessage()
                    );
                }
            }
            
            // Calculate security score
            $score = $this->calculate_security_score();
            
            // Save scan results and score
            update_option('angry_bunny_last_scan_results', $this->scan_results);
            update_option('angry_bunny_last_scan', current_time('mysql'));
            update_option('angry_bunny_security_score', $score);
            
            // Send email notification if enabled
            if (get_option('angry_bunny_email_notifications', '1')) {
                try {
                    $this->send_notification_email($score);
                } catch (Exception $e) {
                    error_log('Angry Bunny Scanner - Error sending notification: ' . $e->getMessage());
                }
            }

            if (defined('DOING_AJAX') && DOING_AJAX) {
                wp_send_json_success(array(
                    'message' => __('Security scan completed successfully.', 'angry-bunny'),
                    'results' => $this->scan_results,
                    'score' => $score
                ));
            }
            
            return true;

        } catch (Exception $e) {
            error_log('Angry Bunny Scanner - Critical error: ' . $e->getMessage());
            if (defined('DOING_AJAX') && DOING_AJAX) {
                wp_send_json_error(array(
                    'message' => __('Error running security scan: ', 'angry-bunny') . $e->getMessage()
                ));
            }
            throw $e;
        }
    }

    /**
     * Calculate security score based on issues found
     */
    private function calculate_security_score() {
        $total_weight = 0;
        $issues_weight = 0;

        // Count total possible weight
        foreach ($this->severity_weights as $weight) {
            $total_weight += $weight;
        }
        $total_weight *= count($this->get_all_checks());

        // Calculate weight of found issues
        foreach ($this->scan_results as $issue) {
            if (isset($this->severity_weights[$issue['severity']])) {
                $issues_weight += $this->severity_weights[$issue['severity']];
            }
        }

        // Calculate score (100 is best, 0 is worst)
        $score = 100;
        if ($total_weight > 0) {
            $score = max(0, 100 - ($issues_weight * 100 / $total_weight));
        }

        return round($score);
    }

    /**
     * Get list of all security checks
     */
    private function get_all_checks() {
        return array(
            'wordpress_version' => __('WordPress Version', 'angry-bunny'),
            'plugin_updates' => __('Plugin Updates', 'angry-bunny'),
            'theme_updates' => __('Theme Updates', 'angry-bunny'),
            'file_permissions' => __('File Permissions', 'angry-bunny'),
            'admin_user' => __('Admin Username', 'angry-bunny'),
            'ssl_status' => __('SSL Status', 'angry-bunny'),
            'debug_status' => __('Debug Mode', 'angry-bunny'),
            'database_prefix' => __('Database Prefix', 'angry-bunny'),
            'file_editor' => __('File Editor', 'angry-bunny')
        );
    }

    /**
     * Get security score grade and color
     */
    public static function get_score_grade($score) {
        if ($score >= 90) {
            return array(
                'grade' => 'A',
                'color' => '#46b450',
                'text' => __('Excellent', 'angry-bunny')
            );
        } elseif ($score >= 80) {
            return array(
                'grade' => 'B',
                'color' => '#00a0d2',
                'text' => __('Good', 'angry-bunny')
            );
        } elseif ($score >= 70) {
            return array(
                'grade' => 'C',
                'color' => '#ffb900',
                'text' => __('Fair', 'angry-bunny')
            );
        } elseif ($score >= 60) {
            return array(
                'grade' => 'D',
                'color' => '#f56e28',
                'text' => __('Poor', 'angry-bunny')
            );
        } else {
            return array(
                'grade' => 'F',
                'color' => '#dc3232',
                'text' => __('Critical', 'angry-bunny')
            );
        }
    }

    /**
     * Check WordPress version
     */
    private function check_wordpress_version() {
        global $wp_version;
        $latest_version = $this->get_latest_wordpress_version();

        if (!$latest_version) {
            throw new Exception(__('Unable to fetch latest WordPress version', 'angry-bunny'));
        }

        if (version_compare($wp_version, $latest_version, '<')) {
            $this->add_issue(
                'wordpress_version',
                'critical',
                sprintf(
                    __('WordPress is outdated. Current version: %s, Latest version: %s', 'angry-bunny'),
                    $wp_version,
                    $latest_version
                ),
                __('Update WordPress to the latest version through your WordPress dashboard.', 'angry-bunny')
            );
        }
    }

    /**
     * Get latest WordPress version
     */
    private function get_latest_wordpress_version() {
        $response = wp_remote_get('https://api.wordpress.org/core/version-check/1.7/');
        
        if (is_wp_error($response)) {
            error_log('Angry Bunny Scanner - WordPress version check error: ' . $response->get_error_message());
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        if (empty($body)) {
            error_log('Angry Bunny Scanner - Empty response from WordPress version check');
            return false;
        }

        $body = json_decode($body);
        if (isset($body->offers[0]->version)) {
            return $body->offers[0]->version;
        }

        return false;
    }

    /**
     * Check for plugin updates
     */
    private function check_plugin_updates() {
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        if (!function_exists('wp_get_plugin_updates')) {
            require_once ABSPATH . 'wp-admin/includes/update.php';
        }
        
        $plugins = get_plugins();
        
        // Force WordPress to check for plugin updates
        wp_update_plugins();
        
        $updates = get_site_transient('update_plugins');
        if (!is_object($updates)) {
            return;
        }

        if (empty($updates->response)) {
            return;
        }
        
        foreach ($updates->response as $plugin_file => $plugin_data) {
            if (isset($plugins[$plugin_file])) {
                $this->add_issue(
                    'plugin_update_' . $plugin_file,
                    'warning',
                    sprintf(
                        __('Plugin "%s" needs to be updated', 'angry-bunny'),
                        $plugins[$plugin_file]['Name']
                    ),
                    __('Update the plugin through your WordPress dashboard.', 'angry-bunny')
                );
            }
        }
    }

    /**
     * Check file permissions
     */
    private function check_file_permissions() {
        $files_to_check = array(
            ABSPATH . 'wp-config.php' => '0400',
            ABSPATH . 'wp-admin' => '0750',
            ABSPATH . 'wp-includes' => '0750',
            WP_CONTENT_DIR => '0755'
        );

        foreach ($files_to_check as $file => $recommended_perms) {
            if (file_exists($file)) {
                $current_perms = substr(sprintf('%o', fileperms($file)), -4);
                if ($current_perms > $recommended_perms) {
                    $this->add_issue(
                        'file_permissions_' . basename($file),
                        'critical',
                        sprintf(
                            __('File permissions too loose for %s. Current: %s, Recommended: %s', 'angry-bunny'),
                            basename($file),
                            $current_perms,
                            $recommended_perms
                        ),
                        sprintf(
                            __('Change file permissions using: chmod %s %s', 'angry-bunny'),
                            $recommended_perms,
                            $file
                        )
                    );
                }
            }
        }
    }

    /**
     * Check if admin username exists
     */
    private function check_admin_user() {
        $user = get_user_by('login', 'admin');
        if ($user) {
            $this->add_issue(
                'admin_user',
                'high',
                __('Default "admin" username exists', 'angry-bunny'),
                __('Change the username or create a new administrator account and delete this one.', 'angry-bunny')
            );
        }
    }

    /**
     * Check SSL status
     */
    private function check_ssl_status() {
        if (!is_ssl()) {
            $this->add_issue(
                'ssl_status',
                'high',
                __('SSL is not enabled', 'angry-bunny'),
                __('Enable SSL on your website to encrypt data transmission.', 'angry-bunny')
            );
        }
    }

    /**
     * Check debug status
     */
    private function check_debug_status() {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $this->add_issue(
                'debug_status',
                'medium',
                __('WordPress debug mode is enabled', 'angry-bunny'),
                __('Disable WP_DEBUG in wp-config.php in production environment.', 'angry-bunny')
            );
        }
    }

    /**
     * Add an issue to scan results
     */
    private function add_issue($id, $severity, $description, $solution) {
        $this->scan_results[] = array(
            'id' => $id,
            'severity' => $severity,
            'description' => $description,
            'solution' => $solution,
            'timestamp' => current_time('mysql')
        );
    }

    /**
     * Send notification email
     */
    private function send_notification_email($score) {
        $to = get_option('admin_email');
        $subject = sprintf(__('[%s] Security Scan Results', 'angry-bunny'), get_bloginfo('name'));
        
        $grade = self::get_score_grade($score);
        
        $message = sprintf(
            __("Security scan completed. Your security score is: %d%% (%s)\n\n", 'angry-bunny'),
            $score,
            $grade['text']
        );
        
        if (!empty($this->scan_results)) {
            $message .= __("Issues found:\n\n", 'angry-bunny');
            
            foreach ($this->scan_results as $issue) {
                $message .= sprintf(
                    "Severity: %s\nIssue: %s\nSolution: %s\n\n",
                    strtoupper($issue['severity']),
                    $issue['description'],
                    $issue['solution']
                );
            }
        } else {
            $message .= __("No security issues were found.\n", 'angry-bunny');
        }

        $headers = array('Content-Type: text/plain; charset=UTF-8');
        
        $result = wp_mail($to, $subject, $message, $headers);
        
        if (!$result) {
            error_log('Angry Bunny Scanner - Failed to send notification email');
        }
    }

    /**
     * Check database prefix
     */
    private function check_database_prefix() {
        global $wpdb;
        if ($wpdb->prefix === 'wp_') {
            $this->add_issue(
                'db_prefix',
                'medium',
                __('Default database prefix (wp_) is being used', 'angry-bunny'),
                __('Change the database prefix in wp-config.php and update the database tables accordingly.', 'angry-bunny')
            );
        }
    }

    /**
     * Check if file editor is enabled
     */
    private function check_file_editor() {
        if (!defined('DISALLOW_FILE_EDIT') || !DISALLOW_FILE_EDIT) {
            $this->add_issue(
                'file_editor',
                'medium',
                __('WordPress file editor is enabled', 'angry-bunny'),
                __('Add define(\'DISALLOW_FILE_EDIT\', true); to wp-config.php to disable the file editor.', 'angry-bunny')
            );
        }
    }
} 