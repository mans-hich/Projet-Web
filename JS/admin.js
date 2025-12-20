let adminProducts = [];

// Charger les statistiques
async function loadStats() {
    try {
        const response = await fetch('api/admin.php?action=stats');
        const data = await response.json();
        
        if (data.success) {
            displayStats(data.stats);
        }
    } catch (error) {
        console.error('Erreur:', error);
    }
}

// Afficher les statistiques
function displayStats(stats) {
    const container = document.getElementById('statsGrid');
    
    container.innerHTML = `
        <div class="stat-card">
            <div class="stat-value">${stats.totalProducts}</div>
            <div class="stat-label">Produits</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-value">${stats.totalUsers}</div>
            <div class="stat-label">Utilisateurs</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-value">${stats.stockValue.toFixed(2)} €</div>
            <div class="stat-label">Valeur du stock</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-value">${stats.outOfStock}</div>
            <div class="stat-label">Ruptures de stock</div>
        </div>
    `;
}

// Charger les produits pour l'admin
async function loadAdminProducts() {
    try {
        const response = await fetch('api/products.php');
        const data = await response.json();
        
        if (data.success) {
            adminProducts = data.products;
            displayAdminProducts(data.products);
        }
    } catch (error) {
        console.error('Erreur:', error);
    }
}

// Afficher les produits dans le tableau admin
function displayAdminProducts(products) {
    const container = document.getElementById('productsTable');
    
    if (products.length === 0) {
        container.innerHTML = '<p style="padding: 2rem; text-align: center;">Aucun produit</p>';
        return;
    }
    
    container.innerHTML = `
        <table>
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Nom</th>
                    <th>Catégorie</th>
                    <th>Prix</th>
                    <th>Stock</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                ${products.map(product => `
                    <tr>
                        <td><img src="${product.image_url}" alt="${product.name}"></td>
                        <td>${product.name}</td>
                        <td>${product.category_name || 'N/A'}</td>
                        <td>${product.price} €</td>
                        <td>${product.stock}</td>
                        <td>
                            <button class="btn btn-primary btn-sm" onclick="openEditModal(${product.id})">
                                Modifier
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="deleteProduct(${product.id})">
                                Supprimer
                            </button>
                        </td>
                    </tr>
                `).join('')}
            </tbody>
        </table>
    `;
}

// Ajouter un produit
async function addProduct(event) {
    event.preventDefault();
    
    const productData = {
        name: document.getElementById('productName').value,
        description: document.getElementById('productDescription').value,
        price: parseFloat(document.getElementById('productPrice').value),
        category_id: document.getElementById('productCategory').value || null,
        stock: parseInt(document.getElementById('productStock').value),
        image_url: document.getElementById('productImage').value || null
    };
    
    const messageDiv = document.getElementById('addProductMessage');
    
    try {
        const response = await fetch('api/admin.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(productData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            messageDiv.innerHTML = '<div class="alert alert-success">Produit ajouté avec succès !</div>';
            document.getElementById('addProductForm').reset();
            loadAdminProducts();
            loadStats();
            
            setTimeout(() => {
                messageDiv.innerHTML = '';
            }, 3000);
        } else {
            messageDiv.innerHTML = `<div class="alert alert-error">${data.message}</div>`;
        }
    } catch (error) {
        console.error('Erreur:', error);
        messageDiv.innerHTML = '<div class="alert alert-error">Erreur lors de l\'ajout</div>';
    }
}

// Ouvrir le modal d'édition
function openEditModal(productId) {
    const product = adminProducts.find(p => p.id === productId);
    
    if (!product) return;
    
    document.getElementById('editProductId').value = product.id;
    document.getElementById('editProductName').value = product.name;
    document.getElementById('editProductDescription').value = product.description;
    document.getElementById('editProductPrice').value = product.price;
    document.getElementById('editProductCategory').value = product.category_id || '';
    document.getElementById('editProductStock').value = product.stock;
    document.getElementById('editProductImage').value = product.image_url || '';
    
    document.getElementById('editModal').style.display = 'block';
}

// Fermer le modal d'édition
function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
    document.getElementById('editProductMessage').innerHTML = '';
}

// Mettre à jour un produit
async function updateProduct(event) {
    event.preventDefault();
    
    const productData = {
        id: parseInt(document.getElementById('editProductId').value),
        name: document.getElementById('editProductName').value,
        description: document.getElementById('editProductDescription').value,
        price: parseFloat(document.getElementById('editProductPrice').value),
        category_id: document.getElementById('editProductCategory').value || null,
        stock: parseInt(document.getElementById('editProductStock').value),
        image_url: document.getElementById('editProductImage').value || null
    };
    
    const messageDiv = document.getElementById('editProductMessage');
    
    try {
        const response = await fetch('api/admin.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(productData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            messageDiv.innerHTML = '<div class="alert alert-success">Produit mis à jour !</div>';
            loadAdminProducts();
            loadStats();
            
            setTimeout(() => {
                closeEditModal();
            }, 1500);
        } else {
            messageDiv.innerHTML = `<div class="alert alert-error">${data.message}</div>`;
        }
    } catch (error) {
        console.error('Erreur:', error);
        messageDiv.innerHTML = '<div class="alert alert-error">Erreur lors de la mise à jour</div>';
    }
}

// Supprimer un produit
async function deleteProduct(productId) {
    if (!confirm('Êtes-vous sûr de vouloir supprimer ce produit ? Cette action est irréversible.')) {
        return;
    }
    
    try {
        const response = await fetch('api/admin.php', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: productId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('Produit supprimé avec succès');
            loadAdminProducts();
            loadStats();
        } else {
            alert(data.message);
        }
    } catch (error) {
        console.error('Erreur:', error);
        alert('Erreur lors de la suppression');
    }
}

// Fermer le modal en cliquant en dehors
document.addEventListener('click', (e) => {
    const modal = document.getElementById('editModal');
    if (e.target === modal) {
        closeEditModal();
    }
});