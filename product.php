<?php
require_once __DIR__ . '/includes/db/product_queries.php';
require_once 'templates/header.php'; // Includes session_start() and $conn

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product = null;
$error_message = '';

if ($product_id > 0) {
    $product = getProductById($conn, $product_id);
    if (!$product) {
        $error_message = "Product not found.";
    }
} else {
    $error_message = "Invalid product ID.";
}
?>

<div class="container my-5">
    <?php if ($error_message): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php elseif ($product): ?>
        <div class="row">
            <div class="col-md-6">
                <img src="<?php echo htmlspecialchars($product['image_url'] ?? 'assets/images/placeholder.png'); ?>"
                     alt="<?php echo htmlspecialchars($product['name'] ?? 'Product Image'); ?>"
                     class="img-fluid rounded">
            </div>
            <div class="col-md-6">
                <h1><?php echo htmlspecialchars($product['name'] ?? 'N/A'); ?></h1>

                <?php if (!empty($product['category_name'])): ?>
                    <p class="text-muted">Category: <?php echo htmlspecialchars($product['category_name']); ?></p>
                <?php endif; ?>

                <p class="lead"><?php echo htmlspecialchars($product['description'] ?? 'No description available.'); ?></p>

                <h3 class="my-3">Price: $<?php echo number_format((float)($product['price'] ?? 0), 2); ?></h3>

                <p>Stock: <?php echo (int)($product['stock'] ?? 0) > 0 ? htmlspecialchars($product['stock']) . ' available' : 'Out of stock'; ?></p>

                <?php if (isset($_SESSION['user_id']) && (int)($product['stock'] ?? 0) > 0): ?>
                    <form action="cart.php" method="POST" class="mt-3">
                        <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['id']); ?>">
                        <input type="hidden" name="action" value="add">
                        <div class="input-group" style="max-width: 200px;">
                            <input type="number" name="quantity" class="form-control" value="1" min="1" max="<?php echo htmlspecialchars($product['stock'] ?? 1); ?>">
                            <button type="submit" class="btn btn-primary">Add to Cart</button>
                        </div>
                    </form>
                <?php elseif ((int)($product['stock'] ?? 0) <= 0): ?>
                    <p class="text-danger">This product is currently out of stock.</p>
                <?php endif; ?>

                <div class="mt-4">
                    <a href="products.php" class="btn btn-outline-secondary">Back to Products</a>
                </div>
            </div>
        </div>
    <?php else: // This case should ideally not be reached if $error_message is handled ?>
        <div class="alert alert-warning">
            Product details are currently unavailable.
        </div>
    <?php endif; ?>
</div>

<?php require_once 'templates/footer.php'; ?>
