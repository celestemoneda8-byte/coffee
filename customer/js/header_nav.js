// --- UPDATE CART BADGE ---
function updateCartBadge(count) {
    const badge = document.getElementById("cart-count");
    if (!badge) return;
    badge.innerText = count;
    badge.style.display = count > 0 ? "flex" : "none";
}

// Resolve image path: accept full path or filename stored in DB
function resolveImg(src) {
    const fallback = 'images/coffee.jpg';
    if (!src) return fallback;
    if (src.startsWith('http://') || src.startsWith('https://') || src.startsWith('/')) return src;
    if (src.startsWith('images/')) return src;
    return `images/${src}`;
}

// --- FETCH CART COUNT FROM SERVER ---
async function fetchCartCount() {
    try {
        const res = await fetch("../api/cart/get_cart_count.php");
        const data = await res.json();
        if (data.status === "success") updateCartBadge(data.count);
    } catch (err) {
        console.error(err);
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
            container.innerHTML = '<p class="text-center text-muted">Your cart is empty</p>';
            totalEl.innerText = "₱0.00";
            updateCartBadge(0);
            return;
        }

        let total = 0;
        container.innerHTML = "";
        cartItems.forEach(item => {
            total += item.subtotal;
            const el = document.createElement("div");
            el.style.display = "flex";
            el.style.gap = "8px";
            el.style.alignItems = "center";
            el.style.marginBottom = "8px";
            el.innerHTML = `
                <img src="${resolveImg(item.img)}" style="width:64px; height:48px; object-fit:cover; border-radius:6px;">
                <div style="flex:1;">
                    <div style="font-weight:600;">${item.name}</div>
                    <div style="font-size:13px; color:#666;">₱${item.price} × ${item.qty}</div>
                </div>
                <div style="font-weight:600;">₱${item.subtotal.toFixed(2)}</div>
            `;
            container.appendChild(el);
        });

        totalEl.innerText = `₱${total.toFixed(2)}`;
        updateCartBadge(data.cart_count);
    } catch (err) {
        console.error(err);
    }
}

// --- INITIAL LOAD ---
fetchCartCount();
loadCartPreview();
