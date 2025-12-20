<?php
require_once 'config.php';

$db = Database::getInstance()->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

// LOGIN
if ($method === 'POST' && isset($_GET['action']) && $_GET['action'] === 'login') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['username']) || !isset($data['password'])) {
        jsonResponse(['success' => false, 'message' => 'Identifiants manquants'], 400);
    }
    
    $stmt = $db->prepare("SELECT id, username, email, password, role FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$data['username'], $data['username']]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($data['password'], $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        
        // Cookie de session (7 jours)
        setcookie('user_session', session_id(), time() + (86400 * 7), '/');
        
        jsonResponse([
            'success' => true,
            'message' => 'Connexion réussie',
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'role' => $user['role']
            ]
        ]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Identifiants incorrects'], 401);
    }
}

// REGISTER
if ($method === 'POST' && isset($_GET['action']) && $_GET['action'] === 'register') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['username']) || !isset($data['email']) || !isset($data['password'])) {
        jsonResponse(['success' => false, 'message' => 'Données manquantes'], 400);
    }
    
    // Validation
    if (strlen($data['password']) < 6) {
        jsonResponse(['success' => false, 'message' => 'Le mot de passe doit contenir au moins 6 caractères'], 400);
    }
    
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        jsonResponse(['success' => false, 'message' => 'Email invalide'], 400);
    }
    
    // Vérifier si l'utilisateur existe
    $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$data['username'], $data['email']]);
    
    if ($stmt->fetch()) {
        jsonResponse(['success' => false, 'message' => 'Nom d\'utilisateur ou email déjà utilisé'], 409);
    }
    
    // Créer l'utilisateur
    $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
    $stmt = $db->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'user')");
    
    try {
        $stmt->execute([$data['username'], $data['email'], $hashedPassword]);
        $userId = $db->lastInsertId();
        
        // Connexion automatique
        $_SESSION['user_id'] = $userId;
        $_SESSION['username'] = $data['username'];
        $_SESSION['email'] = $data['email'];
        $_SESSION['role'] = 'user';
        
        jsonResponse([
            'success' => true,
            'message' => 'Inscription réussie',
            'user' => [
                'id' => $userId,
                'username' => $data['username'],
                'email' => $data['email'],
                'role' => 'user'
            ]
        ]);
    } catch (PDOException $e) {
        jsonResponse(['success' => false, 'message' => 'Erreur lors de l\'inscription'], 500);
    }
}

// LOGOUT
if ($method === 'POST' && isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    setcookie('user_session', '', time() - 3600, '/');
    jsonResponse(['success' => true, 'message' => 'Déconnexion réussie']);
}

// CHECK SESSION
if ($method === 'GET' && isset($_GET['action']) && $_GET['action'] === 'check') {
    if (isLoggedIn()) {
        jsonResponse([
            'success' => true,
            'loggedIn' => true,
            'user' => [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'email' => $_SESSION['email'],
                'role' => $_SESSION['role']
            ]
        ]);
    } else {
        jsonResponse(['success' => true, 'loggedIn' => false]);
    }
}

jsonResponse(['success' => false, 'message' => 'Action invalide'], 400);
?>