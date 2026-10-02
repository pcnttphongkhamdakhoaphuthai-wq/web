<div class="page-header">
  <div>
    <div class="page-title">Admin gốc và nội dung vận hành</div>
    <div class="page-desc">Trang này cho phép admin gốc chỉnh toàn bộ hồ sơ quản trị, nội dung giới thiệu phòng khám, hồ sơ bác sĩ, chatbot, câu trả lời nhanh và khả năng bật/tắt đặt lịch.</div>
  </div>
  <div style="text-align:right;flex-shrink:0;margin-left:20px">
    <div class="txt-sm txt-muted" style="margin-bottom:4px">Vai trò:</div>
    <div class="role-badge"><?= is_root_admin() ? 'Admin gốc' : e($_SESSION['admin_role'] ?? 'Nhân viên') ?></div>
  </div>
</div>

<?php if ($canCreateBackup): ?>
<div class="section-title">Backup dữ liệu</div>
<div class="c">
  <?php if ($latestBackup): ?>
    <div class="f-row">
      <span class="f-label">Backup gần nhất</span>
      <span class="f-val"><?= e(date('d/m/Y H:i', strtotime((string)$latestBackup['created_at']))) ?> · <span class="tag tag-gray"><?= e((string)($latestBackup['trigger']??'manual')) ?></span></span>
    </div>
  <?php endif; ?>
  <div class="f-row" style="border-bottom:none">
    <span class="f-label"></span>
    <span class="f-val">
      <form method="post" style="display:inline">
        <?php render_form_guard('admin_accounts'); ?>
        <input type="hidden" name="action" value="create_backup">
        <button type="submit" class="btn-p">Tạo backup ngay</button>
      </form>
    </span>
  </div>
</div>
<?php endif; ?>

<?php if ($canManageClinicContent): ?>
<div class="section-title">Thông tin phòng khám</div>
<div class="c" style="margin-bottom:16px">
  <form method="post" enctype="multipart/form-data">
    <?php render_form_guard('admin_accounts'); ?>
    <input type="hidden" name="action" value="save_clinic_content">
    <input type="hidden" name="current_logo" value="<?= e($clinicSettings['clinic_logo_path']) ?>">
    <div class="f-row">
      <span class="f-label">Tên phòng khám</span>
      <span class="f-val"><input class="f-inp" name="clinic_name" value="<?= e($clinicSettings['clinic_name']) ?>" required></span>
    </div>
    <div class="f-row">
      <span class="f-label">Địa chỉ</span>
      <span class="f-val"><input class="f-inp" name="clinic_address" value="<?= e($clinicSettings['clinic_address']) ?>"></span>
    </div>
    <div class="f-row">
      <span class="f-label">Hotline hỗ trợ</span>
      <span class="f-val"><input class="f-inp" name="support_hotline" value="<?= e($clinicSettings['support_hotline']) ?>"></span>
    </div>
    <div class="f-row">
      <span class="f-label">Email hỗ trợ</span>
      <span class="f-val"><input class="f-inp" type="email" name="support_email" value="<?= e($clinicSettings['support_email']) ?>"></span>
    </div>
    <div class="f-row">
      <span class="f-label">Link Google Maps</span>
      <span class="f-val"><input class="f-inp" name="google_maps_url" value="<?= e($clinicSettings['google_maps_url']) ?>" placeholder="https://maps.google.com/…"></span>
    </div>
    <div class="f-row">
      <span class="f-label">Link Apple Maps</span>
      <span class="f-val"><input class="f-inp" name="apple_maps_url" value="<?= e($clinicSettings['apple_maps_url']) ?>" placeholder="https://maps.apple.com/…"></span>
    </div>
    <div class="f-row">
      <span class="f-label">
        <span style="display:inline-flex;align-items:center;gap:6px;">
          <span style="width:20px;height:20px;background:#0068ff;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;">
            <svg width="12" height="12" viewBox="0 0 48 48" fill="white"><text x="24" y="34" text-anchor="middle" font-size="18" font-weight="900" font-family="Arial">Z</text></svg>
          </span>
          Link Zalo phòng khám
        </span>
      </span>
      <span class="f-val"><input class="f-inp" name="zalo_url" value="<?= e($clinicSettings['zalo_url'] ?? '') ?>" placeholder="https://zalo.me/0xxxxxxxxx"></span>
    </div>
    <div class="f-row">
      <span class="f-label">
        <span style="display:inline-flex;align-items:center;gap:6px;">
          <span style="width:20px;height:20px;background:linear-gradient(135deg,#0099ff,#a033ff);border-radius:50%;display:inline-flex;align-items:center;justify-content:center;">
            <svg width="12" height="12" viewBox="0 0 28 28" fill="white"><path d="M14 2C7.373 2 2 7.059 2 13.273c0 3.396 1.607 6.43 4.134 8.447V26l3.862-2.122C11.18 24.25 12.567 24.5 14 24.5c6.627 0 12-5.059 12-11.227C26 7.059 20.627 2 14 2zm1.274 15.117l-3.06-3.262-5.976 3.262 6.576-6.98 3.134 3.262 5.902-3.262-6.576 6.98z"/></svg>
          </span>
          Link Messenger phòng khám
        </span>
      </span>
      <span class="f-val"><input class="f-inp" name="messenger_url" value="<?= e($clinicSettings['messenger_url'] ?? '') ?>" placeholder="https://m.me/tenpage"></span>
    </div>
    <div class="f-row">
      <span class="f-label">Logo hiện tại</span>
      <span class="f-val" style="display:flex;align-items:center;gap:12px">
        <img src="<?= e(clinic_logo_url()) ?>" alt="Logo" style="height:36px;width:auto;object-fit:contain;border:0.5px solid var(--ct);border-radius:var(--r);padding:4px">
        <input type="file" name="clinic_logo" accept=".svg,.png,.jpg,.jpeg,.webp" class="img-preview-input" data-preview="logoNewPrev">
        <img id="logoNewPrev" class="img-prev" src="" alt="">
      </span>
    </div>
    <div style="padding:12px 0;margin-top:4px"><button type="submit" class="btn-p">Lưu thông tin cơ bản</button></div>
  </form>
