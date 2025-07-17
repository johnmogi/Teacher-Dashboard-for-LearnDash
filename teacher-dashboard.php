<?php
/**
 * Plugin Name: Teacher Dashboard
 * Plugin URI: https://example.com/teacher-dashboard
 * Description: A comprehensive dashboard for teachers to manage students, groups, and track progress in LearnDash LMS.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * Text Domain: teacher-dashboard
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.3
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('TEACHER_DASHBOARD_VERSION', '1.0.0');
define('TEACHER_DASHBOARD_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TEACHER_DASHBOARD_PLUGIN_URL', plugin_dir_url(__FILE__));
define('TEACHER_DASHBOARD_PLUGIN_FILE', __FILE__);

/**
 * Main Plugin Class
 */
class Teacher_Dashboard_Plugin {
    
    /**
     * Singleton instance
     */
    private static $instance = null;
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Include required files first
        $this->include_files();
        
        // Then add hooks
        add_action('plugins_loaded', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        try {
            // Check for required dependencies
            if (!$this->check_dependencies()) {
                return;
            }

            // Load text domain
            load_plugin_textdomain('teacher-dashboard', false, dirname(plugin_basename(__FILE__)) . '/languages/');
            
            // Initialize core functionality
            if (class_exists('Teacher_Dashboard_Core')) {
                Teacher_Dashboard_Core::get_instance();
            }
        } catch (Exception $e) {
            error_log('Teacher Dashboard Initialization Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Include required files
     */
    private function include_files() {
        // Core files
        require_once TEACHER_DASHBOARD_PLUGIN_DIR . 'includes/class-database-handler.php';
        require_once TEACHER_DASHBOARD_PLUGIN_DIR . 'includes/class-role-manager.php';
        require_once TEACHER_DASHBOARD_PLUGIN_DIR . 'includes/class-teacher-dashboard.php';
        require_once TEACHER_DASHBOARD_PLUGIN_DIR . 'includes/class-shortcode-handler.php';
        require_once TEACHER_DASHBOARD_PLUGIN_DIR . 'includes/class-ajax-handler.php';
    }
    
    /**
     * Check for required dependencies
     */
    private function check_dependencies() {
        // Check if WordPress version is compatible
        if (version_compare($GLOBALS['wp_version'], '5.0', '<')) {
            add_action('admin_notices', array($this, 'show_wp_version_notice'));
            return false;
        }

        // Check if LearnDash is active
        if (!class_exists('LearnDash')) {
            add_action('admin_notices', array($this, 'show_learndash_notice'));
            return false;
        }

        return true;
    }
    
    /**
     * Show WordPress version notice
     */
    public function show_wp_version_notice() {
        echo '<div class="notice notice-error">
            <p>' . __('Teacher Dashboard requires WordPress 5.0 or higher.', 'teacher-dashboard') . '</p>
        </div>';
    }
    
    /**
     * Show LearnDash notice
     */
    public function show_learndash_notice() {
        echo '<div class="notice notice-error">
            <p>' . __('Teacher Dashboard requires LearnDash LMS to be installed and activated.', 'teacher-dashboard') . '</p>
        </div>';
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        if (!$this->check_dependencies()) {
            return;
        }

        // Create database tables if needed
        $this->create_tables();
        
        // Set default options
        $default_options = array(
            'version' => TEACHER_DASHBOARD_VERSION,
            'db_version' => '1.0.0'
        );
        update_option('teacher_dashboard_options', $default_options);
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clean up any resources
        // Note: We don't delete options or tables on deactivation
    }
    
    /**
     * Create database tables
     */
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Define tables
        $tables = array(
            'teacher_dashboard_stats' => "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}teacher_dashboard_stats (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                teacher_id bigint(20) NOT NULL,
                data mediumtext NOT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY  (id)
            ) $charset_collate;"
        );
        
        // Create tables
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        foreach ($tables as $table => $sql) {
            dbDelta($sql);
        }
    }
}

// Initialize the plugin
Teacher_Dashboard_Plugin::get_instance();
?>
