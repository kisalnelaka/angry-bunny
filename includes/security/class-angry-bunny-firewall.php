<?php
/**
 * Basic firewall functionality.
 *
 * @since      1.0.0
 * @package    AngryBunny
 */

class Angry_Bunny_Firewall {

    /**
     * Initialize the firewall
     */
    public function init() {
        if (!get_option('angry_bunny_firewall_enabled', true)) {
            return;
        }

        $this->protect_against_xss();
        $this->protect_against_sql_injection();
        $this->block_suspicious_requests();
        $this->protect_login_page();
    }

    /**
     * Protect against XSS attacks
     */
    private function protect_against_xss() {
        // Check for common XSS patterns in GET/POST data
        $xss_patterns = array(
            '<script',
            'javascript:',
            'onload=',
            'onerror=',
            'onclick=',
            'onmouseover=',
            'eval(',
            'document.cookie'
        );

        foreach ($_GET as $key => $value) {
            foreach ($xss_patterns as $pattern) {
                if (stripos($value, $pattern) !== false) {
                    $this->log_and_block('XSS attempt detected in GET parameter');
                }
            }
        }

        foreach ($_POST as $key => $value) {
            if (is_string($value)) {
                foreach ($xss_patterns as $pattern) {
                    if (stripos($value, $pattern) !== false) {
                        $this->log_and_block('XSS attempt detected in POST parameter');
                    }
                }
            }
        }
    }

    /**
     * Protect against SQL injection
     */
    private function protect_against_sql_injection() {
        // Check for common SQL injection patterns
        $sql_patterns = array(
            'UNION ALL SELECT',
            'UNION SELECT',
            'ORDER BY',
            'GROUP BY',
            '--',
            '/*',
            'DROP TABLE',
            'DROP DATABASE',
            'TRUNCATE TABLE',
            'DELETE FROM',
            'INSERT INTO',
            'UPDATE.*SET'
        );

        foreach ($_GET as $key => $value) {
            foreach ($sql_patterns as $pattern) {
                if (preg_match('/' . $pattern . '/i', $value)) {
                    $this->log_and_block('SQL injection attempt detected in GET parameter');
                }
            }
        }

        foreach ($_POST as $key => $value) {
            if (is_string($value)) {
                foreach ($sql_patterns as $pattern) {
                    if (preg_match('/' . $pattern . '/i', $value)) {
                        $this->log_and_block('SQL injection attempt detected in POST parameter');
                    }
                }
            }
        }
    }

    /**
     * Block suspicious requests
     */
    private function block_suspicious_requests() {
        // Block requests with suspicious user agents
        $suspicious_agents = array(
            'sqlmap',
            'nikto',
            'nmap',
            'dirbuster',
            'hydra',
            'nessus',
            'whatweb',
            'havij',
            'acunetix'
        );

        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        foreach ($suspicious_agents as $agent) {
            if (stripos($user_agent, $agent) !== false) {
                $this->log_and_block('Suspicious user agent detected');
            }
        }

        // Block requests to sensitive files
        $sensitive_files = array(
            'wp-config.php',
            'debug.log',
            'error_log',
            '.git',
            '.svn',
            '.env',
            'readme.html',
            'license.txt'
        );

        $request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        foreach ($sensitive_files as $file) {
            if (stripos($request_uri, $file) !== false) {
                $this->log_and_block('Attempt to access sensitive file');
            }
        }
    }

    /**
     * Protect login page
     */
    private function protect_login_page() {
        if (!is_admin() && !in_array($GLOBALS['pagenow'], array('wp-login.php'))) {
            return;
        }

        // Basic rate limiting
        $ip = $this->get_client_ip();
        $key = 'angry_bunny_login_attempts_' . md5($ip);
        $attempts = get_transient($key);

        if ($attempts === false) {
            set_transient($key, 1, HOUR_IN_SECONDS);
        } elseif ($attempts >= 5) {
            $this->log_and_block('Too many login attempts');
        } else {
            set_transient($key, $attempts + 1, HOUR_IN_SECONDS);
        }
    }

    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip_headers = array(
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        );

        foreach ($ip_headers as $header) {
            if (isset($_SERVER[$header])) {
                foreach (explode(',', $_SERVER[$header]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP)) {
                        return $ip;
                    }
                }
            }
        }

        return '0.0.0.0';
    }

    /**
     * Log and block suspicious requests
     */
    private function log_and_block($reason) {
        global $wpdb;

        // Log the event
        $wpdb->insert(
            $wpdb->prefix . 'angry_bunny_security_log',
            array(
                'event_type' => 'firewall_block',
                'event_severity' => 'high',
                'event_message' => $reason,
                'event_data' => json_encode(array(
                    'ip' => $this->get_client_ip(),
                    'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
                    'request_uri' => isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '',
                    'request_method' => isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '',
                    'timestamp' => current_time('mysql')
                ))
            ),
            array('%s', '%s', '%s', '%s')
        );

        // Block the request
        wp_die(
            'Security violation detected. This request has been blocked.',
            'Security Alert',
            array('response' => 403)
        );
    }
} 