<?php
/**
 * Isolated test to check class loading
 */

echo "Testing class loading...\n";

// Test 1: Check if class exists before loading
if (class_exists('Teacher_Dashboard_Database')) {
    echo "ERROR: Class already exists before loading!\n";
} else {
    echo "✓ Class doesn't exist yet\n";
}

// Test 2: Try to load the class
echo "Loading class file...\n";
require_once('includes/class-database-handler.php');

// Test 3: Check if class exists after loading
if (class_exists('Teacher_Dashboard_Database')) {
    echo "✓ Class loaded successfully\n";
} else {
    echo "ERROR: Class not found after loading!\n";
}

echo "Test complete.\n";
?>
