// public/js/theme-loader.js
// Loads /get_theme.php and applies CSS variables + site-brand.
// Also listens for postMessage theme updates (for iframe preview).

(function(){
  'use strict';
  var url = '/get_theme.php';
  var storageKey = 'site_theme_cache_v1';

  function apply(settings) {
    if (!settings) return;
    var root = document.documentElement;
    if (settings.almond) root.style.setProperty('--almond', settings.almond);
    if (settings.dun) root.style.setProperty('--dun', settings.dun);
    if (settings.tan) root.style.setProperty('--tan', settings.tan);
    if (settings.chamoisee) root.style.setProperty('--chamoisee', settings.chamoisee);
    if (settings.coffee) root.style.setProperty('--coffee', settings.coffee);
    if (settings.muted) root.style.setProperty('--muted', settings.muted);
    if (settings.bg) root.style.setProperty('--bg', settings.bg);

    if (settings.site_name) {
      document.querySelectorAll('.site-brand').forEach(function(el){
        el.textContent = settings.site_name;
      });
      // optional: update title (only for public pages)
      if (document.body && !document.body.classList.contains('admin-page')) {
        document.title = settings.site_name + (document.title ? ' - ' + document.title : '');
      }
    }
  }

  function fetchAndApply() {
    fetch(url, { cache: 'no-cache' }).then(function(r){
      if (!r.ok) throw new Error('Network response not ok');
      return r.json();
    }).then(function(json){
      try { localStorage.setItem(storageKey, JSON.stringify({ts: Date.now(), data: json})); } catch(e){}
      apply(json);
    }).catch(function(){});
  }

  // Try to apply cached theme first
  try {
    var cached = localStorage.getItem(storageKey);
    if (cached) {
      var obj = JSON.parse(cached);
      if (obj && obj.data) apply(obj.data);
    }
  } catch(e){}

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', fetchAndApply);
  } else {
    fetchAndApply();
  }

  // Listen for live preview messages (from admin iframe)
  window.addEventListener('message', function(ev){
    try {
      var m = ev.data;
      if (m && m.type === 'theme_update' && typeof m.data === 'object') {
        apply(m.data);
      }
    } catch(e){}
  }, false);
})();