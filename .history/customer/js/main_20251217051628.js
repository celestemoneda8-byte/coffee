// -------- CONFIG / STATE --------
let coffeeMenu = [];
let cart = JSON.parse(localStorage.getItem("cart")) || [];
let SERVER_CART = []; // cached server cart for the cart page
const injectedSearchQuery = (typeof searchQuery !== 'undefined') ? String(searchQuery).trim() : '';

// -------- HELPERS --------
function resolveImg(src) {
    const fallback = '../images/coffee.jpg';
    if (!src) return fallback;
    if (src.startsWith('http://') || src.startsWith('https://') || src.startsWith('/')) return src;
    if (src.startsWith('../images/')) return src;
    return `../images/${src}`;
}

function escapeHtml(s) {
    return String(s || '').replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' })[c]);
}

// Minimal notification helper. If bootstrap toasts or other library is present, replace as needed.
function showNotification(message, type = "info", timeout = 2500) {
    // type: "success", "error", "info"
    try {
        // Try toast area
        const containerId = 'main-notification-container';
        let container = document.getElementById(containerId);
        if (!container) {
            container = document.createElement('div');
            container.id = containerId;
            container.style.position = 'fixed';
            container.style.top = '12px';
            container.style.right = '12px';
            container.style.zIndex = 10500;
            document.body.appendChild(container);
        }
        const bg = (type === 'success') ? '#198754' : (type === 'error' ? '#dc3545' : '#6c757d');
        const el = document.createElement('div');
        el.style.background = bg;
        el.style.color = '#fff';
        el.style.padding = '10px 14px';
        el.style.marginTop = '8px';
        el.style.borderRadius = '6px';
        el.style.boxShadow = '0 2px 8px rgba(0,0,0,0.12)';
        el.style.fontSize = '14px';
        el.innerText = message;
        container.appendChild(el);
        setTimeout(() => {
            el.style.transition = 'opacity 250ms';
            el.style.opacity = '0';
            setTimeout(() => el.remove(), 300);
        }, timeout);
    } catch (e) {
        // fallback
        alert(message);
    }
}

// Update cart badge (element with id "cart-count" or [data-cart-count])
function updateCartBadge(count) {
    try {
        const el = document.getElementById('cart-count') || document.querySelector('[data-cart-count]');
        if (el) {
            el.innerText = Number(count || 0);
            // ensure visible
            el.style.display = 'inline-block';
        }
    } catch (e) {
        console.warn('updateCartBadge error', e);
    }
}

// Small visual emphasis to show the badge updated (no message toast)
function highlightCartBadge() {
    try {
        const el = document.getElementById('cart-count') || document.querySelector('[data-cart-count]');
        if (!el) return;
        // apply a quick scale pulse
        el.style.transition = 'transform 160ms ease, box-shadow 160ms ease';
        el.style.transformOrigin = 'center';
        el.style.transform = 'scale(1.25)';
        el.style.boxShadow = '0 4px 12px rgba(0,0,0,0.12)';
        setTimeout(() => {
            el.style.transform = 'scale(1)';
            el.style.boxShadow = '';
            // cleanup transition after animation
            setTimeout(() => {
                el.style.transition = '';
            }, 160);
        }, 160);
    } catch (e) {
        // noop
    }
}

// Ensure a minimal visible badge exists if none is present.
// This will create a small badge anchored to the top-right of the page if the site doesn't provide one.
function ensureCartBadgeElement() {
    try {
        let el = document.getElementById('cart-count') || document.querySelector('[data-cart-count]');
        if (el) return el;
        // create a minimal badge attached to body top-right near presumed cart icon
        el = document.createElement('div');
        el.id = 'cart-count';
        el.setAttribute('data-cart-count', '');
        el.innerText = '0';
        el.style.position = 'fixed';
        el.style.top = '12px';
        el.style.right = '12px';
        el.style.minWidth = '28px';
        el.style.height = '28px';
        el.style.padding = '0 8px';
        el.style.display = 'inline-flex';
        el.style.alignItems = 'center';
        el.style.justifyContent = 'center';
        el.style.borderRadius = '14px';
        el.style.background = '#dc3545';
        el.style.color = '#fff';
        el.style.fontWeight = '700';
        el.style.zIndex = 10500;
        el.style.boxShadow = '0 2px 8px rgba(0,0,0,0.12)';
        document.body.appendChild(el);
        return el;
    } catch (e) {
        return null;
    }
}

// -------- LOAD PRODUCTS (MENU) --------
async function loadProductsAndMenu() {
    try {
        const res = await fetch("../api/products/get_products.php");
        const data = await res.json();
        if (data.status === "success") {
            coffeeMenu = (data.products || []).map(p => {
                let category = p.category ?? p.category_name ?? (p.category_id !== undefined ? String(p.category_id) : '');
                // If category is an array, take first element; otherwise use as-is
                if (Array.isArray(category)) {
                    category = category[0] || '';
                }
                return {
                    product_id: p.product_id ?? p.id ?? null,
                    name: p.name ?? p.product_name ?? '',
                    img: p.img ?? p.image ?? '',
                    price: p.price ?? 0,
                    category: String(category) // Keep original casing
                };
            });
            loadMenu("all");
            // If a search query was injected from server, apply it
            if (injectedSearchQuery) {
                applyInjectedSearch(injectedSearchQuery);
            }
        } else {
            console.error("Failed to load products:", data.message);
            const list = document.getElementById("coffee-list");
            if (list) {
                list.innerHTML = '<div class="no-products">No products available. Please check back later.</div>';
            }
        }
        await loadCartPreview();
    } catch (err) {
        console.error("Error loading products:", err);
        const list = document.getElementById("coffee-list");
        if (list) {
            list.innerHTML = '<div class="no-products">Error loading products. Please refresh the page.</div>';
        }
    }
}

