<?php

// --- Configuration ---
// REPLACE THIS WITH YOUR ACTUAL API ENDPOINT
$api_endpoint = 'https://postale.io/api/v1/mailboxes/';
// Replace with encoded API key
$api_token = 'POSTALE API Key';

$message = '';
$message_type = ''; // 'success' or 'error'

// Variables to hold loaded user data
$loaded_user_id = '';
$loaded_name = '';
$loaded_disabled = false;
$loaded_domain_admin = false;
$loaded_full_admin = false;
$form_disabled = true; // Form is disabled by default until a non-admin user is loaded

// Handle user data loading (GET request)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['load_user_id'])) {
    $load_user_id_param = htmlspecialchars($_GET['load_user_id'] ?? '');

    if (!empty($load_user_id_param)) {
        $ch = curl_init();
        $url = $api_endpoint . $load_user_id_param;

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Basic ' . $api_token,
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($curl_error) {
            $message = 'cURL Error loading user: ' . $curl_error;
            $message_type = 'error';
        } else {
            $user_data = json_decode($response, true);

            if ($http_code >= 200 && $http_code < 300) {
                // Populate loaded user data
                $loaded_user_id = $load_user_id_param;
                $loaded_name = $user_data['name'] ?? '';
                $loaded_disabled = $user_data['disabled'] ?? false;
                $loaded_domain_admin = $user_data['domainAdmin'] ?? false;
                $loaded_full_admin = $user_data['fullAdmin'] ?? false;

                // Check if the loaded user is an admin
                if ($loaded_domain_admin || $loaded_full_admin) {
                    $message = 'This user is a Domain Administrator or Full Administrator and cannot be modified via this interface.';
                    $message_type = 'error';
                    $form_disabled = true; // Keep form disabled
                } else {
                    $message = 'User data loaded successfully. You can now update their details.';
                    $message_type = 'success';
                    $form_disabled = false; // Enable form
                }
            } else {
                $message = 'API Error loading user: ' . ($user_data['message'] ?? 'Unknown error');
                $message_type = 'error';
                $message .= ' (HTTP Code: ' . $http_code . ')';
                $form_disabled = true; // Keep form disabled on error
            }
        }
    } else {
        $message = 'Please enter a User ID to load data.';
        $message_type = 'error';
    }
}


