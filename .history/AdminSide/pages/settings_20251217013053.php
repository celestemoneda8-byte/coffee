<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db_connect.php';

// Protect page
if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
    header('Location: /EXpresso/coffee/AdminSide/includes/admin-login.php');
    exit;
}

// Defensive DB connection check
if (!isset($conn) || $conn === null) {
    $err = $GLOBALS['db_connect_error'] ?? 'Database connection is not available.';
    die('<h2>Database connection error</h2><p>' . htmlspecialchars((string)$err) . '</p>');
}

// Fetch current settings
$settings = [];
$result = $conn->query("SELECT * FROM app_settings");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

// Color theme presets
$colorThemes = [
    'brown' => [
        'name' => 'Brown Coffee',
        'primary' => '#7f5539',
        'secondary' => '#7b6a58',
        'accent' => '#dec0ad'
    ],
    'blue' => [
        'name' => 'Ocean Blue',
        'primary' => '#2c3e50',
        'secondary' => '#34495e',
        'accent' => '#3498db'
    ],
    'green' => [
        'name' => 'Fresh Green',
        'primary' => '#27ae60',
        'secondary' => '#229954',
        'accent' => '#82e0aa'
    ],
    'purple' => [
        'name' => 'Royal Purple',
        'primary' => '#8e44ad',
        'secondary' => '#7d3c98',
        'accent' => '#d7bde2'
    ],
    'orange' => [
        'name' => 'Sunset Orange',
        'primary' => '#d35400',
        'secondary' => '#ba4a00',
        'accent' => '#f8b88b'
    ],
    'red' => [
        'name' => 'Ruby Red',
        'primary' => '#c0392b',
        'secondary' => '#a93226',
        'accent' => '#f5b7b1'
    ],
    'teal' => [
        'name' => 'Teal Modern',
        'primary' => '#16a085',
        'secondary' => '#138d75',
        'accent' => '#76d7c4'
    ]
];

$currentUser = $_SESSION['username'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Settings</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Admin Layout Styles -->
    <link rel="stylesheet" href="../css/admin-sidebar-layout.css">
    
    <style>
        .color-preview-box {
            width: 100%;
            height: 40px;
            border-radius: 6px;
            border: 2px solid #dee2e6;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .color-preview-box:hover {
            border-color: #0d6efd;
            transform: scale(1.02);
        }
        
        .theme-card {
            cursor: pointer;
            transition: all 0.2s ease;
            border: 2px solid transparent;
        }
        
        .theme-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transform: translateY(-2px);
        }
        
        .theme-card.active {
            border-color: #0d6efd;
            background-color: rgba(13, 110, 253, 0.05);
        }
        
        .logo-preview {
            max-width: 200px;
            max-height: 150px;
            border-radius: 8px;
            border: 2px solid #dee2e6;
        }
        
        .upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .upload-area:hover {
            border-color: #0d6efd;
            background-color: rgba(13, 110, 253, 0.02);
        }
    </style>