// Apply injected search logic (moved from original menu.js)
function applyInjectedSearch(qRaw) {
    const q = String(qRaw).toLowerCase();
    // Try to match category first (exact match against data-category)
    const categoryBtns = Array.from(document.querySelectorAll(".filter-btn"));
    const matchBtn = categoryBtns.find(b => b.dataset.category && b.dataset.category.toLowerCase() === q);
    if (matchBtn) {
        document.querySelector(".filter-btn.active")?.classList.remove("active");
        matchBtn.classList.add("active");
        loadMenu(matchBtn.dataset.category);
    } else {
        // Try exact product name match (case-insensitive)
        const product = coffeeMenu.find(p => p.name && p.name.toLowerCase() === q);
        const list = document.getElementById("coffee-list");
        if (!list) return;
        if (product) {
            list.innerHTML = `
                <div class="col-12 col-md-6 col-lg-4 mb-3">
                    <div class="coffee-card shadow-sm d-flex flex-row align-items-center">
                        <div style="flex:1;">
                            <img src="${resolveImg(product.img)}" style="width:100%; height:170px; object-fit:cover; border-radius:10px;">
                        </div>
                        <div class="ms-3" style="flex:1;">
                            <h5 class="coffee-name">${product.name}</h5>
                            <p class="coffee-price">₱${product.price}</p>
                            <div class="d-flex justify-content-start align-items-center gap-2 mb-2">
                                <button class="btn btn-sm btn-outline-dark" onclick="changeQty(0, -1)">-</button>
                                <span class="fw-bold quantity" id="qty-0">1</span>
                                <button class="btn btn-sm btn-outline-dark" onclick="changeQty(0, 1)">+</button>
                            </div>
                            <button class="btn btn-primary w-100 mb-2" 
                                onclick="addToCart(${product.product_id}, parseInt(document.getElementById('qty-0').innerText))">
                            Add to Cart
                            </button>
                            <button class="btn btn-success w-100" 
                                onclick="showOrderSummary(${product.product_id}, parseInt(document.getElementById('qty-0').innerText))">
                            Order Now
                            </button>
                        </div>
                    </div>
                </div>`;
        } else {
            list.innerHTML = `<div class="col-12"><p class="text-center text-muted">No results for "${injectedSearchQuery}"</p></div>`;
        }
    }
}

// -------- FILTER BUTTONS BINDING --------
function bindFilterButtons() {
    document.querySelectorAll(".filter-btn").forEach(btn => {
        btn.addEventListener("click", () => {
            document.querySelector(".filter-btn.active")?.classList.remove("active");
            btn.classList.add("active");
            loadMenu(btn.dataset.category);
        });
    });
}

// -------- RENDER MENU --------
function loadMenu(category) {
    const list = document.getElementById("coffee-list");
    if (!list) return;
    list.innerHTML = "";

    coffeeMenu
        .filter(item => {
            if (category === "all") return true;
            if (!item.category) return false;
            try {
                const catStr = String(item.category).toLowerCase();
                const target = String(category).toLowerCase();
                return catStr.includes(target) || catStr === target;
            } catch {
                return false;
            }
        })
        .forEach((item, index) => {
            // create a grid cell wrapper so cards align in the 4-column grid
            const cell = document.createElement('div');
            cell.className = 'grid-item';

            // inner card markup unchanged (keeps visual design)
            cell.innerHTML = `
            <div class="card-list" style="padding:12px; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.05); background:#fff; box-sizing:border-box;">
                <div class="coffee-card p-3 shadow-sm d-flex flex-row align-items-center">
                    <div style="flex: 0 0 170px; max-width:170px;">
                        <img src="${resolveImg(item.img)}" style="width:100%; height:170px; object-fit:cover; border-radius:10px;">
                    </div>
                    <div class="ms-3" style="flex:1;">
                        <h5 class="coffee-name">${escapeHtml(item.name)}</h5>
                        <p class="coffee-price">₱${item.price}</p>
                        <div class="d-flex justify-content-start align-items-center gap-2 mb-2">
                            <button class="btn btn-sm btn-outline-dark" onclick="changeQty(${index}, -1)">-</button>
                            <span class="fw-bold quantity" id="qty-${index}">1</span>
                            <button class="btn btn-sm btn-outline-dark" onclick="changeQty(${index}, 1)">+</button>
                        </div>
                        <button class="btn btn-primary w-100 mb-2" 
                            onclick="addToCart(${item.product_id}, parseInt(document.getElementById('qty-${index}').innerText))">
                        Add to Cart
                        </button>
                        <button class="btn btn-success w-100" 
                            onclick="showOrderSummary(${item.product_id}, parseInt(document.getElementById('qty-${index}').innerText))">
                        Order Now
                        </button>
                    </div>
                </div>
            </div>`;

            list.appendChild(cell);
        });
}
// -------- MENU QUANTITY CONTROL --------
function changeQty(index, delta) {
    const el = document.getElementById(`qty-${index}`);
    if (!el) return;
    let qty = parseInt(el.innerText);
    qty += delta;
    if (qty < 1) qty = 1;
    el.innerText = qty;
}

