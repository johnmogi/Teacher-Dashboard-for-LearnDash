<?php
/**
 * Teacher Dashboard Template
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$current_user = wp_get_current_user();
?>

<div class="wrap">
    <div class="teacher-dashboard-container" id="teacher-dashboard-teacher">
        <div class="dashboard-header">
            <h1><?php printf(__('Welcome, %s', 'teacher-dashboard'), esc_html($current_user->display_name)); ?></h1>
            <div class="dashboard-actions">
                <button type="button" class="button button-secondary refresh-btn">
                    <?php _e('Refresh Data', 'teacher-dashboard'); ?>
                </button>
            </div>
        </div>

        <!-- Teacher Summary -->
        <div class="dashboard-summary">
            <div class="summary-cards">
                <div class="summary-card">
                    <h3><?php _e('My Groups', 'teacher-dashboard'); ?></h3>
                    <div class="summary-number"><?php echo count($groups ?? array()); ?></div>
                </div>
                <div class="summary-card">
                    <h3><?php _e('My Students', 'teacher-dashboard'); ?></h3>
                    <div class="summary-number"><?php echo count($students ?? array()); ?></div>
                </div>
                <div class="summary-card">
                    <h3><?php _e('Quizzes Tracked', 'teacher-dashboard'); ?></h3>
                    <div class="summary-number"><?php echo count($quiz_stats ?? array()); ?></div>
                </div>
                <div class="summary-card">
                    <h3><?php _e('Avg Class Score', 'teacher-dashboard'); ?></h3>
                    <div class="summary-number">
                        <?php 
                        if (!empty($quiz_stats)) {
                            $total_avg = array_sum(array_column($quiz_stats, 'avg_score')) / count($quiz_stats);
                            echo number_format($total_avg, 1) . '%';
                        } else {
                            echo 'N/A';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quiz Performance for Teacher's Classes -->
        <?php if (!empty($quiz_stats)): ?>
        <div class="dashboard-section quiz-stats">
            <h2><?php _e('Quiz Performance - My Classes', 'teacher-dashboard'); ?></h2>
            <div class="quiz-stats-grid">
                <?php foreach ($quiz_stats as $quiz): ?>
                    <div class="quiz-stat-card">
                        <h4><?php echo esc_html($quiz->quiz_name); ?></h4>
                        <?php if ($quiz->course_name): ?>
                            <p class="course-name"><?php echo esc_html($quiz->course_name); ?></p>
                        <?php endif; ?>
                        <div class="stats">
                            <div class="stat">
                                <span class="label"><?php _e('Student Attempts:', 'teacher-dashboard'); ?></span>
                                <span class="value"><?php echo esc_html($quiz->total_attempts); ?></span>
                            </div>
                            <div class="stat">
                                <span class="label"><?php _e('Class Average:', 'teacher-dashboard'); ?></span>
                                <span class="value score-<?php echo $quiz->avg_score >= 70 ? 'good' : ($quiz->avg_score >= 50 ? 'average' : 'poor'); ?>">
                                    <?php echo esc_html(number_format($quiz->avg_score, 1)); ?>%
                                </span>
                            </div>
                            <div class="stat">
                                <span class="label"><?php _e('Score Range:', 'teacher-dashboard'); ?></span>
                                <span class="value"><?php echo esc_html(number_format($quiz->min_score, 1)); ?>% - <?php echo esc_html(number_format($quiz->max_score, 1)); ?>%</span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- My Groups -->
        <?php if (!empty($groups)): ?>
        <div class="dashboard-section groups">
            <h2><?php _e('My Groups', 'teacher-dashboard'); ?></h2>
            <div class="groups-table">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Group Name', 'teacher-dashboard'); ?></th>
                            <th><?php _e('Students Enrolled', 'teacher-dashboard'); ?></th>
                            <th><?php _e('Status', 'teacher-dashboard'); ?></th>
                            <th><?php _e('Actions', 'teacher-dashboard'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($groups as $group): ?>
                            <tr>
                                <td><strong><?php echo esc_html($group->group_name); ?></strong></td>
                                <td>
                                    <span class="student-count">
                                        <?php echo esc_html($group->student_count ?? 0); ?>
                                        <?php _e('students', 'teacher-dashboard'); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status status-<?php echo esc_attr($group->post_status); ?>">
                                        <?php echo esc_html(ucfirst($group->post_status)); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?php echo admin_url('post.php?post=' . $group->group_id . '&action=edit'); ?>" class="button button-small">
                                        <?php _e('Manage', 'teacher-dashboard'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- My Students -->
        <?php if (!empty($students)): ?>
        <div class="dashboard-section students">
            <h2><?php _e('My Students', 'teacher-dashboard'); ?></h2>
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
                                        <span class="score score-<?php echo $student->avg_quiz_score >= 70 ? 'good' : ($student->avg_quiz_score >= 50 ? 'average' : 'poor'); ?>">
                                            <?php echo esc_html(number_format($student->avg_quiz_score, 1)); ?>%
                                        </span>
                                    <?php else: ?>
                                        <span class="no-score">No quizzes yet</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($student->avg_quiz_score): ?>
                                        <?php if ($student->avg_quiz_score >= 80): ?>
                                            <span class="performance-badge excellent">Excellent</span>
                                        <?php elseif ($student->avg_quiz_score >= 70): ?>
                                            <span class="performance-badge good">Good</span>
                                        <?php elseif ($student->avg_quiz_score >= 60): ?>
                                            <span class="performance-badge average">Average</span>
                                        <?php elseif ($student->avg_quiz_score >= 50): ?>
                                            <span class="performance-badge below-average">Below Average</span>
                                        <?php else: ?>
                                            <span class="performance-badge needs-help">Needs Help</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="performance-badge no-data">No Data</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Quick Actions -->
        <div class="dashboard-section quick-actions">
            <h2><?php _e('Quick Actions', 'teacher-dashboard'); ?></h2>
            <div class="action-buttons">
                <a href="<?php echo admin_url('admin.php?page=groups'); ?>" class="button button-primary">
                    <?php _e('Manage My Groups', 'teacher-dashboard'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=learndash-lms-reports'); ?>" class="button button-secondary">
                    <?php _e('View Detailed Reports', 'teacher-dashboard'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=learndash_lms_users'); ?>" class="button button-secondary">
                    <?php _e('Manage Students', 'teacher-dashboard'); ?>
                </a>
            </div>
        </div>

        <!-- Empty State for Teachers -->
        <?php if (empty($groups) && empty($students) && empty($quiz_stats)): ?>
        <div class="dashboard-empty-state">
            <h3><?php _e('Welcome to Your Teaching Dashboard!', 'teacher-dashboard'); ?></h3>
            <p><?php _e('It looks like you haven\'t been assigned to any groups yet, or your groups don\'t have students enrolled.', 'teacher-dashboard'); ?></p>
            <p><?php _e('Contact your administrator to:', 'teacher-dashboard'); ?></p>
            <ul>
                <li><?php _e('Assign you as a group leader', 'teacher-dashboard'); ?></li>
                <li><?php _e('Add students to your groups', 'teacher-dashboard'); ?></li>
                <li><?php _e('Set up courses and quizzes', 'teacher-dashboard'); ?></li>
            </ul>
            <p>
                <a href="<?php echo admin_url('admin.php?page=groups'); ?>" class="button button-primary">
                    <?php _e('View Groups', 'teacher-dashboard'); ?>
                </a>
            </p>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.dashboard-summary {
    margin-bottom: 30px;
}

.summary-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.summary-card {
    background: white;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    text-align: center;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.summary-card h3 {
    margin: 0 0 10px 0;
    font-size: 14px;
    color: #646970;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.summary-number {
    font-size: 32px;
    font-weight: 600;
    color: #1d2327;
    line-height: 1;
}

.score-good { color: #00a32a; font-weight: 600; }
.score-average { color: #dba617; font-weight: 600; }
.score-poor { color: #d63638; font-weight: 600; }

.performance-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.performance-badge.excellent { background: #d4edda; color: #155724; }
.performance-badge.good { background: #d1ecf1; color: #0c5460; }
.performance-badge.average { background: #fff3cd; color: #856404; }
.performance-badge.below-average { background: #ffeaa7; color: #856404; }
.performance-badge.needs-help { background: #f8d7da; color: #721c24; }
.performance-badge.no-data { background: #e2e3e5; color: #6c757d; }

.student-count {
    color: #646970;
    font-size: 14px;
}

.quick-actions {
    background: #f8f9fa;
    border-left: 4px solid #0073aa;
}

.action-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.dashboard-empty-state {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
}

.dashboard-empty-state h3 {
    color: #646970;
    margin-bottom: 15px;
}

.dashboard-empty-state ul {
    text-align: left;
    display: inline-block;
    margin: 20px 0;
}

@media (max-width: 768px) {
    .action-buttons {
        flex-direction: column;
    }
    
    .action-buttons .button {
        width: 100%;
        text-align: center;
    }
}
</style>