// Handle user data update (POST request)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = htmlspecialchars($_POST['user_id'] ?? '');
    $name = htmlspecialchars($_POST['name'] ?? ''); // API uses 'name' for identifier
    $new_password = $_POST['new_password'] ?? '';
    $confirm_new_password = $_POST['confirm_new_password'] ?? '';
    $disabled = isset($_POST['disabled']) ? true : false;
    $domain_admin = isset($_POST['domain_admin']) ? true : false;
    $full_admin = isset($_POST['full_admin']) ? true : false;

    // Retrieve admin status from hidden fields to re-verify on POST
    $is_loaded_domain_admin = isset($_POST['is_loaded_domain_admin']) && $_POST['is_loaded_domain_admin'] === 'true';
    $is_loaded_full_admin = isset($_POST['is_loaded_full_admin']) && $_POST['is_loaded_full_admin'] === 'true';

    // --- Prevent update if the loaded user was an admin ---
    if ($is_loaded_domain_admin || $is_loaded_full_admin) {
        $message = 'Cannot update administrator accounts via this interface.';
        $message_type = 'error';
        $form_disabled = true; // Ensure form remains disabled
    }
    // --- Basic Server-Side Validation for POST ---
    elseif (empty($user_id) || empty($name)) {
        $message = 'User ID and User Name are required for update.';
        $message_type = 'error';
    } elseif (!empty($new_password) && $new_password !== $confirm_new_password) {
        $message = 'New password and confirm password do not match.';
        $message_type = 'error';
    } elseif (!empty($new_password) && strlen($new_password) < 8) {
        $message = 'New password must be at least 8 characters long.';
        $message_type = 'error';
    } else {
        // --- Prepare Payload ---
        $payload = [];
        $payload['name'] = $name; // Assuming 'name' is the identifier for the PATCH

        if (!empty($new_password)) {
            $payload['password'] = $new_password;
        }

        // Add administrative fields
        $payload['disabled'] = $disabled;
        $payload['domainAdmin'] = $domain_admin;
        $payload['fullAdmin'] = $full_admin;

        // --- Make API Call ---
        if ($message_type === '') { // Only proceed if no validation errors so far
            $ch = curl_init();
            $url = $api_endpoint . $user_id; // Append user_id to the endpoint

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH'); // Use PATCH method
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Basic ' . $api_token,
                'Content-Length: ' . strlen(json_encode($payload))
            ]);

            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);
            curl_close($ch);

            if ($curl_error) {
                $message = 'cURL Error: ' . $curl_error;
                $message_type = 'error';
            } else {
                $response_data = json_decode($response, true);

                if ($http_code >= 200 && $http_code < 300) {
                    $message = 'User data updated successfully!';
                    $message_type = 'success';
                    // After successful update, disable form and clear loaded data
                    $form_disabled = true;
                    $loaded_user_id = '';
                    $loaded_name = '';
                    $loaded_disabled = false;
                    $loaded_domain_admin = false;
                    $loaded_full_admin = false;
                } else {
                    $message = 'API Error: ' . ($response_data['message'] ?? 'Unknown error');
                    $message_type = 'error';
                    $message .= ' (HTTP Code: ' . $http_code . ')';
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure User Data Patch</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .container {
            background-color: #ffffff;
            padding: 2.5rem;
            border-radius: 1rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
        }
        .form-group {
            margin-bottom: 1.25rem;
        }
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            box-sizing: border-box;
            font-size: 1rem;
            transition: border-color 0.2s;
        }
        input[type="text"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25);
        }
        .checkbox-group {
            display: flex;
            align-items: center;
            margin-bottom: 0.75rem;
        }
        .checkbox-group input {
            margin-right: 0.75rem;
            width: auto; /* Override full width for checkboxes */
            height: 1.25rem;
            width: 1.25rem;
        }
        .checkbox-group label {
            margin-bottom: 0;
            font-weight: normal;
        }
        button {
            width: 100%;
            padding: 0.85rem;
            background-color: #3b82f6;
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s, transform 0.1s;
        }
        button:hover {
            background-color: #2563eb;
            transform: translateY(-1px);
        }
        button:active {
            transform: translateY(0);
        }
        button:disabled {
            background-color: #9ca3af;
            cursor: not-allowed;
        }
        .message {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }
        .message.success {
            background-color: #d1fae5;
            color: #065f46;
            border: 1px solid #34d399;
        }
        .message.error {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #ef4444;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="text-2xl font-bold text-center mb-6 text-gray-800">Update User Data</h2>

        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Load User Data Section -->
        <form method="GET" action="" class="mb-8 p-6 border border-gray-200 rounded-lg bg-gray-50">
            <h3 class="text-xl font-semibold mb-4 text-gray-700">Load User Data</h3>
            <div class="form-group">
                <label for="load_user_id">User ID to Load:</label>
                <input type="text" id="load_user_id" name="load_user_id" required class="rounded-lg">
            </div>
            <button type="submit" class="bg-gray-600 hover:bg-gray-700">Load User Data</button>
        </form>

        <!-- Update User Data Section -->
        <form method="POST" action="">
            <h3 class="text-xl font-semibold mb-4 text-gray-700">Update User Details</h3>
            <div class="form-group">
                <label for="user_id">Email:</label>
                <input type="text" id="user_id" name="user_id" required class="rounded-lg" value="<?php echo htmlspecialchars($loaded_user_id); ?>" <?php echo $form_disabled ? 'disabled' : ''; ?>>
                <!-- Hidden fields to pass loaded admin status to POST request -->
                <input type="hidden" name="is_loaded_domain_admin" value="<?php echo $loaded_domain_admin ? 'true' : 'false'; ?>">
                <input type="hidden" name="is_loaded_full_admin" value="<?php echo $loaded_full_admin ? 'true' : 'false'; ?>">
            </div>

            <div class="form-group">
                <label for="name">Confirm Email:</label>
                <input type="text" id="name" name="name" required class="rounded-lg" value="<?php echo htmlspecialchars($loaded_name); ?>" <?php echo $form_disabled ? 'disabled' : ''; ?>>
            </div>

            <div class="form-group">
                <label for="new_password">New Password:</label>
                <input type="password" id="new_password" name="new_password" class="rounded-lg" <?php echo $form_disabled ? 'disabled' : ''; ?>>
            </div>

            <div class="form-group">
                <label for="confirm_new_password">Confirm New Password:</label>
                <input type="password" id="confirm_new_password" name="confirm_new_password" class="rounded-lg" <?php echo $form_disabled ? 'disabled' : ''; ?>>
            </div>

            <div class="form-group">
                <div class="checkbox-group">
                    <input type="checkbox" id="disabled" name="disabled" <?php echo $loaded_disabled ? 'checked' : ''; ?> <?php echo $form_disabled ? 'disabled' : ''; ?>>
                    <label for="disabled">Disabled Account</label>
                </div>
                <!-- Removed Domain Administrator and Full Administrator checkboxes -->
            </div>

            <button type="submit" <?php echo $form_disabled ? 'disabled' : ''; ?>>Update User</button>
        </form>
    </div>
</body>
</html>