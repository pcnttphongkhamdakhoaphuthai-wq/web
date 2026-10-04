<?php
declare(strict_types=1);

require_once 'config.php';
require_patient_login();

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$userId = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'delete_appointment') {
        $guardError = validate_form_guard('patient_appointment_manage', 10, 300, 0, false);
        $appointmentId = (int) ($_POST['appointment_id'] ?? 0);
        if ($guardError !== null) {
            set_flash('error', $guardError);
            redirect('dashboard.php');
        }

        $appointment = find_patient_appointment($conn, $appointmentId, $userId);
        if (!$appointment) {
            set_flash('error', 'Không tìm thấy lịch hẹn cần xóa.');
            redirect('dashboard.php');
        }

        if (!appointment_is_editable($appointment)) {
            set_flash('error', 'Lịch hẹn này không còn được phép xóa.');
            redirect('dashboard.php');
        }

        delete_appointment_by_id($conn, $appointmentId);
        set_flash('success', 'Đã xóa lịch hẹn.');
        redirect('dashboard.php');
    }

    $guardError = validate_form_guard('patient_chat', 12, 300, 0, false);
    if ($guardError !== null) {
        set_flash('error', $guardError);
        redirect('dashboard.php#overview');
    }

    if ($action === 'send_chat_message') {
        $message = trim($_POST['message'] ?? '');
        if ($message === '') {
            set_flash('error', 'Vui lòng nhập nội dung cần hỗ trợ.');
            redirect('dashboard.php#overview');
        }

        save_chat_message($userId, 'patient', $message);
        $matched = find_quick_reply(null, $message);
        $botReply = $matched['answer'] ?? chatbot_default_reply();
        save_chat_message($userId, 'bot', $botReply);
        set_flash('success', 'Đã gửi câu hỏi cho bot hỗ trợ.');
        redirect('dashboard.php#overview');
    }

    if ($action === 'send_quick_reply') {
        $quickReplyId = (int) ($_POST['quick_reply_id'] ?? 0);
        $reply = find_quick_reply($quickReplyId);
        if (!$reply) {
            set_flash('error', 'Câu hỏi nhanh không còn tồn tại.');
            redirect('dashboard.php#overview');
        }

        save_chat_message($userId, 'patient', (string) $reply['question']);
        save_chat_message($userId, 'bot', (string) $reply['answer']);
        set_flash('success', 'Đã gửi câu hỏi nhanh.');
        redirect('dashboard.php#overview');
    }
}

$stmt = $conn->prepare(
    'SELECT mr.id, mr.visit_date, mr.diagnosis, mr.prescription, mr.result_file, d.name AS doctor_name
     FROM medical_records mr
     INNER JOIN doctors d ON d.id = mr.doctor_id
     WHERE mr.patient_id = ?
     ORDER BY mr.visit_date DESC, mr.id DESC'
);
$stmt->bind_param('i', $userId);
$stmt->execute();
$records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$stmt = $conn->prepare(
    'SELECT a.id, a.doctor_id, a.appointment_date, a.reason, a.status, d.name AS doctor_name, d.department
     FROM appointments a
     INNER JOIN doctors d ON d.id = a.doctor_id
     WHERE a.patient_id = ?
     ORDER BY a.appointment_date DESC, a.id DESC'
);
$stmt->bind_param('i', $userId);
$stmt->execute();
$appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$doctors = $conn->query('SELECT name, title, department, specialties, bio, photo_path FROM doctors ORDER BY id ASC');
$quickReplies = get_active_quick_replies(8);
$chatPayload = get_patient_chat_poll_payload($userId, 24);
$chatMessages = $chatPayload['messages'];
$announcements = get_patient_announcements($conn, 6);
$newsPosts = get_recent_news_posts(4, true);
$customerResources = get_customer_resources(8, true);
$appointmentsEnabled = appointments_enabled();
$clinic = site_settings([
    'clinic_intro' => 'Phòng khám cung cấp dịch vụ đặt lịch, trả kết quả và quản lý hồ sơ khám bệnh trên cùng một hệ thống trực tuyến.',
    'support_hotline' => '1900 0000',
    'support_email' => 'congnghethongtin247@gmail.com',
    'clinic_address' => '',
    'google_maps_url' => '',
    'apple_maps_url' => '',
    'chatbot_intro' => 'Chat hỗ trợ giúp bệnh nhân xem nhanh hướng dẫn thường gặp và gửi câu hỏi cho bộ phận hỗ trợ.',
]);

