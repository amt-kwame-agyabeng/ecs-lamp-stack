<?php
// --- Database Connection Configuration (from Environment Variables) ---
$dbHost = getenv('DB_HOST');
$dbName = getenv('DB_NAME');
$dbUser = getenv('DB_USER');
$dbPass = getenv('DB_PASSWORD');

$conn = null;
$db_connection_error = '';
$registration_message = '';
$registration_type = '';

// Check if environment variables are set for DB connection
if (!$dbHost || !$dbName || !$dbUser || !$dbPass) {
    $db_connection_error = 'Database environment variables are not fully set.';
} else {
    // Attempt to connect to MySQL
    $conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);

    // Check connection
    if ($conn->connect_error) {
        $db_connection_error = 'Database Connection Error: ' . htmlspecialchars($conn->connect_error);
    } else {
        // Create Users Table if it doesn't exist
        $createTableSql = "
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            email VARCHAR(100) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );";
        if (!$conn->query($createTableSql)) {
            $db_connection_error = 'Error creating users table: ' . htmlspecialchars($conn->error);
        }
    }
}

// Handle Registration Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && !$db_connection_error) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Basic validation
    if (empty($username) || empty($email) || empty($password)) {
        $registration_message = 'All fields are required.';
        $registration_type = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $registration_message = 'Invalid email format.';
        $registration_type = 'error';
    } elseif (strlen($password) < 6) {
        $registration_message = 'Password must be at least 6 characters long.';
        $registration_type = 'error';
    } else {
        // Hash the password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        // Prepare SQL statement to prevent SQL injection
        $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("sss", $username, $email, $password_hash);

            try {
                if ($stmt->execute()) {
                    // Redirect to prevent form resubmission on refresh
                    header('Location: index.php?success=1&user=' . urlencode($username));
                    exit();
                } else {
                    // Check for duplicate entry error
                    if ($conn->errno == 1062) {
                        $registration_message = 'Username or email already exists. Please choose a different one.';
                    } else {
                        $registration_message = 'Error during registration: ' . htmlspecialchars($stmt->error);
                    }
                    $registration_type = 'error';
                }
            } catch (mysqli_sql_exception $e) {
                $registration_message = 'Database error during registration: ' . htmlspecialchars($e->getMessage());
                $registration_type = 'error';
            }

            $stmt->close();
        } else {
            $registration_message = 'Database statement preparation failed: ' . htmlspecialchars($conn->error);
            $registration_type = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .alert { animation: fadeIn 0.3s ease-in; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        .fade-out { animation: fadeOut 0.3s ease-out forwards; }
        @keyframes fadeOut { from { opacity: 1; transform: translateY(0); } to { opacity: 0; transform: translateY(-10px); } }
    </style>
</head>
<body class="bg-gray-50 min-h-screen p-4">
    <div class="max-w-7xl mx-auto">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-light text-gray-900 tracking-tight">Create Account</h1>
            <p class="text-gray-500 mt-2 font-light">Join us today</p>
        </div>

        <?php
        if ($db_connection_error) {
            echo '<div class="alert bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">';
            echo '<div class="flex items-center"><svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>';
            echo '<span class="text-sm">' . $db_connection_error . '</span></div></div>';
            if ($conn) $conn->close();
            exit();
        }

        if (isset($_GET['success']) && $_GET['success'] == '1' && isset($_GET['user'])) {
            echo '<div id="success-alert" class="alert bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">';
            echo '<div class="flex items-center"><svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>';
            echo '<span class="text-sm">Welcome, ' . htmlspecialchars($_GET['user']) . '! Your account has been created.</span></div></div>';
        }

        if ($registration_message) {
            $color = $registration_type === 'error' ? 'red' : 'green';
            $icon = $registration_type === 'error' ? 
                '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>' :
                '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>';
            echo '<div class="alert bg-' . $color . '-50 border border-' . $color . '-200 text-' . $color . '-700 px-4 py-3 rounded-lg mb-6">';
            echo '<div class="flex items-center"><svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">' . $icon . '</svg>';
            echo '<span class="text-sm">' . $registration_message . '</span></div></div>';
        }
        ?>

        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Form Section -->
            <div class="lg:w-1/3">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
                    <form action="index.php" method="POST" class="space-y-6">
                        <div>
                            <label for="username" class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                            <input type="text" id="username" name="username" required
                                   class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 text-gray-900 placeholder-gray-400"
                                   placeholder="Enter your username"
                                   value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                            <input type="email" id="email" name="email" required
                                   class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 text-gray-900 placeholder-gray-400"
                                   placeholder="Enter your email"
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        </div>
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                            <input type="password" id="password" name="password" required
                                   class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 text-gray-900 placeholder-gray-400"
                                   placeholder="Create a password">
                            <p class="text-xs text-gray-500 mt-1">Minimum 6 characters</p>
                        </div>
                        <button type="submit" 
                                class="w-full bg-gray-900 text-white py-3 px-4 rounded-lg font-medium hover:bg-gray-800 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all duration-200">
                            Create Account
                        </button>
                    </form>
                </div>
            </div>

            <!-- Table Section -->
            <?php if ($conn && !$db_connection_error): ?>
            <div class="lg:w-2/3">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
                    <h2 class="text-xl font-light text-gray-900 mb-6">Recent Users</h2>
                    <?php
                    $sql = "SELECT username, email, created_at FROM users ORDER BY created_at DESC LIMIT 10";
                    $result = $conn->query($sql);
                    
                    if ($result && $result->num_rows > 0): ?>
                        <div class="overflow-hidden">
                            <table class="w-full">
                                <thead>
                                    <tr class="border-b border-gray-100">
                                        <th class="text-left py-4 px-6 font-medium text-gray-600">Username</th>
                                        <th class="text-left py-4 px-6 font-medium text-gray-600">Email</th>
                                        <th class="text-left py-4 px-6 font-medium text-gray-600">Date</th>
                                        <th class="text-left py-4 px-6 font-medium text-gray-600">Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr class="border-b border-gray-50 hover:bg-gray-25 transition-colors">
                                        <td class="py-4 px-6 text-gray-900"><?php echo htmlspecialchars($row['username']); ?></td>
                                        <td class="py-4 px-6 text-gray-600"><?php echo htmlspecialchars($row['email']); ?></td>
                                        <td class="py-4 px-6 text-gray-500"><?php echo date('M j, Y', strtotime($row['created_at'])); ?></td>
                                        <td class="py-4 px-6 text-gray-500"><?php echo date('g:i A', strtotime($row['created_at'])); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8">
                            <div class="text-gray-400 mb-2">
                                <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                            </div>
                            <p class="text-gray-500 text-sm">No users yet. Be the first to join!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    <script>
        // Auto-hide success alert after 3 seconds
        const successAlert = document.getElementById('success-alert');
        if (successAlert) {
            setTimeout(() => {
                successAlert.classList.add('fade-out');
                setTimeout(() => successAlert.remove(), 300);
            }, 3000);
        }
    </script>
</body>
</html>

<?php
// Close database connection only once at the end
if ($conn && !$conn->connect_errno) {
    $conn->close();
}
?>