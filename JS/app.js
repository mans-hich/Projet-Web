// État global
let currentUser = null;
let allProducts = [];
let categories = [];

// Vérifier l'authentification
async function checkAuth() {
    try {
        const response = await fetch('api/auth.php?action=check');
        const data = await response.json();
        
        if (data.loggedIn) {
            currentUser = data.user;
            updateAuthUI();
        }
    } catch (error) {
        console.error('Erreur:', error);
    }
}

// Mettre à jour l'interface utilisateur selon l'état de connexion
function updateAuthUI() {
    const authLinks = document.getElementById('authLinks');
    
    if (currentUser) {
        authLinks.innerHTML = `
            <span style="margin-right: 1rem;">Bonjour, ${currentUser.username}</span>
            ${currentUser.role === 'admin' ? '<a href="admin.html">Admin</a>' : ''}
            <button class="btn btn-danger btn-sm" onclick="logout()">Déconnexion</button>
        `;
    } else {
        authLinks.innerHTML = '<a href="login.html" class="btn btn-primary btn-sm">Connexion</a>';
    }
}

// Déconnexion
async function logout() {
    try {
        const response = await fetch('api/auth.php?action=logout', {
            method: 'POST'
        });
        
        const data = await response.json();
        
        if (data.success) {
            currentUser = null;
            window.location.href = 'index.html';
        }
    } catch (error) {
        console.error('Erreur:', error);
    }
}

// Charger les catégories
async function loadCategories() {
    try {
        const response = await fetch('api/products.php?action=categories');
        const data = await response.json();
        
        if (data.success) {
            categories = data.categories;
            
            const categorySelects = document.querySelectorAll('#categoryFilter, #productCategory, #editProductCategory');
            categorySelects.forEach(select => {
                if (select.id === 'categoryFilter') {
                    select.innerHTML = '<option value="">Toutes les catégories</option>';
                } else {
                    select.innerHTML = '<option value="">Aucune</option>';
                }
                
                categories.forEach(cat => {
                    select.innerHTML += `<option value="${cat.id}">${cat.name}</option>`;
                });
            });
        }
    } catch (error) {
        console.error('Erreur:', error);
    }
}

// Charger tous les produits
async function loadProducts() {
    try {
        const response = await fetch('api/products.php');
        const data = await response.json();
        
        if (data.success) {
            allProducts = data.products;
            displayProducts(allProducts);
        }
    } catch (error) {
        console.error('Erreur:', error);
    }
}

// Afficher les produits
function displayProducts(products) {
    const container = document.getElementById('productsContainer');
    
    if (products.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <p>Aucun produit trouvé</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = `
        <div class="products-grid">
            ${products.map(product => `
                <div class="product-card" onclick="window.location.href='product.html?id=${product.id}'">
                    <img src="${product.image_url}" alt="${product.name}" class="product-image">
                    <div class="product-info">
                        <div class="product-category">${product.category_name || 'Sans catégorie'}</div>
                        <h3 class="product-name">${product.name}</h3>
                        <p class="product-description">${product.description}</p>
                        <div class="product-price">${product.price} €</div>
                        <button class="btn btn-primary" onclick="event.stopPropagation(); quickAddToCart(${product.id})">
                            Ajouter au panier
                        </button>
                    </div>
                </div>
            `).join('')}
        </div>
    `;
}

// Ajout rapide au panier
async function quickAddToCart(productId) {
    if (!currentUser) {
        alert('Veuillez vous connecter pour ajouter au panier');
        window.location.href = 'login.html';
        return;
    }
    
    try {
        const response = await fetch('api/cart.php?action=add', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                item_id: productId,
                quantity: 1
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('Produit ajouté au panier !');
            updateCartCount();
        } else {
            alert(data.message);
        }
    } catch (error) {
        console.error('Erreur:', error);
    }
}

// Recherche de produits
async function searchProducts() {
    const searchTerm = document.getElementById('searchInput').value;
    
    if (!searchTerm.trim()) {
        loadProducts();
        return;
    }
    
    try {
        const response = await fetch(`api/products.php?search=${encodeURIComponent(searchTerm)}`);
        const data = await response.json();
        
        if (data.success) {
            displayProducts(data.products);
        }
    } catch (error) {
        console.error('Erreur:', error);
    }
}

// Appliquer les filtres
async function applyFilters() {
    const searchTerm = document.getElementById('searchInput')?.value || '';
    const category = document.getElementById('categoryFilter')?.value || '';
    const minPrice = document.getElementById('minPrice')?.value || '';
    const maxPrice = document.getElementById('maxPrice')?.value || '';
    const sortBy = document.getElementById('sortBy')?.value || 'name';
    
    let url = 'api/products.php?';
    const params = [];
    
    if (searchTerm) params.push(`search=${encodeURIComponent(searchTerm)}`);
    if (category) params.push(`category=${category}`);
    if (minPrice) params.push(`min_price=${minPrice}`);
    if (maxPrice) params.push(`max_price=${maxPrice}`);
    
    // Gérer le tri
    if (sortBy.includes('_desc')) {
        const field = sortBy.replace('_desc', '');
        params.push(`sort=${field}&order=DESC`);
    } else {
        params.push(`sort=${sortBy}&order=ASC`);
    }
    
    url += params.join('&');
    
    try {
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.success) {
            displayProducts(data.products);
        }
    } catch (error) {
        console.error('Erreur:', error);
    }
}

// Réinitialiser les filtres
function resetFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('categoryFilter').value = '';
    document.getElementById('minPrice').value = '';
    document.getElementById('maxPrice').value = '';
    document.getElementById('sortBy').value = 'name';
    loadProducts();
}

// Mettre à jour le compteur de panier
async function updateCartCount() {
    if (!currentUser) {
        const countElements = document.querySelectorAll('.cart-count');
        countElements.forEach(el => el.textContent = '0');
        return;
    }
    
    try {
        const response = await fetch('api/cart.php');
        const data = await response.json();
        
        if (data.success) {
            const countElements = document.querySelectorAll('.cart-count');
            countElements.forEach(el => el.textContent = data.itemCount);
        }
    } catch (error) {
        console.error('Erreur:', error);
    }
}

// Permettre la recherche avec Enter
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                searchProducts();
            }
        });
    }
});