// --------  ADD TO CART (menu) --------
// Behavior: update the visible cart badge count every time an item is added.
// Do not show a textual message notification — instead update the badge and pulse it so the user sees the count update.
// The function prefers calling the backend add endpoint; if unavailable it falls back to localStorage cart_count.
async function addToCart(productId, qty) {
    qty = Number(qty) || 1;
    // Ensure there is a badge element to show the count
    ensureCartBadgeElement();

    // Try backend first
    try {
        const res = await fetch('../api/cart/add_to_cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ product_id: productId, qty })
        });
        const data = await res.json();
        if (data && data.status === 'success') {
            // Prefer explicit cart_count from server if provided
            if (data.cart_count !== undefined) {
                updateCartBadge(data.cart_count);
                highlightCartBadge();
            } else if (Array.isArray(data.cart)) {
                updateCartBadge(data.cart.length);
                highlightCartBadge();
            } else {
                // if server didn't return counts, attempt to refresh preview which may update badge
                if (typeof window.loadCartPreview === 'function') {
                    try { await window.loadCartPreview(); } catch {}
                }
                // as a safety, try to read cart_count from response
                if (data.cart_count !== undefined) {
                    updateCartBadge(data.cart_count);
                    highlightCartBadge();
                }
            }
            return data;
        } else {
            // backend returned failure; fallback below
            console.warn('add_to_cart failed:', data && data.message);
        }
    } catch (err) {
        // endpoint may not exist or network error; fallback below
        console.warn('addToCart: backend add failed, falling back to local increment', err);
    }

    // Local fallback: increment a simple cart_count in localStorage and update badge (no message)
    try {
        const key = 'cart_count';
        let count = Number(localStorage.getItem(key) || 0);
        count += qty;
        localStorage.setItem(key, String(count));
        updateCartBadge(count);
        highlightCartBadge();

        // Also keep a very small local cart array in localStorage for compatibility
        try {
            const localCart = JSON.parse(localStorage.getItem('cart') || '[]');
            const pidStr = String(productId);
            const existing = localCart.find(i => String(i.product_id ?? i.id ?? '') === pidStr);
            if (existing) {
                existing.qty = Number(existing.qty || 0) + qty;
                existing.subtotal = Number(existing.price || 0) * existing.qty;
            } else {
                localCart.push({ product_id: productId, qty, price: 0, subtotal: 0 });
            }
            localStorage.setItem('cart', JSON.stringify(localCart));
        } catch (_) {}
    } catch (e) {
        console.warn('addToCart fallback error', e);
    }

    return Promise.resolve({ status: "fallback", cart_count: Number(localStorage.getItem('cart_count') || 0) });
}

// -------- ORDER SUMMARY (single product) --------
async function showOrderSummary(productId, qty) {
    try {
        const product = coffeeMenu.find(p => Number(p.product_id) === Number(productId));
        if (!product) {
            showNotification("Product not found.", "error");
            return;
        }

        const price = Number(product.price) || 0;
        const item = {
            product_id: product.product_id,
            img: product.img,
            name: product.name,
            price: price,
            qty: Number(qty) || 1,
            subtotal: price * (Number(qty) || 1)
        };

        const cartItems = [item];

        const subtotal = cartItems.reduce((s, it) => s + (Number(it.subtotal) || 0), 0);
        const total = subtotal;

        const orderSummary = { subtotal, total };

        // Display the modal/summary (external function handles rendering)
        if (typeof displayOrderSummaryModal === 'function') {
            displayOrderSummaryModal(cartItems, orderSummary);
        } else {
            // If modal script is not present, show a simple fallback summary
            showNotification(`Order: ${item.name} x${item.qty} — ₱${item.subtotal.toFixed(2)}`, "info", 4000);
        }

    } catch (err) {
        console.error(err);
        showNotification("Error loading order summary.", "error");
    }
}

// Generic remove from cart (used by menu preview and other parts)
async function removeFromCart(productId) {
    try {
        const res = await fetch('../api/cart/remove_from_cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ product_id: productId })
        });
        const data = await res.json();
        if (data.status === 'success') {
            await loadCartPreview();
            showNotification('Item removed from cart', 'success');
            // If server returned cart_count, update badge (this remains a textual notification for removal)
            if (data.cart_count !== undefined) {
                updateCartBadge(data.cart_count);
                highlightCartBadge();
            }
        } else {
            showNotification(data.message || 'Failed to remove item', 'error');
        }
    } catch (err) {
        console.error(err);
        showNotification('Error removing item from cart', 'error');
    }
}
// Updated loadCartPage: quantity controls removed from each cart card


async function changeQtyInPage(productId, delta) {
    const input = document.getElementById(`page-qty-${productId}`);
    if (!input) return;
    let qty = parseInt(input.value || '1');
    qty = Math.max(1, qty + delta);

    try {
        const res = await fetch('../api/cart/update_qty.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ product_id: productId, qty })
        });
        const data = await res.json();
        if (data.status === 'success') {
            input.value = qty;
            const item = SERVER_CART.find(i => String(i.id ?? i.product_id) === String(productId));
            if (item) {
                const price = Number(item.price ?? 0);
                const newSubtotal = price * qty;
                const subtotalEl = document.getElementById(`page-subtotal-${productId}`);
                if (subtotalEl) subtotalEl.innerText = newSubtotal.toFixed(2);
            }
            await reloadServerCartCacheAndRecalcTotal();
        } else {
            alert(data.message || 'Failed to update quantity.');
        }
    } catch (err) {
        console.error(err);
        alert('Error updating quantity.');
    }
}

async function reloadServerCartCacheAndRecalcTotal() {
    try {
        const res = await fetch('../api/cart/get_cart.php');
        const data = await res.json();
        SERVER_CART = data.cart || [];
        let total = 0;
        SERVER_CART.forEach(it => total += Number(it.subtotal ?? (it.price * it.qty) ?? 0));
        const totalEl = document.getElementById('total');
        if (totalEl) totalEl.innerText = total.toFixed(2);
    } catch (err) {
        console.error('Failed to reload cart', err);
    }
}

