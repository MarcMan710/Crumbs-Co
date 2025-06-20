<?php
require_once 'templates/header.php';
require_once __DIR__ . '/includes/db/product_queries.php';
?>

    <!-- Hero Section -->
    <div class="hero-section py-5 bg-light">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1>Welcome to Crumbs & Co.</h1>
                    <p class="lead">Discover our artisanal breads, pastries, and cakes made with love and the finest ingredients.</p>
                    <a href="products.php" class="btn btn-primary">Shop Now</a>
                </div>
                <div class="col-md-6">
                    <img src="assets/images/hero-image.jpg" alt="Bakery Products" class="img-fluid rounded">
                </div>
            </div>
        </div>
    </div>

    <!-- Featured Products -->
    <div class="container my-5">
        <h2 class="text-center mb-4">Featured Products</h2>
        <div class="row">
            <?php
            $featuredProducts = getFeaturedProducts($conn);
            foreach($featuredProducts as $product):
            ?>
            <div class="col-md-3 mb-4">
                <div class="card">
                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                        <p class="card-text">$<?php echo number_format($product['price'], 2); ?></p>
                        <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-primary">View Details</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
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