</div>

<div class="section-title">Cài đặt vận hành</div>
<div class="c" style="margin-bottom:16px">
  <form method="post" enctype="multipart/form-data" id="formOperationSettings" onsubmit="return buildAndSubmitSchedule()">
    <?php render_form_guard('admin_accounts'); ?>
    <input type="hidden" name="action" value="save_clinic_content">
    <input type="hidden" name="current_logo" value="<?= e($clinicSettings['clinic_logo_path']) ?>">
    <div class="f-row">
      <span class="f-label">Cho phép đặt lịch online</span>
      <span class="f-val txt-muted txt-sm">Bật/tắt khả năng bệnh nhân tự đặt lịch</span>
      <label class="tog-wrap">
        <input type="checkbox" name="appointments_enabled"
          data-autosubmit="true"
          <?= site_setting_bool('appointments_enabled', true) ? 'checked' : '' ?>>
        <span class="tog-track"></span>
      </label>
    </div>

    <?php
      // Lấy lịch hiện tại
      $apptSchedule = get_appointment_schedule();
      $dayLabels = [
        '1' => 'Thứ Hai',
        '2' => 'Thứ Ba',
        '3' => 'Thứ Tư',
        '4' => 'Thứ Năm',
        '5' => 'Thứ Sáu',
        '6' => 'Thứ Bảy',
        '7' => 'Chủ Nhật',
      ];
    ?>

    <!-- Lịch nhận lịch hẹn -->
    <div style="margin-top:4px;">
      <div style="font-size:13px;font-weight:600;color:#344054;margin-bottom:10px;display:flex;align-items:center;gap:6px;">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#667085" stroke-width="2" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        Thời gian nhận lịch hẹn theo ngày
      </div>

      <div style="border:1px solid #e4e7ec;border-radius:10px;overflow:hidden;">
        <!-- Header -->
        <div style="display:grid;grid-template-columns:110px 60px 90px 90px 1px 80px 90px 90px;gap:0;background:#f9fafb;border-bottom:1px solid #e4e7ec;padding:8px 14px;font-size:11px;font-weight:600;color:#667085;letter-spacing:.4px;text-transform:uppercase;align-items:center;">
          <span>Ngày</span>
          <span style="text-align:center">Mở cửa</span>
          <span style="text-align:center">Sáng từ</span>
          <span style="text-align:center">Đến</span>
          <span style="background:#e4e7ec;height:100%;width:1px;display:block;margin:0 6px;"></span>
          <span style="text-align:center;color:#f59e0b;">🌙 Nghỉ trưa</span>
          <span style="text-align:center">Nghỉ từ</span>
          <span style="text-align:center">Đến</span>
        </div>

        <?php foreach ($dayLabels as $dayKey => $dayName):
          $cfg       = $apptSchedule[$dayKey] ?? ['open' => false, 'from' => '07:00', 'to' => '17:00', 'break_open' => false, 'break_from' => '12:00', 'break_to' => '13:00'];
          $isOpen    = !empty($cfg['open']);
          $fromVal   = $cfg['from']       ?? '07:00';
          $toVal     = $cfg['to']         ?? '17:00';
          $breakOpen = !empty($cfg['break_open']);
          $bFromVal  = $cfg['break_from'] ?? '12:00';
          $bToVal    = $cfg['break_to']   ?? '13:00';
          $isSunday  = $dayKey === '7';
          $rowBg     = $isOpen ? '#fff' : '#fafafa';
        ?>
        <div class="sched-row" data-day="<?= $dayKey ?>"
          style="display:grid;grid-template-columns:110px 60px 90px 90px 1px 80px 90px 90px;gap:0;align-items:center;padding:9px 14px;border-bottom:1px solid #f0f0f0;background:<?= $rowBg ?>;transition:background .15s;">

          <!-- Tên ngày -->
          <span style="font-size:13px;font-weight:<?= $isSunday ? '700' : '500' ?>;color:<?= $isSunday ? '#e53935' : '#344054' ?>;"><?= $dayName ?></span>

          <!-- Toggle mở cửa -->
          <span style="text-align:center">
            <label class="tog-wrap tog-sm">
              <input type="checkbox" class="sched-open-chk" data-day="<?= $dayKey ?>"
                name="sched_open[<?= $dayKey ?>]" value="1"
                <?= $isOpen ? 'checked' : '' ?>>
              <span class="tog-track"></span>
            </label>
          </span>

          <!-- Giờ mở sáng -->
          <span style="text-align:center">
            <input type="time" name="sched_from[<?= $dayKey ?>]" value="<?= e($fromVal) ?>"
              class="sched-time-input sched-main-time" data-day="<?= $dayKey ?>"
              <?= !$isOpen ? 'disabled' : '' ?>
              style="border:1px solid #d0d5dd;border-radius:6px;padding:4px 6px;font-size:12px;color:#344054;background:<?= !$isOpen ? '#f5f5f5' : '#fff' ?>;outline:none;width:84px;text-align:center;">
          </span>

          <!-- Giờ đóng chiều -->
          <span style="text-align:center">
            <input type="time" name="sched_to[<?= $dayKey ?>]" value="<?= e($toVal) ?>"
              class="sched-time-input sched-main-time" data-day="<?= $dayKey ?>"
              <?= !$isOpen ? 'disabled' : '' ?>
              style="border:1px solid #d0d5dd;border-radius:6px;padding:4px 6px;font-size:12px;color:#344054;background:<?= !$isOpen ? '#f5f5f5' : '#fff' ?>;outline:none;width:84px;text-align:center;">
          </span>

          <!-- Divider dọc -->
          <span style="background:#e4e7ec;height:32px;width:1px;display:block;margin:0 6px;"></span>

          <!-- Toggle nghỉ trưa -->
          <span style="text-align:center">
            <label class="tog-wrap tog-sm" title="Bật để cấu hình giờ nghỉ trưa">
              <input type="checkbox" class="sched-break-chk" data-day="<?= $dayKey ?>"
                name="sched_break_open[<?= $dayKey ?>]" value="1"
                <?= $breakOpen ? 'checked' : '' ?>
                <?= !$isOpen ? 'disabled' : '' ?>>
              <span class="tog-track"></span>
            </label>
          </span>

          <!-- Nghỉ từ -->
          <span style="text-align:center">
            <input type="time" name="sched_break_from[<?= $dayKey ?>]" value="<?= e($bFromVal) ?>"
              class="sched-time-input sched-break-time" data-day="<?= $dayKey ?>"
              <?= (!$isOpen || !$breakOpen) ? 'disabled' : '' ?>
              style="border:1px solid #fed7aa;border-radius:6px;padding:4px 6px;font-size:12px;color:#92400e;background:<?= (!$isOpen || !$breakOpen) ? '#f5f5f5' : '#fff7ed' ?>;outline:none;width:84px;text-align:center;">
          </span>

          <!-- Nghỉ đến -->
          <span style="text-align:center">
            <input type="time" name="sched_break_to[<?= $dayKey ?>]" value="<?= e($bToVal) ?>"
              class="sched-time-input sched-break-time" data-day="<?= $dayKey ?>"
              <?= (!$isOpen || !$breakOpen) ? 'disabled' : '' ?>
              style="border:1px solid #fed7aa;border-radius:6px;padding:4px 6px;font-size:12px;color:#92400e;background:<?= (!$isOpen || !$breakOpen) ? '#f5f5f5' : '#fff7ed' ?>;outline:none;width:84px;text-align:center;">
          </span>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Hidden field chứa JSON schedule (duy nhất, được JS ghi vào trước submit) -->
      <input type="hidden" name="appointment_schedule" id="apptScheduleJson" value="<?= e($clinicSettings['appointment_schedule'] ?? '') ?>">
      <p style="margin:8px 0 0;font-size:12px;color:#94a3b8;">💡 Lịch hẹn chỉ được đặt trong khung giờ đã thiết lập. Ngày đóng cửa sẽ không nhận lịch.</p>
    </div>

    <div class="f-row" style="border-bottom:none;margin-top:12px;">
      <span class="f-label"></span>
      <span class="f-val"></span>
      <button type="submit" class="btn-s">Lưu cài đặt</button>
    </div>
  </form>
