<?php
require_once 'config.php';

$db = Database::getInstance()->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

// GET ALL PRODUCTS avec filtres et recherche
if ($method === 'GET' && !isset($_GET['id'])) {
    $query = "SELECT i.*, c.name as category_name FROM items i 
              LEFT JOIN categories c ON i.category_id = c.id WHERE 1=1";
    $params = [];
    
    // Recherche par mot-clé
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $query .= " AND (i.name LIKE ? OR i.description LIKE ?)";
        $searchTerm = '%' . $_GET['search'] . '%';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    // Filtre par catégorie
    if (isset($_GET['category']) && !empty($_GET['category'])) {
        $query .= " AND i.category_id = ?";
        $params[] = $_GET['category'];
    }
    
    // Filtre par prix minimum
    if (isset($_GET['min_price']) && is_numeric($_GET['min_price'])) {
        $query .= " AND i.price >= ?";
        $params[] = $_GET['min_price'];
    }
    
    // Filtre par prix maximum
    if (isset($_GET['max_price']) && is_numeric($_GET['max_price'])) {
        $query .= " AND i.price <= ?";
        $params[] = $_GET['max_price'];
    }
    
    // Tri
    $sortBy = $_GET['sort'] ?? 'name';
    $sortOrder = $_GET['order'] ?? 'ASC';
    
    $allowedSorts = ['name', 'price', 'created_at'];
    $allowedOrders = ['ASC', 'DESC'];
    
    if (in_array($sortBy, $allowedSorts) && in_array($sortOrder, $allowedOrders)) {
        $query .= " ORDER BY i.$sortBy $sortOrder";
    } else {
        $query .= " ORDER BY i.name ASC";
    }
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
    
    jsonResponse(['success' => true, 'products' => $products]);
}

// GET SINGLE PRODUCT
if ($method === 'GET' && isset($_GET['id'])) {
    $stmt = $db->prepare("SELECT i.*, c.name as category_name FROM items i 
                          LEFT JOIN categories c ON i.category_id = c.id 
                          WHERE i.id = ?");
    $stmt->execute([$_GET['id']]);
    $product = $stmt->fetch();
    
    if ($product) {
        jsonResponse(['success' => true, 'product' => $product]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Produit non trouvé'], 404);
    }
}

// GET CATEGORIES
if ($method === 'GET' && isset($_GET['action']) && $_GET['action'] === 'categories') {
    $stmt = $db->query("SELECT * FROM categories ORDER BY name ASC");
    $categories = $stmt->fetchAll();
    jsonResponse(['success' => true, 'categories' => $categories]);
}

jsonResponse(['success' => false, 'message' => 'Action invalide'], 400);
?>