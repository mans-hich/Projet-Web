<?php
// Activer les erreurs pour debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

$db = Database::getInstance()->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

// Log pour debug
error_log("Cart API called - Method: $method - Session ID: " . session_id());
error_log("Session data: " . json_encode($_SESSION));

// Vérifier l'authentification
if (!isLoggedIn()) {
    error_log("User not logged in");
    jsonResponse(['success' => false, 'message' => 'Non authentifié', 'debug' => [
        'session_id' => session_id(),
        'session_data' => $_SESSION
    ]], 401);
}

$userId = getUserId();
error_log("User ID: $userId");

// GET CART
if ($method === 'GET') {
    try {
        $stmt = $db->prepare("
            SELECT c.id, c.quantity, i.id as item_id, i.name, i.price, i.image_url,
                   (c.quantity * i.price) as subtotal
            FROM cart c
            JOIN items i ON c.item_id = i.id
            WHERE c.user_id = ?
        ");
        $stmt->execute([$userId]);
        $cartItems = $stmt->fetchAll();
        
        $total = 0;
        foreach ($cartItems as $item) {
            $total += $item['subtotal'];
        }
        
        jsonResponse([
            'success' => true,
            'cart' => $cartItems,
            'total' => $total,
            'itemCount' => count($cartItems)
        ]);
    } catch (Exception $e) {
        error_log("Error fetching cart: " . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()], 500);
    }
}

// ADD TO CART
if ($method === 'POST' && isset($_GET['action']) && $_GET['action'] === 'add') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['item_id']) || !isset($data['quantity'])) {
        jsonResponse(['success' => false, 'message' => 'Données manquantes'], 400);
    }
    
    $itemId = $data['item_id'];
    $quantity = max(1, intval($data['quantity']));
    
    try {
        // Vérifier si le produit existe et est en stock
        $stmt = $db->prepare("SELECT stock FROM items WHERE id = ?");
        $stmt->execute([$itemId]);
        $item = $stmt->fetch();
        
        if (!$item) {
            jsonResponse(['success' => false, 'message' => 'Produit non trouvé'], 404);
        }
        
        if ($item['stock'] < $quantity) {
            jsonResponse(['success' => false, 'message' => 'Stock insuffisant'], 400);
        }
        
        // Vérifier si l'item est déjà dans le panier
        $stmt = $db->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND item_id = ?");
        $stmt->execute([$userId, $itemId]);
        $existingItem = $stmt->fetch();
        
        if ($existingItem) {
            // Mettre à jour la quantité
            $newQuantity = $existingItem['quantity'] + $quantity;
            $stmt = $db->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
            $stmt->execute([$newQuantity, $existingItem['id']]);
        } else {
            // Ajouter au panier
            $stmt = $db->prepare("INSERT INTO cart (user_id, item_id, quantity) VALUES (?, ?, ?)");
            $stmt->execute([$userId, $itemId, $quantity]);
        }
        
        jsonResponse(['success' => true, 'message' => 'Produit ajouté au panier']);
    } catch (Exception $e) {
        error_log("Error adding to cart: " . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()], 500);
    }
}

// UPDATE CART ITEM
if ($method === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['cart_id']) || !isset($data['quantity'])) {
        jsonResponse(['success' => false, 'message' => 'Données manquantes'], 400);
    }
    
    $quantity = max(1, intval($data['quantity']));
    
    try {
        // Vérifier que l'item appartient à l'utilisateur
        $stmt = $db->prepare("SELECT item_id FROM cart WHERE id = ? AND user_id = ?");
        $stmt->execute([$data['cart_id'], $userId]);
        $cartItem = $stmt->fetch();
        
        if (!$cartItem) {
            jsonResponse(['success' => false, 'message' => 'Item non trouvé'], 404);
        }
        
        // Vérifier le stock
        $stmt = $db->prepare("SELECT stock FROM items WHERE id = ?");
        $stmt->execute([$cartItem['item_id']]);
        $item = $stmt->fetch();
        
        if ($item['stock'] < $quantity) {
            jsonResponse(['success' => false, 'message' => 'Stock insuffisant'], 400);
        }
        
        $stmt = $db->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
        $stmt->execute([$quantity, $data['cart_id']]);
        jsonResponse(['success' => true, 'message' => 'Quantité mise à jour']);
    } catch (Exception $e) {
        error_log("Error updating cart: " . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()], 500);
    }
}

// REMOVE FROM CART
if ($method === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['cart_id'])) {
        jsonResponse(['success' => false, 'message' => 'ID manquant'], 400);
    }
    
    try {
        $stmt = $db->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
        $stmt->execute([$data['cart_id'], $userId]);
        
        if ($stmt->rowCount() > 0) {
            jsonResponse(['success' => true, 'message' => 'Produit retiré du panier']);
        } else {
            jsonResponse(['success' => false, 'message' => 'Item non trouvé'], 404);
        }
    } catch (Exception $e) {
        error_log("Error removing from cart: " . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()], 500);
    }
}

// CLEAR CART
if ($method === 'POST' && isset($_GET['action']) && $_GET['action'] === 'clear') {
    try {
        $stmt = $db->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->execute([$userId]);
        jsonResponse(['success' => true, 'message' => 'Panier vidé']);
    } catch (Exception $e) {
        error_log("Error clearing cart: " . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()], 500);
    }
}

jsonResponse(['success' => false, 'message' => 'Action invalide'], 400);
?>