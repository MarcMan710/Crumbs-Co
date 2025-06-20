<?php
require_once __DIR__ . '/includes/db/user_queries.php'; // Include user queries
// templates/header.php is included later, which starts session and makes $conn available.

$error = '';

// Logic needs $conn, so it must be processed after templates/header.php is included,
// OR we include config/database.php explicitly here.
// To keep template inclusion at the end for output buffering, let's ensure $conn is available.
// The current structure has templates/header.php including config/database.php.
// So, if header.php is at the end of the file, $conn won't be available here.

// Decision: Include header.php first for $conn and session, then process POST.
// This means any redirects must happen before header.php is included if it outputs HTML.
// For login, successful login redirects, so this is tricky.
// A common pattern:
// 1. Handle POST (which might redirect). If redirect, exit.
// 2. Include header.
// 3. Display page content.
// 4. Include footer.

// Let's adjust: include config/database.php directly for $conn if POST.
// And ensure session_start() is called before accessing $_SESSION.
// templates/header.php calls session_start().

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_once __DIR__ . '/config/database.php'; // For $conn
    if (session_status() == PHP_SESSION_NONE) { // Ensure session is started
        session_start();
    }

    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $user = getUserByEmail($conn, $email);

        if ($user && password_verify($password, $user['password'])) {
            // Regenerate session ID to prevent session fixation
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // Redirect based on role
            if ($user['role'] === 'admin') {
                header('Location: admin/dashboard.php');
            } else {
                // Redirect to a general welcome page or previous page if stored
                header('Location: index.php');
            }
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }
}

// If not a POST request or if POST handling didn't exit, include header and show form.
require_once 'templates/header.php';
// This ensures $conn is available for any operations below if needed, and session is started.
// And $error message from POST handling will be displayed.
?>
    <!-- Login Form -->
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h2 class="text-center mb-4">Login</h2>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email address</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Login</button>
                            </div>
                        </form>

                        <div class="text-center mt-3">
                            <p>Don't have an account? <a href="register.php">Register here</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php require_once 'templates/footer.php'; ?>