<?php
// This file checks for syntax errors in movie_functions.php
$file = 'admin/includes/movie_functions.php';
echo "Checking syntax of $file...\n";

// Try to include the file
try {
    include_once $file;
    echo "No syntax errors detected.\n";
} catch (ParseError $e) {
    echo "Syntax error: " . $e->getMessage() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
?>
