<?php
// This file will check if a title starts with a letter or number
function validateMovieTitle($title) {
    $title = trim($title);
    
    if (empty($title)) {
        return false;
    }
    
    // Check if title starts with a letter or number
    if (!preg_match('/^[a-zA-Z0-9]/', $title)) {
        return false;
    }
    
    return true;
}

// Add this to the createMovie and updateMovie functions
// if (!validateMovieTitle($data['title'])) {
//     return [
//         'success' => false,
//         'message' => "Movie title must start with a letter or number."
//     ];
// }
?>
