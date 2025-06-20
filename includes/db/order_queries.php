<?php

/**
 * Creates a new order in the database.
 *
 * @param PDO $conn The database connection object.
 * @param int $user_id The ID of the user placing the order.
 * @param float $total_amount The total amount of the order.
 * @return string|false The ID of the newly created order on success, or false on failure.
 */
function createOrder(PDO $conn, int $user_id, float $total_amount) {
    $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount) VALUES (:user_id, :total_amount)");
    if ($stmt->execute([':user_id' => $user_id, ':total_amount' => $total_amount])) {
        return $conn->lastInsertId();
    }
    return false;
}

/**
 * Adds an item to a specific order.
 *
 * @param PDO $conn The database connection object.
 * @param int $order_id The ID of the order.
 * @param int $product_id The ID of the product.
 * @param int $quantity The quantity of the product.
 * @param float $price The price of the product at the time of order.
 * @return bool True on success, false on failure.
 */
function addOrderItem(PDO $conn, int $order_id, int $product_id, int $quantity, float $price): bool {
    $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (:order_id, :product_id, :quantity, :price)");
    return $stmt->execute([
        ':order_id' => $order_id,
        ':product_id' => $product_id,
        ':quantity' => $quantity,
        ':price' => $price
    ]);
}

/**
 * Updates the stock of a product.
 *
 * @param PDO $conn The database connection object.
 * @param int $product_id The ID of the product to update.
 * @param int $quantity_to_deduct The quantity to deduct from the stock.
 * @return bool True on success, false on failure.
 */
function updateProductStock(PDO $conn, int $product_id, int $quantity_to_deduct): bool {
    $stmt = $conn->prepare("UPDATE products SET stock = stock - :quantity_to_deduct WHERE id = :product_id");
    return $stmt->execute([
        ':quantity_to_deduct' => $quantity_to_deduct,
        ':product_id' => $product_id
    ]);
}

/**
 * Fetches order details for a specific order and user.
 *
 * @param PDO $conn The database connection object.
 * @param int $order_id The ID of the order.
 * @param int $user_id The ID of the user who owns the order.
 * @return array|false The order details as an associative array, or false if not found or not owned by user.
 */
function getOrderDetails(PDO $conn, int $order_id, int $user_id) {
    $stmt = $conn->prepare("
        SELECT o.*, u.username, u.email
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.id = :order_id AND o.user_id = :user_id
    ");
    $stmt->execute([':order_id' => $order_id, ':user_id' => $user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Fetches all items for a specific order.
 *
 * @param PDO $conn The database connection object.
 * @param int $order_id The ID of the order.
 * @return array An array of order items.
 */
function getOrderItems(PDO $conn, int $order_id): array {
    $stmt = $conn->prepare("
        SELECT oi.*, p.name, p.image_url
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = :order_id
    ");
    $stmt->execute([':order_id' => $order_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

?>
