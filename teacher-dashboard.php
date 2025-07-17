<?php
/**
 * Plugin Name: Teacher Dashboard
 * Plugin URI: https://yoursite.com
 * Description: Custom dashboard for teachers and admins to view LearnDash groups, students, and quiz performance stats.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: teacher-dashboard
 * Domain Path: /languages
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
 * Main Teacher Dashboard Plugin Class
 */
class Teacher_Dashboard_Plugin {
    
    /**
     * Single instance of the plugin
     */
    private static $instance = null;
    
    /**
     * Get single instance
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
        add_action('plugins_loaded', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Initialize the plugin
     */
    public function init() {
        // Check if LearnDash is active (optional - plugin can work without it)
        if (!class_exists('SFWD_LMS')) {
            // LearnDash not found, but continue loading plugin
            error_log('Teacher Dashboard: LearnDash not detected, some features may be limited');
        }
        
        // Load plugin files
        $this->load_dependencies();
        
        // Initialize core functionality
        $this->init_hooks();
    }
    
    /**
     * Load required files
     */
    private function load_dependencies() {
        require_once TEACHER_DASHBOARD_PLUGIN_DIR . 'includes/class-teacher-dashboard.php';
        require_once TEACHER_DASHBOARD_PLUGIN_DIR . 'includes/class-database-handler.php';
        require_once TEACHER_DASHBOARD_PLUGIN_DIR . 'includes/class-role-manager.php';
        require_once TEACHER_DASHBOARD_PLUGIN_DIR . 'includes/class-shortcode-handler.php';
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Initialize main dashboard class
        Teacher_Dashboard::get_instance();
        
        // Initialize shortcode handler
        Teacher_Dashboard_Shortcode::get_instance();
        
        // Enqueue assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_assets() {
        wp_enqueue_style(
            'teacher-dashboard-css',
            TEACHER_DASHBOARD_PLUGIN_URL . 'assets/css/dashboard.css',
            array(),
            TEACHER_DASHBOARD_VERSION
        );
        
        wp_enqueue_script(
            'teacher-dashboard-js',
            TEACHER_DASHBOARD_PLUGIN_URL . 'assets/js/dashboard.js',
            array('jquery'),
            TEACHER_DASHBOARD_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('teacher-dashboard-js', 'teacherDashboard', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'adminUrl' => admin_url(),
            'nonce' => wp_create_nonce('teacher_dashboard_nonce'),
            'userRole' => current_user_can('manage_options') ? 'admin' : 'teacher',
            'i18n' => array(
                'noData' => __('No data available', 'teacher-dashboard'),
                'refreshing' => __('Refreshing...', 'teacher-dashboard'),
                'error' => __('Error', 'teacher-dashboard'),
                'success' => __('Success', 'teacher-dashboard'),
                'teachers' => __('Teachers', 'teacher-dashboard'),
                'teacherName' => __('Teacher Name', 'teacher-dashboard'),
                'email' => __('Email', 'teacher-dashboard'),
                'groups' => __('Groups', 'teacher-dashboard'),
                'groupsLabel' => __('groups', 'teacher-dashboard'),
                'students' => __('Students', 'teacher-dashboard'),
                'actions' => __('Actions', 'teacher-dashboard'),
                'edit' => __('Edit', 'teacher-dashboard'),
                'noTeachersAvailable' => __('No teachers available.', 'teacher-dashboard'),
            )
        ));
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets() {
        $screen = get_current_screen();
        if ($screen && strpos($screen->id, 'teacher-dashboard') !== false) {
            wp_enqueue_style(
                'teacher-dashboard-admin-css',
                TEACHER_DASHBOARD_PLUGIN_URL . 'assets/css/dashboard.css',
                array(),
                TEACHER_DASHBOARD_VERSION
            );
        }
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create any necessary database tables or options
        $this->create_plugin_options();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clean up if necessary
        flush_rewrite_rules();
    }
    
    /**
     * Create plugin options
     */
    private function create_plugin_options() {
        $default_options = array(
            'version' => TEACHER_DASHBOARD_VERSION,
            'db_prefix' => 'edc_',
            'roles_enabled' => array('administrator', 'group_leader', 'school_teacher')
        );
        
        add_option('teacher_dashboard_options', $default_options);
    }
    
    /**
     * Show notice if LearnDash is not active
     */
    public function learndash_missing_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php _e('Teacher Dashboard requires LearnDash LMS to be installed and activated.', 'teacher-dashboard'); ?></p>
        </div>
        <?php
    }
}

// Initialize the plugin
Teacher_Dashboard_Plugin::get_instance();
