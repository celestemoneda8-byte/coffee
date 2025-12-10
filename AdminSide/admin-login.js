
document.addEventListener('DOMContentLoaded', function() {
  const toggleBtn = document.getElementById('togglePassword');
  const passwordInput = document.getElementById('password');
  const toggleIcon = document.getElementById('toggleIcon');

  if (toggleBtn && passwordInput && toggleIcon) {
      e.preventDefault();
      const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
      passwordInput.setAttribute('type', type);
      // swap icons
      toggleIcon.classList.toggle('bi-eye-fill');
      toggleIcon.classList.toggle('bi-eye-slash-fill');
    });
  }

  // optional: small submit UX change
  const form = document.querySelector('.login-form');
  if (form) {
    form.addEventListener('submit', function() {
      const btn = form.querySelector('.btn-submit');
      if (btn) {
        btn.textContent = 'Signing in...';
        btn.disabled = true;
      }
    });
  }
});