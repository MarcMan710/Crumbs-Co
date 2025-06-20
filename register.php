<?php
require_once __DIR__ . '/includes/db/user_queries.php';
// config/database.php (for $conn) and session_start() are included by templates/header.php
// However, for POST processing before any output, we might need $conn earlier.

$errors = [];
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // $conn will be needed for createUser and getUserByEmail (for checking existence)
    // Session might be needed if we were checking login status, but not for registration itself.
    require_once __DIR__ . '/config/database.php'; // Ensures $conn is available for POST logic

    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    // Validation
    if (empty($username)) {
        $errors[] = "Username is required.";
    } elseif (strlen($username) < 3) {
        $errors[] = "Username must be at least 3 characters long.";
    }

    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    } else {
        // Check if email already exists
        $existing_user = getUserByEmail($conn, $email);
        if ($existing_user) {
            $errors[] = "Email address is already registered.";
        }
    }

    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long.";
    }

    if (empty($password_confirm)) {
        $errors[] = "Please confirm your password.";
    } elseif ($password !== $password_confirm) {
        $errors[] = "Passwords do not match.";
    }

    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        if (createUser($conn, $username, $email, $hashed_password)) {
            // Start session here if not already started by header, to pass success message
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['registration_success'] = "Registration successful! You can now log in.";
            header('Location: login.php');
            exit;
        } else {
            $errors[] = "Registration failed. Please try again later.";
        }
    }
}

// Include header here to display the page content
require_once 'templates/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h2 class="text-center mb-4">Register</h2>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php
                    // Display registration success message if redirected from here before login success message handling
                    // This is an alternative to login.php displaying it, if register.php itself were to show a message.
                    // However, the current flow redirects to login.php, which should handle its own messages.
                    // So this part might not be strictly necessary here if login.php handles the session message.
                    ?>

                    <form method="POST" action="register.php">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($username ?? ''); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email address</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="password_confirm" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Register</button>
                        </div>
                    </form>

                    <div class="text-center mt-3">
                        <p>Already have an account? <a href="login.php">Login here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'templates/footer.php'; ?>
