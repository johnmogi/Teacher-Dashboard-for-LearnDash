<?php
/**
 * Database Handler Class for Teacher Dashboard
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Prevent class redeclaration
if (class_exists('Teacher_Dashboard_Database')) {
    return;
}

/**
 * Database Handler Class
 */
class Teacher_Dashboard_Database {
    
    /**
     * Singleton instance of the database handler
     */
    private static $instance = null;
    
    /**
     * Database prefix
     */
    private $prefix;
    
    /**
     * WordPress database instance
     */
    private $wpdb;
    
    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        
        // Use WordPress database prefix
        $this->prefix = $wpdb->prefix;
        
        // Add hooks for proper initialization
        add_action('init', array($this, 'initialize_translations'));
    }
    
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
     * Initialize translations
     */
    public function initialize_translations() {
        load_plugin_textdomain('teacher-dashboard', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }
    
    /**
     * Get database prefix for debugging
     */
    public function get_prefix() {
        return $this->prefix;
    }
    
    /**
     * Get all teachers and their groups (Admin view)
     * Optimized based on database structure analysis
     */
        public function get_all_teachers() {
        global $wpdb;
        
        $sql = "
        SELECT DISTINCT
            u.ID as teacher_id,
            u.user_login,
            u.display_name as teacher_name,
            u.user_email,
            COALESCE(g.ID, 0) as group_id,
            COALESCE(g.post_title, 'No Group') as group_name,
            COALESCE(g.post_status, 'N/A') as post_status,
            COALESCE(
                (SELECT COUNT(DISTINCT um_students.user_id)
                 FROM {$wpdb->usermeta} um_students
                 WHERE um_students.meta_key = CONCAT('learndash_group_users_', g.ID)
                ), 0
            ) as student_count,
            cap.meta_value as user_roles,
            CASE 
                WHEN cap.meta_value LIKE '%stm_lms_instructor%' THEN 'STM LMS Instructor'
                WHEN cap.meta_value LIKE '%school_teacher%' THEN 'School Teacher'
                WHEN cap.meta_value LIKE '%group_leader%' THEN 'Group Leader'
                WHEN um_leader.meta_key IS NOT NULL THEN 'LearnDash Group Leader'
                ELSE 'Teacher'
            END as teacher_type
        FROM {$wpdb->users} u
        JOIN {$wpdb->usermeta} cap ON u.ID = cap.user_id AND cap.meta_key = '{$wpdb->prefix}capabilities'
        LEFT JOIN {$wpdb->usermeta} um_leader ON u.ID = um_leader.user_id AND um_leader.meta_key LIKE 'learndash_group_leaders_%'
        LEFT JOIN {$wpdb->posts} g ON g.ID = SUBSTRING_INDEX(um_leader.meta_key, '_', -1) AND g.post_type = 'groups' AND g.post_status = 'publish'
        WHERE (
            cap.meta_value LIKE '%stm_lms_instructor%'
            OR cap.meta_value LIKE '%school_teacher%'
            OR cap.meta_value LIKE '%group_leader%'
            OR cap.meta_value LIKE '%teacher%'
            OR um_leader.meta_key IS NOT NULL
        )
        AND u.ID NOT IN (
            SELECT user_id FROM {$wpdb->usermeta} 
            WHERE meta_key = '{$wpdb->prefix}capabilities' 
            AND meta_value LIKE '%administrator%'
        )
        ORDER BY u.display_name, g.post_title
        ";
        
        return $wpdb->get_results($sql, ARRAY_A);
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
                OR cap.meta_value LIKE '%teacher%'
                OR um.meta_key LIKE 'learndash_group_leaders_%'
            )
            ORDER BY u.display_name
        ";
        
        $debug_results = $this->wpdb->get_results($debug_sql);
        error_log('DEBUG: Teachers found: ' . print_r($debug_results, true));
        
        return $debug_results;
    }
    

    
    /**
     * Get all groups (Admin view)
     * Optimized based on database structure analysis
     */
        public function get_all_groups() {
        global $wpdb;
        
        $sql = "
        SELECT 
            g.ID as group_id,
            g.post_title as group_name,
            g.post_status,
            g.post_date as created_date,
            COALESCE(
                (SELECT COUNT(DISTINCT um.user_id)
                 FROM {$wpdb->usermeta} um
                 WHERE um.meta_key = CONCAT('learndash_group_users_', g.ID)
                ), 0
            ) as student_count,
            COALESCE(
                (SELECT GROUP_CONCAT(DISTINCT u.display_name ORDER BY u.display_name SEPARATOR ', ')
                 FROM {$wpdb->users} u
                 JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
                 WHERE um.meta_key = CONCAT('learndash_group_leaders_', g.ID)
                ), 'No Leader'
            ) as group_leaders,
            COALESCE(
                (SELECT COUNT(DISTINCT ua.user_id)
                 FROM {$wpdb->prefix}learndash_user_activity ua
                 JOIN {$wpdb->usermeta} um ON ua.user_id = um.user_id
                 WHERE um.meta_key = CONCAT('learndash_group_users_', g.ID)
                 AND ua.activity_type = 'quiz'
                ), 0
            ) as total_quiz_attempts
        FROM {$wpdb->posts} g
        WHERE g.post_type = 'groups'
        AND g.post_status = 'publish'
        ORDER BY g.post_title
        ";
        
        return $wpdb->get_results($sql, ARRAY_A);
    }
    
    /**
     * Get all students (Admin view)
     * Optimized based on database structure analysis
     */
            public function get_all_students() {
        global $wpdb;
        
        // Get all students with their group associations and activity data
        $sql = "
        SELECT DISTINCT
            s.ID as student_id,
            s.user_login,
            s.display_name as student_name,
            s.user_email,
            GROUP_CONCAT(DISTINCT g.post_title ORDER BY g.post_title SEPARATOR ', ') as group_names,
            GROUP_CONCAT(DISTINCT g.ID ORDER BY g.ID SEPARATOR ', ') as group_ids,
            GROUP_CONCAT(DISTINCT t.display_name ORDER BY t.display_name SEPARATOR ', ') as teachers,
            COALESCE(
                (SELECT COUNT(DISTINCT ua_course.course_id)
                 FROM {$wpdb->prefix}learndash_user_activity ua_course
                 WHERE ua_course.user_id = s.ID AND ua_course.activity_type = 'course'
                ), 0
            ) as enrolled_courses,
            COALESCE(
                (SELECT COUNT(DISTINCT ua_quiz.post_id)
                 FROM {$wpdb->prefix}learndash_user_activity ua_quiz
                 WHERE ua_quiz.user_id = s.ID AND ua_quiz.activity_type = 'quiz'
                ), 0
            ) as attempted_quizzes,
            COALESCE(
                (SELECT COUNT(DISTINCT ua_completed.post_id)
                 FROM {$wpdb->prefix}learndash_user_activity ua_completed
                 WHERE ua_completed.user_id = s.ID 
                 AND ua_completed.activity_type = 'quiz' 
                 AND ua_completed.activity_status = 1
                ), 0
            ) as completed_quizzes,
            COALESCE(
                ROUND(
                    (SELECT (COUNT(DISTINCT CASE WHEN ua_rate.activity_status = 1 THEN ua_rate.post_id ELSE NULL END) / 
                             COUNT(DISTINCT ua_rate.post_id) * 100)
                     FROM {$wpdb->prefix}learndash_user_activity ua_rate
                     WHERE ua_rate.user_id = s.ID AND ua_rate.activity_type = 'quiz'
                    ), 2
                ), 0
            ) as success_rate,
            COALESCE(
                (SELECT MAX(ua_last.activity_completed)
                 FROM {$wpdb->prefix}learndash_user_activity ua_last
                 WHERE ua_last.user_id = s.ID
                ), 0
            ) as last_activity,
            cap.meta_value as user_roles
        FROM {$wpdb->users} s
        JOIN {$wpdb->usermeta} cap ON s.ID = cap.user_id AND cap.meta_key = '{$wpdb->prefix}capabilities'
        LEFT JOIN {$wpdb->usermeta} sm ON s.ID = sm.user_id AND sm.meta_key LIKE 'learndash_group_users_%'
        LEFT JOIN {$wpdb->posts} g ON g.ID = SUBSTRING_INDEX(sm.meta_key, '_', -1) AND g.post_type = 'groups' AND g.post_status = 'publish'
        LEFT JOIN {$wpdb->usermeta} tm ON tm.meta_key = CONCAT('learndash_group_leaders_', g.ID)
        LEFT JOIN {$wpdb->users} t ON tm.user_id = t.ID
        WHERE (
            cap.meta_value LIKE '%subscriber%'
            OR cap.meta_value LIKE '%student%'
            OR cap.meta_value LIKE '%stm_lms_student%'
            OR sm.meta_key IS NOT NULL
        )
        AND cap.meta_value NOT LIKE '%administrator%'
        AND cap.meta_value NOT LIKE '%editor%'
        AND cap.meta_value NOT LIKE '%author%'
        AND cap.meta_value NOT LIKE '%instructor%'
        AND cap.meta_value NOT LIKE '%teacher%'
        GROUP BY s.ID
        ORDER BY s.display_name
        ";
        
        return $this->wpdb->get_results($sql, ARRAY_A);
    }
    
    /**
     * Get students for a specific teacher with detailed statistics
     * 
     * @param int $teacher_id The ID of the teacher
     * @return array Array of student data with statistics
     */
    public function get_students_by_teacher($teacher_id) {
        if (!is_numeric($teacher_id)) {
            return array();
        }

        $sql = "
        SELECT 
            s.ID as student_id,
            s.display_name as student_name,
            s.user_email,
            g.post_title as group_name,
            g.ID as group_id,
            COUNT(DISTINCT CASE 
                WHEN ua.activity_type = 'course' THEN ua.course_id 
                ELSE NULL
            END) as enrolled_courses,
            COUNT(DISTINCT CASE 
                WHEN ua.activity_type = 'quiz' THEN ua.post_id 
                ELSE NULL
            END) as attempted_quizzes,
            COUNT(DISTINCT CASE
                WHEN ua.activity_type = 'quiz' AND ua.activity_status = 1 THEN ua.post_id 
                ELSE NULL
            END) as completed_quizzes,
            ROUND(
                CASE 
                    WHEN COUNT(DISTINCT CASE WHEN ua.activity_type = 'quiz' THEN ua.post_id ELSE NULL END) > 0
                    THEN (COUNT(DISTINCT CASE WHEN ua.activity_type = 'quiz' AND ua.activity_status = 1 THEN ua.post_id ELSE NULL END) / 
                          COUNT(DISTINCT CASE WHEN ua.activity_type = 'quiz' THEN ua.post_id ELSE NULL END) * 100)
                    ELSE 0
                END, 2
            ) as success_rate,
            AVG(CASE 
                WHEN am.meta_key = 'percentage' THEN CAST(am.meta_value AS DECIMAL(5,2))
                ELSE NULL 
            END) as avg_quiz_score
        FROM {$this->prefix}users s
        JOIN {$this->prefix}usermeta sm ON s.ID = sm.user_id
        JOIN {$this->prefix}usermeta tm ON tm.meta_key LIKE 'learndash_group_leaders_%' AND tm.user_id = %d
        JOIN {$this->prefix}posts g ON g.ID = SUBSTRING_INDEX(tm.meta_key, '_', -1)
        LEFT JOIN {$this->wpdb->prefix}learndash_user_activity ua ON s.ID = ua.user_id
        LEFT JOIN {$this->wpdb->prefix}learndash_user_activity_meta am ON ua.activity_id = am.activity_id
        WHERE sm.meta_key LIKE 'learndash_group_users_%'
        AND g.ID = SUBSTRING_INDEX(sm.meta_key, '_', -1)
        AND g.post_type = 'groups'
        AND g.post_status = 'publish'
        GROUP BY s.ID, g.ID
        ORDER BY g.post_title, s.display_name
        ";
        
        return $this->wpdb->get_results($this->wpdb->prepare($sql, $teacher_id), ARRAY_A);
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

    /**
     * Get instructor course statistics
     * 
     * @return array Array of instructor course statistics
     */
    public function get_instructor_course_stats() {
        $sql = "
        SELECT 
            u.ID as instructor_id,
            u.display_name as instructor_name,
            COUNT(DISTINCT c.ID) as total_courses,
            COUNT(DISTINCT q.ID) as total_quizzes,
            COUNT(DISTINCT CASE 
                WHEN ua.activity_type = 'course' THEN ua.user_id 
                ELSE NULL 
            END) as enrolled_students,
            COUNT(DISTINCT CASE 
                WHEN ua.activity_type = 'quiz' AND ua.activity_status = 1 THEN ua.user_id 
                ELSE NULL 
            END) as students_completed_quizzes
        FROM {$this->prefix}users u
        JOIN {$this->prefix}usermeta um ON u.ID = um.user_id
        LEFT JOIN {$this->prefix}posts c ON c.post_type = 'sfwd-courses' AND c.post_status = 'publish'
        LEFT JOIN {$this->prefix}posts q ON c.ID = q.post_parent AND q.post_type = 'sfwd-quiz'
        LEFT JOIN {$this->wpdb->prefix}learndash_user_activity ua ON c.ID = ua.course_id
        WHERE um.meta_key = '{$this->prefix}capabilities'
        AND um.meta_value LIKE '%stm_lms_instructor%'
        GROUP BY u.ID
        ORDER BY u.display_name
        ";
        
        return $this->wpdb->get_results($sql, ARRAY_A);
    }

    /**
     * Get teacher's students with detailed information
     * 
     * @param int $teacher_id The ID of the teacher
     * @return array Array of student data
     */
    public function get_teacher_students($teacher_id) {
        if (!is_numeric($teacher_id)) {
            return array();
        }

        $sql = "
        SELECT DISTINCT
            s.ID as student_id,
            s.user_login,
            s.display_name as student_name,
            s.user_email,
            g.ID as group_id,
            g.post_title as group_name,
            AVG(CAST(meta.meta_value AS DECIMAL(5,2))) as avg_quiz_score
        FROM {$this->prefix}users s
        JOIN {$this->prefix}usermeta sm ON s.ID = sm.user_id
        JOIN {$this->prefix}posts g ON g.ID = SUBSTRING_INDEX(sm.meta_key, '_', -1)
        JOIN {$this->prefix}usermeta tm ON g.ID = SUBSTRING_INDEX(tm.meta_key, '_', -1)
        JOIN {$this->prefix}usermeta sc ON s.ID = sc.user_id
        LEFT JOIN {$this->wpdb->prefix}learndash_user_activity ua ON s.ID = ua.user_id AND ua.activity_type = 'quiz'
        LEFT JOIN {$this->wpdb->prefix}learndash_user_activity_meta meta ON ua.activity_id = meta.activity_id AND meta.meta_key = 'percentage'
        WHERE sm.meta_key LIKE 'learndash_group_users_%'
        AND tm.meta_key LIKE 'learndash_group_leaders_%'
        AND tm.user_id = %d
        AND g.post_type = 'groups'
        AND g.post_status = 'publish'
        AND sc.meta_key = '{$this->prefix}capabilities'
        AND (sc.meta_value LIKE '%student_private%' OR sc.meta_value LIKE '%subscriber%')
        GROUP BY s.ID, g.ID
        ORDER BY g.post_title, s.display_name
        ";
        
        return $this->wpdb->get_results($this->wpdb->prepare($sql, $teacher_id), ARRAY_A);
    }

}
