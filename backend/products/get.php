<?php
// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
require_once '../../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    $pdo = getDB();

    // Get query parameters
    $search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
    $category = isset($_GET['category']) ? sanitize($_GET['category']) : '';
    $min_price = isset($_GET['min_price']) ? floatval($_GET['min_price']) : 0;
    $max_price = isset($_GET['max_price']) ? floatval($_GET['max_price']) : 999999;
    $stock_status = isset($_GET['stock_status']) ? sanitize($_GET['stock_status']) : '';
    $sort_by = isset($_GET['sort_by']) ? sanitize($_GET['sort_by']) : 'name_asc';
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 500;

    // Calculate offset
    $offset = ($page - 1) * $limit;

    // Build WHERE clause
    $where_conditions = [];
    $params = [];

    if (!empty($search)) {
        $where_conditions[] = "(Nom LIKE :search OR Description LIKE :search OR Categorie LIKE :search)";
        $params[':search'] = '%' . $search . '%';
    }

    if (!empty($category)) {
        $where_conditions[] = "Categorie = :category";
        $params[':category'] = $category;
    }

    if ($min_price > 0 || $max_price < 999999) {
        $where_conditions[] = "Prix BETWEEN :min_price AND :max_price";
        $params[':min_price'] = $min_price;
        $params[':max_price'] = $max_price;
    }

    if (!empty($stock_status)) {
        switch ($stock_status) {
            case 'in_stock':
                $where_conditions[] = "Stock > 10";
                break;
            case 'low_stock':
                $where_conditions[] = "Stock BETWEEN 1 AND 10";
                break;
            case 'out_of_stock':
                $where_conditions[] = "Stock = 0";
                break;
        }
    }

    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

    // Build ORDER BY clause
    $order_by = 'Nom ASC';
    switch ($sort_by) {
        case 'name_desc':
            $order_by = 'Nom DESC';
            break;
        case 'price_asc':
            $order_by = 'Prix ASC';
            break;
        case 'price_desc':
            $order_by = 'Prix DESC';
            break;
        case 'newest':
            $order_by = 'ID_Produit DESC';
            break;
        case 'name_asc':
        default:
            $order_by = 'Nom ASC';
            break;
    }

    // Get total count for pagination
    $count_sql = "SELECT COUNT(*) as total FROM produit $where_clause";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_count = $count_stmt->fetch()['total'];

    // Get products
    $sql = "SELECT ID_Produit, Nom, Description, Prix, Stock, Categorie, Image_URL, Poids
            FROM produit
            $where_clause
            ORDER BY $order_by
            LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }

    $stmt->execute();
    $products = $stmt->fetchAll();

    // Get categories for filter
    $categories_sql = "SELECT DISTINCT Categorie FROM produit ORDER BY Categorie";
    $categories_stmt = $pdo->query($categories_sql);
    $categories = $categories_stmt->fetchAll(PDO::FETCH_COLUMN);

    // Calculate pagination info
    $total_pages = ceil($total_count / $limit);

    echo json_encode([
        'success' => true,
        'products' => $products,
        'categories' => $categories,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $total_pages,
            'total_products' => $total_count,
            'per_page' => $limit
        ],
        'filters' => [
            'search' => $search,
            'category' => $category,
            'min_price' => $min_price,
            'max_price' => $max_price,
            'stock_status' => $stock_status,
            'sort_by' => $sort_by
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors de la récupération des produits: ' . $e->getMessage()
    ]);
}
?>
