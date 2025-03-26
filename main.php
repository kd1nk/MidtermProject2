<?php

// Database Configuration (using environment variables and explicit host)
$dbHost = "dpg-cvi55i5umphs73cv8hd0-a:5432"; // Explicitly set the DB_HOST
$dbUser = getenv('database_7riv_user');
$dbPass = getenv('V3ZlsQPXEqE38L84qhfE1mnjmWvHTZgi');
$dbName = getenv('database');

// Database Connection (PostgreSQL)
$connStr = "host=$dbHost dbname=$dbName user=$dbUser password=$dbPass";
$conn = pg_connect($connStr);

if (!$conn) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['message' => 'Database Connection Failed: ' . pg_last_error()]);
    exit; // Stop execution
}

// Helper Functions (PostgreSQL)
function getQuotes($conn, $params = []) {
    $sql = "SELECT q.id, q.quote, a.author, c.category 
            FROM quotes q
            JOIN authors a ON q.author_id = a.id 
            JOIN categories c ON q.category_id = c.id";
    $where = [];
    $paramValues = [];
    if (isset($params['id'])) {
        $where[] = "q.id = $1";
        $paramValues[] = $params['id'];
    }
    if (isset($params['author_id'])) {
        $where[] = "q.author_id = $" . (count($paramValues) + 1);
        $paramValues[] = $params['author_id'];
    }
    if (isset($params['category_id'])) {
        $where[] = "q.category_id = $" . (count($paramValues) + 1);
        $paramValues[] = $params['category_id'];
    }
    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }
    $sql =  $sql . " ORDER BY q.id";
    $result = pg_query_params($conn, $sql, $paramValues);
    if ($result && pg_num_rows($result) > 0) {
        $quotes = [];
        while ($row = pg_fetch_assoc($result)) {
            $quotes[] = $row;
        }
        pg_free_result($result);
        return $quotes;
    } else {
        pg_free_result($result);
        return false;
    }
}

function getAuthors($conn, $params = []) {
    $sql = "SELECT id, author FROM authors";
    $paramValues = [];
    if (isset($params['id'])) {
        $sql .= " WHERE id = $1";
        $paramValues[] = $params['id'];
    }
    $sql = $sql . " ORDER BY id";
    $result = pg_query_params($conn, $sql, $paramValues);
    if ($result && pg_num_rows($result) > 0) {
        $authors = [];
        while ($row = pg_fetch_assoc($result)) {
            $authors[] = $row;
        }
        pg_free_result($result);
        return $authors;
    } else {
        pg_free_result($result);
        return false;
    }
}

function getCategories($conn, $params = []) {
    $sql = "SELECT id, category FROM categories";
    $paramValues = [];
    if (isset($params['id'])) {
        $sql .= " WHERE id = $1";
        $paramValues[] = $params['id'];
    }
    $sql = $sql . " ORDER BY id";
    $result = pg_query_params($conn, $sql, $paramValues);
    if ($result && pg_num_rows($result) > 0) {
        $categories = [];
        while ($row = pg_fetch_assoc($result)) {
            $categories[] = $row;
        }
        pg_free_result($result);
        return $categories;
    } else {
        pg_free_result($result);
        return false;
    }
}

function authorExists($conn, $author_id) {
    $sql = "SELECT id FROM authors WHERE id = $1";
    $result = pg_query_params($conn, $sql, [$author_id]);
    $exists = $result && pg_num_rows($result) > 0;
    pg_free_result($result);
    return $exists;
}

function categoryExists($conn, $category_id) {
    $sql = "SELECT id FROM categories WHERE id = $1";
    $result = pg_query_params($conn, $sql, [$category_id]);
    $exists = $result && pg_num_rows($result) > 0;
    pg_free_result($result);
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

    $sql = "INSERT INTO quotes (quote, author_id, category_id) VALUES ($1, $2, $3) RETURNING id";
    $result = pg_query_params($conn, $sql, [$data['quote'], $data['author_id'], $data['category_id']]);

    if ($result && pg_num_rows($result) > 0) {
        $row = pg_fetch_assoc($result);
        $resultData = ['id' => $row['id'], 'quote' => $data['quote'], 'author_id' => $data['author_id'], 'category_id' => $data['category_id']];
        pg_free_result($result);
        return $resultData;
    } else {
        http_response_code(500);
        return ['message' => 'Error: ' . pg_last_error()];
    }
}

