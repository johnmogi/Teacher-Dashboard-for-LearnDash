<?php
/**
 * AJAX Handler Class for Teacher Dashboard
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Teacher_Dashboard_Ajax_Handler {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init();
    }
    
    /**
     * Initialize AJAX handlers
     */
    private function init() {
        add_action('wp_ajax_teacher_dashboard_data', array($this, 'handle_dashboard_data'));
        add_action('wp_ajax_nopriv_teacher_dashboard_data', array($this, 'handle_dashboard_data'));
        add_action('wp_ajax_get_teacher_students', array($this, 'handle_teacher_students'));
        add_action('wp_ajax_nopriv_get_teacher_students', array($this, 'handle_teacher_students'));
    }
    
    /**
     * Handle dashboard data request
     */
    public function handle_dashboard_data() {
        try {
            // Check permissions
            if (!current_user_can('read')) {
                wp_send_json_error(__('Access denied', 'teacher-dashboard'));
                return;
            }
            
            // Get current user
            $user = wp_get_current_user();
            
            // Get core instance
            $core = Teacher_Dashboard_Core::get_instance();
            
            // Get dashboard data
            $data = $core->get_dashboard_data($user);
            
            // Send response
            wp_send_json_success($data);
            
        } catch (Exception $e) {
            error_log('Teacher Dashboard AJAX Error: ' . $e->getMessage());
            wp_send_json_error(__('An error occurred', 'teacher-dashboard'));
        }
    }
    
    /**
     * Handle teacher students request
     */
    public function handle_teacher_students() {
        try {
            // Check permissions
            if (!current_user_can('read')) {
                wp_send_json_error(__('Access denied', 'teacher-dashboard'));
                return;
            }
            
            // Get teacher ID from request
            $teacher_id = isset($_POST['teacher_id']) ? intval($_POST['teacher_id']) : 0;
            
            if (!$teacher_id) {
                wp_send_json_error(__('Invalid teacher ID', 'teacher-dashboard'));
                return;
            }
            
            // Get core instance
            $core = Teacher_Dashboard_Core::get_instance();
            
            // Get teacher students
            $data = $core->get_teacher_students($teacher_id);
            
            // Send response
            wp_send_json_success($data);
            
        } catch (Exception $e) {
            error_log('Teacher Dashboard AJAX Error: ' . $e->getMessage());
            wp_send_json_error(__('An error occurred', 'teacher-dashboard'));
        }
    }
}

// Initialize AJAX handler
new Teacher_Dashboard_Ajax_Handler();
