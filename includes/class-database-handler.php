<?php
/**
 * Database Handler Class for Teacher Dashboard
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Teacher_Dashboard_Database {
    
    /**
     * Database prefix
     */
    private $prefix;
    
    /**
     * WordPress database instance
     */
    private $wpdb;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        
        // Get plugin options
        $options = get_option('teacher_dashboard_options', array());
        $this->prefix = isset($options['db_prefix']) ? $options['db_prefix'] : 'edc_';
    }
    
    /**
     * Get all teachers and their groups (Admin view)
     */
    public function get_all_teachers() {
        $sql = "
            SELECT 
                u.ID as teacher_id,
                u.user_login,
                u.display_name as teacher_name,
                u.user_email,
                g.ID as group_id,
                g.post_title as group_name,
                g.post_status,
                COUNT(DISTINCT CASE 
                    WHEN sm.meta_key LIKE 'learndash_group_users_%' THEN sm.user_id 
                    ELSE NULL 
                END) as student_count,
                cap.meta_value as user_roles
            FROM {$this->prefix}users u
            JOIN {$this->prefix}usermeta cap ON u.ID = cap.user_id AND cap.meta_key = 'wp_capabilities'
            LEFT JOIN {$this->prefix}usermeta um ON u.ID = um.user_id AND um.meta_key LIKE 'learndash_group_leaders_%'
            LEFT JOIN {$this->prefix}posts g ON g.ID = SUBSTRING_INDEX(um.meta_key, '_', -1) AND g.post_type = 'groups' AND g.post_status = 'publish'
            LEFT JOIN {$this->prefix}usermeta sm ON g.ID = SUBSTRING_INDEX(sm.meta_key, '_', -1) AND sm.meta_key LIKE 'learndash_group_users_%'
            WHERE (
                cap.meta_value LIKE '%school_teacher%' 
                OR cap.meta_value LIKE '%group_leader%' 
                OR cap.meta_value LIKE '%instructor%'
                OR cap.meta_value LIKE '%wdm_instructor%'
                OR cap.meta_value LIKE '%Instructor%'
                OR cap.meta_value LIKE '%stm_lms_instructor%'
            )
            GROUP BY u.ID, g.ID
            ORDER BY u.display_name, g.post_title
        ";
        
        return $this->wpdb->get_results($sql);
    }
    
    /**
     * Debug method to test teachers query
     */
    public function debug_teachers_query() {
        // First, let's see what users we have with capabilities
        $debug_sql = "
            SELECT u.ID, u.display_name, u.user_email, cap.meta_value as capabilities
            FROM {$this->prefix}users u
            JOIN {$this->prefix}usermeta cap ON u.ID = cap.user_id AND cap.meta_key = 'wp_capabilities'
            WHERE (
                cap.meta_value LIKE '%school_teacher%' 
                OR cap.meta_value LIKE '%group_leader%' 
                OR cap.meta_value LIKE '%instructor%'
                OR cap.meta_value LIKE '%wdm_instructor%'
                OR cap.meta_value LIKE '%Instructor%'
                OR cap.meta_value LIKE '%stm_lms_instructor%'
            )
            ORDER BY u.display_name
        ";
        
        $debug_results = $this->wpdb->get_results($debug_sql);
        error_log('DEBUG: Teachers found: ' . print_r($debug_results, true));
        
        return $debug_results;
    }
    
    /**
     * Get students for a specific teacher
     */
    public function get_students_by_teacher($teacher_id) {
        $sql = "
            SELECT DISTINCT
                s.ID as student_id,
                s.display_name as student_name,
                s.user_email,
                g.ID as group_id,
                g.post_title as group_name,
                AVG(CASE 
                    WHEN am.meta_key = 'percentage' THEN CAST(am.meta_value AS DECIMAL(5,2))
                    ELSE NULL 
                END) as avg_quiz_score
            FROM {$this->prefix}users s
            JOIN {$this->prefix}usermeta sm ON s.ID = sm.user_id
            JOIN {$this->prefix}posts g ON g.ID = SUBSTRING_INDEX(sm.meta_key, '_', -1)
            JOIN {$this->prefix}usermeta tm ON g.ID = SUBSTRING_INDEX(tm.meta_key, '_', -1)
            LEFT JOIN {$this->prefix}learndash_user_activity ua ON s.ID = ua.user_id
            LEFT JOIN {$this->prefix}learndash_user_activity_meta am ON ua.activity_id = am.activity_id
            WHERE sm.meta_key LIKE 'learndash_group_users_%'
            AND tm.meta_key LIKE 'learndash_group_leaders_%'
            AND tm.user_id = %d
            AND g.post_type = 'groups'
            AND g.post_status = 'publish'
            GROUP BY s.ID, g.ID
            ORDER BY g.post_title, s.display_name
        ";
        
        return $this->wpdb->get_results($this->wpdb->prepare($sql, $teacher_id));
    }
    
    /**
     * Get all groups (Admin view)
     */
    public function get_all_groups() {
        $sql = "
            SELECT 
                g.ID as group_id,
                g.post_title as group_name,
                g.post_status,
                u.display_name as teacher_name,
                COUNT(DISTINCT sm.user_id) as student_count
            FROM {$this->prefix}posts g
            LEFT JOIN {$this->prefix}usermeta lm ON g.ID = SUBSTRING_INDEX(lm.meta_key, '_', -1)
            LEFT JOIN {$this->prefix}users u ON lm.user_id = u.ID
            LEFT JOIN {$this->prefix}usermeta sm ON g.ID = SUBSTRING_INDEX(sm.meta_key, '_', -1)
            WHERE g.post_type = 'groups'
            AND g.post_status = 'publish'
            AND lm.meta_key LIKE 'learndash_group_leaders_%'
            AND sm.meta_key LIKE 'learndash_group_users_%'
            GROUP BY g.ID
            ORDER BY g.post_title
        ";
        
        return $this->wpdb->get_results($sql);
    }
    
    /**
     * Get all students (Admin view)
     */
    public function get_all_students() {
        $sql = "
            SELECT 
                u.ID as student_id,
                u.user_login,
                u.display_name as student_name,
                u.user_email,
                g.ID as group_id,
                g.post_title as group_name,
                AVG(CAST(meta.meta_value AS DECIMAL(5,2))) as avg_quiz_score
            FROM {$this->prefix}users u
            JOIN {$this->prefix}usermeta um ON u.ID = um.user_id
            JOIN {$this->prefix}posts g ON g.ID = SUBSTRING_INDEX(um.meta_key, '_', -1)
            LEFT JOIN {$this->prefix}learndash_user_activity ua ON u.ID = ua.user_id
            LEFT JOIN {$this->prefix}learndash_user_activity_meta meta ON ua.activity_id = meta.activity_id
            WHERE um.meta_key LIKE 'learndash_group_users_%'
            AND g.post_type = 'groups'
            AND g.post_status = 'publish'
            AND (ua.activity_type = 'quiz' OR ua.activity_type IS NULL)
            AND (meta.meta_key = 'percentage' OR meta.meta_key IS NULL)
            GROUP BY u.ID, g.ID
            ORDER BY u.display_name
        ";
        
        return $this->wpdb->get_results($sql);
    }
    
    /**
     * Get teacher's groups
     */
    public function get_teacher_groups($teacher_id) {
        $sql = "
            SELECT 
                g.ID as group_id,
                g.post_title as group_name,
                g.post_status,
                COUNT(DISTINCT sm.user_id) as student_count
            FROM {$this->prefix}posts g
            JOIN {$this->prefix}usermeta lm ON g.ID = SUBSTRING_INDEX(lm.meta_key, '_', -1)
            LEFT JOIN {$this->prefix}usermeta sm ON g.ID = SUBSTRING_INDEX(sm.meta_key, '_', -1)
            WHERE g.post_type = 'groups'
            AND g.post_status = 'publish'
            AND lm.meta_key LIKE 'learndash_group_leaders_%'
            AND lm.user_id = %d
            AND sm.meta_key LIKE 'learndash_group_users_%'
            GROUP BY g.ID
            ORDER BY g.post_title
        ";
        
        return $this->wpdb->get_results($this->wpdb->prepare($sql, $teacher_id));
    }
    
    /**
     * Get teacher's students
     */
    public function get_teacher_students($teacher_id) {
        $sql = "
            SELECT 
                u.ID as student_id,
                u.user_login,
                u.display_name as student_name,
                u.user_email,
                g.ID as group_id,
                g.post_title as group_name,
                AVG(CAST(meta.meta_value AS DECIMAL(5,2))) as avg_quiz_score
            FROM {$this->prefix}users u
            JOIN {$this->prefix}usermeta um ON u.ID = um.user_id
            JOIN {$this->prefix}posts g ON g.ID = SUBSTRING_INDEX(um.meta_key, '_', -1)
            JOIN {$this->prefix}usermeta lm ON g.ID = SUBSTRING_INDEX(lm.meta_key, '_', -1)
            LEFT JOIN {$this->prefix}learndash_user_activity ua ON u.ID = ua.user_id
            LEFT JOIN {$this->prefix}learndash_user_activity_meta meta ON ua.activity_id = meta.activity_id
            WHERE um.meta_key LIKE 'learndash_group_users_%'
            AND lm.meta_key LIKE 'learndash_group_leaders_%'
            AND lm.user_id = %d
            AND g.post_type = 'groups'
            AND g.post_status = 'publish'
            AND (ua.activity_type = 'quiz' OR ua.activity_type IS NULL)
            AND (meta.meta_key = 'percentage' OR meta.meta_key IS NULL)
            GROUP BY u.ID, g.ID
            ORDER BY u.display_name
        ";
        
        return $this->wpdb->get_results($this->wpdb->prepare($sql, $teacher_id));
    }
    
    /**
     * Get quiz statistics (Admin view)
     */
    public function get_quiz_statistics() {
        $sql = "
            SELECT 
                q.ID as quiz_id,
                q.post_title as quiz_name,
                c.post_title as course_name,
                COUNT(ua.activity_id) as total_attempts,
                AVG(CAST(meta.meta_value AS DECIMAL(5,2))) as avg_score,
                MIN(CAST(meta.meta_value AS DECIMAL(5,2))) as min_score,
                MAX(CAST(meta.meta_value AS DECIMAL(5,2))) as max_score
            FROM {$this->prefix}learndash_user_activity ua
            JOIN {$this->prefix}learndash_user_activity_meta meta ON ua.activity_id = meta.activity_id
            JOIN {$this->prefix}posts q ON ua.post_id = q.ID
            LEFT JOIN {$this->prefix}posts c ON ua.course_id = c.ID
            WHERE ua.activity_type = 'quiz'
            AND meta.meta_key = 'percentage'
            AND ua.activity_status = 1
            GROUP BY q.ID
            ORDER BY q.post_title
        ";
        
        return $this->wpdb->get_results($sql);
    }
    
    /**
     * Get teacher's quiz statistics
     */
    public function get_teacher_quiz_statistics($teacher_id) {
        $sql = "
            SELECT 
                q.ID as quiz_id,
                q.post_title as quiz_name,
                c.post_title as course_name,
                COUNT(ua.activity_id) as total_attempts,
                AVG(CAST(meta.meta_value AS DECIMAL(5,2))) as avg_score,
                MIN(CAST(meta.meta_value AS DECIMAL(5,2))) as min_score,
                MAX(CAST(meta.meta_value AS DECIMAL(5,2))) as max_score
            FROM {$this->prefix}learndash_user_activity ua
            JOIN {$this->prefix}learndash_user_activity_meta meta ON ua.activity_id = meta.activity_id
            JOIN {$this->prefix}posts q ON ua.post_id = q.ID
            LEFT JOIN {$this->prefix}posts c ON ua.course_id = c.ID
            JOIN {$this->prefix}usermeta um ON ua.user_id = um.user_id
            JOIN {$this->prefix}posts g ON g.ID = SUBSTRING_INDEX(um.meta_key, '_', -1)
            JOIN {$this->prefix}usermeta lm ON g.ID = SUBSTRING_INDEX(lm.meta_key, '_', -1)
            WHERE ua.activity_type = 'quiz'
            AND meta.meta_key = 'percentage'
            AND ua.activity_status = 1
            AND um.meta_key LIKE 'learndash_group_users_%'
            AND lm.meta_key LIKE 'learndash_group_leaders_%'
            AND lm.user_id = %d
            AND g.post_type = 'groups'
            AND g.post_status = 'publish'
            GROUP BY q.ID
            ORDER BY q.post_title
        ";
        
        return $this->wpdb->get_results($this->wpdb->prepare($sql, $teacher_id));
    }
    
    /**
     * Get student quiz details
     */
    public function get_student_quiz_details($student_id, $teacher_id = null) {
        $where_clause = '';
        $params = array($student_id);
        
        if ($teacher_id) {
            $where_clause = "
                AND EXISTS (
                    SELECT 1 FROM {$this->prefix}usermeta um2
                    JOIN {$this->prefix}posts g2 ON g2.ID = SUBSTRING_INDEX(um2.meta_key, '_', -1)
                    JOIN {$this->prefix}usermeta lm2 ON g2.ID = SUBSTRING_INDEX(lm2.meta_key, '_', -1)
                    WHERE um2.user_id = ua.user_id
                    AND um2.meta_key LIKE 'learndash_group_users_%'
                    AND lm2.meta_key LIKE 'learndash_group_leaders_%'
                    AND lm2.user_id = %d
                )
            ";
            $params[] = $teacher_id;
        }
        
        $sql = "
            SELECT 
                q.post_title as quiz_name,
                c.post_title as course_name,
                FROM_UNIXTIME(ua.activity_completed) as completion_date,
                meta.meta_value as score,
                ua.activity_status
            FROM {$this->prefix}learndash_user_activity ua
            JOIN {$this->prefix}learndash_user_activity_meta meta ON ua.activity_id = meta.activity_id
            JOIN {$this->prefix}posts q ON ua.post_id = q.ID
            LEFT JOIN {$this->prefix}posts c ON ua.course_id = c.ID
            WHERE ua.user_id = %d
            AND ua.activity_type = 'quiz'
            AND meta.meta_key = 'percentage'
            {$where_clause}
            ORDER BY ua.activity_completed DESC
        ";
        
        return $this->wpdb->get_results($this->wpdb->prepare($sql, $params));
    }
}
