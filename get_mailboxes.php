<?php

header('Content-Type: application/json'); // Tell the browser this is JSON

// IMPORTANT: Replace with your actual API Key
$baseApiUrl = 'https://postale.io/api/v1/mailboxes';
$username = 'POSTALEAPIKEY';

// Initialize variables for pagination
$allMailboxes = [];
$currentPage = 0;
$pageSize = 25; // Default page size. Your API's 'paging' field might override this after the first request.
$totalMailboxes = 0;
$lastPage = 0;
$morePages = true;

do {
    // Construct the URL with current page and page size
    // Adjust parameter names (e.g., 'page', 'pageSize') based on your API's documentation
    $apiUrl = $baseApiUrl . "?page=" . $currentPage . "&pageSize=" . $pageSize; // Example query parameters

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPGET, true);
    curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
        http_response_code(500);
        echo json_encode(['error' => 'cURL error: ' . $error_msg, 'fetchedPages' => count($allMailboxes)]);
        curl_close($ch);
        exit; // Stop execution on cURL error
    }

    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($http_code >= 400) {
        http_response_code($http_code);
        echo json_encode(['error' => 'API returned an error', 'status_code' => $http_code, 'details' => $response, 'fetchedPages' => count($allMailboxes)]);
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
    $allMailboxes = array_merge($allMailboxes, $data['results']);

    // Update pagination variables for the next loop iteration
    // Use data from API if available
    if (isset($data['page'])) {
        $currentPage = $data['page'];
    }
    if (isset($data['paging'])) {
        $pageSize = $data['paging']; // Use the API's preferred page size for subsequent requests
    }
    if (isset($data['totalCount'])) {
        $totalMailboxes = $data['totalCount'];
    }
    if (isset($data['lastPage'])) {
        $lastPage = $data['lastPage'];
    }

    curl_close($ch); // Close cURL for this iteration

    // Determine if there are more pages
    // Method 1: Use 'lastPage' field if available (most robust)
    if (isset($data['lastPage'])) {
        $morePages = ($currentPage < $lastPage);
    }
    // Method 2: Fallback if 'lastPage' is not reliable/present, check if we received a full page
    // and if the total collected is less than the reported totalCount
    else {
        $morePages = (count($data['results']) > 0 && count($data['results']) == $pageSize && count($allMailboxes) < $totalMailboxes);
    }
    
    $currentPage++; // Increment page for the next request

    // Small delay to avoid hammering the API, uncomment if needed for rate limiting
    // usleep(50000); // 50ms delay

} while ($morePages);

// Output the consolidated list of all mailboxes
echo json_encode(['results' => $allMailboxes]);

?>