async function removeFromCartPage(productId) {
    if (!confirm('Remove this item from cart?')) return;
    try {
        const res = await fetch('../api/cart/remove_from_cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ product_id: productId })
        });
        const data = await res.json();
        if (data.status === 'success') {
            await loadCartPage();
            await loadCartPreview();
        } else {
            alert(data.message || 'Failed to remove item.');
        }
    } catch (err) {
        console.error(err);
        alert('Error removing item.');
    }
}

// Checkout selected items on cart page
async function checkoutSelected() {
    const checked = Array.from(document.querySelectorAll('.cart-item-checkbox:checked'));
    if (checked.length === 0) {
        alert('Please select items to checkout');
        return;
    }
    const selectedIds = checked.map(cb => cb.dataset.id).map(id => String(id));

    try {
        const res = await fetch('../api/cart/get_cart.php');
        const data = await res.json();
        if (data.status !== 'success') {
            alert('Failed to load cart items');
            return;
        }
        SERVER_CART = data.cart || [];
    } catch (err) {
        console.error(err);
        alert('Failed to fetch cart.');
        return;
    }

    const selectedItems = SERVER_CART
        .filter(it => selectedIds.includes(String(it.id ?? it.product_id ?? it.productId)))
        .map(it => ({
            product_id: it.product_id ?? it.id,
            name: it.product_name ?? it.name ?? 'Item',
            price: Number(it.price ?? 0),
            qty: Number(it.qty ?? 1),
            subtotal: Number(it.subtotal ?? (it.price * it.qty)),
            img: it.img ?? it.image ?? ''
        }));

    if (selectedItems.length === 0) {
        alert('No selected items found in cart.');
        return;
    }

    if (typeof displayOrderSummaryModal !== 'function') {
        alert('Order modal script not loaded. Please include assets/js/checkout.js on this page.');
        return;
    }

    displayOrderSummaryModal(selectedItems);
}

// -------- CART PAGE RENDERING (merged) --------
// Render the cart page (called from mainInit when #cart-page-items exists)
async function loadCartPage() {
    const container = document.getElementById('cart-page-items');
    if (!container) return;

    container.innerHTML = ''; // clear

    let data;
    try {
        const res = await fetch('../api/cart/get_cart.php');
        data = await res.json();
    } catch (err) {
        console.error('Failed to fetch cart', err);
        container.innerHTML = '<div class="text-center small-muted">Failed to load cart items.</div>';
        return;
    }

    const cartItems = (data && data.cart) ? data.cart : [];
    // store server cache used by other functions
    SERVER_CART = cartItems;

    if (!cartItems || cartItems.length === 0) {
        container.innerHTML = '<div class="text-center small-muted">Your cart is empty.</div>';
        // also clear total
        const totalEl = document.getElementById('total');
        if (totalEl) totalEl.innerText = '0.00';
        return;
    }

    // Top row: select all + count
    const top = document.createElement('div');
    top.className = 'cart-top mb-2';
    top.innerHTML = `
      <div class="left">
        <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer;">
          <input type="checkbox" id="cart-select-all" />
          <span class="title">Select items to include in total</span>
        </label>
        <div class="small-muted">(${cartItems.length} item${cartItems.length>1?'s':''})</div>
      </div>
      <div class="right small-muted">Select items and click "Checkout Selected" below</div>
    `;
    container.appendChild(top);

    // Items list
    cartItems.forEach(it => {
      const id = String(it.id ?? it.product_id ?? it.productId ?? '');
      const price = Number(it.price ?? 0);
      const qty = Number(it.qty ?? 1);
      const subtotal = Number(it.subtotal ?? (price * qty));

      const card = document.createElement('div');
      card.className = 'card-list cart-card';
      card.innerHTML = `
        <div class="cart-item">
          <div class="select-wrap">
            <input class="cart-item-checkbox" type="checkbox" data-id="${escapeHtml(id)}" data-price="${price}" />
          </div>

          <div class="cart-image">
            <img src="${escapeHtml(resolveImg(it.img ?? it.image ?? ''))}" alt="${escapeHtml(it.product_name ?? it.name ?? '')}" />
          </div>

          <div class="cart-body">
            <div class="name">${escapeHtml(it.product_name ?? it.name ?? 'Item')}</div>
            <div class="meta">
              <span class="small-muted">Price: ${formatCurrency(price)}</span>
              &nbsp;·&nbsp;
              <span class="small-muted">Quantity: <strong>${qty}</strong></span>
            </div>
            <div class="small-muted">${escapeHtml(it.description ?? '')}</div>
          </div>

          <div class="cart-controls">
            <div class="price">Unit: ${formatCurrency(price)}</div>
            <div class="subtotal" id="page-subtotal-${escapeHtml(id)}">${formatCurrency(subtotal)}</div>

            <div style="display:flex; gap:8px; align-items:center; margin-top:6px;">
              <div class="qty-controls" style="display:none;">
                <!-- kept hidden because server-side qty controls already provided elsewhere.
                     If you prefer inline qty update UI, un-hide and wire to changeQtyInPage() -->
                <button class="btn-qty-minus" onclick="changeQtyInPage('${escapeHtml(id)}', -1)">-</button>
                <input id="page-qty-${escapeHtml(id)}" type="number" value="${qty}" min="1" />
                <button class="btn-qty-plus" onclick="changeQtyInPage('${escapeHtml(id)}', 1)">+</button>
              </div>

              <button class="btn-cart-remove" type="button" data-id="${escapeHtml(id)}">Remove</button>
            </div>
          </div>
        </div>
      `;

      // remove handler
      const removeBtn = card.querySelector('.btn-cart-remove');
      removeBtn.addEventListener('click', async (e) => {
        const pid = e.currentTarget.dataset.id;
        if (!confirm('Remove this item from cart?')) return;
        try {
          // call your existing remove endpoint
          const res = await fetch('../api/cart/remove_from_cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ product_id: pid })
          });
          const json = await res.json();
          if (json && json.status === 'success') {
            // reload cart page and preview
            await loadCartPage();
            if (typeof window.loadCartPreview === 'function') {
              try { window.loadCartPreview(); } catch {}
            }
            if (typeof window.updateCartBadge === 'function') {
              try { window.updateCartBadge(json.cart_count ?? 0); highlightCartBadge(); } catch {}
            }
          } else {
            alert(json.message || 'Failed to remove item.');
          }
        } catch (err) {
          console.error(err);
          alert('Error removing item.');
        }
      });

      container.appendChild(card);
    });

    // wire select-all behavior
    const selectAll = document.getElementById('cart-select-all');
    selectAll.addEventListener('change', (e) => {
      const all = Array.from(document.querySelectorAll('.cart-item-checkbox'));
      all.forEach(cb => cb.checked = !!e.target.checked);
      recalcCheckedTotal();
    });

    // wire per-checkbox recalc
    container.querySelectorAll('.cart-item-checkbox').forEach(cb => {
      cb.addEventListener('change', recalcCheckedTotal);
    });

    // initial total is 0 (no items selected)
    recalcCheckedTotal();
}

