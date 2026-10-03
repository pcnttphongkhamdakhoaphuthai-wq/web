'use strict';

// Progressive enhancement only: valid forms keep the existing PHP POST action.
(function () {
  var form = document.getElementById('login-form');
  if (!form) return;

  var cccd = document.getElementById('cccd');
  var password = document.getElementById('password');
  var toggle = document.getElementById('toggle-password');
  var submit = document.getElementById('submit-login');
  var status = document.getElementById('form-status');
  var pending = false;
  if (!cccd || !password || !submit) return;

  function resetSubmission() {
    pending = false;
    submit.disabled = false;
    form.removeAttribute('aria-busy');
    var label = submit.querySelector('span');
    if (label) label.textContent = 'Đăng nhập';
  }

  function setError(input, message) {
    var output = document.getElementById(input.id + '-error');
    input.setAttribute('aria-invalid', String(Boolean(message)));
    if (!output) return;
    output.textContent = message;
    output.hidden = !message;
  }

  if (toggle) {
    toggle.addEventListener('click', function () {
      var reveal = password.type === 'password';
      password.type = reveal ? 'text' : 'password';
      toggle.setAttribute('aria-pressed', String(reveal));
      toggle.setAttribute('aria-label', reveal ? 'Ẩn mật khẩu' : 'Hiện mật khẩu');
    });
  }

  [cccd, password].forEach(function (input) {
    input.addEventListener('input', function () {
      setError(input, '');
      if (status) status.hidden = true;
    });
  });

  form.addEventListener('submit', function (event) {
    if (pending) {
      event.preventDefault();
      return;
    }

    var cccdError = /^\d{12}$/.test(cccd.value.trim()) ? '' : 'Vui lòng nhập đủ 12 chữ số CCCD.';
    var passwordError = password.value.length ? '' : 'Vui lòng nhập mật khẩu.';
    setError(cccd, cccdError);
    setError(password, passwordError);
    if (cccdError || passwordError) {
      event.preventDefault();
      (cccdError ? cccd : password).focus();
      return;
    }

    // Keep native checks for any required CAPTCHA fields before disabling submit.
    if (!form.reportValidity()) {
      event.preventDefault();
      return;
    }

    pending = true;
    submit.disabled = true;
    form.setAttribute('aria-busy', 'true');
    if (status) status.hidden = true;
    var label = submit.querySelector('span');
    if (label) label.textContent = 'Đang đăng nhập…';
  });

  // Restore the button when returning from another page via the back/forward cache.
  window.addEventListener('pageshow', resetSubmission);
})();
