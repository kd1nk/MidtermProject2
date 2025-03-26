<?php

// Database Configuration
define('DB_HOST', 'your_db_host');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
define('DB_NAME', 'quotesdb');

// Database Connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['message' => 'Database Connection Failed: ' . $conn->connect_error]);
    exit; // Stop execution
}

// Helper Functions
function getQuotes($conn, $params = []) {
    $sql = "SELECT quotes.id, quotes.quote, authors.author, categories.category FROM quotes JOIN authors ON quotes.author_id = authors.id JOIN categories ON quotes.category_id = categories.id";
    $where = [];
    if (isset($params['id'])) {
        $where[] = "quotes.id = ?";
    }
    if (isset($params['author_id'])) {
        $where[] = "quotes.author_id = ?";
    }
    if (isset($params['category_id'])) {
        $where[] = "quotes.category_id = ?";
    }
    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['message' => 'Database Error: ' . $conn->error]);
        exit;
    }

    if (!empty($where)) {
        $types = str_repeat('i', count($where));
        $values = [];
        if (isset($params['id'])) {
            $values[] = $params['id'];
        }
        if (isset($params['author_id'])) {
            $values[] = $params['author_id'];
        }
        if (isset($params['category_id'])) {
            $values[] = $params['category_id'];
        }

        $stmt->bind_param($types, ...$values);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $quotes = [];
        while ($row = $result->fetch_assoc()) {
            $quotes[] = $row;
        }
        $stmt->close();
        return $quotes;
    } else {
        $stmt->close();
        return false;
    }
}

function getAuthors($conn, $params = []) {
    $sql = "SELECT id, author FROM authors";
    if (isset($params['id'])) {
        $sql .= " WHERE id = ?";
    }
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['message' => 'Database Error: ' . $conn->error]);
        exit;
    }
    if(isset($params['id'])){
        $stmt->bind_param('i',$params['id']);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $authors = [];
        while ($row = $result->fetch_assoc()) {
            $authors[] = $row;
        }
        $stmt->close();
        return $authors;
    } else {
        $stmt->close();
        return false;
    }
}

function getCategories($conn, $params = []) {
    $sql = "SELECT id, category FROM categories";
    if (isset($params['id'])) {
        $sql .= " WHERE id = ?";
    }

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['message' => 'Database Error: ' . $conn->error]);
        exit;
    }
    if(isset($params['id'])){
        $stmt->bind_param('i', $params['id']);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $categories = [];
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
        $stmt->close();
        return $categories;
    } else {
        $stmt->close();
        return false;
    }
}

function authorExists($conn, $author_id) {
    $sql = "SELECT id FROM authors WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $author_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->num_rows > 0;
    $stmt->close();
    return $exists;
}

function categoryExists($conn, $category_id) {
    $sql = "SELECT id FROM categories WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $category_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->num_rows > 0;
    $stmt->close();
    return $exists;
}

function createQuote($conn, $data) {
    if (!isset($data['quote']) || !isset($data['author_id']) || !isset($data['category_id'])) {
        http_response_code(400); // Bad Request
        return ['message' => 'Missing Required Parameters'];
    }

    if (!authorExists($conn, $data['author_id'])) {
        http_response_code(404); // Not Found
        return ['message' => 'author_id Not Found'];
    }
    if (!categoryExists($conn, $data['category_id'])) {
        http_response_code(404); // Not Found
        return ['message' => 'category_id Not Found'];
    }

    $sql = "INSERT INTO quotes (quote, author_id, category_id) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sii', $data['quote'], $data['author_id'], $data['category_id']);

    if ($stmt->execute()) {
        $result = ['id' => $conn->insert_id, 'quote' => $data['quote'], 'author_id' => $data['author_id'], 'category_id' => $data['category_id']];
        $stmt->close();
        return $result;
    } else {
        http_response_code(500);
        $stmt->close();
        return ['message' => 'Error: ' . $sql . '<br>' . $conn->error];
    }
}

