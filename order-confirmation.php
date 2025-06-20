<?php
<?php
require_once __DIR__ . '/includes/db/order_queries.php'; // Include order queries
// Note: templates/header.php includes config/database.php (for $conn) and starts session.

// Redirect if not logged in - This must happen before any HTML output from header.php
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$order_id) {
    // Perhaps redirect to a user's order history page if it exists, or index.
    header('Location: index.php');
    exit;
}

// Get order details using the new function
// $conn is available because header.php (which will be required later) includes database.php
// However, we need $conn *before* header.php if header.php is at the end.
// Let's ensure config/database.php (which defines $conn) is included before it's used.
// The current structure has templates/header.php including config/database.php.
// So, calls needing $conn must be after templates/header.php, or we include config/database.php manually here.

// For consistency and clarity, let's include header first, then perform operations.
// The login check and $order_id check are safe before header.
require_once 'templates/header.php'; // This makes $conn available.

$order = getOrderDetails($conn, $order_id, $user_id);

if (!$order) {
    // Order not found or doesn't belong to the user
    // You might want to show an error message on the page instead of just redirecting
    // For now, keeping the redirect.
    header('Location: index.php'); // Or a specific "my orders" page
    exit;
}

// Get order items using the new function
$order_items = getOrderItems($conn, $order_id);
// No need to require 'templates/header.php' again, it's already above.
?>
    <!-- Order Confirmation Section -->
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                        <h1 class="mt-3">Thank You for Your Order!</h1>
                        <p class="lead">Your order has been successfully placed.</p>
                        <p>Order #<?php echo $order_id; ?></p>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">Order Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6>Order Information</h6>
                                <p class="mb-1">Order Date: <?php echo date('F j, Y', strtotime($order['created_at'])); ?></p>
                                <p class="mb-1">Order Status: <?php echo ucfirst($order['status']); ?></p>
                                <p class="mb-1">Total Amount: $<?php echo number_format($order['total_amount'], 2); ?></p>
                            </div>
                            <div class="col-md-6">
                                <h6>Customer Information</h6>
                                <p class="mb-1">Name: <?php echo htmlspecialchars($order['username']); ?></p>
                                <p class="mb-1">Email: <?php echo htmlspecialchars($order['email']); ?></p>
                            </div>
                        </div>

                        <h6>Order Items</h6>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Quantity</th>
                                        <th>Price</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($order_items as $item): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                                         alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                                         class="img-thumbnail" 
                                                         style="width: 50px; margin-right: 1rem;">
                                                    <?php echo htmlspecialchars($item['name']); ?>
                                                </div>
                                            </td>
                                            <td><?php echo $item['quantity']; ?></td>
                                            <td>$<?php echo number_format($item['price'], 2); ?></td>
                                            <td>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                        <td><strong>$<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <a href="products.php" class="btn btn-primary">Continue Shopping</a>
                </div>
            </div>
        </div>
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