// Recalculate total using only checked items' visible subtotal
function recalcCheckedTotal() {
    const checked = Array.from(document.querySelectorAll('.cart-item-checkbox:checked'));
    let total = 0;
    checked.forEach(cb => {
      const id = cb.dataset.id;
      // try to read subtotal element; fallback to dataset price * server qty
      const subtotalEl = document.getElementById(`page-subtotal-${id}`);
      if (subtotalEl) {
        // strip non-digit except dot and minus
        const txt = subtotalEl.innerText.replace(/[^\d.-]/g,'');
        const val = Number(txt) || 0;
        total += val;
      } else {
        const price = Number(cb.dataset.price) || 0;
        // attempt to find qty in SERVER_CART
        const serverItem = (window.SERVER_CART || []).find(si => String(si.id ?? si.product_id ?? si.productId) === String(id));
        const qty = Number(serverItem?.qty ?? 1);
        total += price * qty;
      }
    });

    const totalEl = document.getElementById('total');
    if (totalEl) totalEl.innerText = Number(total || 0).toFixed(2);
}

// small helpers that mirror functions in other modules; safe fallbacks if not present
function formatCurrency(n) {
  return `₱${Number(n || 0).toFixed(2)}`;
}

// -------- INITIALIZATION --------
function mainInit() {
    bindFilterButtons();
    loadProductsAndMenu();

    // If this is the cart page, initialize cart page rendering
    if (document.getElementById('cart-page-items')) {
        loadCartPage();
    }

    // Ensure there's at least a badge element to update
    ensureCartBadgeElement();
}

// Expose a few functions globally that are used inline in markup
window.changeQty = changeQty;
window.addToCart = addToCart;
window.showOrderSummary = showOrderSummary;
window.changeQtyInPage = changeQtyInPage;
window.removeFromCartPage = removeFromCartPage;
window.checkoutSelected = checkoutSelected;
window.displayOrderSummaryModal = window.displayOrderSummaryModal; // will be defined below by the modal IIFE
window.removeFromCart = removeFromCart;
window.loadCartPage = loadCartPage;
window.updateCartBadge = updateCartBadge; // exported for other modules

// Run init on page load
window.addEventListener('load', mainInit);

