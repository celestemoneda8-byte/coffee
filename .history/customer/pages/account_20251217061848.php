<?php
session_start();
if (!isset($_SESSION['customer_id'])) {
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../css/globa.css">
  <link rel="stylesheet" href="../css/account.css">
  <link rel="stylesheet" href="../css/foot.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <title>Account | Expresso Café</title>
</head>

<body>
<?php require_once 'header_nav.php'; ?>

<main id="content-area">

  <h2 class="text-center mb-4">My Account</h2>

  <div class="coffee-card p-3">
    <div class="coffee-right text-start">

      <p><strong>Name:</strong>
        <input type="text" id="profileName" class="form-control" readonly placeholder="Loading..." />
      </p>

      <p><strong>Email:</strong>
        <input type="text" id="profileEmail" class="form-control" readonly placeholder="Loading..." />
      </p>

      <p><strong>Address:</strong>
        <textarea id="profileAddress" class="form-control" rows="2" placeholder="Enter your delivery address"></textarea>
      </p>

      <p><strong>Phone:</strong>
        <input type="tel" id="profilePhone" class="form-control" placeholder="Enter your phone number" />
      </p>

      <p><strong>Joined:</strong>
        <span id="profileJoined">Loading...</span>
      </p>

      <button id="updateProfile" class="btn btn-primary w-100 mb-2">Update Address & Phone</button>
      <a href="logout.php" class="btn btn-danger w-100">Logout</a>

      <div id="profileAlert" class="mt-3"></div>

    </div>
  </div>

</main>

<script>
document.addEventListener('DOMContentLoaded', async () => {
  const alertEl = document.getElementById("profileAlert");

  function showAlert(html) {
    alertEl.innerHTML = html;
  }

  try {
    const res = await fetch('../api/auth/updateProfile.php?action=get', {
      method: 'GET',
      credentials: 'same-origin'
    });

    // prefer res.json() directly, but keep a fallback
    const data = await res.json();

    if (data.success) {
      const user = data.user || {};

      // API returns 'fullname' and 'email'
      document.getElementById('profileName').value = user.fullname || '';
      document.getElementById('profileEmail').value = user.email || '';
      document.getElementById('profileAddress').value = user.address || '';
      document.getElementById('profilePhone').value = user.phone || '';

      if (user.created_at) {
        const d = new Date(user.created_at);
        if (!isNaN(d)) {
          document.getElementById('profileJoined').innerText =
            d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: '2-digit' });
        } else {
          document.getElementById('profileJoined').innerText = user.created_at;
        }
      } else {
        document.getElementById('profileJoined').innerText = '—';
      }

      showAlert(''); // clear
    } else {
      showAlert(`<div class="alert alert-danger">${data.message || 'Failed to load profile'}</div>`);
    }

  } catch (err) {
    console.error("Load error:", err);
    showAlert('<div class="alert alert-danger">Error loading profile</div>');
  }
});

// Save updated address + phone
document.getElementById("updateProfile").addEventListener("click", async () => {
  const address = document.getElementById("profileAddress").value;
  const phone = document.getElementById("profilePhone").value;
  const alertEl = document.getElementById("profileAlert");

  // Basic client-side length checks (same rules as server)
  if (address && address.length > 255) {
    alertEl.innerHTML = '<div class="alert alert-danger">Address too long (max 255 chars)</div>';
    return;
  }
  if (phone && phone.length > 50) {
    alertEl.innerHTML = '<div class="alert alert-danger">Phone too long (max 50 chars)</div>';
    return;
  }

  const payload = {
    address: address === '' ? null : address,
    phone: phone === '' ? null : phone
  };

  const btn = document.getElementById("updateProfile");
  btn.disabled = true;

  try {
    const res = await fetch("../api/auth/updateProfile.php", {
      method: "POST",
      credentials: 'same-origin',
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload)
    });

    const data = await res.json();

    alertEl.innerHTML =
      `<div class="alert ${data.success ? 'alert-success' : 'alert-danger'}">
          ${data.message || (data.success ? 'Saved' : 'Failed to save')}
       </div>`;

    // If server returned updated user, refresh displayed values (ensures what you see matches DB)
    if (data.success && data.user) {
      document.getElementById('profileAddress').value = data.user.address || '';
      document.getElementById('profilePhone').value = data.user.phone || '';
    }

  } catch (err) {
    console.error("Update error:", err);
    alertEl.innerHTML = '<div class="alert alert-danger">Error updating profile</div>';
  } finally {
    btn.disabled = false;
  }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="sidebah.js"></script>
<?php require_once 'footer.php'; ?>
</body>
</html>