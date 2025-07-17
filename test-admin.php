<?php
/**
 * Admin Test Page for Teacher Dashboard
 * Access via: /wp-content/plugins/teacher-dashboard/test-admin.php
 */

// Include WordPress
require_once(dirname(__FILE__) . '/../../../wp-load.php');

// Check if user is admin
if (!current_user_can('manage_options')) {
    wp_die('Access denied. Please login as administrator.');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Teacher Dashboard Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>Teacher Dashboard Plugin Test</h1>
    
    <div class="test-section">
        <h2>Plugin Status</h2>
        <?php
        $plugin_file = 'teacher-dashboard/teacher-dashboard.php';
        if (is_plugin_active($plugin_file)) {
            echo '<p class="success">✅ Plugin is active</p>';
        } else {
            echo '<p class="error">❌ Plugin is not active</p>';
            echo '<p>Attempting to activate...</p>';
            $result = activate_plugin($plugin_file);
            if (is_wp_error($result)) {
                echo '<p class="error">Failed to activate: ' . $result->get_error_message() . '</p>';
            } else {
                echo '<p class="success">✅ Plugin activated successfully</p>';
            }
        }
        ?>
    </div>
    
    <div class="test-section">
        <h2>Database Connection</h2>
        <?php
        global $wpdb;
        $prefix = $wpdb->prefix;
        echo "<p>Database prefix: <strong>{$prefix}</strong></p>";
        
        // Test basic query
        $user_count = $wpdb->get_var("SELECT COUNT(*) FROM {$prefix}users");
        echo "<p>Total users in database: <strong>{$user_count}</strong></p>";
        ?>
    </div>
    
    <div class="test-section">
        <h2>Teachers Query Test</h2>
        <?php
        $teachers_sql = "
            SELECT u.ID, u.display_name, u.user_email, cap.meta_value as capabilities
            FROM {$prefix}users u
            JOIN {$prefix}usermeta cap ON u.ID = cap.user_id AND cap.meta_key = 'wp_capabilities'
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
        
        $teachers = $wpdb->get_results($teachers_sql);
        echo "<p>Teachers found: <strong>" . count($teachers) . "</strong></p>";
        
        if (!empty($teachers)) {
            echo '<table>';
            echo '<tr><th>Name</th><th>Email</th><th>Roles</th></tr>';
            foreach ($teachers as $teacher) {
                echo '<tr>';
                echo '<td>' . esc_html($teacher->display_name) . '</td>';
                echo '<td>' . esc_html($teacher->user_email) . '</td>';
                echo '<td>' . esc_html($teacher->capabilities) . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }
        ?>
    </div>
    
    <div class="test-section">
        <h2>Groups Query Test</h2>
        <?php
        $groups_sql = "
            SELECT p.ID, p.post_title, p.post_status,
                   COUNT(DISTINCT CASE WHEN um.meta_key LIKE 'learndash_group_users_%' THEN um.user_id ELSE NULL END) as student_count
            FROM {$prefix}posts p
            LEFT JOIN {$prefix}usermeta um ON p.ID = SUBSTRING_INDEX(um.meta_key, '_', -1) AND um.meta_key LIKE 'learndash_group_users_%'
            WHERE p.post_type = 'groups' AND p.post_status = 'publish'
            GROUP BY p.ID
            ORDER BY p.post_title
        ";
        
        $groups = $wpdb->get_results($groups_sql);
        echo "<p>Groups found: <strong>" . count($groups) . "</strong></p>";
        
        if (!empty($groups)) {
            echo '<table>';
            echo '<tr><th>Group Name</th><th>ID</th><th>Students</th></tr>';
            foreach ($groups as $group) {
                echo '<tr>';
                echo '<td>' . esc_html($group->post_title) . '</td>';
                echo '<td>' . esc_html($group->ID) . '</td>';
                echo '<td>' . esc_html($group->student_count) . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }
        ?>
    </div>
    
    <div class="test-section">
        <h2>Plugin Classes Test</h2>
        <?php
        if (class_exists('Teacher_Dashboard')) {
            echo '<p class="success">✅ Teacher_Dashboard class exists</p>';
        } else {
            echo '<p class="error">❌ Teacher_Dashboard class not found</p>';
        }
        
        if (class_exists('Teacher_Database_Handler')) {
            echo '<p class="success">✅ Teacher_Database_Handler class exists</p>';
        } else {
            echo '<p class="error">❌ Teacher_Database_Handler class not found</p>';
        }
        
        if (class_exists('Teacher_Role_Manager')) {
            echo '<p class="success">✅ Teacher_Role_Manager class exists</p>';
        } else {
            echo '<p class="error">❌ Teacher_Role_Manager class not found</p>';
        }
        ?>
    </div>
    
    <div class="test-section">
        <h2>Dashboard Shortcode Test</h2>
        <p>Shortcode: <code>[teacher_dashboard]</code></p>
        <div style="border: 1px solid #ccc; padding: 10px; background: #f9f9f9;">
            <?php echo do_shortcode('[teacher_dashboard]'); ?>
        </div>
    </div>
    
</body>
</html>