</head>
<body>
    <div class="d-flex min-vh-100">
      
      <!-- SIDEBAR -->
      <?php include 'sidebar.php'; ?>

      <!-- MAIN CONTENT -->
      <main class="flex-fill p-4">
        <div class="container-fluid">
          
          <!-- HEADER with user badge -->
          <?php include 'header.php'; ?>

          <!-- Settings Actions -->
          <div class="mb-4 d-flex gap-2">
            <button class="btn btn-primary" id="saveBtn">
              <i class="bi bi-check-circle me-1"></i> Save All Changes
            </button>
            <button class="btn btn-outline-secondary" id="restoreBtn">
              <i class="bi bi-arrow-clockwise me-1"></i> Restore Default
            </button>
          </div>

          <!-- Alert Container -->
          <div id="alertContainer"></div>

        <!-- General Settings -->
        <div class="card mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="bi bi-globe me-2"></i> General Settings</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Website Name</label>
                        <input type="text" class="form-control" id="websiteName" 
                               placeholder="Your website name" 
                               value="<?php echo htmlspecialchars($settings['website_name'] ?? 'EXpresso Caffe'); ?>">
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Website Logo</label>
                        <div class="upload-area" onclick="document.getElementById('logoInput').click()">
                            <input type="file" id="logoInput" accept="image/*" style="display: none;">
                            <div id="logoPreviewContainer">
                                <?php if (!empty($settings['website_logo'])): ?>
                                    <img src="../../../uploads/<?php echo htmlspecialchars($settings['website_logo']); ?>" 
                                         alt="Logo" class="logo-preview mb-2" id="logoImg">
                                <?php endif; ?>
                            </div>
                            <div id="uploadText">
                                <i class="bi bi-cloud-arrow-up fs-1 text-primary"></i>
                                <p class="mt-2 mb-0"><strong>Click to upload</strong> or drag and drop</p>
                                <small class="text-muted">PNG, JPG, GIF, WebP (max 5MB)</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Admin Panel Theme -->
        <div class="card mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="bi bi-palette me-2"></i> Admin Panel Theme</h5>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">Choose a color scheme for the admin dashboard</p>
                <div class="row g-3" id="adminThemeGrid">
                    <?php foreach ($colorThemes as $key => $theme): ?>
                        <div class="col-lg-3 col-md-4 col-sm-6">
                            <div class="card theme-card h-100" data-theme="<?php echo $key; ?>" data-section="admin">
                                <div class="card-body">
                                    <h6 class="card-title mb-3"><?php echo $theme['name']; ?></h6>
                                    <div class="d-flex gap-2">
                                        <div class="color-preview-box flex-fill" 
                                             style="background-color: <?php echo $theme['primary']; ?>;"
                                             title="Primary"></div>
                                        <div class="color-preview-box flex-fill" 
                                             style="background-color: <?php echo $theme['secondary']; ?>;"
                                             title="Secondary"></div>
                                        <div class="color-preview-box flex-fill" 
                                             style="background-color: <?php echo $theme['accent']; ?>;"
                                             title="Accent"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Customer Portal Theme -->
        <div class="card mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="bi bi-shop me-2"></i> Customer Portal Theme</h5>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">Choose a color scheme for the customer website</p>
                <div class="row g-3" id="customerThemeGrid">
                    <?php foreach ($colorThemes as $key => $theme): ?>
                        <div class="col-lg-3 col-md-4 col-sm-6">
                            <div class="card theme-card h-100" data-theme="<?php echo $key; ?>" data-section="customer">
                                <div class="card-body">
                                    <h6 class="card-title mb-3"><?php echo $theme['name']; ?></h6>
                                    <div class="d-flex gap-2">
                                        <div class="color-preview-box flex-fill" 
                                             style="background-color: <?php echo $theme['primary']; ?>;"></div>
                                        <div class="color-preview-box flex-fill" 
                                             style="background-color: <?php echo $theme['secondary']; ?>;"></div>
                                        <div class="color-preview-box flex-fill" 
                                             style="background-color: <?php echo $theme['accent']; ?>;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Rider Portal Theme -->
        <div class="card mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="bi bi-bicycle me-2"></i> Rider Portal Theme</h5>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">Choose a color scheme for the rider dashboard</p>
                <div class="row g-3" id="riderThemeGrid">
                    <?php foreach ($colorThemes as $key => $theme): ?>
                        <div class="col-lg-3 col-md-4 col-sm-6">
                            <div class="card theme-card h-100" data-theme="<?php echo $key; ?>" data-section="rider">
                                <div class="card-body">
                                    <h6 class="card-title mb-3"><?php echo $theme['name']; ?></h6>
                                    <div class="d-flex gap-2">
                                        <div class="color-preview-box flex-fill" 
                                             style="background-color: <?php echo $theme['primary']; ?>;"></div>
                                        <div class="color-preview-box flex-fill" 
                                             style="background-color: <?php echo $theme['secondary']; ?>;"></div>
                                        <div class="color-preview-box flex-fill" 
                                             style="background-color: <?php echo $theme['accent']; ?>;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const API_URL = './api/settings_api.php';
        const colorThemes = <?php echo json_encode($colorThemes); ?>;
        
        let settingsData = {
            website_name: '<?php echo htmlspecialchars($settings['website_name'] ?? 'EXpresso Caffe'); ?>',
            admin_primary_color: '<?php echo htmlspecialchars($settings['admin_primary_color'] ?? '#7f5539'); ?>',
            admin_secondary_color: '<?php echo htmlspecialchars($settings['admin_secondary_color'] ?? '#7b6a58'); ?>',
            admin_accent_color: '<?php echo htmlspecialchars($settings['admin_accent_color'] ?? '#dec0ad'); ?>',
            customer_primary_color: '<?php echo htmlspecialchars($settings['customer_primary_color'] ?? '#7f5539'); ?>',
            customer_secondary_color: '<?php echo htmlspecialchars($settings['customer_secondary_color'] ?? '#7b6a58'); ?>',
            customer_accent_color: '<?php echo htmlspecialchars($settings['customer_accent_color'] ?? '#dec0ad'); ?>',
            rider_primary_color: '<?php echo htmlspecialchars($settings['rider_primary_color'] ?? '#7f5539'); ?>',
            rider_secondary_color: '<?php echo htmlspecialchars($settings['rider_secondary_color'] ?? '#7b6a58'); ?>',
            rider_accent_color: '<?php echo htmlspecialchars($settings['rider_accent_color'] ?? '#dec0ad'); ?>'
        };

        // Handle website name changes
        document.getElementById('websiteName').addEventListener('change', function() {
            settingsData.website_name = this.value;
        });

        // Handle logo upload
        document.getElementById('logoInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('logo', file);
            formData.append('action', 'upload_logo');

            showSpinner('Uploading logo...');
            
            fetch(API_URL, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const img = document.getElementById('logoImg');
                    if (img) {
                        img.src = data.url + '?t=' + Date.now();
                    } else {
                        const container = document.getElementById('logoPreviewContainer');
                        container.innerHTML = `<img src="${data.url}" alt="Logo" class="logo-preview mb-2" id="logoImg">`;
                    }
                    document.getElementById('uploadText').style.display = 'none';
                    showAlert('Logo uploaded successfully', 'success');
                } else {
                    showAlert(data.message || 'Upload failed', 'danger');
                }
            })
            .catch(err => {
                showAlert('Error uploading logo: ' + err.message, 'danger');
            })
            .finally(() => hideSpinner());
        });

        // Handle theme selection
        document.querySelectorAll('.theme-card').forEach(card => {
            card.addEventListener('click', function() {
                const section = this.dataset.section;
                const theme = this.dataset.theme;
                const themeData = colorThemes[theme];
                
                // Remove active class from siblings
                this.parentElement.parentElement.querySelectorAll('.theme-card').forEach(c => {
                    c.classList.remove('active');
                });
                this.classList.add('active');

                // Update settings
                settingsData[`${section}_primary_color`] = themeData.primary;
                settingsData[`${section}_secondary_color`] = themeData.secondary;
                settingsData[`${section}_accent_color`] = themeData.accent;
            });
        });

        // Set initial active themes
        function initializeThemePresets() {
            ['admin', 'customer', 'rider'].forEach(section => {
                const grid = document.getElementById(`${section}ThemeGrid`);
                const primaryKey = `${section}_primary_color`;
                const currentPrimary = settingsData[primaryKey];
                
                grid.querySelectorAll('.theme-card').forEach(card => {
                    const theme = colorThemes[card.dataset.theme];
                    if (theme.primary === currentPrimary) {
                        card.classList.add('active');
                    }
                });
            });
        }

        // Save all settings
        document.getElementById('saveBtn').addEventListener('click', function() {
            showSpinner('Saving settings...');
            
            fetch(API_URL + '?action=update', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(settingsData)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showAlert('Settings saved successfully!', 'success');
                    localStorage.setItem('themeUpdated', Date.now().toString());
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showAlert(data.message || 'Failed to save settings', 'danger');
                }
            })
            .catch(err => {
                showAlert('Error saving settings: ' + err.message, 'danger');
            })
            .finally(() => hideSpinner());
        });

        // Restore default settings
        document.getElementById('restoreBtn').addEventListener('click', function() {
            if (!confirm('Are you sure you want to restore all settings to default?')) return;

            showSpinner('Restoring default settings...');
            
            const defaultSettings = {
                website_name: 'EXpresso Caffe',
                admin_primary_color: '#7f5539',
                admin_secondary_color: '#7b6a58',
                admin_accent_color: '#dec0ad',
                customer_primary_color: '#7f5539',
                customer_secondary_color: '#7b6a58',
                customer_accent_color: '#dec0ad',
                rider_primary_color: '#7f5539',
                rider_secondary_color: '#7b6a58',
                rider_accent_color: '#dec0ad'
            };

            fetch(API_URL + '?action=update', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(defaultSettings)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showAlert('Settings restored successfully!', 'success');
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showAlert(data.message || 'Failed to restore settings', 'danger');
                }
            })
            .catch(err => {
                showAlert('Error restoring settings: ' + err.message, 'danger');
            })
            .finally(() => hideSpinner());
        });

        // Helper functions
        function showAlert(message, type = 'info') {
            const alertContainer = document.getElementById('alertContainer');
            alertContainer.innerHTML = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            
            setTimeout(() => {
                const alert = alertContainer.querySelector('.alert');
                if (alert) new bootstrap.Alert(alert).close();
            }, 5000);
        }

        function showSpinner(message = 'Loading...') {
            document.getElementById('alertContainer').innerHTML = `
                <div class="alert alert-info">
                    <div class="spinner-border spinner-border-sm me-2" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    ${message}
                </div>
            `;
        }

        function hideSpinner() {
            // Alert will auto-dismiss or be replaced
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', initializeThemePresets);
    </script>

          <!-- FOOTER -->
          <?php include 'footer.php'; ?>

        </div>
      </main>
    </div>

</body>
</html>