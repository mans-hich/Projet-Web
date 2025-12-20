<?php
require_once 'config.php';
try {
    $db = Database::getInstance()->getConnection();
    echo "✅ Connexion réussie !";
} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage();
}
?>  