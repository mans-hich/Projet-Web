let cartItems = [];

// Charger le panier
async function loadCart() {
    const container = document.getElementById('cartContent');
    
    // Vérifier d'abord si l'utilisateur est connecté
    try {
        const authResponse = await fetch('api/auth.php?action=check');
        const authData = await authResponse.json();
        
        if (!authData.loggedIn) {
            container.innerHTML = `
                <div class="empty-state">
                    <h2>🔒 Connexion requise</h2>
                    <p>Veuillez vous connecter pour voir votre panier</p>
                    <a href="login.html" class="btn btn-primary">Se connecter</a>
                    <a href="index.html" class="btn btn-secondary">Retour à l'accueil</a>
                </div>
            `;
            return;
        }
        
        // Utilisateur connecté, charger le panier
        const response = await fetch('api/cart.php');
        const data = await response.json();
        
        if (data.success) {
            cartItems = data.cart;
            displayCart(data.cart, data.total);
        } else {
            container.innerHTML = `
                <div class="empty-state">
                    <p>Erreur lors du chargement du panier</p>
                    <p>${data.message || ''}</p>
                    <a href="index.html" class="btn btn-primary">Retour à l'accueil</a>
                </div>
            `;
        }
    } catch (error) {
        console.error('Erreur:', error);
        container.innerHTML = `
            <div class="empty-state">
                <p>Erreur de connexion</p>
                <a href="index.html" class="btn btn-primary">Retour à l'accueil</a>
            </div>
        `;
    }
}

// Afficher le panier
function displayCart(items, total) {
    const container = document.getElementById('cartContent');
    
    if (items.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <h2>🛒 Votre panier est vide</h2>
                <p>Découvrez nos produits et commencez vos achats !</p>
                <a href="index.html" class="btn btn-primary">Continuer les achats</a>
            </div>
        `;
        return;
    }
    
    container.innerHTML = `
        <div class="cart-items">
            ${items.map(item => `
                <div class="cart-item" id="cart-item-${item.id}">
                    <img src="${item.image_url}" alt="${item.name}" class="cart-item-image">
                    
                    <div class="cart-item-info">
                        <h3>${item.name}</h3>
                        <p>Prix unitaire: ${parseFloat(item.price).toFixed(2)} €</p>
                        <p class="product-price">Sous-total: ${parseFloat(item.subtotal).toFixed(2)} €</p>
                    </div>
                    
                    <div class="cart-item-actions">
                        <input 
                            type="number" 
                            value="${item.quantity}" 
                            min="1" 
                            class="quantity-input"
                            onchange="updateQuantity(${item.id}, ${item.item_id}, this.value)"
                        >
                        
                        <button class="btn btn-danger btn-sm" onclick="removeFromCart(${item.id})">
                            Supprimer
                        </button>
                    </div>
                </div>
            `).join('')}
        </div>
        
        <div class="cart-summary">
            <div class="cart-total">
                <span>Total:</span>
                <span>${parseFloat(total).toFixed(2)} €</span>
            </div>
            
            <button class="btn btn-success" style="width: 100%; padding: 1rem; font-size: 1.1rem;" onclick="checkout()">
                Procéder au paiement
            </button>
            
            <button class="btn btn-outline" style="width: 100%; margin-top: 1rem;" onclick="clearCart()">
                Vider le panier
            </button>
            
            <a href="index.html" class="btn btn-secondary" style="width: 100%; margin-top: 1rem; text-align: center;">
                Continuer les achats
            </a>
        </div>
    `;
}

// Fonction de paiement (placeholder)
function checkout() {
    alert('Fonctionnalité de paiement non implémentée dans cette version de démonstration.');
}

// Mettre à jour la quantité
async function updateQuantity(cartId, itemId, newQuantity) {
    if (newQuantity < 1) {
        alert('La quantité doit être au moins 1');
        loadCart();
        return;
    }
    
    try {
        const response = await fetch('api/cart.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                cart_id: cartId,
                quantity: parseInt(newQuantity)
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            loadCart();
            updateCartCount();
        } else {
            alert(data.message);
            loadCart();
        }
    } catch (error) {
        console.error('Erreur:', error);
        alert('Erreur lors de la mise à jour');
        loadCart();
    }
}

// Supprimer du panier
async function removeFromCart(cartId) {
    if (!confirm('Voulez-vous vraiment supprimer cet article ?')) {
        return;
    }
    
    try {
        const response = await fetch('api/cart.php', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ cart_id: cartId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Animation de suppression
            const itemElement = document.getElementById(`cart-item-${cartId}`);
            if (itemElement) {
                itemElement.style.transition = 'opacity 0.3s, transform 0.3s';
                itemElement.style.opacity = '0';
                itemElement.style.transform = 'translateX(-100px)';
                
                setTimeout(() => {
                    loadCart();
                    updateCartCount();
                }, 300);
            }
        } else {
            alert(data.message);
        }
    } catch (error) {
        console.error('Erreur:', error);
        alert('Erreur lors de la suppression');
    }
}

// Vider le panier
async function clearCart() {
    if (!confirm('Voulez-vous vraiment vider votre panier ?')) {
        return;
    }
    
    try {
        const response = await fetch('api/cart.php?action=clear', {
            method: 'POST'
        });
        
        const data = await response.json();
        
        if (data.success) {
            loadCart();
            updateCartCount();
        } else {
            alert(data.message);
        }
    } catch (error) {
        console.error('Erreur:', error);
        alert('Erreur lors du vidage du panier');
    }
}