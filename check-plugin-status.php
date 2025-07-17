<?php
/**
 * Check plugin activation status
 */

// Load WordPress
require_once('../../../wp-config.php');

echo "=== PLUGIN STATUS CHECK ===\n\n";

// Check if plugin is activated
$active_plugins = get_option('active_plugins', array());
$plugin_file = 'teacher-dashboard/teacher-dashboard.php';

echo "Looking for plugin: $plugin_file\n";
echo "Active plugins:\n";
foreach ($active_plugins as $plugin) {
    echo "  - $plugin\n";
}

if (in_array($plugin_file, $active_plugins)) {
    echo "\n✓ Plugin is ACTIVATED\n";
} else {
    echo "\n✗ Plugin is NOT ACTIVATED\n";
    echo "To activate, run: wp plugin activate teacher-dashboard\n";
}

// Check if plugin files exist
echo "\nPlugin files check:\n";
$plugin_dir = dirname(__FILE__);
$files = [
    'teacher-dashboard.php',
    'includes/class-database-handler.php',
    'includes/class-teacher-dashboard.php'
];

foreach ($files as $file) {
    if (file_exists($plugin_dir . '/' . $file)) {
        echo "✓ $file exists\n";
    } else {
        echo "✗ $file missing\n";
    }
}

// Check for syntax errors
echo "\nSyntax check:\n";
foreach ($files as $file) {
    if (file_exists($plugin_dir . '/' . $file) && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
        $output = shell_exec("php -l \"$plugin_dir/$file\" 2>&1");
        if (strpos($output, 'No syntax errors') !== false) {
            echo "✓ $file - No syntax errors\n";
        } else {
            echo "✗ $file - Syntax error: " . trim($output) . "\n";
        }
    }
}

echo "\n=== CHECK COMPLETE ===\n";
?>
