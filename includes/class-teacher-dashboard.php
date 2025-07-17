<?php
/**
 * Core Teacher Dashboard Class
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Teacher_Dashboard {
    
    /**
     * Single instance
     */
    private static $instance = null;
    
    /**
     * Database handler instance
     */
    private $db_handler;
    
    /**
     * Role manager instance
     */
    private $role_manager;
    
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
        $this->init();
    }
    
    /**
     * Initialize the dashboard
     */
    private function init() {
        // Initialize dependencies
        $this->db_handler = new Teacher_Dashboard_Database();
        $this->role_manager = new Teacher_Dashboard_Role_Manager();
        
        // Add hooks
        add_action('init', array($this, 'init_hooks'));
        add_action('wp_ajax_teacher_dashboard_data', array($this, 'ajax_get_dashboard_data'));
        add_action('wp_ajax_nopriv_teacher_dashboard_data', array($this, 'ajax_get_dashboard_data'));
        add_action('wp_ajax_get_teacher_students', array($this, 'ajax_get_teacher_students'));
        add_action('wp_ajax_nopriv_get_teacher_students', array($this, 'ajax_get_teacher_students'));
    }
    
    /**
     * Initialize hooks
     */
    public function init_hooks() {
        // Add admin menu if user has appropriate permissions
        if ($this->role_manager->can_access_dashboard()) {
            add_action('admin_menu', array($this, 'add_admin_menu'));
        }
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Teacher Dashboard', 'teacher-dashboard'),
            __('Teacher Dashboard', 'teacher-dashboard'),
            'read',
            'teacher-dashboard',
            array($this, 'render_admin_page'),
            'dashicons-groups',
            30
        );
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        $current_user = wp_get_current_user();
        $is_admin = $this->role_manager->is_admin($current_user);
        
        // Get dashboard data
        $dashboard_data = $this->get_dashboard_data($current_user);
        
        // Load appropriate template
        if ($is_admin) {
            $this->load_template('admin-dashboard', $dashboard_data);
        } else {
            $this->load_template('teacher-dashboard', $dashboard_data);
        }
    }
    
    /**
     * Get dashboard data based on user role
     */
    public function get_dashboard_data($user = null) {
        if (!$user) {
            $user = wp_get_current_user();
        }
        
        $is_admin = $this->role_manager->is_admin($user);
        
        if ($is_admin) {
            // Debug: Check what teachers we find
            $debug_teachers = $this->db_handler->debug_teachers_query();
            $teachers = $this->db_handler->get_all_teachers();
            
            error_log('DEBUG: Admin dashboard - Teachers count: ' . count($teachers));
            error_log('DEBUG: Admin dashboard - Teachers data: ' . print_r($teachers, true));
            
            // Admin sees all data
            return array(
                'teachers' => $teachers,
                'groups' => $this->db_handler->get_all_groups(),
                'students' => $this->db_handler->get_all_students(),
                'quiz_stats' => $this->db_handler->get_quiz_statistics(),
                'user_role' => 'admin'
            );
        } else {
            // Teacher sees only their data
            return array(
                'groups' => $this->db_handler->get_teacher_groups($user->ID),
                'students' => $this->db_handler->get_teacher_students($user->ID),
                'quiz_stats' => $this->db_handler->get_teacher_quiz_statistics($user->ID),
                'user_role' => 'teacher'
            );
        }
    }
    
    /**
     * AJAX handler for dashboard data
     */
    public function ajax_get_dashboard_data() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'teacher_dashboard_nonce')) {
            wp_die('Security check failed');
        }
        
        // Check permissions
        if (!$this->role_manager->can_access_dashboard()) {
            wp_die('Insufficient permissions');
        }
        
        $data = $this->get_dashboard_data();
        wp_send_json_success($data);
    }
    
    /**
     * AJAX handler for getting teacher students
     */
    public function ajax_get_teacher_students() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'teacher_dashboard_nonce')) {
            wp_die('Security check failed');
        }
        
        // Check permissions
        if (!$this->role_manager->can_access_dashboard()) {
            wp_die('Insufficient permissions');
        }
        
        $teacher_id = intval($_POST['teacher_id']);
        if (!$teacher_id) {
            wp_send_json_error('Invalid teacher ID');
        }
        
        $students = $this->db_handler->get_students_by_teacher($teacher_id);
        wp_send_json_success($students);
    }
    
    /**
     * Load template file
     */
    private function load_template($template_name, $data = array()) {
        $template_path = TEACHER_DASHBOARD_PLUGIN_DIR . 'templates/' . $template_name . '.php';
        
        if (file_exists($template_path)) {
            // Extract data for template
            extract($data);
            include $template_path;
        } else {
            echo '<div class="notice notice-error"><p>' . __('Template not found: ', 'teacher-dashboard') . $template_name . '</p></div>';
        }
    }
    
    /**
     * Get database handler
     */
    public function get_db_handler() {
        return $this->db_handler;
    }
    
    /**
     * Get role manager
     */
    public function get_role_manager() {
        return $this->role_manager;
    }
}
