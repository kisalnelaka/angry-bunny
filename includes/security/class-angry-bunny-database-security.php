<?php
/**
 * Database security functionality.
 *
 * @since      1.0.0
 * @package    AngryBunny
 */

class Angry_Bunny_Database_Security {

    /**
     * Initialize database security features
     */
    public function __construct() {
        add_action('init', array($this, 'check_database_security'));
    }

    /**
     * Check database security
     */
    public function check_database_security() {
        $issues = array();

        // Check database prefix
        $this->check_database_prefix($issues);

        // Check user table security
        $this->check_user_table_security($issues);

        // Check for unnecessary database tables
        $this->check_unnecessary_tables($issues);

        // Save issues if found
        if (!empty($issues)) {
            $this->save_issues($issues);
        }
    }

    /**
     * Check database prefix
     */
    private function check_database_prefix(&$issues) {
        global $wpdb;

        if ($wpdb->prefix === 'wp_') {
            $issues[] = array(
                'id' => 'default_db_prefix',
                'severity' => 'medium',
                'description' => 'Default WordPress database prefix (wp_) is being used',
                'solution' => 'Change the database prefix in wp-config.php and update table names'
            );
        }
    }

    /**
     * Check user table security
     */
    private function check_user_table_security(&$issues) {
        global $wpdb;

        // Check for user with ID 1
        $user_1 = $wpdb->get_row("SELECT * FROM {$wpdb->users} WHERE ID = 1");
        if ($user_1) {
            $issues[] = array(
                'id' => 'user_id_1',
                'severity' => 'medium',
                'description' => 'User with ID 1 exists (commonly targeted in attacks)',
                'solution' => 'Create a new admin user and delete this one'
            );
        }

        // Check for users with username 'admin'
        $admin_user = $wpdb->get_row("SELECT * FROM {$wpdb->users} WHERE user_login = 'admin'");
        if ($admin_user) {
            $issues[] = array(
                'id' => 'admin_username',
                'severity' => 'high',
                'description' => 'User with username "admin" exists',
                'solution' => 'Change the username or create a new administrator account'
            );
        }
    }

    /**
     * Check for unnecessary tables
     */
    private function check_unnecessary_tables(&$issues) {
        global $wpdb;

        $unnecessary_tables = array(
            'wp_commentmeta',
            'wp_comments',
            'wp_links'
        );

        foreach ($unnecessary_tables as $table) {
            $real_table = str_replace('wp_', $wpdb->prefix, $table);
            if ($wpdb->get_var("SHOW TABLES LIKE '$real_table'") === $real_table) {
                // Check if table is actually being used
                $count = $wpdb->get_var("SELECT COUNT(*) FROM $real_table");
                if ($count == 0) {
                    $issues[] = array(
                        'id' => 'unused_table_' . $table,
                        'severity' => 'low',
                        'description' => sprintf('Unused database table found: %s', $real_table),
                        'solution' => sprintf('Consider removing the table if not needed: DROP TABLE %s', $real_table)
                    );
                }
            }
        }
    }

    /**
     * Optimize database tables
     */
    public function optimize_tables() {
        global $wpdb;

        $tables = $wpdb->get_col('SHOW TABLES');
        $optimized = array();
        $failed = array();

        foreach ($tables as $table) {
            $result = $wpdb->query("OPTIMIZE TABLE $table");
            if ($result) {
                $optimized[] = $table;
            } else {
                $failed[] = $table;
            }
        }

        // Log the optimization results
        $wpdb->insert(
            $wpdb->prefix . 'angry_bunny_security_log',
            array(
                'event_type' => 'database_optimization',
                'event_severity' => 'info',
                'event_message' => 'Database optimization completed',
                'event_data' => json_encode(array(
                    'optimized_tables' => $optimized,
                    'failed_tables' => $failed,
                    'timestamp' => current_time('mysql')
                ))
            ),
            array('%s', '%s', '%s', '%s')
        );

        return array(
            'optimized' => $optimized,
            'failed' => $failed
        );
    }

    /**
     * Save security issues
     */
    private function save_issues($issues) {
        global $wpdb;

        foreach ($issues as $issue) {
            $wpdb->insert(
                $wpdb->prefix . 'angry_bunny_security_log',
                array(
                    'event_type' => 'database_security',
                    'event_severity' => $issue['severity'],
                    'event_message' => $issue['description'],
                    'event_data' => json_encode($issue)
                ),
                array('%s', '%s', '%s', '%s')
            );
        }

        update_option('angry_bunny_database_issues', $issues);
    }

    /**
     * Get database security recommendations
     */
    public function get_security_recommendations() {
        return array(
            array(
                'title' => 'Change Database Prefix',
                'description' => 'Using a custom database prefix makes it harder for attackers to guess your table names.',
                'priority' => 'medium'
            ),
            array(
                'title' => 'Regular Backups',
                'description' => 'Ensure regular database backups are configured and stored securely.',
                'priority' => 'high'
            ),
            array(
                'title' => 'Remove Unused Tables',
                'description' => 'Remove unnecessary database tables to reduce attack surface.',
                'priority' => 'low'
            ),
            array(
                'title' => 'User Table Security',
                'description' => 'Avoid using common usernames and user IDs that are frequently targeted.',
                'priority' => 'medium'
            ),
            array(
                'title' => 'Regular Optimization',
                'description' => 'Regularly optimize database tables to maintain performance and integrity.',
                'priority' => 'medium'
            )
        );
    }
} 