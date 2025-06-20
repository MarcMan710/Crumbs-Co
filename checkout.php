<?php
require_once __DIR__ . '/includes/db/cart_queries.php'; // Include cart queries
require_once __DIR__ . '/includes/db/order_queries.php'; // Include order queries

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) { // Session is started in header.php, but this check needs to happen before header output
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id']; // Assuming session is started and user_id is set
$error = ''; // For general transaction/DB errors
$success = '';
$errors_checkout = []; // For specific form validation errors

// Initialize shipping form variables to ensure they are always defined for the form
$shipping_name = '';
$shipping_email = '';
$shipping_address = '';
$shipping_city = '';
$shipping_state = '';
$shipping_zip = '';
$shipping_phone = '';
$shipping_notes = '';

// Get cart items using the cart query function
// $conn is available via header.php which includes config/database.php
// However, this script part is before header.php is included.
// This means $conn is NOT YET AVAILABLE HERE.

// We need to ensure $conn is available. The POST block includes config/database.php.
// For GET requests, $conn is only made available by templates/header.php.
// This is a structural issue. $conn should be available before it's used for page rendering logic.

// Simplest immediate fix: Include config/database.php if not already included by POST block.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    require_once __DIR__ . '/config/database.php'; // For $conn on GET request
    if (session_status() == PHP_SESSION_NONE) { // And ensure session is started for $user_id
        session_start();
    }
    // Re-check user_id as session might have just started
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
    $user_id = $_SESSION['user_id']; // Ensure $user_id is fresh if session just started
}
// Now $conn should be available.
$cart_items = getCartItemsForUser($conn, $user_id);


// Calculate total
$total = 0;
foreach ($cart_items as $item) {
    $total += $item['price'] * $item['quantity'];
}