function createAuthor($conn, $data) {
    if (!isset($data['author'])) {
        http_response_code(400);
        return ['message' => 'Missing Required Parameters'];
    }

    $sql = "INSERT INTO authors (author) VALUES (?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $data['author']);

    if ($stmt->execute()) {
        $result = ['id' => $conn->insert_id, 'author' => $data['author']];
        $stmt->close();
        return $result;
    } else {
        http_response_code(500);
        $stmt->close();
        return ['message' => 'Error: ' . $sql . '<br>' . $conn->error];
    }
}

function createCategory($conn, $data) {
    if (!isset($data['category'])) {
        http_response_code(400);
        return ['message' => 'Missing Required Parameters'];
    }
    $sql = "INSERT INTO categories (category) VALUES (?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $data['category']);
    if($stmt->execute()){
        $result = ['id'=>$conn->insert_id, 'category' => $data['category']];
        $stmt->close();
        return $result;
    } else {
        http_response_code(500);
        $stmt->close();
        return ['message' => 'Error: ' . $sql . '<br>' . $conn->error];
        function updateQuote($conn, $data) {
    if (!isset($data['id']) || !isset($data['quote']) || !isset($data['author_id']) || !isset($data['category_id'])) {
        http_response_code(400); // Bad Request
        return ['message' => 'Missing Required Parameters'];
    }

    if (!authorExists($conn, $data['author_id'])) {
        http_response_code(404); // Not Found
        return ['message' => 'author_id Not Found'];
    }
    if (!categoryExists($conn, $data['category_id'])) {
        http_response_code(404); // Not Found
        return ['message' => 'category_id Not Found'];
    }

    $sql = "UPDATE quotes SET quote = ?, author_id = ?, category_id = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('siii', $data['quote'], $data['author_id'], $data['category_id'], $data['id']);

    if ($stmt->execute()) {
        $result = ['id' => $data['id'], 'quote' => $data['quote'], 'author_id' => $data['author_id'], 'category_id' => $data['category_id']];
        $stmt->close();
        return $result;
    } else {
        http_response_code(404);
        $stmt->close();
        return ['message' => 'No Quotes Found'];
    }
}

function updateAuthor($conn, $data) {
    if (!isset($data['id']) || !isset($data['author'])) {
        http_response_code(400);
        return ['message' => 'Missing Required Parameters'];
    }
    $sql = "UPDATE authors SET author = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('si', $data['author'], $data['id']);
    if ($stmt->execute()) {
        $result = ['id' => $data['id'], 'author' => $data['author']];
        $stmt->close();
        return $result;
    } else {
        http_response_code(404);
        $stmt->close();
        return ['message' => 'No Authors Found'];
    }
}

function updateCategory($conn, $data) {
    if (!isset($data['id']) || !isset($data['category'])) {
        http_response_code(400);
        return ['message' => 'Missing Required Parameters'];
    }
    $sql = "UPDATE categories SET category = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('si', $data['category'], $data['id']);
    if ($stmt->execute()) {
        $result = ['id' => $data['id'], 'category' => $data['category']];
        $stmt->close();
        return $result;
    } else {
        http_response_code(404);
        $stmt->close();
        return ['message' => 'No Categories Found'];
    }
}

function deleteQuote($conn, $id) {
    $sql = "DELETE FROM quotes WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        $result = ['id' => $id];
        $stmt->close();
        return $result;
    } else {
        http_response_code(404);
        $stmt->close();
        return ['message' => 'No Quotes Found'];
    }
}

function deleteAuthor($conn, $id) {
    $sql = "DELETE FROM authors WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        $result = ['id' => $id];
        $stmt->close();
        return $result;
    } else {
        http_response_code(404);
        $stmt->close();
        return ['message' => 'No Authors Found'];
    }
}

function deleteCategory($conn, $id) {
    $sql = "DELETE FROM categories WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        $result = ['id' => $id];
        $stmt->close();
        return $result;
    } else {
        http_response_code(404);
        $stmt->close();
        return ['message' => 'No Categories Found'];
    }
}

// Routing and Request Handling
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];
$uriParts = explode('/', trim(str_replace('/api', '', $requestUri), '/'));

header('Content-Type: application/json');

