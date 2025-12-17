// ========== HEADER NAV JS ==========

// --- UPDATE CART BADGE ---
function updateCartBadge(count) {
    const badge = document.getElementById("cart-count");
    if (!badge) return;
    const numCount = Number(count) || 0;
    badge.innerText = numCount;
    badge.style.display = numCount > 0 ? "flex" : "none";
    badge.setAttribute('data-count', numCount);
}

// Resolve image path: accept full path or filename stored in DB
function resolveImg(src) {
    const fallback = '../images/coffee.jpg';
    if (!src) return fallback;
    if (src.startsWith('http://') || src.startsWith('https://') || src.startsWith('/')) return src;
    if (src.startsWith('../images/')) return src;
    return `../images/${src}`;
}

// --- FETCH CART COUNT FROM SERVER ---
async function fetchCartCount() {
    try {
        const res = await fetch("../api/cart/get_cart_count.php");
        const data = await res.json();
        if (data.status === "success") {
            updateCartBadge(data.count);
        }
    } catch (err) {
        console.error("Error fetching cart count:", err);
    }
}

// --- LOAD CART PREVIEW ---
async function loadCartPreview() {
    const container = document.getElementById("cart-preview-body");
    const totalEl = document.getElementById("cart-preview-total");
    if (!container || !totalEl) return;

    try {
        const res = await fetch("../api/cart/get_cart.php");
        const data = await res.json();
        const cartItems = data.cart || [];

        if (cartItems.length === 0) {
            container.innerHTML = '<p class="text-center text-muted" style="padding:20px 0;">Your cart is empty</p>';
            totalEl.innerText = "₱0.00";
            updateCartBadge(0);
            return;
        }

        let total = 0;
        container.innerHTML = "";
        cartItems.forEach(item => {
            const subtotal = Number(item.subtotal) || (Number(item.price) * Number(item.qty));
            total += subtotal;
            
            const el = document.createElement("div");
            el.className = "cart-preview-item";
            el.innerHTML = `
                <img src="${resolveImg(item.img || item.image)}" alt="${item.name || item.product_name || 'Item'}">
                <div class="cart-preview-item-info">
                    <div class="cart-preview-item-name">${item.name || item.product_name || 'Item'}</div>
                    <div class="cart-preview-item-meta">₱${Number(item.price).toFixed(2)} × ${item.qty}</div>
                </div>
                <div class="cart-preview-item-subtotal">₱${subtotal.toFixed(2)}</div>
            `;
            container.appendChild(el);
        });

        totalEl.innerText = `₱${total.toFixed(2)}`;
        updateCartBadge(data.cart_count || cartItems.length);
    } catch (err) {
        console.error("Error loading cart preview:", err);
        container.innerHTML = '<p class="text-center text-muted">Error loading cart</p>';
    }
}

// --- CART PREVIEW TOGGLE ---
function setupCartPreview() {
    const cartBtn = document.getElementById("icon-cart");
    const cartPreview = document.getElementById("cart-preview");
    const closeBtn = document.getElementById("close-cart-preview");
    
    if (!cartBtn || !cartPreview) return;
    
    // Toggle cart preview on cart icon click
    cartBtn.addEventListener("click", (e) => {
        e.stopPropagation();
        cartPreview.classList.toggle("show");
        if (cartPreview.classList.contains("show")) {
            loadCartPreview();
        }
    });
    
    // Close button
    if (closeBtn) {
        closeBtn.addEventListener("click", () => {
            cartPreview.classList.remove("show");
        });
    }
    
    // Close when clicking outside
    document.addEventListener("click", (e) => {
        if (!cartPreview.contains(e.target) && e.target !== cartBtn && !cartBtn.contains(e.target)) {
            cartPreview.classList.remove("show");
        }
    });
}

// --- SIDEBAR TOGGLE (enhanced) ---
function setupSidebar() {
    const sidebar = document.getElementById("sidebar");
    const toggleBtn = document.getElementById("toggle-btn-menu");
    const overlay = document.getElementById("sidebar-overlay");
    
    if (!sidebar || !toggleBtn) return;
    
    toggleBtn.addEventListener("click", () => {
        // On mobile, use 'show' class; on desktop use 'collapsed'
        if (window.innerWidth <= 768) {
            sidebar.classList.toggle("show");
            if (overlay) overlay.classList.toggle("show");
        } else {
            sidebar.classList.toggle("collapsed");
        }
    });
    
    // Close sidebar when clicking overlay
    if (overlay) {
        overlay.addEventListener("click", () => {
            sidebar.classList.remove("show");
            overlay.classList.remove("show");
        });
    }
}

// --- INITIAL LOAD ---
document.addEventListener("DOMContentLoaded", () => {
    fetchCartCount();
    setupCartPreview();
    setupSidebar();
});

// Export for use by other scripts
window.updateCartBadge = updateCartBadge;
window.loadCartPreview = loadCartPreview;
window.fetchCartCount = fetchCartCount;
