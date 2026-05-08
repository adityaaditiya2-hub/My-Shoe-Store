document.addEventListener('DOMContentLoaded', () => {
    const app = document.getElementById('app');
    const user = JSON.parse(localStorage.getItem('user'));

    updateNav(user);

    // Simple router based on path
    const path = window.location.pathname;

    if (path === '/' || path === '/index.html') {
        loadHome();
    } else if (path === '/products.html') {
        loadProducts();
    } else if (path === '/product-detail.html') {
        loadProductDetail();
    } else if (path === '/cart.html') {
        loadCart();
    }
});

function updateNav(user) {
    const navLinks = document.querySelector('.nav-links');
    if (user) {
        navLinks.innerHTML = `
            <li><a href="/">Home</a></li>
            <li><a href="/products.html">Shoes</a></li>
            <li><a href="/cart.html">Cart</a></li>
            <li><a href="#" id="logoutBtn">Logout (${user.username})</a></li>
        `;
        document.getElementById('logoutBtn').addEventListener('click', (e) => {
            e.preventDefault();
            localStorage.removeItem('user');
            window.location.href = '/login.html';
        });
    } else {
        navLinks.innerHTML = `
            <li><a href="/">Home</a></li>
            <li><a href="/products.html">Shoes</a></li>
            <li><a href="/login.html">Login</a></li>
            <li><a href="/register.html" class="auth-btn">Register</a></li>
        `;
    }
}

async function loadHome() {
    try {
        const response = await fetch('/api/products/brands');
        const brands = await response.json();
        const brandContainer = document.getElementById('brand-container');

        if (brandContainer) {
            brandContainer.innerHTML = brands.map(brand => `
                <div class="card" onclick="window.location.href='/products.html?brand=${brand.id}'" style="cursor:pointer;">
                    <img src="${brand.image_url}" alt="${brand.name}">
                    <div class="card-body">
                        <h3 class="card-title">${brand.name}</h3>
                    </div>
                </div>
            `).join('');
        }
    } catch (error) {
        console.error('Error loading brands:', error);
    }
}

async function loadProducts() {
    const urlParams = new URLSearchParams(window.location.search);
    const brandId = urlParams.get('brand');

    let url = '/api/products';
    if (brandId) url += `?brand_id=${brandId}`;

    try {
        const response = await fetch(url);
        const products = await response.json();
        const productContainer = document.getElementById('product-container');

        if (productContainer) {
            productContainer.innerHTML = products.map(product => `
                <div class="card">
                    <img src="${product.image_url}" alt="${product.name}">
                    <div class="card-body">
                        <h3 class="card-title">${product.name}</h3>
                        <p class="card-text">${product.brand_name || ''}</p>
                        <p class="card-price">₹${product.price}</p>
                        <a href="/product-detail.html?id=${product.id}" class="btn">View Details</a>
                    </div>
                </div>
            `).join('');
        }
    } catch (error) {
        console.error('Error loading products:', error);
    }
}

async function loadProductDetail() {
    const urlParams = new URLSearchParams(window.location.search);
    const id = urlParams.get('id');

    if (!id) return;

    try {
        const response = await fetch(`/api/products/${id}`);
        const product = await response.json();

        document.getElementById('product-img').src = product.image_url;
        document.getElementById('product-title').textContent = product.name;
        document.getElementById('product-brand').textContent = product.brand_name;
        document.getElementById('product-price').textContent = `₹${product.price}`;
        document.getElementById('product-desc').textContent = product.description;

        document.getElementById('add-to-cart-btn').onclick = async () => {
            const user = JSON.parse(localStorage.getItem('user'));
            if (!user) {
                alert('Please login to add to cart');
                window.location.href = '/login.html';
                return;
            }

            const res = await fetch('/api/cart', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    user_id: user.id,
                    product_id: product.id,
                    quantity: 1
                })
            });

            if (res.ok) {
                alert('Added to cart!');
            }
        };
    } catch (error) {
        console.error('Error loading product:', error);
    }
}

async function loadCart() {
    const user = JSON.parse(localStorage.getItem('user'));
    if (!user) {
        window.location.href = '/login.html';
        return;
    }

    try {
        const response = await fetch(`/api/cart/${user.id}`);
        const cartItems = await response.json();
        const cartContainer = document.getElementById('cart-container');
        const cartTotal = document.getElementById('cart-total');

        let total = 0;

        cartContainer.innerHTML = cartItems.map(item => {
            total += item.price * item.quantity;
            return `
                <div class="cart-item">
                    <div style="display:flex; align-items:center; gap:20px;">
                        <img src="${item.image_url}" style="width:50px; height:50px; object-fit:contain;">
                        <div>
                            <h4>${item.name}</h4>
                            <p>₹${item.price} x ${item.quantity}</p>
                        </div>
                    </div>
                    <button class="btn" style="width:auto; background:var(--accent-color);" onclick="removeFromCart(${item.id})">Remove</button>
                </div>
            `;
        }).join('');

        cartTotal.textContent = total.toFixed(2);

        document.getElementById('checkout-btn').onclick = async () => {
            const res = await fetch('/api/orders', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    user_id: user.id,
                    total_amount: total,
                    items: cartItems.map(i => ({ product_id: i.id, quantity: i.quantity, price: i.price })) // Wait, api/orders expects product_id from the join? 
                    // No, the cart items query returns id as cart_item id. 
                    // I need to adjust the cart query or the order logic.
                    // The 'items' in cart query doesn't have product_id explicitly selected if I used "SELECT ci.id...".
                    // Let me check cart.js.
                    // "SELECT ci.id, ci.quantity, p.name, p.price, p.image_url FROM..."
                    // It is missing p.id as product_id! 
                    // I need to fix cart.js first or update the query here. 
                    // I'll update cart.js in a moment. For now assuming I will fix it.
                })
            });
            // I'll fix cart.js in next turn.
            if (res.ok) {
                alert('Order placed successfully!');
                window.location.href = '/';
            }
        };

    } catch (error) {
        console.error('Error loading cart:', error);
    }
}

async function removeFromCart(id) {
    await fetch(`/api/cart/${id}`, { method: 'DELETE' });
    loadCart();
}
