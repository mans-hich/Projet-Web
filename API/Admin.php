<?php
require_once 'config.php';

$db = Database::getInstance()->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

// Vérifier les droits admin
if (!isAdmin()) {
    jsonResponse(['success' => false, 'message' => 'Accès non autorisé'], 403);
}

// CREATE PRODUCT
if ($method === 'POST' && !isset($_GET['action'])) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['name']) || !isset($data['description']) || !isset($data['price'])) {
        jsonResponse(['success' => false, 'message' => 'Données manquantes'], 400);
    }
    
    $name = $data['name'];
    $description = $data['description'];
    $price = floatval($data['price']);
    $categoryId = $data['category_id'] ?? null;
    $stock = intval($data['stock'] ?? 0);
    $imageUrl = $data['image_url'] ?? null;
    
    if ($price <= 0) {
        jsonResponse(['success' => false, 'message' => 'Prix invalide'], 400);
    }
    
    try {
        $stmt = $db->prepare("
            INSERT INTO items (name, description, price, category_id, stock, image_url) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$name, $description, $price, $categoryId, $stock, $imageUrl]);
        $productId = $db->lastInsertId();
        
        jsonResponse([
            'success' => true,
            'message' => 'Produit créé avec succès',
            'product_id' => $productId
        ]);
    } catch (PDOException $e) {
        jsonResponse(['success' => false, 'message' => 'Erreur lors de la création'], 500);
    }
}

// UPDATE PRODUCT
if ($method === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['id'])) {
        jsonResponse(['success' => false, 'message' => 'ID manquant'], 400);
    }
    
    $productId = $data['id'];
    
    // Vérifier si le produit existe
    $stmt = $db->prepare("SELECT id FROM items WHERE id = ?");
    $stmt->execute([$productId]);
    
    if (!$stmt->fetch()) {
        jsonResponse(['success' => false, 'message' => 'Produit non trouvé'], 404);
    }
    
    // Construire la requête de mise à jour dynamiquement
    $updates = [];
    $params = [];
    
    if (isset($data['name'])) {
        $updates[] = "name = ?";
        $params[] = $data['name'];
    }
    if (isset($data['description'])) {
        $updates[] = "description = ?";
        $params[] = $data['description'];
    }
    if (isset($data['price'])) {
        $price = floatval($data['price']);
        if ($price <= 0) {
            jsonResponse(['success' => false, 'message' => 'Prix invalide'], 400);
        }
        $updates[] = "price = ?";
        $params[] = $price;
    }
    if (isset($data['category_id'])) {
        $updates[] = "category_id = ?";
        $params[] = $data['category_id'];
    }
    if (isset($data['stock'])) {
        $updates[] = "stock = ?";
        $params[] = intval($data['stock']);
    }
    if (isset($data['image_url'])) {
        $updates[] = "image_url = ?";
        $params[] = $data['image_url'];
    }
    
    if (empty($updates)) {
        jsonResponse(['success' => false, 'message' => 'Aucune donnée à mettre à jour'], 400);
    }
    
    $params[] = $productId;
    $query = "UPDATE items SET " . implode(", ", $updates) . " WHERE id = ?";
    
    try {
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        jsonResponse(['success' => true, 'message' => 'Produit mis à jour']);
    } catch (PDOException $e) {
        jsonResponse(['success' => false, 'message' => 'Erreur lors de la mise à jour'], 500);
    }
}

// DELETE PRODUCT
if ($method === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['id'])) {
        jsonResponse(['success' => false, 'message' => 'ID manquant'], 400);
    }
    
    try {
        $stmt = $db->prepare("DELETE FROM items WHERE id = ?");
        $stmt->execute([$data['id']]);
        
        if ($stmt->rowCount() > 0) {
            jsonResponse(['success' => true, 'message' => 'Produit supprimé']);
        } else {
            jsonResponse(['success' => false, 'message' => 'Produit non trouvé'], 404);
        }
    } catch (PDOException $e) {
        jsonResponse(['success' => false, 'message' => 'Erreur lors de la suppression'], 500);
    }
}

// GET STATISTICS
if ($method === 'GET' && isset($_GET['action']) && $_GET['action'] === 'stats') {
    try {
        // Nombre total de produits
        $stmt = $db->query("SELECT COUNT(*) as total FROM items");
        $totalProducts = $stmt->fetch()['total'];
        
        // Nombre d'utilisateurs
        $stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE role = 'user'");
        $totalUsers = $stmt->fetch()['total'];
        
        // Valeur totale du stock
        $stmt = $db->query("SELECT SUM(price * stock) as total FROM items");
        $stockValue = $stmt->fetch()['total'] ?? 0;
        
        // Produits en rupture de stock
        $stmt = $db->query("SELECT COUNT(*) as total FROM items WHERE stock = 0");
        $outOfStock = $stmt->fetch()['total'];
        
        jsonResponse([
            'success' => true,
            'stats' => [
                'totalProducts' => $totalProducts,
                'totalUsers' => $totalUsers,
                'stockValue' => floatval($stockValue),
                'outOfStock' => $outOfStock
            ]
        ]);
    } catch (PDOException $e) {
        jsonResponse(['success' => false, 'message' => 'Erreur'], 500);
    }
}

jsonResponse(['success' => false, 'message' => 'Action invalide'], 400);
?>