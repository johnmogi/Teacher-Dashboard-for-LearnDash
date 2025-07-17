<?php
/**
 * Shortcode Handler Class for Teacher Dashboard
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Teacher_Dashboard_Shortcode {
    
    /**
     * Single instance
     */
    private static $instance = null;
    
    /**
     * Dashboard instance
     */
    private $dashboard;
    
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
        add_action('init', array($this, 'register_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_get_teacher_students', array($this, 'ajax_get_teacher_students'));
        add_action('wp_ajax_nopriv_get_teacher_students', array($this, 'ajax_get_teacher_students'));
    }
    
    /**
     * Register shortcode
     */
    public function register_shortcode() {
        add_shortcode('teacher_dashboard', array($this, 'render_shortcode'));
    }
    
    /**
     * Render shortcode
     */
    public function render_shortcode($atts = array()) {
        // Parse attributes
        $atts = shortcode_atts(array(
            'view' => 'auto', // auto, admin, teacher
            'show_stats' => 'true',
            'show_groups' => 'true',
            'show_students' => 'true',
            'limit' => 50
        ), $atts);
        
        // Check if user can access dashboard
        $role_manager = new Teacher_Dashboard_Role_Manager();
        if (!$role_manager->can_access_dashboard()) {
            return '<div class="teacher-dashboard-error">' . __('You do not have permission to access this dashboard.', 'teacher-dashboard') . '</div>';
        }
        
        // Get dashboard instance
        if (!$this->dashboard) {
            $this->dashboard = Teacher_Dashboard_Core::get_instance();
        }
        
        // Get current user and determine view
        $current_user = wp_get_current_user();
        $is_admin = $role_manager->is_admin($current_user);
        
        // Override view if specified
        if ($atts['view'] === 'admin' && !$is_admin) {
            return '<div class="teacher-dashboard-error">' . __('You do not have admin permissions.', 'teacher-dashboard') . '</div>';
        }
        
        $view_type = ($atts['view'] === 'auto') ? ($is_admin ? 'admin' : 'teacher') : $atts['view'];
        
        // Get dashboard data
        $dashboard_data = $this->dashboard->get_dashboard_data($current_user);
        $dashboard_data['shortcode_atts'] = $atts;
        $dashboard_data['view_type'] = $view_type;
        
        // Start output buffering
        ob_start();
        
        // Render appropriate template
        $this->render_dashboard_html($dashboard_data);
        
        return ob_get_clean();
    }
    
    /**
     * Render dashboard HTML
     */
    private function render_dashboard_html($data) {
        $view_type = $data['view_type'];
        $atts = $data['shortcode_atts'];
        
        ?>
        <div class="teacher-dashboard-container" id="teacher-dashboard-<?php echo esc_attr($view_type); ?>">
            <div class="dashboard-header">
                <h2><?php echo $view_type === 'admin' ? __('Admin Dashboard', 'teacher-dashboard') : __('Teacher Dashboard', 'teacher-dashboard'); ?></h2>
                <div class="dashboard-refresh">
                    <button type="button" class="refresh-btn" onclick="teacherDashboard.refresh()">
                        <?php _e('Refresh', 'teacher-dashboard'); ?>
                    </button>
                </div>
            </div>
            
            <?php if ($view_type === 'admin' && isset($data['teachers'])): ?>
                <div class="dashboard-section teachers">
                    <h3><?php _e('Teachers', 'teacher-dashboard'); ?></h3>
                    <?php $this->render_teachers($data['teachers']); ?>
                </div>
            <?php endif; ?>
            
            <!-- Filtered Students Section (Initially Hidden) -->
            <div class="dashboard-section filtered-students" id="filtered-students" style="display: none;">
                <h3><?php _e('Students for Selected Teacher', 'teacher-dashboard'); ?></h3>
                <div id="selected-teacher-name"></div>
                <div id="teacher-students-content">
                    <p><?php _e('Select a teacher to view their students.', 'teacher-dashboard'); ?></p>
                </div>
            </div>
            
            <?php if ($atts['show_stats'] === 'true' && isset($data['quiz_stats'])): ?>
                <div class="dashboard-section quiz-stats">
                    <h3><?php _e('Quiz Statistics', 'teacher-dashboard'); ?></h3>
                    <?php $this->render_quiz_stats($data['quiz_stats']); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($atts['show_groups'] === 'true' && isset($data['groups'])): ?>
                <div class="dashboard-section groups">
                    <h3><?php _e('Groups', 'teacher-dashboard'); ?></h3>
                    <?php $this->render_groups($data['groups'], $view_type); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($atts['show_students'] === 'true' && isset($data['students'])): ?>
                <div class="dashboard-section students">
                    <h3><?php _e('Students', 'teacher-dashboard'); ?></h3>
                    <?php $this->render_students($data['students'], $view_type); ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render quiz statistics
     */
    private function render_quiz_stats($quiz_stats) {
        if (empty($quiz_stats)) {
            echo '<p>' . __('No quiz data available.', 'teacher-dashboard') . '</p>';
            return;
        }
        
        ?>
        <div class="quiz-stats-grid">
            <?php foreach ($quiz_stats as $quiz): ?>
                <div class="quiz-stat-card">
                    <h4><?php echo esc_html($quiz->quiz_name); ?></h4>
                    <?php if ($quiz->course_name): ?>
                        <p class="course-name"><?php echo esc_html($quiz->course_name); ?></p>
                    <?php endif; ?>
                    <div class="stats">
                        <div class="stat">
                            <span class="label"><?php _e('Attempts:', 'teacher-dashboard'); ?></span>
                            <span class="value"><?php echo esc_html($quiz->total_attempts); ?></span>
                        </div>
                        <div class="stat">
                            <span class="label"><?php _e('Avg Score:', 'teacher-dashboard'); ?></span>
                            <span class="value"><?php echo esc_html(number_format($quiz->avg_score, 1)); ?>%</span>
                        </div>
                        <div class="stat">
                            <span class="label"><?php _e('Range:', 'teacher-dashboard'); ?></span>
                            <span class="value"><?php echo esc_html(number_format($quiz->min_score, 1)); ?>% - <?php echo esc_html(number_format($quiz->max_score, 1)); ?>%</span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }
    
    /**
     * Render groups
     */
    private function render_groups($groups, $view_type) {
        if (empty($groups)) {
            echo '<p>' . __('No groups available.', 'teacher-dashboard') . '</p>';
            return;
        }
        
        ?>
        <div class="groups-table">
            <table>
                <thead>
                    <tr>
                        <th><?php _e('Group Name', 'teacher-dashboard'); ?></th>
                        <?php if ($view_type === 'admin'): ?>
                            <th><?php _e('Teacher', 'teacher-dashboard'); ?></th>
                        <?php endif; ?>
                        <th><?php _e('Students', 'teacher-dashboard'); ?></th>
                        <th><?php _e('Status', 'teacher-dashboard'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($groups as $group): ?>
                        <tr>
                            <td><?php echo esc_html($group->group_name); ?></td>
                            <?php if ($view_type === 'admin'): ?>
                                <td><?php echo esc_html($group->teacher_name ?? 'N/A'); ?></td>
                            <?php endif; ?>
                            <td><?php echo esc_html($group->student_count ?? 0); ?></td>
                            <td>
                                <span class="status status-<?php echo esc_attr($group->post_status); ?>">
                                    <?php echo esc_html(ucfirst($group->post_status)); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    /**
     * Render students
     */
    private function render_students($students, $view_type) {
        if (empty($students)) {
            echo '<p>' . __('No students available.', 'teacher-dashboard') . '</p>';
            return;
        }
        
        ?>
        <div class="students-table">
            <table>
                <thead>
                    <tr>
                        <th><?php _e('Student Name', 'teacher-dashboard'); ?></th>
                        <th><?php _e('Email', 'teacher-dashboard'); ?></th>
                        <th><?php _e('Group', 'teacher-dashboard'); ?></th>
                        <th><?php _e('Avg Quiz Score', 'teacher-dashboard'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                        <tr>
                            <td><?php echo esc_html($student->student_name); ?></td>
                            <td><?php echo esc_html($student->user_email); ?></td>
                            <td><?php echo esc_html($student->group_name ?? 'N/A'); ?></td>
                            <td>
                                <?php if ($student->avg_quiz_score): ?>
                                    <span class="score"><?php echo esc_html(number_format($student->avg_quiz_score, 1)); ?>%</span>
                                <?php else: ?>
                                    <span class="no-score">N/A</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    /**
     * Render teachers (admin only)
     */
    private function render_teachers($teachers) {
        if (empty($teachers)) {
            echo '<p>' . __('No teachers available.', 'teacher-dashboard') . '</p>';
            return;
        }
        
        // Process teachers data to group by teacher_id
        $processed_teachers = array();
        foreach ($teachers as $teacher) {
            $teacher_id = $teacher->teacher_id;
            
            if (!isset($processed_teachers[$teacher_id])) {
                $processed_teachers[$teacher_id] = array(
                    'teacher_id' => $teacher_id,
                    'teacher_name' => $teacher->teacher_name,
                    'user_email' => $teacher->user_email,
                    'groups' => array(),
                    'student_count' => 0
                );
            }
            
            // Add group to this teacher
            if (!empty($teacher->group_id)) {
                $processed_teachers[$teacher_id]['groups'][] = array(
                    'group_id' => $teacher->group_id,
                    'group_name' => $teacher->group_name
                );
            }
            
            // Add student count
            if (!empty($teacher->student_count)) {
                $processed_teachers[$teacher_id]['student_count'] += $teacher->student_count;
            }
        }
        
        ?>
        <div class="teachers-table">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Teacher Name', 'teacher-dashboard'); ?></th>
                        <th><?php _e('Email', 'teacher-dashboard'); ?></th>
                        <th><?php _e('Groups', 'teacher-dashboard'); ?></th>
                        <th><?php _e('Students', 'teacher-dashboard'); ?></th>
                        <th><?php _e('Actions', 'teacher-dashboard'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($processed_teachers as $teacher): ?>
                        <tr class="teacher-row" data-teacher-id="<?php echo esc_attr($teacher['teacher_id']); ?>">
                            <td>
                                <strong><?php echo esc_html($teacher['teacher_name']); ?></strong>
                            </td>
                            <td><?php echo esc_html($teacher['user_email']); ?></td>
                            <td><?php echo count($teacher['groups']); ?> <?php _e('groups', 'teacher-dashboard'); ?></td>
                            <td><?php echo esc_html($teacher['student_count']); ?></td>
                            <td>
                                <button class="button button-small view-students-btn" data-teacher-id="<?php echo esc_attr($teacher['teacher_id']); ?>">
                                    <?php _e('View Students', 'teacher-dashboard'); ?>
                                </button>
                                <a href="<?php echo admin_url('user-edit.php?user_id=' . $teacher['teacher_id']); ?>" class="button button-small">
                                    <?php _e('Edit', 'teacher-dashboard'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        // Only enqueue on pages with the shortcode
        global $post;
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'teacher_dashboard')) {
            wp_enqueue_style(
                'teacher-dashboard-style',
                TEACHER_DASHBOARD_PLUGIN_URL . 'assets/css/dashboard.css',
                array(),
                '1.0.0'
            );
            
            wp_enqueue_script(
                'teacher-dashboard-script',
                TEACHER_DASHBOARD_PLUGIN_URL . 'assets/js/dashboard.js',
                array('jquery'),
                '1.0.0',
                true
            );
            
            // Localize script for AJAX
            wp_localize_script('teacher-dashboard-script', 'teacherDashboardAjax', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('teacher_dashboard_nonce'),
                'strings' => array(
                    'loading' => __('Loading...', 'teacher-dashboard'),
                    'error' => __('Error loading data', 'teacher-dashboard'),
                    'no_students' => __('No students found for this teacher', 'teacher-dashboard')
                )
            ));
        }
    }
    
    /**
     * AJAX handler for getting teacher students
     */
    public function ajax_get_teacher_students() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'teacher_dashboard_nonce')) {
            wp_die('Security check failed');
        }
        
        $teacher_id = intval($_POST['teacher_id']);
        
        // Debug logging
        error_log('AJAX get_teacher_students called with teacher_id: ' . $teacher_id);
        
        if (!$teacher_id) {
            wp_send_json_error('Invalid teacher ID');
        }
        
        // Check permissions
        $role_manager = new Teacher_Dashboard_Role_Manager();
        if (!$role_manager->can_access_dashboard()) {
            wp_die('Insufficient permissions');
        }
        
        // Get dashboard instance
        if (!$this->dashboard) {
            $this->dashboard = Teacher_Dashboard_Core::get_instance();
        }
        
        // Get students for this teacher
        $students = $this->dashboard->get_students_by_teacher($teacher_id);
        $teacher_info = get_userdata($teacher_id);
        
        ob_start();
        ?>
        <div class="teacher-students-header">
            <h4><?php echo sprintf(__('Students for %s', 'teacher-dashboard'), esc_html($teacher_info->display_name)); ?></h4>
        </div>
        
        <?php if (!empty($students)): ?>
            <div class="students-table">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Student Name', 'teacher-dashboard'); ?></th>
                            <th><?php _e('Email', 'teacher-dashboard'); ?></th>
                            <th><?php _e('Group', 'teacher-dashboard'); ?></th>
                            <th><?php _e('Quiz Average', 'teacher-dashboard'); ?></th>
                            <th><?php _e('Performance', 'teacher-dashboard'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td><strong><?php echo esc_html($student->student_name); ?></strong></td>
                                <td><?php echo esc_html($student->user_email); ?></td>
                                <td><?php echo esc_html($student->group_name ?? 'N/A'); ?></td>
                                <td>
                                    <?php if ($student->avg_quiz_score): ?>
                                        <span class="score"><?php echo esc_html(number_format($student->avg_quiz_score, 1)); ?>%</span>
                                    <?php else: ?>
                                        <span class="no-score">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    $score = $student->avg_quiz_score ?? 0;
                                    $badge_class = 'needs-help';
                                    $badge_text = __('Needs Help', 'teacher-dashboard');
                                    
                                    if ($score >= 90) {
                                        $badge_class = 'excellent';
                                        $badge_text = __('Excellent', 'teacher-dashboard');
                                    } elseif ($score >= 80) {
                                        $badge_class = 'good';
                                        $badge_text = __('Good', 'teacher-dashboard');
                                    } elseif ($score >= 70) {
                                        $badge_class = 'average';
                                        $badge_text = __('Average', 'teacher-dashboard');
                                    } elseif ($score >= 60) {
                                        $badge_class = 'below-average';
                                        $badge_text = __('Below Average', 'teacher-dashboard');
                                    }
                                    ?>
                                    <span class="performance-badge <?php echo esc_attr($badge_class); ?>">
                                        <?php echo esc_html($badge_text); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p><?php _e('No students found for this teacher.', 'teacher-dashboard'); ?></p>
        <?php endif; ?>
        <?php
        
        $output = ob_get_clean();
        wp_send_json_success($output);
    }
}