// Handle order submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ensure $conn is available (it should be if POST logic in previous step was correct)
    if (!isset($conn) || !$conn) { // Defensive check
        require_once __DIR__ . '/config/database.php';
    }
    if (session_status() == PHP_SESSION_NONE) { // Should be started
        session_start();
    }
    // Re-check user_id as session might have just started if not via normal header flow
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php'); // Should not happen if initial check is effective
        exit;
    }
    $user_id = $_SESSION['user_id'];

    // Retrieve and trim shipping information
    $shipping_name = trim($_POST['name'] ?? '');
    $shipping_email = trim($_POST['email'] ?? '');
    $shipping_address = trim($_POST['address'] ?? '');
    $shipping_city = trim($_POST['city'] ?? '');
    $shipping_state = trim($_POST['state'] ?? '');
    $shipping_zip = trim($_POST['zip'] ?? '');
    $shipping_phone = trim($_POST['phone'] ?? '');
    $shipping_notes = trim($_POST['notes'] ?? '');

    // Server-side validation for shipping information
    if (empty($shipping_name)) $errors_checkout[] = "Full Name is required.";
    if (empty($shipping_email)) {
        $errors_checkout[] = "Email is required.";
    } elseif (!filter_var($shipping_email, FILTER_VALIDATE_EMAIL)) {
        $errors_checkout[] = "Invalid Email format.";
    }
    if (empty($shipping_address)) $errors_checkout[] = "Address is required.";
    if (empty($shipping_city)) $errors_checkout[] = "City is required.";
    if (empty($shipping_state)) $errors_checkout[] = "State is required.";
    if (empty($shipping_zip)) $errors_checkout[] = "ZIP Code is required.";
    // Phone is optional in this version of the form, so no empty check for it.

    if (empty($errors_checkout)) {
        try {
            $conn->beginTransaction();

            // Create order
        $order_id = createOrder($conn, $user_id, $total);
        if (!$order_id) {
            throw new Exception("Failed to create order.");
        }

        // Add order items and update stock
        foreach ($cart_items as $item) {
            // Check stock
            if ($item['stock'] < $item['quantity']) {
                throw new Exception("Insufficient stock for " . htmlspecialchars($item['name']));
            }

            // Add order item
            if (!addOrderItem($conn, (int)$order_id, $item['product_id'], $item['quantity'], $item['price'])) {
                throw new Exception("Failed to add item to order: " . htmlspecialchars($item['name']));
            }

            // Update stock
            if (!updateProductStock($conn, $item['product_id'], $item['quantity'])) {
                // Log this error, but don't necessarily fail the entire order if items are already inserted.
                // Depending on business logic, you might want to throw new Exception here too.
                error_log("Failed to update stock for product ID: {$item['product_id']} for order ID: $order_id");
                // throw new Exception("Failed to update stock for " . htmlspecialchars($item['name']));
            }
        }

        // Clear cart
        if (!clearCartForUser($conn, $user_id)) {
            // Log this error, but the order is placed, so don't necessarily halt for user.
            error_log("Failed to clear cart for user ID: $user_id after order ID: $order_id");
        }
        // $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
        // $stmt->execute([$user_id]);

        $conn->commit();
        $success = 'Order placed successfully!';
        
        // Redirect to order confirmation
        header("Location: order-confirmation.php?id=" . $order_id);
        exit;
    } catch (Exception $e) {
        $conn->rollBack();
            $error = $e->getMessage(); // This is for transaction errors
        }
    } // else $errors_checkout contains validation errors, transaction is skipped.
      // If there were validation errors, $shipping_... variables hold trimmed user input for form repopulation.
}
require_once 'templates/header.php';
?>
    <!-- Checkout Section -->
    <div class="container my-5">
        <h1 class="text-center mb-4">Checkout</h1>

        <?php if (!empty($errors_checkout)): ?>
            <div class="alert alert-danger">
                <p class="fw-bold">Please correct the following errors:</p>
                <ul>
                    <?php foreach ($errors_checkout as $e): ?>
                        <li><?php echo htmlspecialchars($e); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($error && empty($errors_checkout)): ?> <!-- Show general/transaction error only if no validation errors -->
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php
        // Show empty cart message only if it's truly empty and not a POST request (or a failed POST)
        // If it's a POST request (even a failed one), the cart items were loaded for the transaction attempt.
        // The form should still be displayed if cart_items is not empty, regardless of POST.
        if (empty($cart_items) && $_SERVER['REQUEST_METHOD'] !== 'POST') :
        ?>
            <div class="alert alert-info">
                Your cart is empty. <a href="products.php">Continue shopping</a>
            </div>
        <?php elseif (!empty($cart_items)): // Only show the form and summary if cart is not empty ?>
            <div class="row">
                <!-- Order Summary -->
                <div class="col-md-4">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Order Summary</h5>
                        </div>
                        <div class="card-body">
                            <?php foreach ($cart_items as $item): ?>
                                <div class="d-flex justify-content-between mb-2">
                                    <span><?php echo htmlspecialchars($item['name']); ?> x <?php echo $item['quantity']; ?></span>
                                    <span>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                                </div>
                            <?php endforeach; ?>
                            <hr>
                            <div class="d-flex justify-content-between">
                                <strong>Total:</strong>
                                <strong>$<?php echo number_format($total, 2); ?></strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Checkout Form -->
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Shipping Information</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="name" class="form-label">Full Name</label>
                                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($shipping_name); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($shipping_email); ?>" required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="address" class="form-label">Address</label>
                                    <input type="text" class="form-control" id="address" name="address" value="<?php echo htmlspecialchars($shipping_address); ?>" required>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="city" class="form-label">City</label>
                                        <input type="text" class="form-control" id="city" name="city" value="<?php echo htmlspecialchars($shipping_city); ?>" required>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="state" class="form-label">State</label>
                                        <input type="text" class="form-control" id="state" name="state" value="<?php echo htmlspecialchars($shipping_state); ?>" required>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="zip" class="form-label">ZIP Code</label>
                                        <input type="text" class="form-control" id="zip" name="zip" value="<?php echo htmlspecialchars($shipping_zip); ?>" required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone Number (Optional)</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($shipping_phone); ?>">
                                </div>

                                <div class="mb-3">
                                    <label for="notes" class="form-label">Order Notes (Optional)</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($shipping_notes); ?></textarea>
                                </div>

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">Place Order</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>Crumbs & Co.</h5>
                    <p>Artisanal bakery serving the finest breads and pastries since 2024.</p>
                </div>
                <div class="col-md-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="about.php" class="text-light">About Us</a></li>
                        <li><a href="products.php" class="text-light">Products</a></li>
                        <li><a href="contact.php" class="text-light">Contact</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Contact Us</h5>
                    <address>
                        123 Bakery Street<br>
                        City, State 12345<br>
                        Phone: (123) 456-7890<br>
                        Email: info@crumbsco.com
                    </address>
                </div>
            </div>
        </div>
    </footer>

<?php require_once 'templates/footer.php'; ?>