<?php
// admin/appearance.php (enhanced) - includes Save All, Revert Defaults, Export/Import
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/../includes/settings_store.php';

// Permission
if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin'] || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    echo "Permission denied.";
    exit;
}

if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['csrf_token'];

$current = settings_get_all($conn ?? null);
function s($arr,$k,$def='') { return isset($arr[$k]) ? $arr[$k] : $def; }
$defaults = [
    'site_name' => 'EXpresso',
    'almond' => '#ede0d4',
    'dun' => '#e6ccb2',
    'tan' => '#ddb892',
    'chamoisee' => '#b08968',
    'coffee' => '#7f5539',
    'muted' => '#9a8b7b',
    'bg' => '#f6e9d6'
];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Appearance Settings — Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .color-sample { width:32px; height:32px; border-radius:6px; border:1px solid rgba(0,0,0,0.06); display:inline-block; vertical-align:middle; }
    .preview-bar { padding:12px; border-radius:8px; background:#fff; box-shadow:0 4px 12px rgba(0,0,0,0.04); }
    iframe#previewFrame { width:100%; height:420px; border-radius:8px; border:1px solid rgba(0,0,0,0.06); }
  </style>
</head>
<body class="p-4 admin-page">
  <div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h3 class="mb-0">Appearance Settings</h3>
        <small class="text-muted">Edit brand name and palette for customer pages. Use Save All to commit everything at once.</small>
      </div>
      <div class="d-flex gap-2">
        <button id="btnRevert" class="btn btn-sm btn-outline-secondary">Revert Defaults</button>
        <button id="btnExport" class="btn btn-sm btn-outline-primary">Export JSON</button>
        <label class="btn btn-sm btn-outline-info mb-0">
          Import <input type="file" id="importFile" accept="application/json" style="display:none">
        </label>
        <button id="btnSaveAll" class="btn btn-sm btn-primary">Save All</button>
      </div>
    </div>

    <div class="row g-3">
      <div class="col-12 col-md-6">
        <div class="card p-3 mb-3">
          <label class="form-label">Site name</label>
          <input id="site_name" class="form-control mb-2" maxlength="60" value="<?php echo htmlspecialchars(s($current,'site_name',$defaults['site_name'])); ?>">

          <h6 class="mt-3">Colors</h6>
          <?php $vars=['almond','dun','tan','chamoisee','coffee','muted','bg']; foreach($vars as $v): ?>
            <div class="mb-3">
              <label class="form-label text-capitalize"><?php echo $v; ?></label>
              <div class="d-flex gap-2 align-items-center">
                <input type="color" id="color_<?php echo $v; ?>" value="<?php echo htmlspecialchars(s($current,$v,$defaults[$v])); ?>">
                <input type="text" id="hex_<?php echo $v; ?>" class="form-control form-control-sm w-50" value="<?php echo htmlspecialchars(s($current,$v,$defaults[$v])); ?>">
                <div id="sample_<?php echo $v; ?>" class="color-sample" style="background:<?php echo htmlspecialchars(s($current,$v,$defaults[$v])); ?>;"></div>
              </div>
            </div>
          <?php endforeach; ?>

          <div id="save_status" class="text-success" style="display:none">Saved</div>
          <div id="save_error" class="text-danger" style="display:none"></div>
        </div>
      </div>

      <div class="col-12 col-md-6">
        <h6>Live Preview (interactive)</h6>
        <div class="card p-3 mb-3">
          <div id="preview" class="preview-bar">
            <iframe id="previewFrame" src="/" title="Live preview (customer)" sandbox="allow-same-origin allow-scripts allow-forms"></iframe>
          </div>
          <div class="mt-3 text-muted small">Preview updates automatically as you edit. Save All commits changes to storage.</div>
        </div>
      </div>
    </div>
  </div>

<script>
(function(){
  var csrf = '<?php echo $csrf; ?>';
  var vars = ['almond','dun','tan','chamoisee','coffee','muted','bg'];
  var defaults = <?php echo json_encode($defaults, JSON_UNESCAPED_UNICODE); ?>;

  function updateSample(name, value) {
    var sample = document.getElementById('sample_' + name);
    if (sample) sample.style.background = value;
    var hex = document.getElementById('hex_' + name);
    var picker = document.getElementById('color_' + name);
    if (hex && hex.value !== value) hex.value = value;
    if (picker && picker.value !== value) picker.value = value;
  }

  function collectSettings() {
    var s = {};
    vars.forEach(function(v){
      s[v] = document.getElementById('color_' + v).value;
    });
    s.site_name = document.getElementById('site_name').value.trim() || defaults.site_name;
    return s;
  }

  function broadcast(settings) {
    var iframe = document.getElementById('previewFrame');
    try {
      iframe.contentWindow.postMessage({type:'theme_update', data: settings}, window.location.origin);
    } catch(e){}
  }

  function showStatus(msg, isError) {
    var s = document.getElementById('save_status');
    var e = document.getElementById('save_error');
    if (isError) {
      if (s) s.style.display = 'none';
      if (e) { e.style.display = 'block'; e.textContent = msg; }
    } else {
      if (e) e.style.display = 'none';
      if (s) { s.style.display = 'block'; s.textContent = msg || 'Saved'; }
      setTimeout(function(){ if (s) s.style.display = 'none'; }, 1200);
    }
  }

  // Save single setting via existing endpoint (optional) - kept for immediate autosave
  function saveSingle(name, value) {
    var fd = new FormData();
    fd.append('csrf', csrf);
    fd.append('name', name);
    fd.append('value', value);
    return fetch('save_appearance.php', { method:'POST', body: fd, credentials:'same-origin' })
      .then(function(r){ return r.json(); });
  }

  // Bulk save via new endpoint
  function saveAllBulk(settings) {
    return fetch('save_appearance_bulk.php', {
      method: 'POST',
      credentials: 'same-origin',
      headers: {'Content-Type':'application/json','X-CSRF-Token': csrf},
      body: JSON.stringify(settings)
    }).then(function(r){ return r.json(); });
  }

  // Wire inputs with local broadcast and autosave (single) debounce
  vars.forEach(function(v){
    var picker = document.getElementById('color_' + v);
    var text = document.getElementById('hex_' + v);
    [picker, text].forEach(function(el){
      if (!el) return;
      el.addEventListener('input', function(){
        var val = (this.value || '').trim();
        if (!/^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/.test(val)) return;
        updateSample(v, val);
        broadcast(collectSettings());
      });
    });
  });

  var nameInput = document.getElementById('site_name');
  if (nameInput) {
    nameInput.addEventListener('input', function(){
      broadcast(collectSettings());
    });
  }

  // Save All button
  document.getElementById('btnSaveAll').addEventListener('click', function(){
    var settings = collectSettings();
    showStatus('Saving...');
    saveAllBulk(settings).then(function(json){
      if (json && json.success) {
        showStatus('All saved');
        broadcast(settings);
        // update localStorage cache for theme-loader
        try {
          localStorage.setItem('site_theme_cache_v1', JSON.stringify({ts: Date.now(), data: settings}));
        } catch(e){}
      } else {
        showStatus(json.error || 'Save failed', true);
      }
    }).catch(function(){ showStatus('Network error', true); });
  });

  // Revert defaults
  document.getElementById('btnRevert').addEventListener('click', function(){
    if (!confirm('Revert to default theme values?')) return;
    // set UI back to defaults
    vars.forEach(function(v){
      document.getElementById('color_' + v).value = defaults[v];
      document.getElementById('hex_' + v).value = defaults[v];
      updateSample(v, defaults[v]);
    });
    document.getElementById('site_name').value = defaults.site_name;
    broadcast(collectSettings());
  });

  // Export JSON
  document.getElementById('btnExport').addEventListener('click', function(){
    var settings = collectSettings();
    var blob = new Blob([JSON.stringify(settings, null, 2)], {type: 'application/json'});
    var url = URL.createObjectURL(blob);
    var a = document.createElement('a');
    a.href = url;
    a.download = 'site_theme_' + (new Date().toISOString().slice(0,19)).replace(/[:T]/g,'-') + '.json';
    document.body.appendChild(a);
    a.click();
    a.remove();
    URL.revokeObjectURL(url);
  });

  // Import JSON
  document.getElementById('importFile').addEventListener('change', function(e){
    var f = this.files && this.files[0];
    if (!f) return;
    var r = new FileReader();
    r.onload = function(ev){
      try {
        var obj = JSON.parse(ev.target.result);
        // only accept allowed keys
        var allowed = ['site_name','almond','dun','tan','chamoisee','coffee','muted','bg'];
        var any=false;
        allowed.forEach(function(k){
          if (obj[k]) {
            any=true;
            if (k === 'site_name') document.getElementById('site_name').value = obj[k].substring(0,60);
            else {
              var v = obj[k];
              if (/^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/.test(v)) {
                document.getElementById('color_' + k).value = v;
                document.getElementById('hex_' + k).value = v;
                updateSample(k, v);
              }
            }
          }
        });
        if (any) {
          broadcast(collectSettings());
          showStatus('Imported (remember to Save All to persist)');
        } else {
          showStatus('Import file had no valid keys', true);
        }
      } catch(e){ showStatus('Invalid JSON file', true); }
      // clear input
      e.target.value = '';
    };
    r.readAsText(f);
  });

  // Init: update samples and broadcast initial settings to iframe when it loads
  (function init(){
    var cur = collectSettings();
    vars.forEach(function(v){ updateSample(v, cur[v]); });
    var iframe = document.getElementById('previewFrame');
    iframe.addEventListener('load', function(){ broadcast(cur); });
  })();

})();
</script>
</body>
</html>