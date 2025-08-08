<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Adjust this for security if needed

// Get the API key from environment variable set in .htaccess
$apiKey = getenv('GEMINI_API_KEY');

if ($apiKey) {
    echo json_encode(['apiKey' => $apiKey]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'API key not configured']);
}
?>