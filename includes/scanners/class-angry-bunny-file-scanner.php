<?php
/**
 * File integrity scanner functionality.
 *
 * @since      1.0.0
 * @package    AngryBunny
 */

class Angry_Bunny_File_Scanner {

    /**
     * Array to store scan results
     */
    private $scan_results = array();

    /**
     * WordPress core files checksums
     */
    private $core_checksums = array();

    /**
     * Run the file integrity scan
     */
    public function run_scan() {
        try {
            $this->load_core_checksums();
            $this->check_core_files();
            $this->check_file_permissions();
            $this->check_uploads_directory();
            $this->check_htaccess();
            
            $this->save_results();
        } catch (Exception $e) {
            error_log('Angry Bunny File Scanner - Error: ' . $e->getMessage());
        }
    }

    /**
     * Load WordPress core files checksums
     */
    private function load_core_checksums() {
        global $wp_version;
        
        $response = wp_remote_get(
            'https://api.wordpress.org/core/checksums/1.0/?version=' . $wp_version . '&locale=' . get_locale()
        );

        if (is_wp_error($response)) {
            throw new Exception('Failed to load WordPress checksums');
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if ($data && isset($data['checksums'])) {
            $this->core_checksums = $data['checksums'];
        }
    }

    /**
     * Check WordPress core files integrity
     */
    private function check_core_files() {
        if (empty($this->core_checksums)) {
            return;
        }

        foreach ($this->core_checksums as $file => $checksum) {
            $file_path = ABSPATH . $file;
            
            if (!file_exists($file_path)) {
                $this->add_issue(
                    'missing_core_file_' . md5($file),
                    'critical',
                    sprintf('Missing WordPress core file: %s', $file),
                    'Reinstall WordPress or restore the file from a backup'
                );
                continue;
            }

            $file_checksum = md5_file($file_path);
            if ($file_checksum !== $checksum) {
                $this->add_issue(
                    'modified_core_file_' . md5($file),
                    'critical',
                    sprintf('Modified WordPress core file: %s', $file),
                    'Restore the original file or reinstall WordPress'
                );
            }
        }
    }

    /**
     * Check file permissions
     */
    private function check_file_permissions() {
        $files_to_check = array(
            ABSPATH . 'wp-config.php' => array('recommended' => '0400', 'critical' => true),
            ABSPATH . 'wp-admin' => array('recommended' => '0750', 'critical' => true),
            ABSPATH . 'wp-includes' => array('recommended' => '0750', 'critical' => true),
            WP_CONTENT_DIR => array('recommended' => '0755', 'critical' => false),
            WP_CONTENT_DIR . '/themes' => array('recommended' => '0755', 'critical' => false),
            WP_CONTENT_DIR . '/plugins' => array('recommended' => '0755', 'critical' => false),
            WP_CONTENT_DIR . '/uploads' => array('recommended' => '0755', 'critical' => false)
        );

        foreach ($files_to_check as $file => $config) {
            if (!file_exists($file)) {
                continue;
            }

            $perms = substr(sprintf('%o', fileperms($file)), -4);
            if ($perms > $config['recommended']) {
                $this->add_issue(
                    'file_permissions_' . md5($file),
                    $config['critical'] ? 'critical' : 'high',
                    sprintf('Incorrect permissions on %s: %s', basename($file), $perms),
                    sprintf('Change permissions to %s', $config['recommended'])
                );
            }
        }
    }

    /**
     * Check uploads directory for suspicious files
     */
    private function check_uploads_directory() {
        $uploads_dir = wp_upload_dir();
        $base_dir = $uploads_dir['basedir'];

        if (!is_dir($base_dir)) {
            return;
        }

        $suspicious_extensions = array('php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'pht', 'phar');

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($base_dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($ext, $suspicious_extensions)) {
                $this->add_issue(
                    'suspicious_upload_' . md5($file),
                    'high',
                    sprintf('Suspicious file found in uploads: %s', str_replace($base_dir, '', $file)),
                    'Remove the file or verify its legitimacy'
                );
            }
        }
    }

    /**
     * Check .htaccess file
     */
    private function check_htaccess() {
        $htaccess_file = ABSPATH . '.htaccess';
        
        if (!file_exists($htaccess_file)) {
            return;
        }

        $content = file_get_contents($htaccess_file);
        if ($content === false) {
            return;
        }

        $suspicious_patterns = array(
            'RewriteCond.*HTTP_REFERER' => 'Suspicious referrer check',
            'RewriteCond.*HTTP_USER_AGENT' => 'Suspicious user agent check',
            'php_value.*auto_append_file' => 'Suspicious PHP configuration',
            'php_value.*auto_prepend_file' => 'Suspicious PHP configuration',
            'SetHandler.*application/x-httpd-php' => 'Suspicious handler configuration'
        );

        foreach ($suspicious_patterns as $pattern => $description) {
            if (preg_match('/' . $pattern . '/i', $content)) {
                $this->add_issue(
                    'htaccess_' . md5($pattern),
                    'high',
                    sprintf('Suspicious .htaccess configuration: %s', $description),
                    'Review and verify .htaccess configurations'
                );
            }
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
     * Save scan results
     */
    private function save_results() {
        global $wpdb;
        
        foreach ($this->scan_results as $result) {
            $wpdb->insert(
                $wpdb->prefix . 'angry_bunny_security_log',
                array(
                    'event_type' => 'file_integrity',
                    'event_severity' => $result['severity'],
                    'event_message' => $result['description'],
                    'event_data' => json_encode($result)
                ),
                array('%s', '%s', '%s', '%s')
            );
        }

        update_option('angry_bunny_last_file_scan', current_time('mysql'));
        update_option('angry_bunny_file_scan_results', $this->scan_results);
    }
} 