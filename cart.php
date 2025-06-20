<?php
<?php
require_once __DIR__ . '/includes/db/cart_queries.php'; // Include the new cart queries

// Ensure header is required after other includes if they set session/db variables needed by header
// However, in this setup, header.php handles session_start and db connection ($conn)
// So, product_queries.php and cart_queries.php will rely on $conn from header.php

// Redirect if not logged in - This is handled by header.php if we make it consistent
// For now, keeping it here as header.php might not enforce login for all pages.
// NOTE: Actually, templates/header.php *doesn't* redirect. It only starts session and includes db.
// So, login check should remain on pages that require login.
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id']; // $user_id is set after session_start() in header.php
$message = '';

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    $product_id = (int)($_POST['product_id'] ?? 0);

    if ($product_id > 0) { // Basic validation
        switch ($action) {
            case 'add':
                $quantity = (int)($_POST['quantity'] ?? 1);
                if (addProductToCart($conn, $user_id, $product_id, $quantity)) {
                    $message = 'Product added to cart.';
                } else {
                    $message = 'Failed to add product to cart.';
                }
                break;

            case 'update':
                $quantity = (int)($_POST['quantity'] ?? 0);
                // updateCartItemQuantity handles quantity <= 0 by removing
                if (updateCartItemQuantity($conn, $user_id, $product_id, $quantity)) {
                    $message = 'Cart updated.';
                } else {
                    $message = 'Failed to update cart.';
                }
                break;

            case 'remove':
                if (removeCartItem($conn, $user_id, $product_id)) {
                    $message = 'Product removed from cart.';
                } else {
                    $message = 'Failed to remove product from cart.';
                }
                break;
            default:
                $message = 'Invalid cart action.';
        }
    } else {
        $message = 'Invalid product ID for cart action.';
    }
     // Redirect to cart page to prevent form resubmission on refresh
    header('Location: cart.php?message=' . urlencode($message));
    exit;
}

// Get message from URL query parameter if redirected
if (isset($_GET['message'])) {
    $message = htmlspecialchars($_GET['message']);
}


// Get cart items with product details
$cart_items = getCartItemsForUser($conn, $user_id);

// Calculate total
$total = 0;
foreach ($cart_items as $item) {
    $total += $item['price'] * $item['quantity'];
}

// This must be included *after* $conn is available and session is started.
// And after any potential redirects (like login check or POST handling)
require_once 'templates/header.php';
// Note: $conn is made available through 'templates/header.php' which includes 'config/database.php'.
// $_SESSION variables are available because 'templates/header.php' calls session_start().
?>
    <!-- Cart Section -->
    <div class="container my-5">
        <h1 class="text-center mb-4">Shopping Cart</h1>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if (empty($cart_items)): ?>
            <div class="alert alert-info">
                Your cart is empty. <a href="products.php">Continue shopping</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Subtotal</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cart_items as $item): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                             alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                             class="img-thumbnail" 
                                             style="width: 100px; margin-right: 1rem;">
                                        <div>
                                            <h5 class="mb-0"><?php echo htmlspecialchars($item['name']); ?></h5>
                                        </div>
                                    </div>
                                </td>
                                <td>$<?php echo number_format($item['price'], 2); ?></td>
                                <td>
                                    <form action="" method="POST" class="d-flex align-items-center">
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                        <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" 
                                               min="1" class="form-control" style="width: 80px;">
                                        <button type="submit" class="btn btn-sm btn-outline-primary ms-2">Update</button>
                                    </form>
                                </td>
                                <td>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                <td>
                                    <form action="" method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="remove">
                                        <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-end"><strong>Total:</strong></td>
                            <td><strong>$<?php echo number_format($total, 2); ?></strong></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="d-flex justify-content-between mt-4">
                <a href="products.php" class="btn btn-outline-primary">Continue Shopping</a>
                <a href="checkout.php" class="btn btn-primary">Proceed to Checkout</a>
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