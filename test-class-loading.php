<?php
/**
 * Test class loading without WordPress context
 */

echo "=== CLASS LOADING TEST ===\n\n";

// Test 1: Check if class exists before loading
echo "1. Pre-load check:\n";
if (class_exists('Teacher_Dashboard_Database')) {
    echo "✗ Class already exists (potential duplicate loading)\n";
} else {
    echo "✓ Class doesn't exist yet\n";
}

// Test 2: Try to load the class file
echo "\n2. Loading class file:\n";
try {
    require_once 'includes/class-database-handler.php';
    echo "✓ Class file loaded successfully\n";
} catch (Exception $e) {
    echo "✗ Error loading class: " . $e->getMessage() . "\n";
}

// Test 3: Check if class exists after loading
echo "\n3. Post-load check:\n";
if (class_exists('Teacher_Dashboard_Database')) {
    echo "✓ Class exists after loading\n";
    
    // Test 4: Check class methods
    echo "\n4. Method check:\n";
    $methods = get_class_methods('Teacher_Dashboard_Database');
    $expected_methods = [
        'get_instance',
        'get_all_teachers',
        'get_all_students',
        'get_all_groups',
        'get_students_by_teacher',
        'get_teacher_students'
    ];
    
    foreach ($expected_methods as $method) {
        if (in_array($method, $methods)) {
            echo "✓ Method $method exists\n";
        } else {
            echo "✗ Method $method missing\n";
        }
    }
    
    // Check for duplicate methods (this would cause fatal error if duplicates exist)
    echo "\n5. Duplicate method check:\n";
    $method_counts = array_count_values($methods);
    $duplicates_found = false;
    foreach ($method_counts as $method => $count) {
        if ($count > 1) {
            echo "✗ Duplicate method found: $method (appears $count times)\n";
            $duplicates_found = true;
        }
    }
    
    if (!$duplicates_found) {
        echo "✓ No duplicate methods found\n";
    }
    
} else {
    echo "✗ Class doesn't exist after loading\n";
}

echo "\n=== TEST COMPLETE ===\n";
?>
