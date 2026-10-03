<?php
declare(strict_types=1);

require_once 'config.php';

if (isset($_SESSION['user_id'])) {
    $redirect = trim((string) ($_GET['redirect'] ?? ''));
    if ($redirect === 'records') {
        redirect('dashboard.php#records');
    }
    redirect('dashboard.php');
}

$redirectTarget = trim((string) ($_POST['redirect'] ?? $_GET['redirect'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $guardError = validate_form_guard('patient_login', 10, 600, 0);
    $cccd = normalize_single_line_input($_POST['cccd'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $cooldownIdentity = $cccd !== '' ? $cccd : 'guest';
    $cooldownRemaining = login_submit_cooldown_remaining('patient_login_submit', $cooldownIdentity, 2, 10);
    $lockedUntil = $cccd !== '' ? get_login_lockout('patient_login', $cccd) : null;

    if ($guardError !== null) {
        security_log('patient_login_guard_blocked', ['cccd' => $cccd]);
        set_flash('error', $guardError);
    } elseif ($cooldownRemaining > 0) {
        security_log('patient_login_cooldown_hit', ['cccd' => $cccd, 'remaining' => $cooldownRemaining]);
        set_flash('error', 'Thao tác quá nhanh. Vui lòng đợi ' . $cooldownRemaining . ' giây trước khi thử lại.');
    } elseif ($lockedUntil !== null) {
        $minutesLeft = max(1, (int) ceil(($lockedUntil - time()) / 60));
        security_log('patient_login_attempt_while_locked', ['cccd' => $cccd, 'minutes_left' => $minutesLeft]);
        set_flash('error', 'Tài khoản tạm thời bị khóa do đăng nhập sai nhiều lần. Vui lòng thử lại sau ' . $minutesLeft . ' phút.');
    } else {
        $patient = find_patient_by_cccd($conn, $cccd);
        if ($patient && password_verify($password, (string) ($patient['password_hash'] ?? ''))) {
            record_login_success('patient_login', $cccd);
            security_log('patient_login_success', ['patient_id' => (int) $patient['id'], 'cccd' => $cccd]);
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $patient['id'];
            $_SESSION['role'] = 'patient';
            $_SESSION['name'] = (string) ($patient['full_name'] ?? 'Bệnh nhân');
            $_SESSION['cccd'] = (string) ($patient['cccd'] ?? $cccd);
            $_SESSION['phone'] = (string) ($patient['phone'] ?? '');

            if ($redirectTarget === 'records') {
                redirect('dashboard.php#records');
            }
            redirect('dashboard.php');
        } else {
            $lockoutState = record_login_failure('patient_login', $cccd !== '' ? $cccd : 'guest', 5, 86400);
            security_log('patient_login_failure', [
                'cccd' => $cccd,
                'attempt_count' => $lockoutState['attempts'],
                'locked' => $lockoutState['locked'],
            ]);

            if ($lockoutState['locked']) {
                $hoursLeft = max(1, (int) ceil(($lockoutState['locked_until'] - time()) / 3600));
                set_flash('error', 'Đăng nhập sai quá 5 lần. Tài khoản bị tạm khóa 24 giờ. Vui lòng thử lại sau ' . $hoursLeft . ' giờ hoặc liên hệ hotline để được hỗ trợ.');
            } else {
                $remainingAttempts = max(0, 5 - $lockoutState['attempts']);
                set_flash('error', 'Số CCCD hoặc mật khẩu không chính xác. Bạn còn ' . $remainingAttempts . ' lần thử.');
            }
        }
    }
}

render_header('Đăng nhập người bệnh · Phú Thái');
?>

<div class="container login-layout">
  <section class="intro" aria-labelledby="intro-title">
    <div class="eyebrow">CỔNG DỊCH VỤ NGƯỜI BỆNH</div>
    <h1 id="intro-title">Kết nối dễ dàng.<br><span>An tâm chăm sóc.</span></h1>
    <p class="intro-description">Tra cứu kết quả khám, theo dõi hồ sơ và nhận hỗ trợ từ Phòng khám đa khoa Phú Thái.</p>
    <div class="benefit-list">
      <div class="benefit">
        <span class="benefit-icon"><svg class="icon" aria-hidden="true"><use href="#i-file"/></svg></span>
        <div>
          <h2>Kết quả khám trong tầm tay</h2>
          <p>Xem và tải kết quả của từng lần khám.</p>
        </div>
      </div>
      <div class="benefit">
        <span class="benefit-icon"><svg class="icon" aria-hidden="true"><use href="#i-folder"/></svg></span>
        <div>
          <h2>Hồ sơ được sắp xếp rõ ràng</h2>
          <p>Dễ tìm lại thông tin khi bạn cần.</p>
        </div>
      </div>
      <div class="benefit">
        <span class="benefit-icon"><svg class="icon" aria-hidden="true"><use href="#i-chat"/></svg></span>
        <div>
          <h2>Luôn có hướng dẫn để bắt đầu</h2>
          <p>Nhận trợ giúp về tài khoản và cách sử dụng.</p>
        </div>
      </div>
    </div>
    <div class="intro-caption"><span class="caption-line"></span> Đồng hành cùng người bệnh, từ những điều nhỏ nhất.</div>
    <span class="decor-cross" aria-hidden="true"></span>
  </section>

  <section class="login-card" aria-labelledby="login-title">
    <div class="card-heading">
      <span class="card-icon"><svg class="icon" aria-hidden="true"><use href="#i-lock"/></svg></span>
      <span class="card-kicker">TÀI KHOẢN NGƯỜI BỆNH</span>
    </div>
    <h2 id="login-title">Chào mừng bạn trở lại</h2>
    <p class="card-description">Đăng nhập để xem kết quả và hồ sơ của bạn.</p>

    <?php render_flash(); ?>

    <form id="login-form" method="POST" action="login.php" novalidate>
      <?php echo render_form_guard('patient_login'); ?>
      <?php if ($redirectTarget !== ''): ?>
        <input type="hidden" name="redirect" value="<?php echo e($redirectTarget); ?>">
      <?php endif; ?>

      <div class="field">
        <label for="cccd">Số CCCD</label>
        <input id="cccd" name="cccd" type="text" inputmode="numeric" autocomplete="username" maxlength="12" placeholder="Nhập 12 chữ số CCCD" required aria-describedby="cccd-hint cccd-error" value="<?php echo e($cccd ?? ''); ?>">
        <p id="cccd-hint" class="field-hint">Dùng số CCCD đã đăng ký với phòng khám.</p>
        <p id="cccd-error" class="field-error" hidden></p>
      </div>

      <div class="field password-field">
        <div class="label-row">
          <label for="password">Mật khẩu</label>
          <a href="forgot_password.php" class="inline-link">Quên mật khẩu?</a>
        </div>
        <div class="password-wrap">
          <input id="password" name="password" type="password" autocomplete="current-password" placeholder="Nhập mật khẩu của bạn" required aria-describedby="password-error">
          <button id="toggle-password" type="button" class="password-toggle" aria-label="Hiện mật khẩu" aria-pressed="false">
            <svg class="icon" aria-hidden="true"><use href="#i-eye"/></svg>
          </button>
        </div>
        <p id="password-error" class="field-error" hidden></p>
      </div>

      <?php render_captcha('patient_login'); ?>

      <button class="primary-button login-submit" type="submit" id="submit-login">
        <span>Đăng nhập</span>
        <svg class="icon" aria-hidden="true"><use href="#i-arrow"/></svg>
      </button>
      <p id="form-status" class="form-status" role="status" aria-live="polite" hidden></p>
      <noscript><p class="field-error">Vui lòng bật JavaScript trên trình duyệt để sử dụng đầy đủ chức năng.</p></noscript>
    </form>

    <div class="register-row">
      Bạn chưa có tài khoản? <a href="register.php" class="inline-link">Đăng ký ngay <span aria-hidden="true">↗</span></a>
    </div>
    <div class="card-divider"></div>
    <button class="card-help" type="button" data-dialog="guide">
      <svg class="icon" aria-hidden="true"><use href="#i-book"/></svg>
      <span>Lần đầu sử dụng? <strong>Xem hướng dẫn</strong></span>
    </button>
    <p class="demo-notice">Cổng dịch vụ y tế chính thức · Phòng khám đa khoa Phú Thái.</p>
  </section>
</div>

<script>
'use strict';
(function() {
  var form = document.getElementById('login-form');
  var cccd = document.getElementById('cccd');
  var password = document.getElementById('password');
  var toggle = document.getElementById('toggle-password');
  var submit = document.getElementById('submit-login');
  var status = document.getElementById('form-status');
  var pending = false;

  if (toggle && password) {
    toggle.addEventListener('click', function() {
      var reveal = password.type === 'password';
      password.type = reveal ? 'text' : 'password';
      toggle.setAttribute('aria-pressed', String(reveal));
      toggle.setAttribute('aria-label', reveal ? 'Ẩn mật khẩu' : 'Hiện mật khẩu');
    });
  }

  function setError(input, message) {
    var output = document.getElementById(input.id + '-error');
    if (!output) return;
    input.setAttribute('aria-invalid', String(Boolean(message)));
    output.textContent = message;
    output.hidden = !message;
  }

  if (cccd && password) {
    [cccd, password].forEach(function(input) {
      input.addEventListener('input', function() {
        setError(input, '');
        if (status) status.hidden = true;
      });
    });
  }

  if (form && submit) {
    form.addEventListener('submit', function(event) {
      if (pending) {
        event.preventDefault();
        return;
      }
      if (status) status.hidden = true;
      var cccdVal = cccd ? cccd.value.trim() : '';
      var passVal = password ? password.value : '';
      var cccdError = /^\d{12}$/.test(cccdVal) ? '' : 'Vui lòng nhập đủ 12 chữ số CCCD.';
      var passwordError = passVal.length > 0 ? '' : 'Vui lòng nhập mật khẩu.';

      if (cccd) setError(cccd, cccdError);
      if (password) setError(password, passwordError);

      if (cccdError || passwordError) {
        event.preventDefault();
        (cccdError && cccd ? cccd : password).focus();
        return;
      }

      pending = true;
      submit.disabled = true;
      form.setAttribute('aria-busy', 'true');
      var labelSpan = submit.querySelector('span');
      if (labelSpan) labelSpan.textContent = 'Đang đăng nhập…';
    });
  }
})();
</script>

<?php render_footer(); ?>
