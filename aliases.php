<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Postale API Query</title>
    <!-- Tailwind CSS CDN for styling -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Custom styles for Inter font and general layout */
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 2rem 1rem; /* Add padding for smaller screens */
        }
        .container {
            background-color: #ffffff;
            padding: 2.5rem;
            border-radius: 1rem; /* Rounded corners */
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 900px; /* Increased max-width for better table display */
            box-sizing: border-box;
        }
        input[type="text"], input[type="number"] {
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            width: 100%;
            box-sizing: border-box;
            transition: border-color 0.2s;
        }
        input[type="text"]:focus, input[type="number"]:focus {
            outline: none;
            border-color: #3b82f6; /* Blue focus ring */
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25);
        }
        button {
            background-color: #3b82f6; /* Blue button */
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s, transform 0.1s;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        button:hover {
            background-color: #2563eb; /* Darker blue on hover */
            transform: translateY(-1px);
        }
        button:active {
            transform: translateY(0);
            box-shadow: none;
        }
        button:disabled {
            background-color: #9ca3af; /* Gray for disabled buttons */
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        .response-box {
            margin-top: 1.5rem;
        }
        /* Table specific styles */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        th, td {
            padding: 0.75rem;
            text-align: left;
        }
        th {
            background-color: #f8fafc; /* Lighter background for headers */
            font-weight: 600;
            color: #475569;
            border-bottom: 2px solid #e2e8f0; /* Darker border for headers */
        }
        td {
            border-bottom: 1px solid #e2e8f0; /* Light gray border for cells */
        }
        tr:hover {
            background-color: #f0f4f8; /* Subtle hover effect */
        }
        .pagination-controls {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 1.5rem;
            align-items: center;
        }
        .pagination-controls button {
            min-width: 100px; /* Ensure buttons have consistent width */
        }
        .message-box {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            font-weight: 500;
        }
        .message-box.error {
            background-color: #fee2e2;
            border: 1px solid #fca5a5;
            color: #dc2626;
        }
        .message-box.warning {
            background-color: #fffbeb;
            border: 1px solid #fcd34d;
            color: #d97706;
        }
        .message-box.info {
            background-color: #e0f2fe;
            border: 1px solid #93c5fd;
            color: #2563eb;
        }
        .message-box.success {
            background-color: #dcfce7;
            border: 1px solid #86efac;
            color: #16a34a;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="text-3xl font-bold text-center text-gray-800 mb-6">Postale API Query</h1>

        <!-- HTML Form -->
        <form action="aliases.php" method="GET" class="space-y-4">
            <div class="flex flex-col">
                <label for="query" class="text-lg font-medium text-gray-700 mb-2">Query:</label>
                <input type="text" id="query" name="query" placeholder="e.g., example@domain.com" class="focus:ring-blue-500 focus:border-blue-500" value="<?php echo htmlspecialchars($_GET['query'] ?? ''); ?>">
            </div>
            <div class="flex flex-col">
                <label for="page" class="text-lg font-medium text-gray-700 mb-2">Page (optional):</label>
                <input type="number" id="page" name="page" placeholder="e.g., 1" class="focus:ring-blue-500 focus:border-blue-500" min="1" value="<?php echo htmlspecialchars($_GET['page'] ?? '1'); ?>">
            </div>
            <div class="flex flex-col">
                <label for="paging" class="text-lg font-medium text-gray-700 mb-2">Paging (optional):</label>
                <input type="number" id="paging" name="paging" placeholder="e.g., 25" class="focus:ring-blue-500 focus:border-blue-500" min="1" value="<?php echo htmlspecialchars($_GET['paging'] ?? '25'); ?>">
            </div>
            <button type="submit" class="w-full">Get Postale Info</button>
        </form>

        <!-- PHP Response Display Area -->
        <div class="response-box">
            <?php
            // Check if any of the expected GET parameters are set to trigger the API call.
            if (isset($_GET['query']) || isset($_GET['page']) || isset($_GET['paging'])) {
                // Sanitize input using htmlspecialchars to prevent XSS vulnerabilities.
                $query = htmlspecialchars($_GET['query'] ?? '');
                $page = htmlspecialchars($_GET['page'] ?? '1'); // Default to page 1 if not set
                $paging = htmlspecialchars($_GET['paging'] ?? '25'); // Default to 25 if not set

                // Define the Postale API endpoint.
                $api_url = "https://postale.io/api/v1/aliases";

                // Build an array of query parameters.
                $params = [];
                if (!empty($query)) {
                    $params['query'] = $query;
                }
                // Ensure page and paging are numeric and valid before adding to params
                if (is_numeric($page) && $page > 0) {
                    $params['page'] = (int)$page;
                }
                if (is_numeric($paging) && $paging > 0) {
                    $params['paging'] = (int)$paging;
                }

                // Append the parameters to the API URL if any exist.
                if (!empty($params)) {
                    $api_url .= '?' . http_build_query($params);
                }

                // Initialize a new cURL session.
                $ch = curl_init();

                // Set cURL options for the request.
                curl_setopt($ch, CURLOPT_URL, $api_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HEADER, false);

                // --- Basic Authentication Settings ---
                // IMPORTANT: Replace 'POSTALEAPIKEY' with your actual Postale API credentials.
                curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
                curl_setopt($ch, CURLOPT_USERPWD, 'POSTALEAPIKEY:');
                // -------------------------------------

                // Execute the cURL request and get the response.
                $response = curl_exec($ch);

                // Check for any cURL errors during the execution.
                if (curl_errno($ch)) {
                    echo "<div class='message-box error' role='alert'>";
                    echo "<strong class='font-bold'>Error!</strong>";
                    echo "<span class='block sm:inline'> cURL Error: " . htmlspecialchars(curl_error($ch)) . "</span>";
                    echo "</div>";
                } else {
                    // Attempt to decode the JSON response received from the API.
                    $decoded_response = json_decode($response, true);

                    // Check if JSON decoding was successful.
                    if (json_last_error() === JSON_ERROR_NONE) {
                        // Check if the API response contains the 'results' key and if it's an array.
                        if (isset($decoded_response['results']) && is_array($decoded_response['results'])) {
                            $results = $decoded_response['results'];

                            // Extract pagination information
                            $currentPage = (int)($decoded_response['page'] ?? 1);
                            $itemsPerPage = (int)($decoded_response['paging'] ?? 25);
                            $lastPage = (int)($decoded_response['lastPage'] ?? 1);
                            $totalCount = (int)($decoded_response['totalCount'] ?? 0);

                            // Display pagination summary
                            echo "<div class='message-box info' role='info'>";
                            echo "<p class='font-bold'>Pagination Info:</p>";
                            echo "<ul class='list-disc list-inside'>";
                            echo "<li>Current Page: " . $currentPage . "</li>";
                            echo "<li>Items Per Page: " . $itemsPerPage . "</li>";
                            echo "<li>Last Page: " . $lastPage . "</li>";
                            echo "<li>Total Aliases: " . $totalCount . "</li>";
                            echo "</ul>";
                            echo "</div>";

                            // Check if any aliases were found.
                            if (count($results) > 0) {
                                echo "<p class='text-gray-700 mb-4'>Displaying " . count($results) . " aliases on this page:</p>";
                                echo "<div class='overflow-x-auto rounded-lg shadow-md'>";
                                echo "<table class='min-w-full bg-white border border-gray-200'>";
                                echo "<thead>";
                                echo "<tr>";
                                echo "<th class='py-3 px-4 bg-gray-100 text-gray-600 uppercase text-sm leading-normal border-b border-gray-200'>Address</th>";
                                echo "<th class='py-3 px-4 bg-gray-100 text-gray-600 uppercase text-sm leading-normal border-b border-gray-200'>Name</th>";
                                echo "<th class='py-3 px-4 bg-gray-100 text-gray-600 uppercase text-sm leading-normal border-b border-gray-200'>Redirect To</th>";
                                echo "<th class='py-3 px-4 bg-gray-100 text-gray-600 uppercase text-sm leading-normal border-b border-gray-200'>Created</th>";
                                echo "</tr>";
                                echo "</thead>";
                                echo "<tbody>";

                                foreach ($results as $item) {
                                    $address = htmlspecialchars($item['address'] ?? 'N/A');
                                    $name = htmlspecialchars($item['name'] ?? 'N/A');
                                    $redirectTo = isset($item['redirectTo']) && is_array($item['redirectTo']) ? htmlspecialchars(implode(', ', $item['redirectTo'])) : 'N/A';
                                    $created = isset($item['created']) ? date('Y-m-d H:i:s', strtotime($item['created'])) : 'N/A';

                                    echo "<tr class='hover:bg-gray-50'>";
                                    echo "<td class='py-2 px-4 border-b border-gray-200'>" . $address . "</td>";
                                    echo "<td class='py-2 px-4 border-b border-gray-200'>" . $name . "</td>";
                                    echo "<td class='py-2 px-4 border-b border-gray-200'>" . $redirectTo . "</td>";
                                    echo "<td class='py-2 px-4 border-b border-gray-200'>" . $created . "</td>";
                                    echo "</tr>";
                                }

                                echo "</tbody>";
                                echo "</table>";
                                echo "</div>"; // Close overflow-x-auto

                                // Pagination Controls
                                echo "<div class='pagination-controls'>";
                                // Previous Button
                                echo "<form action='aliases.php' method='GET'>";
                                echo "<input type='hidden' name='query' value='" . $query . "'>";
                                echo "<input type='hidden' name='paging' value='" . $paging . "'>";
                                echo "<input type='hidden' name='page' value='" . ($currentPage - 1) . "'>";
                                echo "<button type='submit' " . ($currentPage <= 1 ? 'disabled' : '') . ">Previous</button>";
                                echo "</form>";

                                // Current Page / Total Pages display
                                echo "<span class='text-gray-700 font-medium'>Page " . $currentPage . " of " . $lastPage . "</span>";

                                // Next Button
                                echo "<form action='aliases.php' method='GET'>";
                                echo "<input type='hidden' name='query' value='" . $query . "'>";
                                echo "<input type='hidden' name='paging' value='" . $paging . "'>";
                                echo "<input type='hidden' name='page' value='" . ($currentPage + 1) . "'>";
                                echo "<button type='submit' " . ($currentPage >= $lastPage ? 'disabled' : '') . ">Next</button>";
                                echo "</form>";
                                echo "</div>"; // Close pagination-controls

                            } else {
                                echo "<div class='message-box warning' role='alert'>";
                                echo "<strong class='font-bold'>No Results!</strong>";
                                echo "<span class='block sm:inline'> No aliases found for the given query.</span>";
                                echo "</div>";
                            }
                        } else {
                            echo "<div class='message-box warning' role='alert'>";
                            echo "<strong class='font-bold'>Warning!</strong>";
                            echo "<span class='block sm:inline'> API response structure unexpected. No 'results' array found.</span>";
                            echo "<p class='mt-2 text-sm'>Raw Response:</p><pre class='bg-gray-100 p-2 rounded text-xs overflow-auto'>" . htmlspecialchars(json_encode($decoded_response, JSON_PRETTY_PRINT)) . "</pre>";
                            echo "</div>";
                        }
                    } else {
                        echo "<div class='message-box error' role='alert'>";
                        echo "<strong class='font-bold'>Error!</strong>";
                        echo "<span class='block sm:inline'> Failed to decode JSON response.</span>";
                        echo "<p class='mt-2 text-sm'>Raw Response: " . htmlspecialchars($response) . "</p>";
                        echo "</div>";
                    }
                }

                // Close the cURL session to free up resources.
                curl_close($ch);
            }
            ?>
        </div>
    </div>
</body>
</html>
