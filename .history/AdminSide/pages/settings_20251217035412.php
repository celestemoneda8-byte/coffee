<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db_connect.php';

session_start();

// Protect page
if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
    header('Location: ' . ADMIN_BASE_URL . '/includes/admin-login.php');
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
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Main Admin Stylesheet -->
    <link rel="stylesheet" href="../css/main.css?v=1.1">
    <!-- Complete Theme Stylesheet -->
    <link rel="stylesheet" href="../css/theme-complete.css?v=1.0">
    
    <!-- Dynamic Theme Stylesheet -->
    <style id="dynamicTheme">
        :root {
            --admin-primary: <?php echo htmlspecialchars($settings['admin_primary_color'] ?? '#7f5539'); ?>;
            --admin-primary-light: <?php echo htmlspecialchars($settings['admin_primary_color'] ?? '#7f5539'); ?>cc;
            --admin-primary-dark: <?php echo htmlspecialchars($settings['admin_primary_color'] ?? '#7f5539'); ?>cc;
            --admin-secondary: <?php echo htmlspecialchars($settings['admin_secondary_color'] ?? '#7b6a58'); ?>;
            --admin-secondary-light: <?php echo htmlspecialchars($settings['admin_secondary_color'] ?? '#7b6a58'); ?>cc;
            --admin-secondary-dark: <?php echo htmlspecialchars($settings['admin_secondary_color'] ?? '#7b6a58'); ?>cc;
            --admin-accent: <?php echo htmlspecialchars($settings['admin_accent_color'] ?? '#dec0ad'); ?>;
            --admin-accent-dark: <?php echo htmlspecialchars($settings['admin_accent_color'] ?? '#dec0ad'); ?>cc;
            --page-bg: <?php echo htmlspecialchars($settings['admin_primary_color'] ?? '#7f5539'); ?>15;
        }
    </style>
    
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
                <h5 class="mb-0"><i class="bi bi-palette me-2"></i> Admin Panel Theme - Coffee</h5>
            </div>
            <div class="card-body">
                <p class="text-muted mb-4">Current coffee theme colors applied to the admin dashboard</p>
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="text-center">
                            <h6 class="mb-2">Primary Color</h6>
                            <div class="color-preview-box" style="background-color: #7f5539; margin-bottom: 0.5rem;"></div>
                            <code>#7f5539</code>
                            <p class="small text-muted mt-1">Coffee Brown</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center">
                            <h6 class="mb-2">Secondary Color</h6>
                            <div class="color-preview-box" style="background-color: #7b6a58; margin-bottom: 0.5rem;"></div>
                            <code>#7b6a58</code>
                            <p class="small text-muted mt-1">Muted Brown</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center">
                            <h6 class="mb-2">Accent Color</h6>
                            <div class="color-preview-box" style="background-color: #dec0ad; margin-bottom: 0.5rem;"></div>
                            <code>#dec0ad</code>
                            <p class="small text-muted mt-1">Light Tan</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Simple logo upload handler
        document.getElementById('logoInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('logo', file);
            formData.append('action', 'upload_logo');

            fetch('../api/settings_api.php', {
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
                } else {
                    alert(data.message || 'Upload failed');
                }
            })
            .catch(err => {
                alert('Error uploading logo: ' + err.message);
            });
        });

        // Drag and drop for logo upload
        const uploadArea = document.querySelector('.upload-area');
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            uploadArea.addEventListener(eventName, () => {
                uploadArea.style.backgroundColor = 'rgba(13, 110, 253, 0.1)';
            });
        });

        ['dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, () => {
                uploadArea.style.backgroundColor = 'transparent';
            });
        });

        uploadArea.addEventListener('drop', function(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            document.getElementById('logoInput').files = files;
            
            // Trigger change event
            const event = new Event('change', { bubbles: true });
            document.getElementById('logoInput').dispatchEvent(event);
        });
    </script>

          <!-- FOOTER -->
          <?php include 'footer.php'; ?>

        </div>
      </main>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/admin-login.js"></script>
</body>
</html>