<?php

header('Content-Type: application/json'); // Tell the browser this is JSON

// IMPORTANT: Replace with your actual POSTALEAPI Key
// Example: 'https://api.your-company.com/v1/domains' (without page/pageSize parameters)
$baseApiUrl = 'https://postale.io/api/v1/domains';
$username = 'POSTALEAPIKEY';

// Initialize variables for pagination
$allDomains = [];
$currentPage = 0;
$pageSize = 25; // Default page size. Your API's 'paging' field might override this after the first request.
$totalDomains = 0;
$lastPage = 0;
$morePages = true;

do {
    // Construct the URL with current page and page size
    // Adjust parameter names (e.g., 'page', 'pageSize') based on your API's documentation
    $apiUrl = $baseApiUrl . "?page=" . $currentPage . "&pageSize=" . $pageSize; // Example query parameters

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return the response as a string
    curl_setopt($ch, CURLOPT_HTTPGET, true); // Specify GET request
    curl_setopt($ch, CURLOPT_USERPWD, "$username:"); // Set basic authentication

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
        http_response_code(500); // Internal Server Error
        echo json_encode(['error' => 'cURL error: ' . $error_msg, 'fetchedPages' => count($allDomains)]);
        curl_close($ch);
        exit; // Stop execution on cURL error
    }

    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($http_code >= 400) {
        http_response_code($http_code); // Pass through the API's error code
        echo json_encode(['error' => 'API returned an error', 'status_code' => $http_code, 'details' => $response, 'fetchedPages' => count($allDomains)]);
        curl_close($ch);
        exit; // Stop execution on API error
    }

    $data = json_decode($response, true); // Decode response into an associative array

    // Check if the decoded data is valid and contains 'results'
    if (json_last_error() !== JSON_ERROR_NONE || !isset($data['results'])) {
        http_response_code(500);
        echo json_encode(['error' => 'Invalid JSON response or missing results array from API', 'rawResponse' => $response]);
        curl_close($ch);
        exit;
    }

    // Append current page's results
    $allDomains = array_merge($allDomains, $data['results']);

    // Update pagination variables for the next loop iteration
    if (isset($data['page'])) {
        $currentPage = $data['page'];
    }
    if (isset($data['paging'])) {
        $pageSize = $data['paging']; // Use the API's preferred page size for subsequent requests
    }
    if (isset($data['totalCount'])) {
        $totalDomains = $data['totalCount'];
    }
    if (isset($data['lastPage'])) {
        $lastPage = $data['lastPage'];
    }

    curl_close($ch); // Close cURL for this iteration

    // Determine if there are more pages
    if (isset($data['lastPage'])) {
        $morePages = ($currentPage < $lastPage);
    } else {
        // Fallback if 'lastPage' is not reliable/present
        $morePages = (count($data['results']) > 0 && count($data['results']) == $pageSize && count($allDomains) < $totalDomains);
    }
    
    $currentPage++; // Increment page for the next request

    // Small delay to avoid hammering the API, uncomment if needed for rate limiting
    // usleep(50000); // 50ms delay

} while ($morePages);

// Output the consolidated list of all domains
echo json_encode(['results' => $allDomains]);

?>
