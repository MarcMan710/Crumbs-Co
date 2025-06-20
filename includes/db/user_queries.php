<?php

/**
 * Fetches a user by their email address.
 *
 * @param PDO $conn The database connection object.
 * @param string $email The email address of the user.
 * @return array|false The user data as an associative array, or false if not found.
 */
function getUserByEmail(PDO $conn, string $email) {
    $stmt = $conn->prepare("SELECT id, username, email, password, role FROM users WHERE email = :email");
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Creates a new user in the database.
 *
 * @param PDO $conn The database connection object.
 * @param string $username The username.
 * @param string $email The email address.
 * @param string $hashed_password The hashed password.
 * @return bool True on successful creation, false otherwise.
 */
function createUser(PDO $conn, string $username, string $email, string $hashed_password): bool {
    // Default role is 'customer'
    $role = 'customer';
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (:username, :email, :password, :role)");
    return $stmt->execute([
        ':username' => $username,
        ':email' => $email,
        ':password' => $hashed_password,
        ':role' => $role
    ]);
}

?>
