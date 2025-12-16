<?php
session_start();
require_once "../../db.php";

// NOTE: this page used $_SESSION['user_id'] previously. We'll expose it to JS as CUSTOMER_ID
$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

// Fetch pending orders (any order not completed/cancelled)
$pending = $conn->prepare("SELECT order_id, created_at, total_amount AS total, order_status AS status FROM orders WHERE customer_id = ? AND order_status NOT IN ('Completed','Cancelled') ORDER BY created_at DESC");
$pending->bind_param("i", $userId);
$pending->execute();
$pendingResult = $pending->get_result();

// Fetch order history (completed or cancelled)
$history = $conn->prepare("SELECT order_id, created_at, total_amount AS total, order_status AS status FROM orders WHERE customer_id = ? AND order_status IN ('Completed','Cancelled') ORDER BY created_at DESC");
$history->bind_param("i", $userId);
$history->execute();
$historyResult = $history->get_result();

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>My Orders | Expresso Café</title>

<link rel="stylesheet" href="css/globa.css">
<link rel="stylesheet" href="css/order.css">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

<style>
/* small override to keep the modal inside page-scopes when bootstrap isn't used */
#orderDetailsModal .modal-dialog { max-width:800px; }
.order-tracker { display:flex; gap:6px; flex-wrap:wrap; margin-top:8px; }
.order-tracker .step { padding:6px 8px; border-radius:6px; background:#f1f1f1; font-size:13px; color:#666; }
.order-tracker .step.active { background:#198754; color:#fff; }
</style>
</head>
<body>

<?php require_once "header_nav.php"; ?>

<main id="content-area" class="container my-4">

    <h2 class="mb-3">Pending Orders</h2>
    <div class="row">
        <?php if ($pendingResult && $pendingResult->num_rows > 0): ?>
            <?php while($order = $pendingResult->fetch_assoc()): ?>
                <div class="col-md-6 mb-3">
                    <div class="card p-3">
                        <h5>Order #<?= htmlspecialchars($order['order_id']) ?></h5>
                        <p>Date: <?= date('M d, Y H:i', strtotime($order['created_at'])) ?></p>
                        <p>Total: ₱<?= number_format($order['total'], 2) ?></p>

                        <!-- Progress Tracker -->
                        <div class="order-tracker mt-2">
                            <?php
                            $statuses = ['Pending','Approved','Preparing','Ready','On-Delivery','Completed'];
                            foreach ($statuses as $s):
                                $active = (array_search($s, $statuses) <= array_search(ucfirst(strtolower($order['status'])), array_map('ucfirst', array_map('strtolower', $statuses)))) ? 'active' : '';
                            ?>
                                <div class="step <?= $active ?>"><?= ucfirst($s) ?></div>
                            <?php endforeach; ?>
                        </div>

                        <button class="btn btn-sm btn-primary mt-2" onclick="viewOrderDetails(<?= (int)$order['order_id'] ?>)">View Details</button>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <p class="text-muted">No pending orders.</p>
            </div>
        <?php endif; ?>
    </div>

    <hr class="my-4">

    <h2 class="mb-3">Order History</h2>
    <div class="row">
        <?php if ($historyResult && $historyResult->num_rows > 0): ?>
            <?php while($order = $historyResult->fetch_assoc()): ?>
                <div class="col-md-6 mb-3">
                    <div class="card p-3">
                        <h5>Order #<?= htmlspecialchars($order['order_id']) ?></h5>
                        <p>Date: <?= date('M d, Y H:i', strtotime($order['created_at'])) ?></p>
                        <p>Total: ₱<?= number_format($order['total'], 2) ?></p>

                        <!-- Progress Tracker -->
                        <div class="order-tracker mt-2">
                            <?php
                            $statuses = ['Pending','Approved','Preparing','Ready','On-Delivery','Completed'];
                            foreach ($statuses as $s):
                                $active = (array_search($s, $statuses) <= array_search(ucfirst(strtolower($order['status'])), array_map('ucfirst', array_map('strtolower', $statuses)))) ? 'active' : '';
                            ?>
                                <div class="step <?= $active ?>"><?= ucfirst($s) ?></div>
                            <?php endforeach; ?>
                        </div>

                        <button class="btn btn-sm btn-secondary mt-2" onclick="viewOrderDetails(<?= (int)$order['order_id'] ?>)">View Details</button>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <p class="text-muted">No order history found.</p>
            </div>
        <?php endif; ?>
    </div>

</main>
<?php require_once "footer.php"; ?>

<script>
  // expose customer id to client for cancel requests (if session key differs on your backend, adjust accordingly)
  window.CUSTOMER_ID = <?= json_encode($userId) ?>;
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/main.js"></script>

<script>
/*
  viewOrderDetails(order_id)
  - Fetches order items + addons + summary and displays a read-only order details modal.
  - If order is still 'Pending', a Cancel Order button is shown and will call api/order/cancel_order.php
*/
async function viewOrderDetails(orderId) {
  try {
    const res = await fetch(`/api/order/get_order_details.php?order_id=${encodeURIComponent(orderId)}`, { credentials: 'same-origin' });
    const data = await res.json();
    if (!data || data.status !== 'success') {
      alert(data && data.message ? data.message : 'Failed to load order details.');
      return;
    }

    const order = data.order;
    const items = data.items || [];
    const summary = data.summary || {};

    // create or reuse modal
    let modal = document.getElementById('order-details-modal');
    if (!modal) {
      modal = document.createElement('div');
      modal.id = 'order-details-modal';
      modal.style.position = 'fixed';
      modal.style.inset = '0';
      modal.style.background = 'rgba(0,0,0,0.45)';
      modal.style.display = 'flex';
      modal.style.alignItems = 'center';
      modal.style.justifyContent = 'center';
      modal.style.zIndex = '13000';
      modal.innerHTML = `
        <div style="width:100%; max-width:900px; max-height:90vh; overflow:auto; background:#fff; border-radius:10px; box-shadow:0 10px 30px rgba(0,0,0,0.2); position:relative;">
          <button id="order-details-close" aria-label="Close" title="Close" style="position:absolute; right:12px; top:12px; border:none; background:transparent; font-size:20px; cursor:pointer;">✕</button>
          <div style="padding:20px;">
            <h4 id="od-title">Order Details</h4>
            <div id="od-meta" style="color:#666; margin-bottom:12px;"></div>
            <div id="od-items" style="display:flex; flex-direction:column; gap:12px; margin-bottom:12px;"></div>

            <div id="od-addons-wrapper" style="margin-bottom:12px;"></div>

            <div style="display:flex; justify-content:flex-end; gap:24px; align-items:center; margin-top:8px;">
              <div style="display:flex; flex-direction:column; align-items:flex-end; gap:4px;">
                <div style="display:flex; justify-content:space-between; gap:16px; min-width:160px;">
                  <span style="color:#666;">Items Subtotal</span>
                  <span id="od-subtotal" style="font-weight:700;">₱0.00</span>
                </div>
                <div style="display:flex; justify-content:space-between; gap:16px; min-width:160px;">
                  <span style="color:#666;">Add-ons</span>
                  <span id="od-addons-total" style="font-weight:700;">₱0.00</span>
                </div>
                <div style="display:flex; justify-content:space-between; gap:16px; min-width:160px;">
                  <span style="color:#666;">Total</span>
                  <span id="od-total" style="font-weight:800; color:#111;">₱0.00</span>
                </div>
              </div>
              <div style="display:flex; gap:8px;">
                <button id="od-cancel-btn" style="background:#dc3545; color:#fff; padding:10px 14px; border-radius:8px; border:none; cursor:pointer; display:none;">Cancel Order</button>
                <button id="od-close-btn" style="background:#6c757d; color:#fff; padding:10px 14px; border-radius:8px; border:none; cursor:pointer;">Close</button>
              </div>
            </div>

          </div>
        </div>
      `;
      document.body.appendChild(modal);
      // close handlers
      modal.querySelector('#order-details-close').addEventListener('click', () => modal.style.display = 'none');
      modal.querySelector('#od-close-btn').addEventListener('click', () => modal.style.display = 'none');
      modal.addEventListener('click', (e) => { if (e.target === modal) modal.style.display = 'none'; });
      window.addEventListener('keydown', (e) => { if (e.key === 'Escape') modal.style.display = 'none'; });
    }

    // populate meta
    modal.querySelector('#od-title').innerText = `Order #${order.order_id} — ${order.order_status}`;
    modal.querySelector('#od-meta').innerText = `Placed: ${new Date(order.created_at).toLocaleString()}`;

    // items list
    const itemsContainer = modal.querySelector('#od-items');
    itemsContainer.innerHTML = '';
    let itemsSubtotal = 0;
    items.forEach(it => {
      const row = document.createElement('div');
      row.style.display = 'flex';
      row.style.gap = '12px';
      row.style.alignItems = 'center';
      row.style.padding = '8px 0';
      row.style.borderBottom = '1px solid #f1f1f1';

      const imgSrc = it.image || it.img || 'images/coffee.jpg';
      const price = Number(it.price || 0);
      const qty = Number(it.quantity || it.qty || 1);
      const subtotalVal = price * qty;
      itemsSubtotal += subtotalVal;

      row.innerHTML = `
        <div style="flex:0 0 72px; height:72px; border-radius:8px; overflow:hidden; background:#f6f6f6;">
          <img src="${escapeHtml(imgSrc)}" style="width:100%; height:100%; object-fit:cover; display:block;">
        </div>
        <div style="flex:1;">
          <div style="font-weight:700; color:#222;">${escapeHtml(it.name ?? it.product_name ?? 'Item')}</div>
          <div style="color:#666; margin-top:6px;">₱${Number(price).toFixed(2)} × ${qty}</div>
          ${ (it.description ?? '') ? `<div style="color:#666; margin-top:6px;">${escapeHtml(it.description)}</div>` : '' }
        </div>
        <div style="min-width:120px; text-align:right; font-weight:700;">₱${Number(subtotalVal).toFixed(2)}</div>
      `;

      // append addons for this item if any (small list)
      if (it.addons && Array.isArray(it.addons) && it.addons.length > 0) {
        const addonsWrap = document.createElement('div');
        addonsWrap.style.marginTop = '8px';
        addonsWrap.style.marginLeft = '84px';
        addonsWrap.style.display = 'flex';
        addonsWrap.style.flexDirection = 'column';
        addonsWrap.style.gap = '6px';
        it.addons.forEach(a => {
          const aRow = document.createElement('div');
          aRow.style.display = 'flex';
          aRow.style.justifyContent = 'space-between';
          aRow.style.gap = '8px';
          aRow.style.maxWidth = 'calc(100% - 84px)';
          aRow.innerHTML = `<div style="color:#666;">➤ ${escapeHtml(a.name || a.addon_name || 'Addon')}</div><div style="font-weight:700;">₱${Number(a.price || a.addon_price || 0).toFixed(2)}</div>`;
          addonsWrap.appendChild(aRow);
        });
        row.appendChild(addonsWrap);
      }

      itemsContainer.appendChild(row);
    });

    // addons total (sum of item addons)
    const addonsTotal = (summary.addons_subtotal !== undefined) ? Number(summary.addons_subtotal) : items.reduce((s, it) => {
      return s + ((it.addons || []).reduce((ss, a) => ss + (Number(a.price || a.addon_price || 0) || 0), 0));
    }, 0);

    const totalComputed = (summary.total !== undefined) ? Number(summary.total) : (itemsSubtotal + addonsTotal);

    modal.querySelector('#od-subtotal').innerText = `₱${Number(itemsSubtotal || 0).toFixed(2)}`;
    modal.querySelector('#od-addons-total').innerText = `₱${Number(addonsTotal || 0).toFixed(2)}`;
    modal.querySelector('#od-total').innerText = `₱${Number(totalComputed || 0).toFixed(2)}`;

    // show/hide cancel button depending on order status
    const cancelBtn = modal.querySelector('#od-cancel-btn');
    if (String(order.order_status).toLowerCase() === 'pending') {
      cancelBtn.style.display = 'inline-block';
      cancelBtn.disabled = false;
      cancelBtn.onclick = async function () {
        if (!confirm('Cancel this order?')) return;
        try {
          const payload = { order_id: order.order_id, customer_id: window.CUSTOMER_ID || null };
          const r = await fetch('/api/order/cancel_order.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify(payload)
          });
          const jr = await r.json();
          if (jr && jr.status === 'success') {
            alert('Order cancelled.');
            modal.style.display = 'none';
            // refresh page to update lists
            window.location.reload();
            return;
          } else {
            alert(jr && jr.message ? jr.message : 'Failed to cancel order.');
          }
        } catch (err) {
          console.error(err);
          alert('Error cancelling order.');
        }
      };
    } else {
      cancelBtn.style.display = 'none';
    }

    modal.style.display = 'flex';
    // scroll modal content to top
    modal.querySelector('div[role="dialog"], div').scrollTop = 0;

  } catch (err) {
    console.error(err);
    alert('Failed to load order details.');
  }

  // small helper to escape html inside this inline script
  function escapeHtml(s) {
    return String(s || '').replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' })[c]);
  }
}
</script>

</body>
</html>