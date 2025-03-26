<?php

// Database Configuration (using environment variables for Render.com)
$dbHost = getenv('DB_HOST');
$dbUser = getenv('DB_USER');
$dbPass = getenv('DB_PASSWORD');
$dbName = getenv('DB_NAME');

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['message' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// Helper Functions (same as before)
function getQuotes($pdo, $params = []) {
    $sql = "SELECT quotes.id, quotes.quote, authors.author, categories.category FROM quotes JOIN authors ON quotes.author_id = authors.id JOIN categories ON quotes.category_id = categories.id";
    $where = [];
    foreach ($params as $key => $value) {
        $where[] = "$key = :$key";
    }
    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue(":$key", $value);
    }
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAuthors($pdo, $params = []) {
    $sql = "SELECT id, author FROM authors";
    $where = [];
    foreach ($params as $key => $value) {
        $where[] = "$key = :$key";
    }
    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue(":$key", $value);
    }
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getCategories($pdo, $params = []) {
    $sql = "SELECT id, category FROM categories";
    $where = [];
    foreach ($params as $key => $value) {
        $where[] = "$key = :$key";
    }
    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue(":$key", $value);
    }
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function createQuote($pdo, $data) {
    if (!isset($data['quote'], $data['author_id'], $data['category_id'])) {
        return ['message' => 'Missing Required Parameters'];
    }
    $authorExists = getAuthors($pdo, ['id' => $data['author_id']]);
    if (empty($authorExists)) {
        return ['message' => 'author_id Not Found'];
    }
    $categoryExists = getCategories($pdo, ['id' => $data['category_id']]);
    if (empty($categoryExists)) {
        return ['message' => 'category_id Not Found'];
    }
    $stmt = $pdo->prepare("INSERT INTO quotes (quote, author_id, category_id) VALUES (:quote, :author_id, :category_id)");
    $stmt->execute(['quote' => $data['quote'], 'author_id' => $data['author_id'], 'category_id' => $data['category_id']]);
    return ['id' => $pdo->lastInsertId(), 'quote' => $data['quote'], 'author_id' => $data['author_id'], 'category_id' => $data['category_id']];
}

function createAuthor($pdo, $data) {
    if (!isset($data['author'])) {
        return ['message' => 'Missing Required Parameters'];
    }
    $stmt = $pdo->prepare("INSERT INTO authors (author) VALUES (:author)");
    $stmt->execute(['author' => $data['author']]);
    return ['id' => $pdo->lastInsertId(), 'author' => $data['author']];
}

function createCategory($pdo, $data) {
    if (!isset($data['category'])) {
        return ['message' => 'Missing Required Parameters'];
    }
    $stmt = $pdo->prepare("INSERT INTO categories (category) VALUES (:category)");
    $stmt->execute(['category' => $data['category']]);
    return ['id' => $pdo->lastInsertId(), 'category' => $data['category']];
}

function updateQuote($pdo, $data) {
    if (!isset($data['id'], $data['quote'], $data['author_id'], $data['category_id'])) {
        return ['message' => 'Missing Required Parameters'];
    }
    $quoteExists = getQuotes($pdo, ['id' => $data['id']]);
    if (empty($quoteExists)) {
        return ['message' => 'No Quotes Found'];
    }
    $authorExists = getAuthors($pdo, ['id' => $data['author_id']]);
    if (empty($authorExists)) {
        return ['message' => 'author_id Not Found'];
    }
    $categoryExists = getCategories($pdo, ['id' => $data['category_id']]);
    if (empty($categoryExists)) {
        return ['message' => 'category_id Not Found'];
    }
    $stmt = $pdo->prepare("UPDATE quotes SET quote = :quote, author_id = :author_id, category_id = :category_id WHERE id = :id");
    $stmt->execute(['id' => $data['id'], 'quote' => $data['quote'], 'author_id' => $data['author_id'], 'category_id' => $data['category_id']]);
    return ['id' => $data['id'], 'quote' => $data['quote'], 'author_id' => $data['author_id'], 'category_id' => $data['category_id']];
}

function updateAuthor($pdo, $data) {
    if (!isset($data['id'], $data['author'])) {
        return ['message' => 'Missing Required Parameters'];
    }
    $authorExists = getAuthors($pdo, ['id' => $data['id']]);
    if (empty($authorExists)) {
        return ['message' => 'author_id Not Found'];
    }
    $stmt = $pdo->prepare("UPDATE authors SET author = :author WHERE id = :id");
    $stmt->execute(['id' => $data['id'], 'author' => $data['author']]);
    return ['id' => $data['id'], 'author' => $data['author']];
}

function updateCategory($pdo, $data) {
    if (!isset($data['id'], $data['category'])) {
        return ['message' => 'Missing Required Parameters'];
    }
    $categoryExists = getCategories($pdo, ['id' => $data['id']]);
    if (empty($categoryExists)) {
        return ['message' => 'category_id Not Found'];
    }
    $stmt = $pdo->prepare("UPDATE categories SET category = :category WHERE id = :id");
    $stmt->execute(['id' => $data['id'], 'category' => $data['category']]);
    return ['id' => $data['id'], 'category' => $data['category']];
}

function deleteQuote($pdo, $id) {
    $quoteExists = getQuotes($pdo, ['id' => $id]);
    if (empty($quoteExists)) {
        return ['message' => 'No Quotes Found'];
    }
    $stmt = $pdo->prepare("DELETE FROM quotes WHERE id = :id");
    $stmt->execute(['id' => $id]);
    return ['id' => $id];
}

function deleteAuthor($pdo, $id) {
    $authorExists = getAuthors($pdo, ['id' => $id]);
    if (empty($authorExists)) {
        return ['message' => 'author_id Not Found'];
    }
    $stmt = $pdo->prepare("DELETE FROM authors WHERE id = :id");
    $stmt->execute(['id' => $id]);
    return ['id' => $id];
}

function deleteCategory($pdo, $id) {
    $categoryExists = getCategories($pdo, ['id' => $id]);
    if (empty($categoryExists)) {
php?>
