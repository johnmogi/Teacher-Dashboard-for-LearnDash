<?php
/**
 * Test WordPress loading and plugin functionality
 */

// Load WordPress
require_once('../../../wp-config.php');

echo "=== WORDPRESS TEST ===\n\n";

// Test 1: WordPress loaded
echo "1. WordPress Status:\n";
echo "✓ WordPress loaded successfully\n";
echo "✓ Database prefix: " . $wpdb->prefix . "\n";

// Test 2: Check if our classes exist
echo "\n2. Class Loading:\n";

if (class_exists('Teacher_Dashboard_Database')) {
    echo "✓ Teacher_Dashboard_Database class exists\n";
    
    try {
        $db_handler = Teacher_Dashboard_Database::get_instance();
        echo "✓ Database handler instance created\n";
        
        // Test basic query
        $teachers = $db_handler->get_all_teachers();
        echo "✓ get_all_teachers() executed: " . count($teachers) . " teachers found\n";
        
        if (!empty($teachers)) {
            echo "  First teacher: " . $teachers[0]['display_name'] . "\n";
        }
        
    } catch (Exception $e) {
        echo "✗ Database handler error: " . $e->getMessage() . "\n";
    }
} else {
    echo "✗ Teacher_Dashboard_Database class not found\n";
}

if (class_exists('Teacher_Dashboard_Core')) {
    echo "✓ Teacher_Dashboard_Core class exists\n";
} else {
    echo "✗ Teacher_Dashboard_Core class not found\n";
}

// Test 3: Check admin page
echo "\n3. Admin Page:\n";
if (function_exists('add_menu_page')) {
    echo "✓ WordPress admin functions available\n";
} else {
    echo "✗ WordPress admin functions not available\n";
}

// Test 4: Check hooks
echo "\n4. Plugin Hooks:\n";
global $wp_filter;
if (isset($wp_filter['admin_menu'])) {
    echo "✓ admin_menu hook exists\n";
} else {
    echo "✗ admin_menu hook not found\n";
}

echo "\n=== TEST COMPLETE ===\n";
?>