switch ($uriParts[0]) {
    case 'quotes':
        handleQuotesRequest($conn, $requestMethod, $uriParts);
        break;
    case 'authors':
        handleAuthorsRequest($conn, $requestMethod, $uriParts);
        break;
    case 'categories':
        handleCategoriesRequest($conn, $requestMethod, $uriParts);
        break;
    default:
        http_response_code(404);
        echo json_encode(['message' => 'Not Found']);
        break;
}

$conn->close();

function handleQuotesRequest($conn, $method, $uriParts) {
    switch ($method) {
        case 'GET':
            $params = $_GET;
            if (empty($params)) {
                $result = getQuotes($conn);
            } else {
                $result = getQuotes($conn, $params);
            }
            if ($result) {
                echo json_encode($result);
            } else {
                http_response_code(404);
                echo json_encode(['message' => 'No Quotes Found']);
            }
            break;
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            echo json_encode(createQuote($conn, $data));
            break;
        case 'PUT':
            $data = json_decode(file_get_contents('php://input'), true);
            echo json_encode(updateQuote($conn, $data));
            break;
        case 'DELETE':
            if (isset($uriParts[1]) && is_numeric($uriParts[1])) {
                echo json_encode(deleteQuote($conn, $uriParts[1]));
            } else {
                http_response_code(400);
                echo json_encode(['message' => 'Missing ID parameter']);
            }
            break;
    }
}

function handleAuthorsRequest($conn, $method, $uriParts) {
    switch ($method) {
        case 'GET':
            $params = $_GET;
            if (empty($params)) {
                $result = getAuthors($conn);
            } else {
                $result = getAuthors($conn, $params);
            }
            if ($result) {
                echo json_encode($result);
            } else {
                http_response_code(404);
                echo json_encode(['message' => 'author_id Not Found']);
            }
            break;
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            echo json_encode(createAuthor($conn, $data));
            break;
        case 'PUT':
            $data = json_decode(file_get_contents('php://input'), true);
            echo json_encode(updateAuthor($conn, $data));
            break;
        case 'DELETE':
            if (isset($uriParts[1]) && is_numeric($uriParts[1])) {
                echo json_encode(deleteAuthor($conn, $uriParts[1]));
            } else {
                http_response_code(400);
                echo json_encode(['message' => 'Missing ID parameter']);
            }
            break;
    }
}

function handleCategoriesRequest($conn, $method, $uriParts) {
    switch ($method) {
        case 'GET':
            $params = $_GET;
            if (empty($params)) {
                $result = getCategories($conn);
            } else {
                function handleCategoriesRequest($conn, $method, $uriParts) {
    switch ($method) {
        case 'GET':
            $params = $_GET;
            if (empty($params)) {
                $result = getCategories($conn);
            } else {
                $result = getCategories($conn, $params);
            }
            if ($result) {
                echo json_encode($result);
            } else {
                http_response_code(404);
                echo json_encode(['message' => 'category_id Not Found']);
            }
            break;
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            echo json_encode(createCategory($conn, $data));
            break;
        case 'PUT':
            $data = json_decode(file_get_contents('php://input'), true);
            echo json_encode(updateCategory($conn, $data));
            break;
        case 'DELETE':
            if (isset($uriParts[1]) && is_numeric($uriParts[1])) {
                echo json_encode(deleteCategory($conn, $uriParts[1]));
            } else {
                http_response_code(400);
                echo json_encode(['message' => 'Missing ID parameter']);
            }
            break;
    }
}

// Routing and Request Handling
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];
$uriParts = explode('/', trim(str_replace('/api', '', $requestUri), '/'));

header('Content-Type: application/json');

switch ($uriParts[0]) {
    case 'quotes':
        handleQuotesRequest($conn, $requestMethod, $uriParts);
        break;
    case 'authors':
        handleAuthorsRequest($conn, $requestMethod, $uriParts);
        break;
    case 'categories':
        handleCategoriesRequest($conn, $requestMethod, $uriParts);
        break;
    default:
        http_response_code(404);
        echo json_encode(['message' => 'Not Found']);
        break;
}

$conn->close();

?>

