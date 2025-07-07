<?php

header('Content-Type: application/json'); // Tell the browser this is JSON

// IMPORTANT: Replace with your actual APIKEY
// The API URL will be constructed as YOUR_BASE_API_URL/api/v1/logs/email/{date}
$yourBaseApiUrl = 'https://postale.io'; // e.g., 'https://your-api-domain.com'
$username = 'POSTALEAPIKEY';

// Get the date from the GET request
$date = isset($_GET['date']) ? $_GET['date'] : '';

if (empty($date)) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Date parameter is missing.']);
    exit;
}

// Construct the initial API URL with the date
// Ensure this base URL does NOT include any page or paging parameters
$apiEndpoint = "/api/v1/logs/email/{$date}";
$fullBaseApiUrl = $yourBaseApiUrl . $apiEndpoint;

// Initialize variables for pagination
$allEmailLogs = [];
$currentPage = 0;
$pageSize = 25; // Default page size. Your API's 'paging' field might override this after the first request.
$totalEmailLogs = 0;
$lastPage = 0;
$morePages = true;

do {
    // Construct the URL with current page and page size
    // Adjust parameter names (e.g., 'page', 'pageSize') based on your API's documentation
    $apiUrl = $fullBaseApiUrl . "?page=" . $currentPage . "&pageSize=" . $pageSize; // Example query parameters

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPGET, true);
    curl_setopt($ch, CURLOPT_USERPWD, "$username:");

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
        http_response_code(500);
        echo json_encode(['error' => 'cURL error: ' . $error_msg, 'fetchedPages' => count($allEmailLogs)]);
        curl_close($ch);
        exit;
    }

    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($http_code >= 400) {
        http_response_code($http_code);
        echo json_encode(['error' => 'API returned an error', 'status_code' => $http_code, 'details' => $response, 'fetchedPages' => count($allEmailLogs)]);
        curl_close($ch);
        exit;
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
    $allEmailLogs = array_merge($allEmailLogs, $data['results']);

    // Update pagination variables for the next loop iteration
    if (isset($data['page'])) {
        $currentPage = $data['page'];
    }
    if (isset($data['paging'])) {
        $pageSize = $data['paging']; // Use the API's preferred page size for subsequent requests
    }
    if (isset($data['totalCount'])) {
        $totalEmailLogs = $data['totalCount'];
    }
    if (isset($data['lastPage'])) {
        $lastPage = $data['lastPage'];
    }

    curl_close($ch); // Close cURL for this iteration

    // Determine if there are more pages
    if (isset($data['lastPage'])) {
        $morePages = ($currentPage < $lastPage);
    } else {
        // Fallback: if 'lastPage' is not reliable/present, check if we received a full page
        $morePages = (count($data['results']) > 0 && count($data['results']) == $pageSize && count($allEmailLogs) < $totalEmailLogs);
    }
    
    $currentPage++; // Increment page for the next request

    // Small delay to avoid hammering the API, uncomment if needed
    // usleep(50000); // 50ms delay

} while ($morePages);

// Output the consolidated list of all email logs
echo json_encode(['results' => $allEmailLogs]);

?>
