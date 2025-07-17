<?php
/**
 * Role Manager Class for Teacher Dashboard
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Teacher_Dashboard_Role_Manager {
    
    /**
     * Allowed roles for dashboard access
     */
    private $allowed_roles = array(
        'administrator',
        'group_leader',
        'school_teacher'
    );
    
    /**
     * Admin roles
     */
    private $admin_roles = array(
        'administrator'
    );
    
    /**
     * Teacher roles
     */
    private $teacher_roles = array(
        'group_leader',
        'school_teacher'
    );
    
    /**
     * Constructor
     */
    public function __construct() {
        // Get allowed roles from plugin options
        $options = get_option('teacher_dashboard_options', array());
        if (isset($options['roles_enabled']) && is_array($options['roles_enabled'])) {
            $this->allowed_roles = $options['roles_enabled'];
        }
    }
    
    /**
     * Check if current user can access dashboard
     */
    public function can_access_dashboard($user = null) {
        if (!$user) {
            $user = wp_get_current_user();
        }
        
        if (!$user || !$user->exists()) {
            return false;
        }
        
        // Check if user has any of the allowed roles
        foreach ($this->allowed_roles as $role) {
            if (in_array($role, $user->roles)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if user is admin
     */
    public function is_admin($user = null) {
        if (!$user) {
            $user = wp_get_current_user();
        }
        
        if (!$user || !$user->exists()) {
            return false;
        }
        
        // Check if user has admin role
        foreach ($this->admin_roles as $role) {
            if (in_array($role, $user->roles)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if user is teacher
     */
    public function is_teacher($user = null) {
        if (!$user) {
            $user = wp_get_current_user();
        }
        
        if (!$user || !$user->exists()) {
            return false;
        }
        
        // Check if user has teacher role
        foreach ($this->teacher_roles as $role) {
            if (in_array($role, $user->roles)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if user can view specific group
     */
    public function can_view_group($group_id, $user = null) {
        if (!$user) {
            $user = wp_get_current_user();
        }
        
        if (!$user || !$user->exists()) {
            return false;
        }
        
        // Admins can view all groups
        if ($this->is_admin($user)) {
            return true;
        }
        
        // Teachers can only view their assigned groups
        if ($this->is_teacher($user)) {
            return $this->is_group_leader($user->ID, $group_id);
        }
        
        return false;
    }
    
    /**
     * Check if user can view specific student
     */
    public function can_view_student($student_id, $user = null) {
        if (!$user) {
            $user = wp_get_current_user();
        }
        
        if (!$user || !$user->exists()) {
            return false;
        }
        
        // Admins can view all students
        if ($this->is_admin($user)) {
            return true;
        }
        
        // Teachers can only view students in their groups
        if ($this->is_teacher($user)) {
            return $this->is_student_in_teacher_groups($student_id, $user->ID);
        }
        
        return false;
    }
    
    /**
     * Check if user is group leader for specific group
     */
    private function is_group_leader($user_id, $group_id) {
        $meta_key = 'learndash_group_leaders_' . $group_id;
        $meta_value = get_user_meta($user_id, $meta_key, true);
        
        return !empty($meta_value);
    }
    
    /**
     * Check if student is in teacher's groups
     */
    private function is_student_in_teacher_groups($student_id, $teacher_id) {
        global $wpdb;
        
        $options = get_option('teacher_dashboard_options', array());
        $prefix = isset($options['db_prefix']) ? $options['db_prefix'] : 'edc_';
        
        $sql = "
            SELECT COUNT(*) as count
            FROM {$prefix}usermeta um1
            JOIN {$prefix}usermeta um2 ON SUBSTRING_INDEX(um1.meta_key, '_', -1) = SUBSTRING_INDEX(um2.meta_key, '_', -1)
            WHERE um1.user_id = %d
            AND um1.meta_key LIKE 'learndash_group_users_%'
            AND um2.user_id = %d
            AND um2.meta_key LIKE 'learndash_group_leaders_%'
        ";
        
        $result = $wpdb->get_var($wpdb->prepare($sql, $student_id, $teacher_id));
        
        return $result > 0;
    }
    
    /**
     * Get user's accessible groups
     */
    public function get_user_accessible_groups($user = null) {
        if (!$user) {
            $user = wp_get_current_user();
        }
        
        if (!$user || !$user->exists()) {
            return array();
        }
        
        // Admins can access all groups
        if ($this->is_admin($user)) {
            return $this->get_all_groups();
        }
        
        // Teachers can access only their groups
        if ($this->is_teacher($user)) {
            return $this->get_teacher_groups($user->ID);
        }
        
        return array();
    }
    
    /**
     * Get all groups (for admins)
     */
    private function get_all_groups() {
        $groups = get_posts(array(
            'post_type' => 'groups',
            'post_status' => 'publish',
            'numberposts' => -1,
            'fields' => 'ids'
        ));
        
        return $groups;
    }
    
    /**
     * Get teacher's groups
     */
    private function get_teacher_groups($teacher_id) {
        global $wpdb;
        
        $options = get_option('teacher_dashboard_options', array());
        $prefix = isset($options['db_prefix']) ? $options['db_prefix'] : 'edc_';
        
        $sql = "
            SELECT DISTINCT SUBSTRING_INDEX(meta_key, '_', -1) as group_id
            FROM {$prefix}usermeta
            WHERE user_id = %d
            AND meta_key LIKE 'learndash_group_leaders_%'
        ";
        
        $results = $wpdb->get_results($wpdb->prepare($sql, $teacher_id));
        
        $group_ids = array();
        foreach ($results as $result) {
            $group_ids[] = intval($result->group_id);
        }
        
        return $group_ids;
    }
    
    /**
     * Get current user role for dashboard
     */
    public function get_dashboard_role($user = null) {
        if (!$user) {
            $user = wp_get_current_user();
        }
        
        if (!$user || !$user->exists()) {
            return 'none';
        }
        
        if ($this->is_admin($user)) {
            return 'admin';
        }
        
        if ($this->is_teacher($user)) {
            return 'teacher';
        }
        
        return 'none';
    }
    
    /**
     * Get allowed roles
     */
    public function get_allowed_roles() {
        return $this->allowed_roles;
    }
    
    /**
     * Set allowed roles
     */
    public function set_allowed_roles($roles) {
        if (is_array($roles)) {
            $this->allowed_roles = $roles;
            
            // Update plugin options
            $options = get_option('teacher_dashboard_options', array());
            $options['roles_enabled'] = $roles;
            update_option('teacher_dashboard_options', $options);
        }
    }
}