function createAuthor($conn, $data) {
    if (!isset($data['author'])) {
        http_response_code(400);
        return ['message' => 'Missing Required Parameters'];
    }

    $sql = "INSERT INTO authors (author) VALUES ($1) RETURNING id";
    $result = pg_query_params($conn, $sql, [$data['author']]);

    if ($result && pg_num_rows($result) > 0) {
        $row = pg_fetch_assoc($result);
        $resultData = ['id' => $row['id'], 'author' => $data['author']];
        pg_free_result($result);
        return $resultData;
    } else {
        http_response_code(500);
        return ['message' => 'Error: ' . pg_last_error()];
    }
}

function createCategory($conn, $data) {
    if (!isset($data['category'])) {
        http_response_code(400);
        return ['message' => 'Missing Required Parameters'];
    }
    $sql = "INSERT INTO categories (category) VALUES ($1) RETURNING id";
    $result = pg_query_params($conn, $sql, [$data['category']]);
    if ($result && pg_num_rows($result) > 0) {
        $row = pg_fetch_assoc($result);
        $resultData = ['id' => $row['id'], 'category' => $data['category']];
        pg_free_result($result);
        return $resultData;
    } else {
        http_response_code(500);
        return ['message' => 'Error: ' . pg_last_error()];
    }
}

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

    $sql = "UPDATE quotes SET quote = $1, author_id = $2, category_id = $3 WHERE id = $4";
    $result = pg_query_params($conn, $sql, [$data['quote'], $data['author_id'], $data['category_id'], $data['id']]);

    if ($result) {
        $resultData = ['id' => $data['id'], 'quote' => $data['quote'], 'author_id' => $data['author_id'], 'category_id' => $data['category_id']];
        return $resultData;
    } else {
        http_response_code(404);
        return ['message' => 'No Quotes Found'];
    }
}

function updateAuthor($conn, $data) {
    if (!isset($data['id']) || !isset($data['author'])) {
        http_response_code(400);
        return ['message' => 'Missing Required Parameters'];
    }
    $sql = "UPDATE authors SET author = $1 WHERE id = $2";
    $result = pg_query_params($conn, $sql, [$data['author'], $data['id']]);
    if ($result) {
        $resultData = ['id' => $data['id'], 'author' => $data['author']];
        return $resultData;
    } else {
        http_response_code(404);
        return ['message' => 'No Authors Found'];
    }
}

function updateCategory($conn, $data) {
    if (!isset($data['id']) || !isset($data['category'])) {
        http_response_code(400);
        return ['message' => 'Missing Required Parameters'];
    }
    $sql = "UPDATE categories SET category = $1 WHERE id = $2";
    $result = pg_query_params($conn, $sql, [$data['category'], $data['id']]);
    if ($result) {
        $resultData = ['id' => $data['id'], 'category' => $data['category']];
        return $resultData;
    } else {
        http_response_code(404);
        return ['message' => 'No Categories Found'];
    }
}

function deleteQuote($conn, $id) {
    $sql = "DELETE FROM quotes WHERE id = $1";
    $result = pg_query_params($conn, $sql, [$id]);
    if ($result) {
        $resultData = ['id' => $id];
        return $resultData;
    } else {
        http_response_code(404);
        return ['message' => 'No Quotes Found'];
    }
}

function deleteAuthor($conn, $id) {
    $sql = "DELETE FROM authors WHERE id = $1";
    $result = pg_query_params($conn, $sql, [$id]);
    if ($result) {
        $resultData = ['id' => $id];
        return $resultData;
    } else {
        http_response_code(404);
        return ['message' => 'No Authors Found'];
    }
}

function deleteCategory($conn, $id) {
    $sql = "DELETE FROM categories WHERE id = $1";
    $result = pg_query_params($conn, $sql, [$id]);
    if ($result) {
        $resultData = ['id' => $id];
        return $resultData;
    } else {
        http_response_code(404);
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

pg_close($conn);

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
?>

