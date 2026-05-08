const API_URL = '../api';

let currentUser = null;
let cart = JSON.parse(localStorage.getItem('shoe_cart')) || [];
let products = [];
let wishlist = [];

function showToast(message, type = 'success') {
    const container = document.getElementById('toast-container');
    if (!container) return;
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `<span>${message}</span>`;
    container.appendChild(toast);
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(-10px)';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Ensure auth check handles the strict redirect flow without caching loops
async function checkAuth() {
    try {
        const res = await fetch(`${API_URL}/auth.php?action=check&t=${Date.now()}`, {
            headers: { 'Cache-Control': 'no-cache, no-store, must-revalidate' },
            credentials: 'include'
        });
        const data = await res.json();
        
        const path = window.location.pathname;
        const isAuthPage = path.endsWith('login.html') || path.endsWith('register.html');

        if (data.authenticated) {
            currentUser = data.user;
            
            // Both Admin and User should go to the storefront from auth pages
            if (isAuthPage) {
                window.location.href = 'index.html';
                return;
            }

            // If user tries to access admin page
            if (path.endsWith('admin_dashboard.html') && currentUser.role !== 'admin') {
                window.location.href = 'index.html';
                return;
            }
            
            updateNavAuth();
            // Wishlist fetch should be handled by individual pages if needed
        } else {
            // Not authenticated: Force redirect to login unless already on auth page
            if (!isAuthPage) {
                window.location.href = 'login.html';
                return;
            }
        }
    } catch (e) {
        console.error('Auth check failed', e);
        if (!window.location.pathname.endsWith('login.html') && !window.location.pathname.endsWith('register.html')) {
            window.location.href = 'login.html';
        }
    }
}

function updateNavAuth() {
    const topBarLinks = document.getElementById('top-bar-auth');
    if (topBarLinks && currentUser) {
        let links = `
            <a href="#">Help</a>
            <a href="#">Hi, ${currentUser.username}</a>
            <a href="#" onclick="logout(); return false;">Log Out</a>
        `;
        if (currentUser.role === 'admin') {
            links += `<a href="admin_dashboard.html">Dashboard</a>`;
        }
        topBarLinks.innerHTML = links;
    }
}

async function logout() {
    await fetch(`${API_URL}/auth.php?action=logout`, { credentials: 'include' });
    currentUser = null;
    window.location.href = 'login.html';
}

async function fetchProducts(filters = {}) {
    const queryParams = new URLSearchParams(filters).toString();
    const res = await fetch(`${API_URL}/products.php?${queryParams}`, { credentials: 'include' });
    products = await res.json();
    renderProducts();
}

function renderProducts() {
    const container = document.getElementById('products-grid');
    if (!container) return;
    
    if (products.length === 0) {
        container.innerHTML = `<p style="grid-column: 1/-1; text-align: center; color: var(--text-secondary); padding: 40px;">No products found.</p>`;
        return;
    }
    
    container.innerHTML = products.map(p => `
        <div class="product-card" onclick="location.href='#'">
            <div class="product-image">
                <img src="${p.image_url}" alt="${p.name}">
            </div>
            <div class="product-info">
                <div class="product-details">
                    <h3>${p.name}</h3>
                    <p>${p.type.charAt(0).toUpperCase() + p.type.slice(1)} Shoe</p>
                    <p>Size: ${p.size || 'N/A'}</p>
                </div>
                <div class="product-price">₹${p.price}</div>
            </div>
            <div style="margin-top: 12px; display:flex; gap:8px">
                <button class="btn" style="padding: 8px 16px; font-size:14px" onclick="event.stopPropagation(); addToCart(${p.id})">Add to Bag</button>
                ${currentUser && currentUser.role === 'user' ? `<button class="icon-btn" onclick="event.stopPropagation(); addToWishlist(${p.id})" title="Wishlist">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                </button>` : ''}
            </div>
        </div>
    `).join('');
}

function addToCart(productId) {
    const product = products.find(p => p.id == productId);
    if (!product) return;
    
    if (product.stock <= 0) {
        showToast('Out of stock! We will notify you.', 'error');
        return;
    }
    
    const existing = cart.find(i => i.id == productId);
    if (existing) existing.quantity += 1;
    else cart.push({ ...product, quantity: 1 });
    
    saveCart();
    showToast(`Added ${product.name} to Bag`);
}

function saveCart() {
    localStorage.setItem('shoe_cart', JSON.stringify(cart));
    const countEl = document.getElementById('cart-count');
    if (countEl) countEl.innerText = cart.reduce((a,b) => a + b.quantity, 0);
    if (window.location.pathname.endsWith('cart.html')) renderCart();
}

// Call checkAuth on init
document.addEventListener('DOMContentLoaded', () => {
    if(!document.getElementById('toast-container')){
        const tc = document.createElement('div');
        tc.id = 'toast-container';
        document.body.appendChild(tc);
    }
    
    // Set initial cart count
    const countEl = document.getElementById('cart-count');
    if (countEl) countEl.innerText = cart.reduce((a,b) => a + b.quantity, 0);

    checkAuth();
});

// Expose filters globally
window.setFilter = function(key, value) {
    const filters = {};
    filters[key] = value;
    fetchProducts(filters);
}