// -------------------------
// ORDER SUMMARY MODAL (inlined + enhancements)
// - auto-fill logged-in customer details (via api/customer/get_account.php)
// - pastry add-ons displayed as cards inside order summary, each card showing image, name, price
// - totals section displayed horizontally (Subtotal + Total next to buttons)
// -------------------------
(function () {
  // NOTE: CHANGED TO ABSOLUTE PATHS TO MATCH WHERE PHP FILES LIVE
  const ORDER_API = '../api/order/create_order.php'; // <- changed from '../api/order/create_order.php'
  const ADDONS_API = '../api/checkout/get_addons.php'; // ensure this path exists or adjust
  const CUSTOMER_API = '../api/customer/get_account.php'; // ensure this path exists or adjust

  // Try to reuse helper functions from global scope if present, otherwise use local implementations
  const _resolveImg = window.resolveImg || function (src) {
    const fallback = '../images/coffee.jpg';
    if (!src) return fallback;
    if (src.startsWith('http://') || src.startsWith('https://') || src.startsWith('/')) return src;
    if (src.startsWith('../images/')) return src;
    return `../images/${src}`;
  };
  const _escapeHtml = window.escapeHtml || function (s) {
    return String(s || '').replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' })[c]);
  };
  const _showNotification = window.showNotification || function (m, t) { alert(m); };

  function formatCurrency(n) {
    return `₱${Number(n || 0).toFixed(2)}`;
  }

  function ensureNormalizedItems(items) {
    if (!Array.isArray(items)) return [];
    return items.map(it => ({
      product_id: it.product_id ?? it.id ?? it.productId ?? null,
      name: it.name ?? it.product_name ?? it.productName ?? 'Item',
      price: Number(it.price ?? 0),
      qty: Number(it.qty ?? 1),
      subtotal: Number(it.subtotal ?? ((Number(it.price || 0)) * (Number(it.qty || 1)))),

      img: it.img ?? it.image ?? ''
    }));
  }

  // Cache addons list to avoid repeated fetches
  let CACHED_ADDONS = null;

  async function fetchAddons() {
    if (CACHED_ADDONS !== null) return CACHED_ADDONS;
    try {
      const res = await fetch(ADDONS_API);
      const data = await res.json();
      if (data && data.status === 'success') {
        CACHED_ADDONS = (data.addons || data.data || []).map(a => ({
          addon_id: a.addon_id ?? a.id,
          name: a.addon_name ?? a.name,
          price: Number(a.price ?? 0),
          img: a.image ?? a.img ?? ''
        }));
      } else {
        CACHED_ADDONS = [];
      }
    } catch (err) {
      console.error('Failed to fetch addons', err);
      CACHED_ADDONS = [];
    }
    return CACHED_ADDONS;
  }

  // Fetch logged-in customer/account details
  async function fetchCustomerAccount() {
    try {
      const res = await fetch(CUSTOMER_API);
      const data = await res.json();
      if (data && data.status === 'success' && data.account) {
        return data.account;
      }
    } catch (err) {
      console.error('Failed to fetch customer account', err);
    }
    return null;
  }

  // Build/create modal container and return references
  function createModalElements() {
    // If modal already exists, reuse
    let modal = document.getElementById('order-summary-modal');
    if (modal) return {
      modal,
      overlay: modal.querySelector('.osm-overlay'),
      content: modal.querySelector('.osm-content'),
      itemsContainer: modal.querySelector('.osm-items'),
      addonsContainer: modal.querySelector('.osm-addons'),
      subtotalEl: modal.querySelector('.osm-subtotal'),
      totalEl: modal.querySelector('.osm-total'),
      confirmBtn: modal.querySelector('.osm-confirm'),
      closeBtn: modal.querySelector('.osm-close'),
      nameInput: modal.querySelector('.osm-name'),
      contactInput: modal.querySelector('.osm-contact'),
      addressInput: modal.querySelector('.osm-address'),
      paymentSelect: modal.querySelector('.osm-payment'),
      closeModal: () => { modal.style.display = 'none'; }
    };

    modal = document.createElement('div');
    modal.id = 'order-summary-modal';
    modal.innerHTML = `
      <div class="osm-overlay" style="position:fixed; inset:0; background:rgba(0,0,0,0.45); display:flex; align-items:center; justify-content:center; z-index:12000; padding:20px;">
        <div class="osm-content" role="dialog" aria-modal="true" style="width:100%; max-width:900px; max-height:90vh; overflow:auto; background:#fff; border-radius:10px; box-shadow:0 8px 30px rgba(0,0,0,0.2); position:relative;">
          <button class="osm-close" aria-label="Close" title="Close" style="position:absolute; right:12px; top:12px; border:none; background:transparent; font-size:20px; cursor:pointer;">✕</button>
          <div style="padding:20px;">
            <h4 style="margin:0 0 12px 0;">Order Summary</h4>

            <!-- Main drink items -->
            <div class="osm-items" style="display:flex; flex-direction:column; gap:12px; margin-bottom:12px;"></div>

            <!-- Pastry add-ons as cards INSIDE summary -->
            <div style="margin-bottom:16px;">
              <label style="display:block; font-weight:700; margin-bottom:6px;">Pastry Add-ons (optional)</label>
              <div class="osm-addons" style="display:flex; flex-wrap:wrap; gap:12px;"></div>
            </div>

            <!-- Customer info -->
            <div style="display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end; margin-bottom:12px;">
              <div style="flex:1; min-width:200px;">
                <label style="display:block; font-weight:600; margin-bottom:6px;">Name</label>
                <input class="osm-name" type="text" placeholder="Your name" style="width:100%; padding:8px; border-radius:6px; border:1px solid #ddd;">
              </div>
              <div style="flex:1; min-width:200px;">
                <label style="display:block; font-weight:600; margin-bottom:6px;">Contact (phone or email)</label>
                <input class="osm-contact" type="text" placeholder="Phone or email" style="width:100%; padding:8px; border-radius:6px; border:1px solid #ddd;">
              </div>
              <div style="flex:1; min-width:220px;">
                <label style="display:block; font-weight:600; margin-bottom:6px;">Address</label>
                <input class="osm-address" type="text" placeholder="Delivery address (optional)" style="width:100%; padding:8px; border-radius:6px; border:1px solid #ddd;">
              </div>
              <div style="min-width:160px;">
                <label style="display:block; font-weight:600; margin-bottom:6px;">Payment</label>
                <select class="osm-payment" style="width:100%; padding:8px; border-radius:6px; border:1px solid #ddd;">
                  <option value="cod">COD (Cash on Delivery)</option>
                  <option value="gcash">GCash</option>
                  <option value="card">Card</option>
                </select>
              </div>
            </div>

            <!-- Totals horizontally next to buttons -->
            <div style="display:flex; justify-content:flex-end; align-items:center; gap:24px; margin-top:8px;">
              <div style="display:flex; flex-direction:column; align-items:flex-end; gap:4px;">
                <div style="display:flex; justify-content:space-between; gap:16px; min-width:140px;">
                  <span style="color:#666;">Subtotal</span>
                  <span class="osm-subtotal" style="font-weight:700;">₱0.00</span>
                </div>
                <div style="display:flex; justify-content:space-between; gap:16px; min-width:140px;">
                  <span style="color:#666;">Total</span>
                  <span class="osm-total" style="font-weight:800; color:#111;">₱0.00</span>
                </div>
              </div>
              <div style="display:flex; gap:8px;">
                <button class="osm-confirm" style="background:#198754; color:#fff; padding:10px 16px; border-radius:8px; border:none; cursor:pointer; font-weight:700; white-space:nowrap;">Confirm & Checkout</button>
                <button class="osm-cancel" style="background:#6c757d; color:#fff; padding:10px 14px; border-radius:8px; border:none; cursor:pointer; white-space:nowrap;">Cancel</button>
              </div>
            </div>

          </div>
        </div>
      </div>`;

    document.body.appendChild(modal);

    const overlay = modal.querySelector('.osm-overlay');
    const content = modal.querySelector('.osm-content');
    const itemsContainer = modal.querySelector('.osm-items');
    const addonsContainer = modal.querySelector('.osm-addons');
    const subtotalEl = modal.querySelector('.osm-subtotal');
    const totalEl = modal.querySelector('.osm-total');
    const confirmBtn = modal.querySelector('.osm-confirm');
    const closeBtn = modal.querySelector('.osm-close');
    const cancelBtn = modal.querySelector('.osm-cancel');
    const nameInput = modal.querySelector('.osm-name');
    const contactInput = modal.querySelector('.osm-contact');
    const addressInput = modal.querySelector('.osm-address');
    const paymentSelect = modal.querySelector('.osm-payment');

    // close handlers
    overlay.addEventListener('click', (e) => {
      if (e.target === overlay) closeModal();
    });
    closeBtn.addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', closeModal);

    // ESC to close
    window.addEventListener('keydown', function onKey(e) {
      if (e.key === 'Escape') {
        if (modal && modal.parentNode) {
          closeModal();
        }
      }
    });

    function closeModal() {
      if (modal) modal.style.display = 'none';
    }

    return {
      modal, overlay, content, itemsContainer, addonsContainer, subtotalEl, totalEl,
      confirmBtn, closeBtn, nameInput, contactInput, addressInput, paymentSelect, closeModal
    };
  }

  async function submitOrder(payload) {
    try {
      const res = await fetch(ORDER_API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
        credentials: 'same-origin'
      });
      const data = await res.json();
      return data;
    } catch (err) {
      console.error('submitOrder error', err);
      throw err;
    }
  }

  // Render pastry add-ons as cards (image + name + price) inside order summary
  function renderAddons(els, addons, selectedSet, onChangeCallback) {
    els.addonsContainer.innerHTML = '';
    if (!Array.isArray(addons) || addons.length === 0) {
      els.addonsContainer.innerHTML = '<div style="color:#666;">No add-ons available</div>';
      return;
    }

    addons.forEach(addon => {
      const idStr = String(addon.addon_id);
      const card = document.createElement('div');
      card.style.display = 'flex';
      card.style.flexDirection = 'column';
      card.style.alignItems = 'center';
      card.style.width = '150px';
      card.style.borderRadius = '10px';
      card.style.border = '1px solid #eee';
      card.style.padding = '8px';
      card.style.cursor = 'pointer';
      card.style.boxShadow = '0 1px 4px rgba(0,0,0,0.04)';
      card.style.background = '#fff';

      const imgWrap = document.createElement('div');
      imgWrap.style.width = '120px';
      imgWrap.style.height = '80px';
      imgWrap.style.borderRadius = '8px';
      imgWrap.style.overflow = 'hidden';
      imgWrap.style.background = '#f6f6f6';

      const imgEl = document.createElement('img');
      imgEl.src = _resolveImg(addon.img);
      imgEl.alt = addon.name;
      imgEl.style.width = '100%';
      imgEl.style.height = '100%';
      imgEl.style.objectFit = 'cover';

      imgWrap.appendChild(imgEl);

      const nameEl = document.createElement('div');
      nameEl.style.marginTop = '6px';
      nameEl.style.fontWeight = '600';
      nameEl.style.fontSize = '13px';
      nameEl.style.textAlign = 'center';
      nameEl.innerText = addon.name;

      const priceEl = document.createElement('div');
      priceEl.style.fontSize = '13px';
      priceEl.style.color = '#666';
      priceEl.style.marginTop = '2px';
      priceEl.innerText = formatCurrency(addon.price);

      card.appendChild(imgWrap);
      card.appendChild(nameEl);
      card.appendChild(priceEl);

      function updateVisual() {
        if (selectedSet.has(idStr)) {
          card.style.borderColor = '#198754';
          card.style.boxShadow = '0 4px 10px rgba(25,135,84,0.18)';
        } else {
          card.style.borderColor = '#eee';
          card.style.boxShadow = '0 1px 4px rgba(0,0,0,0.04)';
        }
      }

      card.addEventListener('click', () => {
        if (selectedSet.has(idStr)) selectedSet.delete(idStr);
        else selectedSet.add(idStr);
        updateVisual();
        onChangeCallback && onChangeCallback();
      });

      updateVisual();
      els.addonsContainer.appendChild(card);
    });
  }

  // Public function
  async function displayOrderSummaryModal(itemsRaw, summaryRaw) {
    const items = ensureNormalizedItems(itemsRaw || []);
    if (items.length === 0) {
      _showNotification('No items to display in order summary.', 'error');
      return;
    }


    const itemsSubtotal = items.reduce((s, it) => s + (Number(it.subtotal) || 0), 0);

    const els = createModalElements();

    // Build items list
    els.itemsContainer.innerHTML = '';
    items.forEach(it => {
      const row = document.createElement('div');
      row.style.display = 'flex';
      row.style.gap = '12px';
      row.style.alignItems = 'center';
      row.style.padding = '8px 0';
      row.style.borderBottom = '1px solid #f1f1f1';
      row.innerHTML = `
        <div style="flex:0 0 72px; height:72px; border-radius:8px; overflow:hidden; background:#f6f6f6;">
          <img src="${_escapeHtml(_resolveImg(it.img))}" style="width:100%; height:100%; object-fit:cover; display:block;">
        </div>
        <div style="flex:1;">
          <div style="font-weight:700; color:#222;">${_escapeHtml(it.name)}</div>
          <div style="color:#666; margin-top:6px;">${formatCurrency(it.price)} × ${it.qty}</div>
        </div>
        <div style="min-width:120px; text-align:right; font-weight:700;">${formatCurrency(it.subtotal)}</div>
      `;
      els.itemsContainer.appendChild(row);
    });

    // Pre-populate customer info if available
    const account = await fetchCustomerAccount().catch(() => null);
    if (account) {
      if (account.name) els.nameInput.value = account.name;
      if (account.customer_name) els.nameInput.value = account.customer_name;
      if (account.email) els.contactInput.value = account.email;
      if (account.phone && !els.contactInput.value) els.contactInput.value = account.phone;
      if (account.address) els.addressInput.value = account.address;
      els.modal.dataset.accountId = account.account_id ?? account.id ?? '';
      els.modal.dataset.customerId = account.customer_id ?? account.customerId ?? '';
    } else {
      els.modal.dataset.accountId = '';
      els.modal.dataset.customerId = '';
    }

    // Addons
    const addons = await fetchAddons();
    const selectedAddonsSet = new Set();

    function recomputeAndDisplayTotals() {
      const addonsSubtotal = (addons || [])
        .filter(a => selectedAddonsSet.has(String(a.addon_id)))
        .reduce((s, a) => s + (Number(a.price) || 0), 0);
      const subtotal = itemsSubtotal + addonsSubtotal;
      const total = subtotal;
      els.subtotalEl.innerText = formatCurrency(subtotal);
      els.totalEl.innerText = formatCurrency(total);
      els.modal.dataset._computedSubtotal = subtotal;
      els.modal.dataset._computedTotal = total;
    }

    renderAddons(els, addons, selectedAddonsSet, recomputeAndDisplayTotals);

    // initial totals
    recomputeAndDisplayTotals();

    // reset inputs if empty
    if (!els.nameInput.value) els.nameInput.value = '';
    if (!els.contactInput.value) els.contactInput.value = '';
    if (!els.addressInput.value) els.addressInput.value = '';
    els.paymentSelect.value = 'cod';

    els.modal.style.display = 'block';

    // confirm handler
    async function onConfirmClick() {
      const name = els.nameInput.value.trim();
      const contact = els.contactInput.value.trim();
      const address = els.addressInput.value.trim();
      const payment_method = els.paymentSelect.value;

      if (!name) {
        _showNotification('Please enter your name.', 'error');
        els.nameInput.focus();
        return;
      }
      if (!contact) {
        _showNotification('Please enter contact info (phone or email).', 'error');
        els.contactInput.focus();
        return;
      }

      const selectedAddons = (addons || [])
        .filter(a => selectedAddonsSet.has(String(a.addon_id)))
        .map(a => ({
          addon_id: a.addon_id,
          name: a.name,
          price: a.price,
          qty: 1,
          subtotal: a.price * 1
        }));

      const subtotal = Number(els.modal.dataset._computedSubtotal ?? itemsSubtotal);
      const total = Number(els.modal.dataset._computedTotal ?? subtotal);

      const payload = {
        customer_name: name,
        customer_contact: contact,
        address: address || null,
        payment_method,
        items: items.map(it => ({
          product_id: it.product_id,
          name: it.name,
          price: it.price,
          qty: it.qty,
          subtotal: it.subtotal
        })),
        addons: selectedAddons,
        summary: { subtotal, total }
      };

      const acctId = els.modal.dataset.accountId;
      const custId = els.modal.dataset.customerId;
      if (acctId) payload.account_id = acctId;
      if (custId) payload.customer_id = custId;

      const origTxt = els.confirmBtn.innerText;
      els.confirmBtn.disabled = true;
      els.confirmBtn.style.opacity = '0.7';
      els.confirmBtn.innerText = 'Processing...';

      try {
        const res = await submitOrder(payload);
        if (res && res.status === 'success') {
          // If server provides a cart_count, update badge (no message)
          if (res.cart_count !== undefined) {
            ensureCartBadgeElement();
            updateCartBadge(res.cart_count);
            highlightCartBadge();
          } else {
            _showNotification(res.message || 'Order placed successfully!', 'success');
          }

          if (typeof window.loadCartPreview === 'function') {
            try { window.loadCartPreview(); } catch {}
          }
          if (typeof window.updateCartBadge === 'function' && (res.cart_count !== undefined)) {
            try { window.updateCartBadge(res.cart_count); } catch {}
          }
          if (els.closeModal) els.closeModal();
          if (res.redirect_url) {
            window.location.href = res.redirect_url;
            return;
          }
        } else {
          const msg = res?.message || 'Failed to place order.';
          _showNotification(msg, 'error');
        }
      } catch (err) {
        console.error(err);
        _showNotification('Error submitting order. Please try again.', 'error');
      } finally {
        els.confirmBtn.disabled = false;
        els.confirmBtn.style.opacity = '1';
        els.confirmBtn.innerText = origTxt;
      }
    }

    // prevent multiple handlers
    els.confirmBtn.replaceWith(els.confirmBtn.cloneNode(true));
    const newConfirm = els.modal.querySelector('.osm-confirm');
    newConfirm.addEventListener('click', onConfirmClick);
  }

  // Export to global scope
  window.displayOrderSummaryModal = displayOrderSummaryModal;

})();
