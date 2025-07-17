<?php
/**
 * Minimal test to check plugin functionality
 */

// Set up minimal WordPress environment
define('WP_USE_THEMES', false);
define('ABSPATH', dirname(dirname(dirname(__DIR__))) . '/');

// Load WordPress
require_once(ABSPATH . 'wp-load.php');

echo "=== MINIMAL PLUGIN TEST ===\n\n";

// Check if plugin is active
$active_plugins = get_option('active_plugins', array());
$plugin_slug = 'teacher-dashboard/teacher-dashboard.php';

echo "1. Plugin Status:\n";
if (in_array($plugin_slug, $active_plugins)) {
    echo "✓ Plugin is active\n";
} else {
    echo "✗ Plugin is not active\n";
    echo "Activating plugin...\n";
    
    // Try to activate plugin
    if (function_exists('activate_plugin')) {
        $result = activate_plugin($plugin_slug);
        if (is_wp_error($result)) {
            echo "✗ Activation failed: " . $result->get_error_message() . "\n";
        } else {
            echo "✓ Plugin activated successfully\n";
        }
    }
}

// Test class loading
echo "\n2. Class Loading:\n";
if (class_exists('Teacher_Dashboard_Database')) {
    echo "✓ Teacher_Dashboard_Database class loaded\n";
} else {
    echo "✗ Teacher_Dashboard_Database class not found\n";
}

if (class_exists('Teacher_Dashboard_Core')) {
    echo "✓ Teacher_Dashboard_Core class loaded\n";
} else {
    echo "✗ Teacher_Dashboard_Core class not found\n";
}

// Test database connection
echo "\n3. Database Test:\n";
global $wpdb;
$result = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}");
echo "✓ Database connection working - {$result} users found\n";

echo "\n=== TEST COMPLETE ===\n";
?>
