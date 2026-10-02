<?php if ($canManagePatients): ?>
<div class="sbar">
  <input class="sinp" id="searchPatients" placeholder="Tìm bệnh nhân theo tên, SĐT, CCCD...">
</div>
<div class="g3">
  <div class="c"><div class="c-label">Tổng bệnh nhân</div><div class="c-val"><?= $patients->num_rows ?></div><div class="c-sub">Đã đăng ký</div></div>
  <div class="c"><div class="c-label">Lịch hẹn hôm nay</div><div class="c-val"><?php
    $today = date('Y-m-d');
    $apt_today = $conn->query("SELECT COUNT(*) as n FROM appointments WHERE DATE(appointment_date)='$today'")->fetch_assoc()['n'] ?? 0;
    echo (int)$apt_today;
  ?></div><div class="c-sub">Đã xác nhận</div></div>
  <div class="c"><div class="c-label">Chờ xác nhận</div><div class="c-val"><?php
    $apt_pending = $conn->query("SELECT COUNT(*) as n FROM appointments WHERE status='pending'")->fetch_assoc()['n'] ?? 0;
    echo (int)$apt_pending;
  ?></div><div class="c-sub">Cần xử lý</div></div>
</div>
<div class="c">
  <div class="tbl-wrap">
    <table>
      <thead>
        <tr>
          <th>Bệnh nhân</th><th>CCCD</th><th>Điện thoại</th>
          <?php if ($patientEmailEnabled): ?><th>Gmail</th><?php endif; ?>
          <th>Thao tác</th>
        </tr>
      </thead>
      <tbody id="patientsTbody">
      <?php $patients->data_seek(0); while ($pat = $patients->fetch_assoc()):
        $pd = htmlspecialchars(json_encode([
          'patient_id'=>$pat['id'],'cccd'=>$pat['cccd'],'full_name'=>$pat['full_name'],
          'phone'=>$pat['phone'],'email'=>$pat['email']??'',
        ], JSON_UNESCAPED_UNICODE), ENT_QUOTES);
      ?>
        <tr>
          <td>
            <div class="row-f">
              <div class="av"><?= mb_strtoupper(mb_substr(e($pat['full_name']), 0, 2)) ?></div>
              <span style="font-weight:500"><?= e($pat['full_name']) ?></span>
            </div>
          </td>
          <td><code style="font-size:12px;background:var(--bg2);padding:2px 6px;border-radius:var(--r);border:0.5px solid var(--ct)"><?= e($pat['cccd']) ?></code></td>
          <td><?= e($pat['phone']) ?></td>
          <?php if ($patientEmailEnabled): ?><td><?= $pat['email'] ? e($pat['email']) : '<span class="txt-muted">—</span>' ?></td><?php endif; ?>
          <td>
            <div class="actions-row" style="margin:0;gap:6px;">
              <button type="button" class="btn-s"
                data-open-modal="modalEditPatient"
                data-fill-form="formEditPatient"
                data-fill="<?= $pd ?>">Sửa</button>
              <form method="post" style="display:inline">
                <?php render_form_guard('admin_accounts'); ?>
                <input type="hidden" name="action" value="reset_patient_password">
                <input type="hidden" name="target_id" value="<?= (int)$pat['id'] ?>">
                <button type="submit" class="btn-s">Mật khẩu tạm</button>
              </form>
              <form method="post" style="display:inline"
                data-confirm="Xóa bệnh nhân «<?= e(addslashes($pat['full_name'])) ?>»?">
                <?php render_form_guard('admin_accounts'); ?>
                <input type="hidden" name="action" value="delete_patient">
                <input type="hidden" name="target_id" value="<?= (int)$pat['id'] ?>">
                <button type="submit" class="btn-d">Xóa</button>
              </form>
            </div>
          </td>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal Sửa bệnh nhân -->
<div id="modalEditPatient" class="modal-overlay">
  <div class="modal">
    <div class="modal-header"><h3>Chỉnh sửa bệnh nhân</h3><button class="modal-close" type="button">✕</button></div>
    <form method="post" id="formEditPatient">
      <?php render_form_guard('admin_accounts'); ?>
      <input type="hidden" name="action" value="update_patient">
      <input type="hidden" name="patient_id" value="">
      <div class="f-grid2">
        <div class="f-group"><label class="f-label-sm">Số CCCD *</label><input class="f-inp" name="cccd" required></div>
        <div class="f-group"><label class="f-label-sm">Họ tên *</label><input class="f-inp" name="full_name" required></div>
        <div class="f-group"><label class="f-label-sm">Điện thoại *</label><input class="f-inp" name="phone" required></div>
        <?php if ($patientEmailEnabled): ?>
        <div class="f-group"><label class="f-label-sm">Gmail</label><input class="f-inp" type="email" name="email"></div>
        <?php else: ?><input type="hidden" name="email" value=""><?php endif; ?>
      </div>
      <div class="actions-row"><button type="submit" class="btn-p">Lưu</button><button type="button" class="btn-s modal-close">Hủy</button></div>
    </form>
  </div>
</div>
<?php endif; ?>
