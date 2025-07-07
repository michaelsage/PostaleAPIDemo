<?php
// process_alias.php
// Function to parse raw HTTP headers into an associative array
function parseHttpHeaders($headers) {
    $headerLines = explode("\r\n", $headers);
    $parsedHeaders = [];
    foreach ($headerLines as $line) {
        if (empty($line)) continue; // Skip empty lines

        $parts = explode(':', $line, 2);
        if (count($parts) === 2) {
            $key = strtolower(trim($parts[0])); // Convert header name to lowercase for consistent access
            $value = trim($parts[1]);
            $parsedHeaders[$key] = $value;
        }
    }
    return $parsedHeaders;
}

// Check if the form was submitted via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect data from the form
    $alias = $_POST['alias'] ?? '';
    $name = $_POST['name'] ?? '';
    $redirectTo = $_POST['redirectTo'] ?? '';

    // Validate input (simple validation, you might want more robust validation)
    if (empty($alias) || empty($name) || empty($redirectTo)) {
        echo "<p style='color: red;'>Error: All fields are required.</p>";
        exit;
    }

    // Format redirectTo as a PHP array containing the email address.
    // This will be correctly encoded as a JSON array (e.g., ["user@example.com"])
    // when json_encode is called later.
    $formattedRedirectTo = [$redirectTo];

    // Prepare the data for the API request as a JSON object
    $data = [
        'address' => $alias,
        'name' => $name,
        'redirectTo' => $formattedRedirectTo // Using the newly formatted redirectTo (now a PHP array)
    ];
    $json_data = json_encode($data);

    // Postale.io API endpoint
    $api_url = 'https://postale.io/api/v1/aliases';

    // Initialize cURL session
    $ch = curl_init($api_url);

    // Set cURL options
    curl_setopt($ch, CURLOPT_POST, 1); // Set as POST request
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data); // Set the JSON data
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return the response as a string
    curl_setopt($ch, CURLOPT_HEADER, true); // IMPORTANT: Include headers in the output
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($json_data),
        // Replace with your encoded API Key don't forget the : at the end 
        'Authorization: Basic 'baseencodeded username with : at the end'
    ]);

    // Execute the cURL request
    $response = curl_exec($ch);

    // Check if cURL execution failed
    if ($response === false) {
        echo "<p style='color: red;'>cURL Error: " . curl_error($ch) . " (Code: " . curl_errno($ch) . ")</p>";
        curl_close($ch);
        exit;
    }

    // Get cURL information
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE); // Get the size of the header

    // Separate headers from the body
    $headers = substr($response, 0, $header_size);
    $body = substr($response, $header_size);

    // Parse headers into an associative array
    $parsed_headers = parseHttpHeaders($headers);

    $location_header_found = isset($parsed_headers['location']);
    $new_alias_url = $location_header_found ? $parsed_headers['location'] : '';

    // Handle the API response
    if ($http_code === 201 && $location_header_found) {
        echo "<p style='color: green;'>Alias created successfully! (HTTP 201 Created)</p>";
        // Optionally, display body if it exists, even if it's not strictly JSON for 201
        if (!empty($body)) {
            echo "<p>Raw API Response Body (if any):</p>";
            echo "<pre>" . htmlspecialchars($body) . "</pre>";
        }
    } else {
        // If not a 201 Created with Location header, attempt to decode body as JSON
        if (empty($body)) {
            echo "<p style='color: red;'>Error: No response body received from the API (or not a 201 success). HTTP Status Code: " . htmlspecialchars($http_code) . "</p>";
        } else {
            $decoded_response = json_decode($body, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                // Check for specific success/error messages from Postale.io API
                if (isset($decoded_response['message']) && strpos($decoded_response['message'], 'email address is already taken') !== false) {
                    // Specific error: Email address already in use
                    echo "<p style='color: orange;'>The email address you provided is already in use. Please try a different one.</p>";
                } elseif (isset($decoded_response['success']) && $decoded_response['success'] === true) {
                    echo "<p style='color: green;'>Alias created successfully!</p>";
                    echo "<pre>" . print_r($decoded_response, true) . "</pre>";
                } elseif (isset($decoded_response['error'])) {
                    echo "<p style='color: red;'>API Error: " . htmlspecialchars($decoded_response['error']) . "</p>";
                    echo "<pre>" . print_r($decoded_response, true) . "</pre>";
                } else {
                    echo "<p style='color: orange;'>Unknown API Response (JSON):</p>";
                    echo "<pre>" . print_r($decoded_response, true) . "</pre>";
                }
            } else {
                echo "<p style='color: red;'>Failed to decode API response (not valid JSON). HTTP Status Code: " . htmlspecialchars($http_code) . "</p>";
                echo "<p>Raw API Response Body (for debugging):</p>";
                echo "<pre>" . htmlspecialchars($body) . "</pre>";
            }
        }
    }

    // Close cURL session
    curl_close($ch);
} else {
    // If accessed directly without POST data
    echo "<p>Please submit the form to create an alias.</p>";
}
?>