render_header('Bảng điều khiển');
render_hero('Cổng hỗ trợ dịch vụ', 'Bảng điều khiển tổng hợp có phân quyền rõ ràng, lịch khám, kết quả xét nghiệm, hồ sơ bệnh án và khu vực hỗ trợ nhanh.');
?>
<style>
.dashboard-tab { display: none !important; }
.dashboard-tab.tab-active { display: block !important; }
.records-list { display: flex; flex-direction: column; gap: 16px; }
.record-card { border: 1px solid var(--border, #e2e8f0); border-radius: 10px; overflow: hidden; background: #fff; }
.record-header { display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; background: #f8fafc; border-bottom: 1px solid var(--border, #e2e8f0); gap: 12px; flex-wrap: wrap; }
.record-date { font-weight: 600; font-size: 14px; margin-right: 14px; }
.record-doctor { color: #475569; font-size: 14px; }
.btn-pdf { display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 6px; background: #0ea5e9; color: #fff; font-size: 13px; font-weight: 500; text-decoration: none; white-space: nowrap; }
.btn-pdf:hover { background: #0284c7; }
.record-body { padding: 16px; display: flex; flex-direction: column; gap: 14px; }
.record-label { font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; color: #94a3b8; margin-bottom: 6px; }
.record-content { font-size: 14px; color: #1e293b; line-height: 1.6; white-space: pre-wrap; background: #f8fafc; border-radius: 6px; padding: 10px 14px; }
</style>
<div class="wrap">
  <?php render_flash(); ?>

  <div class="dashboard-layout">
    <aside class="sidebar">
      <h3>Xin chào: <?= e($_SESSION['name']) ?></h3>
      <p class="muted">Bạn đang đăng nhập với vai trò bệnh nhân và chỉ có quyền xem dữ liệu của chính mình.</p>

      <nav class="sidebar-nav">
        <a href="#overview" data-tab="tab-overview" class="active">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
          <span>Tổng quan</span>
        </a>
        <a href="#appointments" data-tab="tab-appointments">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
          <span>Lịch khám</span>
        </a>
        <a href="#records" data-tab="tab-records">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
          <span>Kết quả & Hồ sơ</span>
        </a>
        <a href="#support" data-tab="tab-support">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
          <span>Hỗ trợ & Liên hệ</span>
        </a>
        <a href="account.php">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
          <span>Quản lý tài khoản</span>
        </a>
        <a href="#" data-chat-open="true">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2a2 2 0 0 1 2 2c0 .74-.4 1.38-1 1.72V7h2a7 7 0 0 1 7 7v4a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-4a7 7 0 0 1 7-7h2V5.72c-.6-.34-1-.98-1-1.72a2 2 0 0 1 2-2z"/><path d="M9 13h.01"/><path d="M15 13h.01"/><path d="M10 17h4"/></svg>
          <span>Bot chat nổi</span>
        </a>
      </nav>
      <?php if ($appointmentsEnabled): ?>
      <div class="actions">
        <a class="btn" href="book_appointment.php" style="width:100%;text-align:center;">Đặt lịch khám</a>
      </div>
      <?php endif; ?>
    </aside>

    <div>
      <?php require __DIR__ . '/dashboard_tabs/tab_overview.php'; ?>
      <?php require __DIR__ . '/dashboard_tabs/tab_appointments.php'; ?>
      <?php require __DIR__ . '/dashboard_tabs/tab_records.php'; ?>
      <?php require __DIR__ . '/dashboard_tabs/tab_support.php'; ?>
    </div>
  </div>
</div>
<script>
(function() {
  function getTabEls() {
    return document.querySelectorAll('[id^="tab-"].dashboard-tab');
  }

  function activateTab(tabId) {
    var allTabs = document.querySelectorAll('.dashboard-tab');
    var navLinks = document.querySelectorAll('[data-tab]');
    var found = false;

    allTabs.forEach(function(el) {
      el.classList.remove('tab-active');
    });

    var target = document.getElementById(tabId);
    if (target && target.classList.contains('dashboard-tab')) {
      target.classList.add('tab-active');
      found = true;
    } else if (allTabs.length > 0) {
      allTabs[0].classList.add('tab-active');
    }

    navLinks.forEach(function(a) {
      if (a.getAttribute('data-tab') === tabId) {
        a.classList.add('active');
      } else {
        a.classList.remove('active');
      }
    });
  }

  function getHashTab() {
    var hash = window.location.hash.replace('#', '');
    return hash ? 'tab-' + hash : null;
  }

  var initial = getHashTab();
  activateTab(initial || 'tab-overview');

  document.querySelectorAll('[data-tab]').forEach(function(a) {
    a.addEventListener('click', function(e) {
      var tabId = a.getAttribute('data-tab');
      if (tabId) {
        e.preventDefault();
        history.pushState(null, '', '#' + tabId.replace('tab-', ''));
        activateTab(tabId);
        window.scrollTo({ top: 0, behavior: 'smooth' });
      }
    });
  });

  window.addEventListener('popstate', function() {
    var tabId = getHashTab();
    if (tabId) activateTab(tabId);
  });

  window.addEventListener('hashchange', function() {
    var tabId = getHashTab();
    if (tabId) activateTab(tabId);
  });
})();
</script>
<div class="floating-chat" data-floating-chat data-chat-endpoint="patient_chat_poll.php" data-chat-latest-id="<?= (int) $chatPayload['latest_chat_id'] ?>">
  <div class="floating-chat-panel" id="floating-chat-panel" aria-live="polite">
    <div class="floating-chat-header">
      <div>
        <strong>Bot hỗ trợ phòng khám</strong>
        <div class="floating-chat-min">Trả lời nhanh các câu hỏi thường gặp</div>
      </div>
      <button type="button" class="btn floating-chat-close" data-chat-close="true">Thu gọn</button>
    </div>
    <div class="floating-chat-body">
      <div class="quick-replies">
        <?php foreach ($quickReplies as $reply): ?>
          <form method="post" class="inline-form">
            <?php render_form_guard('patient_chat'); ?>
            <input type="hidden" name="action" value="send_quick_reply">
            <input type="hidden" name="quick_reply_id" value="<?= (int) $reply['id'] ?>">
            <button type="submit" class="btn-light"><?= e($reply['question']) ?></button>
          </form>
        <?php endforeach; ?>
      </div>

      <div class="chat-thread" data-chat-thread>
        <?php if ($chatMessages === []): ?>
          <div class="empty-state">Chưa có tin nhắn nào. Bạn có thể chọn một câu hỏi nhanh hoặc nhập nội dung cần hỗ trợ.</div>
        <?php else: ?>
          <?php foreach ($chatMessages as $chat): ?>
            <div class="chat-message <?= e($chat['sender']) ?>">
              <strong><?= e($chat['sender'] === 'patient' ? 'Bạn' : 'Hỗ trợ phòng khám') ?></strong>
              <div><?= nl2br(e($chat['message'])) ?></div>
              <div class="muted text-sm" style="margin-top:8px;"><?= e(date('d/m/Y H:i', strtotime($chat['created_at']))) ?></div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <form method="post" class="grid">
        <?php render_form_guard('patient_chat'); ?>
        <input type="hidden" name="action" value="send_chat_message">
        <div>
          <label for="chat_message">Nhập câu hỏi cho bot hỗ trợ</label>
          <textarea id="chat_message" name="message" placeholder="Ví dụ: Tôi muốn xem kết quả xét nghiệm, cần làm thế nào?"></textarea>
        </div>
        <div class="actions">
          <button type="submit">Gửi câu hỏi</button>
        </div>
      </form>
    </div>
  </div>
  <div class="floating-chat-nudge" data-chat-nudge hidden>
    <button type="button" class="floating-chat-nudge-close" data-chat-nudge-close="true" aria-label="Đóng thông báo chat">×</button>
    <button type="button" class="floating-chat-nudge-body" data-chat-nudge-open="true">
      <span class="floating-chat-avatar" aria-hidden="true">HT</span>
      <span class="floating-chat-bubble">
        <strong>Tin nhắn mới</strong>
        <span data-chat-nudge-text>Hỗ trợ phòng khám vừa phản hồi.</span>
      </span>
    </button>
  </div>
  <button type="button" class="btn floating-chat-launcher" data-chat-toggle="true" aria-controls="floating-chat-panel" aria-expanded="false">
    <span>Chat</span>
    <span class="floating-chat-count"><?= (int) $chatPayload['message_count'] ?></span>
  </button>
</div>
<?php render_footer(); ?>
