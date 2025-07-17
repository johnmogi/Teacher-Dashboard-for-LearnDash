<?php
/**
 * Admin Dashboard Template
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <div class="teacher-dashboard-container" id="teacher-dashboard-admin">
        <div class="dashboard-header">
            <h1><?php _e('Teacher Dashboard - Admin View', 'teacher-dashboard'); ?></h1>
            <div class="dashboard-actions">
                <button type="button" class="button button-secondary refresh-btn">
                    <?php _e('Refresh Data', 'teacher-dashboard'); ?>
                </button>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="dashboard-summary">
            <div class="summary-cards">
                <div class="summary-card">
                    <h3><?php _e('Total Teachers', 'teacher-dashboard'); ?></h3>
                    <div class="summary-number"><?php echo count($teachers ?? array()); ?></div>
                </div>
                <div class="summary-card">
                    <h3><?php _e('Total Groups', 'teacher-dashboard'); ?></h3>
                    <div class="summary-number"><?php echo count($groups ?? array()); ?></div>
                </div>
                <div class="summary-card">
                    <h3><?php _e('Total Students', 'teacher-dashboard'); ?></h3>
                    <div class="summary-number"><?php echo count($students ?? array()); ?></div>
                </div>
                <div class="summary-card">
                    <h3><?php _e('Active Quizzes', 'teacher-dashboard'); ?></h3>
                    <div class="summary-number"><?php echo count($quiz_stats ?? array()); ?></div>
                </div>
            </div>
        </div>

        <!-- Teachers (Interactive Selection) -->
        <?php if (!empty($teachers)): ?>
        <div class="dashboard-section teachers">
            <h3><?php _e('Teachers', 'teacher-dashboard'); ?></h3>
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
                        <?php 
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
                        
                        foreach ($processed_teachers as $teacher): 
                        ?>
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
        </div>
        <?php else: ?>
        <div class="dashboard-section teachers">
            <h3><?php _e('Teachers', 'teacher-dashboard'); ?></h3>
            <p><?php _e('No teachers available.', 'teacher-dashboard'); ?></p>
        </div>
        <?php endif; ?>
        
        <!-- Students Section (filtered by selected teacher) -->
        <div class="dashboard-section students" id="filtered-students">
            <h3><?php _e('Students', 'teacher-dashboard'); ?> <span id="selected-teacher-name"></span></h3>
            <div id="students-content">
                <p><?php _e('Select a teacher to view their students.', 'teacher-dashboard'); ?></p>
            </div>
        </div>

        <!-- Groups Overview -->
        <?php if (!empty($groups)): ?>
        <div class="dashboard-section groups">
            <h3><?php _e('Groups', 'teacher-dashboard'); ?></h3>
            <div class="groups-table">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Group Name', 'teacher-dashboard'); ?></th>
                            <th><?php _e('Teacher', 'teacher-dashboard'); ?></th>
                            <th><?php _e('Students', 'teacher-dashboard'); ?></th>
                            <th><?php _e('Status', 'teacher-dashboard'); ?></th>
                            <th><?php _e('Actions', 'teacher-dashboard'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($groups as $group): ?>
                            <tr>
                                <td><strong><?php echo esc_html($group->group_name); ?></strong></td>
                                <td><?php echo esc_html($group->teacher_name ?? 'Unassigned'); ?></td>
                                <td><?php echo esc_html($group->student_count ?? 0); ?></td>
                                <td>
                                    <span class="status status-<?php echo esc_attr($group->post_status); ?>">
                                        <?php echo esc_html(ucfirst($group->post_status)); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?php echo admin_url('post.php?post=' . $group->group_id . '&action=edit'); ?>" class="button button-small">
                                        <?php _e('Edit', 'teacher-dashboard'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Quiz Performance -->
        <?php if (!empty($quiz_stats)): ?>
        <div class="dashboard-section quiz-stats">
            <h3><?php _e('Quiz Statistics', 'teacher-dashboard'); ?></h3>
            <div class="quiz-stats-grid">
                <?php foreach ($quiz_stats as $quiz): ?>
                    <div class="quiz-stat-card">
                        <h4><?php echo esc_html($quiz->quiz_name); ?></h4>
                        <?php if ($quiz->course_name): ?>
                            <p class="course-name"><?php echo esc_html($quiz->course_name); ?></p>
                        <?php endif; ?>
                        <div class="stats">
                            <div class="stat">
                                <span class="label"><?php _e('Total Attempts:', 'teacher-dashboard'); ?></span>
                                <span class="value"><?php echo esc_html($quiz->total_attempts); ?></span>
                            </div>
                            <div class="stat">
                                <span class="label"><?php _e('Average Score:', 'teacher-dashboard'); ?></span>
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
        <?php else: ?>
        <div class="dashboard-section quiz-stats">
            <h3><?php _e('Quiz Statistics', 'teacher-dashboard'); ?></h3>
            <p><?php _e('No quiz data available.', 'teacher-dashboard'); ?></p>
        </div>
        <?php endif; ?>

        <!-- Empty State -->
        <?php if (empty($teachers) && empty($groups) && empty($students) && empty($quiz_stats)): ?>
        <div class="dashboard-empty-state">
            <h3><?php _e('No Data Available', 'teacher-dashboard'); ?></h3>
            <p><?php _e('There is no dashboard data to display. This could be because:', 'teacher-dashboard'); ?></p>
            <ul>
                <li><?php _e('No teachers have been assigned to groups', 'teacher-dashboard'); ?></li>
                <li><?php _e('No students are enrolled in groups', 'teacher-dashboard'); ?></li>
                <li><?php _e('No quizzes have been completed', 'teacher-dashboard'); ?></li>
            </ul>
            <p>
                <a href="<?php echo admin_url('admin.php?page=groups'); ?>" class="button button-primary">
                    <?php _e('Manage Groups', 'teacher-dashboard'); ?>
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

.score-good { color: #00a32a; }
.score-average { color: #dba617; }
.score-poor { color: #d63638; }

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
</style>
