<?php

/**
 * Fetches a specific cart item for a user.
 *
 * @param PDO $conn
 * @param int $user_id
 * @param int $product_id
 * @return array|false
 */
function getCartItem(PDO $conn, int $user_id, int $product_id) {
    $stmt = $conn->prepare("SELECT * FROM cart WHERE user_id = :user_id AND product_id = :product_id");
    $stmt->execute([':user_id' => $user_id, ':product_id' => $product_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Adds a product to the cart or updates its quantity if it already exists.
 *
 * @param PDO $conn
 * @param int $user_id
 * @param int $product_id
 * @param int $quantity
 * @return bool True on success, false on failure.
 */
function addProductToCart(PDO $conn, int $user_id, int $product_id, int $quantity = 1): bool {
    $cart_item = getCartItem($conn, $user_id, $product_id);

    if ($cart_item) {
        // Update quantity
        $new_quantity = $cart_item['quantity'] + $quantity;
        return updateCartItemQuantity($conn, $user_id, $product_id, $new_quantity);
    } else {
        // Add new item
        $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (:user_id, :product_id, :quantity)");
        return $stmt->execute([
            ':user_id' => $user_id,
            ':product_id' => $product_id,
            ':quantity' => $quantity
        ]);
    }
}

/**
 * Updates the quantity of an item in the cart. If quantity is 0 or less, removes the item.
 *
 * @param PDO $conn
 * @param int $user_id
 * @param int $product_id
 * @param int $quantity
 * @return bool True on success, false on failure.
 */
function updateCartItemQuantity(PDO $conn, int $user_id, int $product_id, int $quantity): bool {
    if ($quantity <= 0) {
        return removeCartItem($conn, $user_id, $product_id);
    } else {
        $stmt = $conn->prepare("UPDATE cart SET quantity = :quantity WHERE user_id = :user_id AND product_id = :product_id");
        return $stmt->execute([
            ':quantity' => $quantity,
            ':user_id' => $user_id,
            ':product_id' => $product_id
        ]);
    }
}

/**
 * Removes an item from the cart.
 *
 * @param PDO $conn
 * @param int $user_id
 * @param int $product_id
 * @return bool True on success, false on failure.
 */
function removeCartItem(PDO $conn, int $user_id, int $product_id): bool {
    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = :user_id AND product_id = :product_id");
    return $stmt->execute([':user_id' => $user_id, ':product_id' => $product_id]);
}

/**
 * Fetches all cart items with product details for a user.
 *
 * @param PDO $conn
 * @param int $user_id
 * @return array
 */
function getCartItemsForUser(PDO $conn, int $user_id): array {
    $stmt = $conn->prepare("
        SELECT c.*, p.name, p.price, p.image_url, p.stock
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.user_id = :user_id
    ");
    $stmt->execute([':user_id' => $user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Clears all items from a user's cart.
 *
 * @param PDO $conn
 * @param int $user_id
 * @return bool True on success, false on failure.
 */
function clearCartForUser(PDO $conn, int $user_id): bool {
    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = :user_id");
    return $stmt->execute([':user_id' => $user_id]);
}

?>
