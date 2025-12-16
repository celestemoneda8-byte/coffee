// Rider UI helpers to accept and complete orders (use in rider order list)
// Accept order (assign to rider and set On-Delivery)
async function acceptOrder(orderId) {
  if (!confirm('Accept this order and start delivery?')) return;
  try {
    const res = await fetch('/api/order/assign_order.php', {
      method: 'POST',
      credentials: 'same-origin',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({ order_id: orderId })
    });
    const data = await res.json();
    if (data.status === 'success') {
      alert('Order accepted. Please deliver to the customer.');
      location.reload();
    } else {
      alert(data.message || 'Failed to accept order.');
    }
  } catch (err) {
    console.error(err);
    alert('Error accepting order.');
  }
}

// Mark order as completed by rider
async function markOrderComplete(orderId) {
  if (!confirm('Mark this order as completed?')) return;
  try {
    const res = await fetch('/api/order/update_status.php', {
      method: 'POST',
      credentials: 'same-origin',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({ order_id: orderId, status: 'Completed' })
    });
    const data = await res.json();
    if (data.status === 'success') {
      alert('Order marked as completed.');
      location.reload();
    } else {
      alert(data.message || 'Failed to update status.');
    }
  } catch (err) {
    console.error(err);
    alert('Error updating order status.');
  }
}