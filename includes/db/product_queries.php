<?php

/**
 * Fetches all categories from the database.
 *
 * @param PDO $conn The database connection object.
 * @return array An array of all categories.
 */
function getAllCategories(PDO $conn): array {
    return $conn->query("SELECT * FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Fetches a specified number of featured products.
 *
 * @param PDO $conn The database connection object.
 * @param int $limit The maximum number of products to fetch.
 * @return array An array of featured products.
 */
function getFeaturedProducts(PDO $conn, int $limit = 4): array {
    $stmt = $conn->prepare("SELECT * FROM products ORDER BY created_at DESC LIMIT :limit");
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Fetches products based on search criteria, category, and sort order.
 *
 * @param PDO $conn The database connection object.
 * @param string $search Search term.
 * @param int $category_id Category ID to filter by.
 * @param string $sort Sort order.
 * @return array An array of products.
 */
function getProducts(PDO $conn, string $search = '', int $category_id = 0, string $sort = 'name_asc'): array {
    $query = "SELECT p.*, c.name as category_name
              FROM products p
              LEFT JOIN categories c ON p.category_id = c.id
              WHERE 1=1";
    $params = [];

    if ($search) {
        $query .= " AND (p.name LIKE :search OR p.description LIKE :search_desc)";
        $params[':search'] = "%$search%";
        $params[':search_desc'] = "%$search%";
    }

    if ($category_id) {
        $query .= " AND p.category_id = :category_id";
        $params[':category_id'] = $category_id;
    }

    // Add sorting
    // Using a whitelist for sort column to prevent SQL injection
    $sort_options = [
        'price_asc' => 'p.price ASC',
        'price_desc' => 'p.price DESC',
        'name_asc' => 'p.name ASC',
        'name_desc' => 'p.name DESC',
        'created_at_desc' => 'p.created_at DESC', // Added for completeness, like featured
        'created_at_asc' => 'p.created_at ASC'
    ];

    if (array_key_exists($sort, $sort_options)) {
        $query .= " ORDER BY " . $sort_options[$sort];
    } else {
        $query .= " ORDER BY p.name ASC"; // Default sort
    }

    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Fetches a single product by its ID.
 *
 * @param PDO $conn The database connection object.
 * @param int $product_id The ID of the product to fetch.
 * @return array|false The product data as an associative array, or false if not found.
 */
function getProductById(PDO $conn, int $product_id) {
    $stmt = $conn->prepare("SELECT p.*, c.name as category_name
                            FROM products p
                            LEFT JOIN categories c ON p.category_id = c.id
                            WHERE p.id = :product_id");
    $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

?>