</div>

<style>
.tog-sm { transform: scale(0.82); transform-origin: center; display:inline-flex; }
.sched-row:hover { background: #f8faff !important; }
.sched-time-input:focus { border-color: #2563eb !important; box-shadow: 0 0 0 2px #dbeafe; }
</style>

<script>
// Toggle mở cửa ngày → bật/tắt giờ sáng + điều khiển nghỉ trưa
document.querySelectorAll('.sched-open-chk').forEach(function(chk) {
  chk.addEventListener('change', function() {
    var day = this.dataset.day;
    var row = document.querySelector('.sched-row[data-day="' + day + '"]');
    var isOpen = chk.checked;

    // Giờ chính (sáng/chiều)
    row.querySelectorAll('.sched-main-time').forEach(function(inp) {
      inp.disabled = !isOpen;
      inp.style.background = isOpen ? '#fff' : '#f5f5f5';
    });

    // Toggle nghỉ trưa
    var breakChk = row.querySelector('.sched-break-chk');
    if (breakChk) {
      breakChk.disabled = !isOpen;
      if (!isOpen) breakChk.checked = false;
    }

    // Giờ nghỉ trưa
    var breakOn = isOpen && breakChk && breakChk.checked;
    row.querySelectorAll('.sched-break-time').forEach(function(inp) {
      inp.disabled = !breakOn;
      inp.style.background = breakOn ? '#fff7ed' : '#f5f5f5';
    });

    row.style.background = isOpen ? '#fff' : '#fafafa';
  });
});

// Toggle nghỉ trưa → bật/tắt time inputs nghỉ trưa
document.querySelectorAll('.sched-break-chk').forEach(function(chk) {
  chk.addEventListener('change', function() {
    var day = this.dataset.day;
    var row = document.querySelector('.sched-row[data-day="' + day + '"]');
    var breakOn = chk.checked;
    row.querySelectorAll('.sched-break-time').forEach(function(inp) {
      inp.disabled = !breakOn;
      inp.style.background = breakOn ? '#fff7ed' : '#f5f5f5';
    });
  });
});

function buildApptScheduleJson() {
  var schedule = {};
  document.querySelectorAll('.sched-row').forEach(function(row) {
    var day        = row.dataset.day;
    var openChk    = row.querySelector('.sched-open-chk');
    var breakChk   = row.querySelector('.sched-break-chk');
    var mainTimes  = row.querySelectorAll('.sched-main-time');
    var breakTimes = row.querySelectorAll('.sched-break-time');
    schedule[day] = {
      open:       openChk  ? openChk.checked  : false,
      from:       mainTimes[0]  ? mainTimes[0].value  : '07:00',
      to:         mainTimes[1]  ? mainTimes[1].value  : '17:00',
      break_open: breakChk ? breakChk.checked : false,
      break_from: breakTimes[0] ? breakTimes[0].value : '12:00',
      break_to:   breakTimes[1] ? breakTimes[1].value : '13:00'
    };
  });
  document.getElementById('apptScheduleJson').value = JSON.stringify(schedule);
}

function buildAndSubmitSchedule() {
  buildApptScheduleJson();
  return true;
}
</script>



<div class="section-title">Nội dung giới thiệu</div>
<div class="c">
  <form method="post">
    <?php render_form_guard('admin_accounts'); ?>
    <input type="hidden" name="action" value="save_clinic_content">
    <input type="hidden" name="current_logo" value="<?= e($clinicSettings['clinic_logo_path']) ?>">
    <div class="f-grid2">
      <div class="f-group"><label class="f-label-sm">Giới thiệu chung</label><textarea class="f-ta" name="clinic_intro"><?= e($clinicSettings['clinic_intro']) ?></textarea></div>
      <div class="f-group"><label class="f-label-sm">Sứ mệnh</label><textarea class="f-ta" name="clinic_mission"><?= e($clinicSettings['clinic_mission']) ?></textarea></div>
      <div class="f-group"><label class="f-label-sm">Cơ sở vật chất</label><textarea class="f-ta" name="clinic_facility"><?= e($clinicSettings['clinic_facility']) ?></textarea></div>
      <div class="f-group"><label class="f-label-sm">Dịch vụ khám</label><textarea class="f-ta" name="clinic_services"><?= e($clinicSettings['clinic_services']) ?></textarea></div>
      <div class="f-group"><label class="f-label-sm">Dịch vụ hỗ trợ</label><textarea class="f-ta" name="clinic_support"><?= e($clinicSettings['clinic_support']) ?></textarea></div>
      <div class="f-group"><label class="f-label-sm">Giới thiệu chatbot</label><textarea class="f-ta" name="chatbot_intro"><?= e($clinicSettings['chatbot_intro']) ?></textarea></div>
    </div>
    <div class="f-group"><label class="f-label-sm">Câu trả lời mặc định (chatbot)</label><textarea class="f-ta" name="chatbot_fallback"><?= e($clinicSettings['chatbot_fallback']) ?></textarea></div>
    <button type="submit" class="btn-p">Lưu nội dung</button>
  </form>
</div>
<?php endif; ?>

<?php if (is_root_admin()): ?>
<div class="section-title">🔑 Quản lý API Key Gemini (Tự động luân chuyển)</div>
<div class="c" style="margin-bottom:16px">
  <form method="post" id="gemini-key-form" onsubmit="return syncGeminiKeys()">
    <?php render_form_guard('admin_accounts'); ?>
    <input type="hidden" name="action" value="save_clinic_content">
    <input type="hidden" name="current_logo"   value="<?= e($clinicSettings['clinic_logo_path']) ?>">
    <textarea name="gemini_api_keys" id="gemini_api_keys_hidden" style="display:none;"></textarea>

    <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:10px;padding:14px 18px;margin-bottom:14px;">
      <p style="margin:0 0 6px;font-size:13px;color:#0369a1;">
        <strong>⚡ Cách hoạt động:</strong> Hệ thống thử key theo thứ tự từ trên xuống. Khi key hết quota (lỗi 429), tự chuyển sang key tiếp theo.
      </p>
      <p style="margin:0;font-size:12px;color:#0284c7;">
        Lấy key miễn phí: <strong>https://aistudio.google.com/apikey</strong> (đăng nhập nhiều tài khoản Google để có nhiều key)
      </p>
    </div>

    <!-- Key chính từ .secrets.php -->
    <div style="margin-bottom:14px;">
      <div style="font-size:12px;font-weight:600;color:#475569;margin-bottom:6px;">⭐ Key chính (từ file .secrets.php — không thể sửa ở đây)</div>
      <div style="display:flex;align-items:center;gap:10px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:10px 14px;">
        <span style="font-family:monospace;font-size:13px;color:#475569;flex:1;word-break:break-all;">
          <?= e(substr(APP_GEMINI_API_KEY ?? '', 0, 16)) ?>••••••••••••••••••
        </span>
        <span style="font-size:11px;background:#fef9c3;color:#854d0e;padding:2px 8px;border-radius:10px;font-weight:600;white-space:nowrap;">⭐ Ưu tiên 1</span>
      </div>
    </div>

    <!-- Danh sách key dự phòng dạng card -->
    <div style="font-size:12px;font-weight:600;color:#475569;margin-bottom:8px;">🔄 Keys dự phòng (tự động luân chuyển khi hết quota)</div>
    <div id="gemini-key-list" style="display:flex;flex-direction:column;gap:8px;margin-bottom:10px;">
      <?php
        $savedKeys = array_values(array_filter(array_map('trim', explode("\n", $clinicSettings['gemini_api_keys'] ?? ''))));
      ?>
      <div id="gemini-empty-hint" style="<?= !empty($savedKeys) ? 'display:none;' : '' ?>font-size:13px;color:#94a3b8;padding:10px 0;">
        Chưa có key dự phòng nào. Nhấn "+ Thêm key" để thêm.
      </div>
      <?php foreach ($savedKeys as $i => $k): ?>
      <div class="gemini-key-row" style="display:flex;align-items:center;gap:10px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:10px 14px;">
        <span style="font-size:11px;background:#e0f2fe;color:#0369a1;padding:2px 7px;border-radius:10px;font-weight:600;white-space:nowrap;flex-shrink:0;">🔄 #<?= $i + 2 ?></span>
        <input type="password" class="gemini-key-input" value="<?= e($k) ?>" placeholder="AIzaSy..."
          style="flex:1;font-family:monospace;font-size:13px;border:none;background:transparent;outline:none;color:#475569;min-width:0;letter-spacing:2px;"
          title="Click để xem/sửa key" onfocus="this.type='text';this.style.letterSpacing='0'" onblur="this.type='password';this.style.letterSpacing='2px'">
        <span style="font-size:11px;color:#94a3b8;white-space:nowrap;flex-shrink:0;">(click để sửa)</span>
        <button type="button" onclick="removeGeminiKey(this)"
          style="flex-shrink:0;background:#fee2e2;border:none;color:#dc2626;border-radius:6px;padding:4px 10px;cursor:pointer;font-size:13px;font-weight:600;" title="Xóa key này">✕</button>
      </div>
      <?php endforeach; ?>
    </div>

    <button type="button" onclick="addGeminiKey()"
      style="display:inline-flex;align-items:center;gap:6px;background:#f0fdf4;border:1.5px dashed #4ade80;color:#16a34a;border-radius:8px;padding:8px 16px;font-size:13px;font-weight:600;cursor:pointer;margin-bottom:14px;">
      ➕ Thêm key mới
    </button>

    <div style="padding:4px 0 0">
      <button type="submit" class="btn-p">💾 Lưu danh sách API Key</button>
      <span id="gemini-save-count" style="margin-left:10px;font-size:12px;color:#64748b;"></span>
    </div>
  </form>

  <!-- Badge hiển thị key đang chạy thực tế -->
  <div id="active-key-status" style="margin-top:12px;padding:12px 16px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;display:none;">
    <div style="font-size:12px;color:#15803d;font-weight:600;margin-bottom:6px;">✅ Key đang hoạt động (cập nhật sau mỗi lần chatbot trả lời)</div>
    <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;">
      <div>
        <span style="font-size:11px;color:#64748b;">🔑 Key:</span>
        <code id="active-key-display" style="font-size:13px;font-weight:700;color:#166534;background:#dcfce7;padding:2px 8px;border-radius:6px;"></code>
        <span id="active-key-source-badge" style="margin-left:6px;font-size:11px;padding:2px 7px;border-radius:10px;font-weight:600;"></span>
      </div>
      <div>
        <span style="font-size:11px;color:#64748b;">🤖 Model:</span>
        <code id="active-model-display" style="font-size:13px;font-weight:700;color:#1e40af;background:#dbeafe;padding:2px 8px;border-radius:6px;"></code>
      </div>
    </div>
  </div>
  <div id="active-key-hint" style="margin-top:8px;font-size:12px;color:#94a3b8;">⏳ Gửi 1 tin nhắn chatbot để xem key đang được dùng...</div>

</div>

<script>
function addGeminiKey() {
  var list = document.getElementById('gemini-key-list');
  var hint = document.getElementById('gemini-empty-hint');
  if (hint) hint.style.display = 'none';
  var rows = list.querySelectorAll('.gemini-key-row');
  var idx = rows.length + 2;
  var div = document.createElement('div');
  div.className = 'gemini-key-row';
  div.style.cssText = 'display:flex;align-items:center;gap:10px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:10px 14px;';
  div.innerHTML = '<span style="font-size:11px;background:#e0f2fe;color:#0369a1;padding:2px 7px;border-radius:10px;font-weight:600;white-space:nowrap;flex-shrink:0;">🔄 #'+idx+'</span>'
    + '<input type="text" class="gemini-key-input" placeholder="AIzaSy..." '
    + 'style="flex:1;font-family:monospace;font-size:13px;border:none;background:transparent;outline:none;color:#475569;min-width:0;">'
    + '<span style="font-size:11px;color:#94a3b8;white-space:nowrap;flex-shrink:0;">(click để sửa)</span>'
    + '<button type="button" onclick="removeGeminiKey(this)" '
    + 'style="flex-shrink:0;background:#fee2e2;border:none;color:#dc2626;border-radius:6px;padding:4px 10px;cursor:pointer;font-size:13px;font-weight:600;" title="Xóa">✕</button>';
  list.appendChild(div);
  var inp = div.querySelector('input');
  inp.focus();
  renumberGeminiKeys();
}

function removeGeminiKey(btn) {
  btn.closest('.gemini-key-row').remove();
  renumberGeminiKeys();
  var list = document.getElementById('gemini-key-list');
  var hint = document.getElementById('gemini-empty-hint');
  if (hint && list.querySelectorAll('.gemini-key-row').length === 0) hint.style.display = '';
}

function renumberGeminiKeys() {
  document.querySelectorAll('#gemini-key-list .gemini-key-row').forEach(function(row, i) {
    var badge = row.querySelector('span');
    if (badge) badge.textContent = '🔄 #' + (i + 2);
  });
}

function syncGeminiKeys() {
  var keys = [];
  document.querySelectorAll('#gemini-key-list .gemini-key-input').forEach(function(inp) {
    // Force đọc value dù input đang ở type=password
    var origType = inp.type;
    inp.type = 'text';
    var v = inp.value.trim();
    inp.type = origType;
    if (v) keys.push(v);
  });
  var hidden = document.getElementById('gemini_api_keys_hidden');
  if (hidden) hidden.value = keys.join('\n');
  var cnt = document.getElementById('gemini-save-count');
  if (cnt) cnt.textContent = '→ Đang lưu ' + keys.length + ' key...';
  return true;
}

(function() {
  var origFetch = window.fetch;
  window.fetch = function(url, opts) {
    return origFetch.apply(this, arguments).then(function(response) {
      if (typeof url === 'string' && url.includes('api_chat_ai.php')) {
        response.clone().json().then(function(data) {
          if (data && data.used_key) {
            var statusEl = document.getElementById('active-key-status');
            var hintEl   = document.getElementById('active-key-hint');
            var keyEl    = document.getElementById('active-key-display');
            var modelEl  = document.getElementById('active-model-display');
            var srcEl    = document.getElementById('active-key-source-badge');
            if (statusEl) statusEl.style.display = 'block';
            if (hintEl)   hintEl.style.display   = 'none';
            if (keyEl)    keyEl.textContent  = data.used_key;
            if (modelEl)  modelEl.textContent = data.used_model;
            if (srcEl) {
              if (data.key_source === 'primary') {
                srcEl.textContent = '⭐ Key chính';
                srcEl.style.background = '#fef9c3';
                srcEl.style.color = '#854d0e';
              } else {
                srcEl.textContent = '🔄 ' + data.key_source.replace('backup_', 'Dự phòng #');
                srcEl.style.background = '#e0f2fe';
                srcEl.style.color = '#0369a1';
              }
            }
          }
        }).catch(function(){});
      }
      return response;
    });
  };
})();
</script>

<div class="section-title">Dữ liệu huấn luyện AI Chatbot</div>
<div class="c" style="margin-bottom:16px">
  <form method="post" id="ai-training-form">
    <?php render_form_guard('admin_accounts'); ?>
    <input type="hidden" name="action" value="save_ai_content">

    <?php
    $aiFields = [
        'ai_prompt_procedures' => ['label' => 'Quy trình khám bệnh', 'placeholder' => 'Nhập các bước quy trình khám...'],
        'ai_prompt_pricing'    => ['label' => 'Bảng giá dịch vụ',    'placeholder' => 'Nhập bảng giá dịch vụ...'],
        'ai_prompt_documents'  => ['label' => 'Hồ sơ thủ tục & Công văn', 'placeholder' => 'Ví dụ: Bệnh nhân cần mang theo CCCD...'],
        'ai_prompt_benefits'   => ['label' => 'Lợi ích BHYT & Chế độ bệnh nhân', 'placeholder' => 'Ví dụ: BHYT đúng tuyến hưởng 80%...'],
        'ai_prompt_schedule'   => ['label' => 'Giờ làm việc & Thời gian phục vụ', 'placeholder' => 'Ví dụ: Thứ 2 - Thứ 6: 7h30 - 17h00...'],
    ];
    foreach ($aiFields as $fieldName => $meta):
    ?>
    <div class="f-group" style="margin-bottom: 18px;">
      <label class="f-label-sm" style="font-weight:600;"><?= $meta['label'] ?> <code style="font-weight:400;font-size:11px;color:#64748b;">(<?= $fieldName ?>)</code></label>
      <textarea class="f-ta" name="<?= $fieldName ?>" id="field-<?= $fieldName ?>" rows="5" placeholder="<?= $meta['placeholder'] ?>"><?= e($clinicSettings[$fieldName] ?? '') ?></textarea>

      <!-- File upload row -->
      <div style="display:flex;align-items:center;gap:10px;margin-top:8px;flex-wrap:wrap;">
        <label style="
          display:inline-flex;align-items:center;gap:7px;cursor:pointer;
          background:#f1f5f9;border:1.5px dashed #94a3b8;border-radius:8px;
          padding:7px 14px;font-size:13px;color:#475569;font-weight:500;
          transition:all 0.15s;
        " onmouseover="this.style.borderColor='#2563eb';this.style.color='#2563eb'"
           onmouseout="this.style.borderColor='#94a3b8';this.style.color='#475569'">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
          Tải file lên
          <input type="file" accept=".txt,.docx,.xlsx,.csv,.pdf" style="display:none"
                 onchange="aiExtractFile(this, '<?= $fieldName ?>')" data-field="<?= $fieldName ?>">
        </label>
        <span style="font-size:12px;color:#94a3b8;">Hỗ trợ: .docx .xlsx .csv .txt .pdf (tối đa 5 MB) · <a href="download_template.php?type=<?= $fieldName ?>" style="color:#2563eb;font-weight:600;text-decoration:none;" target="_blank">📥 Tải file mẫu Excel (.csv)</a></span>
        <span id="status-<?= $fieldName ?>" style="font-size:12px;font-weight:500;"></span>
      </div>
      <div style="margin-top:6px;display:flex;gap:8px;flex-wrap:wrap;">
        <button type="button" onclick="aiAppendMode('<?= $fieldName ?>')"
          id="mode-append-<?= $fieldName ?>"
          style="font-size:12px;padding:4px 10px;border-radius:6px;border:1px solid #cbd5e1;background:#e2e8f0;color:#475569;cursor:pointer;font-weight:500;" title="Nội dung file sẽ được chèn thêm vào cuối ô hiện tại">
          ➕ Thêm vào cuối
        </button>
        <button type="button" onclick="aiReplaceMode('<?= $fieldName ?>')"
          id="mode-replace-<?= $fieldName ?>"
          style="font-size:12px;padding:4px 10px;border-radius:6px;border:1px solid #fca5a5;background:#fee2e2;color:#dc2626;cursor:pointer;font-weight:500;" title="Nội dung file sẽ thay thế toàn bộ nội dung hiện tại">
          🔄 Ghi đè
        </button>
      </div>
    </div>
    <?php endforeach; ?>

    <div style="margin-top:16px"><button type="submit" class="btn-p">Lưu dữ liệu huấn luyện AI</button></div>
  </form>
</div>

<script>
// Store upload mode per field: 'append' or 'replace'
var aiUploadMode = {};

function aiAppendMode(field) {
    aiUploadMode[field] = 'append';
    document.getElementById('mode-append-' + field).style.background = '#dbeafe';
    document.getElementById('mode-append-' + field).style.borderColor = '#3b82f6';
    document.getElementById('mode-append-' + field).style.color = '#1d4ed8';
    document.getElementById('mode-replace-' + field).style.background = '#e2e8f0';
    document.getElementById('mode-replace-' + field).style.borderColor = '#cbd5e1';
    document.getElementById('mode-replace-' + field).style.color = '#475569';
}

function aiReplaceMode(field) {
    aiUploadMode[field] = 'replace';
    document.getElementById('mode-replace-' + field).style.background = '#fee2e2';
    document.getElementById('mode-replace-' + field).style.borderColor = '#fca5a5';
    document.getElementById('mode-replace-' + field).style.color = '#dc2626';
    document.getElementById('mode-append-' + field).style.background = '#e2e8f0';
    document.getElementById('mode-append-' + field).style.borderColor = '#cbd5e1';
    document.getElementById('mode-append-' + field).style.color = '#475569';
}

function aiExtractFile(input, field) {
    var file = input.files[0];
    if (!file) return;

    var statusEl = document.getElementById('status-' + field);
    statusEl.style.color = '#f59e0b';
    statusEl.textContent = '⏳ Đang trích xuất "' + file.name + '"...';

    var formData = new FormData();
    formData.append('ai_file', file);
    formData.append('target_field', field);
    // Include CSRF token if available
    var guard = document.querySelector('#ai-training-form input[name="_form_token"]');
    if (guard) formData.append('_form_token', guard.value);

    fetch('/api_ai_file_extract.php', {
        method: 'POST',
        body: formData,
    })
    .then(res => res.json())
    .then(data => {
        if (data.error) {
            statusEl.style.color = '#dc2626';
            statusEl.textContent = '❌ ' + data.error;
            return;
        }
        var textarea = document.getElementById('field-' + field);
        var mode = aiUploadMode[field] || 'append';
        if (mode === 'replace') {
            textarea.value = data.text;
        } else {
            var sep = textarea.value.trim() === '' ? '' : '\n\n--- Từ file: ' + data.file + ' ---\n';
            textarea.value = textarea.value.trim() + sep + data.text;
        }
        statusEl.style.color = '#16a34a';
        statusEl.textContent = '✅ Đã trích xuất ' + data.chars.toLocaleString() + ' ký tự từ "' + data.file + '"';
        input.value = ''; // Reset file input
    })
    .catch(err => {
        statusEl.style.color = '#dc2626';
        statusEl.textContent = '❌ Lỗi kết nối: ' + err.message;
    });
}
</script>
<?php endif; ?>

