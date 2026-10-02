<?php if ($canManageStaffAccounts): ?>
<div class="sbar">
  <input class="sinp" id="searchStaff" placeholder="Tìm nhân viên theo tên, vai trò...">
  <button type="button" class="btn-p" data-open-modal="modalAddStaff">+ Thêm nhân viên</button>
</div>
<div class="g3">
  <div class="c"><div class="c-label">Tổng nhân viên</div><div class="c-val"><?= $staffAccounts->num_rows ?></div><div class="c-sub">Đã đăng ký</div></div>
  <div class="c"><div class="c-label">Đang hoạt động</div><div class="c-val"><?= (function() use ($staffAccounts) { $staffAccounts->data_seek(0); $n=0; while($r=$staffAccounts->fetch_assoc()) if(!empty($r['is_active'])) $n++; $staffAccounts->data_seek(0); return $n; })() ?></div><div class="c-sub">Tài khoản kích hoạt</div></div>
  <div class="c"><div class="c-label">Phân quyền</div><div class="c-val"><?= count($adminPermissionDefinitions) ?></div><div class="c-sub">Loại quyền hạn</div></div>
</div>
<div class="c">
  <div class="tbl-wrap">
    <table>
      <thead><tr><th>Nhân viên</th><th>Bộ phận / Vai trò</th><th>Quyền hạn</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
      <tbody id="staffTbody">
      <?php $staffAccounts->data_seek(0); while ($staff = $staffAccounts->fetch_assoc()):
        $sd = htmlspecialchars(json_encode([
          'staff_id'=>$staff['id'],'username'=>$staff['username'],'full_name'=>$staff['full_name'],
          'department'=>$staff['department']??'','role'=>$staff['role']??'staff',
          'is_active'=>(int)!empty($staff['is_active']),
          'can_manage_accounts'=>(int)!empty($staff['can_manage_accounts']),
          'can_manage_records'=>(int)!empty($staff['can_manage_records']),
          'can_manage_all_records'=>(int)!empty($staff['can_manage_all_records']),
          'can_manage_clinic_content'=>(int)!empty($staff['can_manage_clinic_content']),
          'can_manage_doctors'=>(int)!empty($staff['can_manage_doctors']),
          'can_manage_patients'=>(int)!empty($staff['can_manage_patients']),
          'can_manage_chatbot'=>(int)!empty($staff['can_manage_chatbot']),
          'can_manage_support_chat'=>(int)!empty($staff['can_manage_support_chat']),
          'can_publish_announcements'=>(int)!empty($staff['can_publish_announcements']),
          'can_create_backup'=>(int)!empty($staff['can_create_backup']),
          'can_view_logs'=>(int)!empty($staff['can_view_logs']),
        ], JSON_UNESCAPED_UNICODE), ENT_QUOTES);
      ?>
        <tr>
          <td>
            <div class="row-f">
              <div class="av"><?= mb_strtoupper(mb_substr(e($staff['full_name']), 0, 2)) ?></div>
              <div>
                <div style="font-weight:500"><?= e($staff['full_name']) ?></div>
                <div class="txt-sm txt-muted"><?= e($staff['username']) ?></div>
              </div>
            </div>
          </td>
          <td><?= e($staff['department'] ?? '—') ?><br><span class="txt-sm txt-muted"><?= e($staff['role'] ?? 'staff') ?></span></td>
          <td>
            <?php foreach ($adminPermissionDefinitions as $perm => $def): ?>
              <?php if (!empty($staff[$def['column']])): ?>
                <span class="tag tag-blue" style="margin:1px;"><?= e($def['label']) ?></span>
              <?php endif; ?>
            <?php endforeach; ?>
          </td>
          <td>
            <?= !empty($staff['is_active'])
              ? '<span class="tag tag-green">Hoạt động</span>'
              : '<span class="tag tag-gray">Tạm khóa</span>' ?>
          </td>
          <td>
            <div class="actions-row" style="margin:0;gap:6px;">
              <button type="button" class="btn-s"
                data-open-modal="modalEditStaff"
                data-fill-form="formEditStaff"
                data-fill="<?= $sd ?>">Sửa</button>
              <form method="post" style="display:inline">
                <?php render_form_guard('admin_accounts'); ?>
                <input type="hidden" name="action" value="reset_staff_password">
                <input type="hidden" name="target_id" value="<?= (int)$staff['id'] ?>">
                <button type="submit" class="btn-s">Mật khẩu tạm</button>
              </form>
              <form method="post" style="display:inline"
                data-confirm="Xóa nhân viên «<?= e(addslashes($staff['full_name'])) ?>»?">
                <?php render_form_guard('admin_accounts'); ?>
                <input type="hidden" name="action" value="delete_staff">
                <input type="hidden" name="target_id" value="<?= (int)$staff['id'] ?>">
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

<!-- Modal Thêm -->
<div id="modalAddStaff" class="modal-overlay">
  <div class="modal">
    <div class="modal-header"><h3>Thêm nhân viên mới</h3><button class="modal-close" type="button">✕</button></div>
    <form method="post">
      <?php render_form_guard('admin_accounts'); ?>
      <input type="hidden" name="action" value="save_staff">
      <input type="hidden" name="staff_id" value="0">
      <div class="f-grid2">
        <div class="f-group"><label class="f-label-sm">Tên đăng nhập *</label><input class="f-inp" name="username" required placeholder="vd: nhanvien01"></div>
        <div class="f-group"><label class="f-label-sm">Họ tên *</label><input class="f-inp" name="full_name" required placeholder="Nguyễn Văn A"></div>
        <div class="f-group"><label class="f-label-sm">Bộ phận</label><input class="f-inp" name="department" placeholder="Xét nghiệm, Tiếp đón…"></div>
        <div class="f-group"><label class="f-label-sm">Chức danh</label><input class="f-inp" name="role" value="staff"></div>
        <div class="f-group"><label class="f-label-sm">Mật khẩu *</label><input class="f-inp" type="password" name="password" minlength="8" required></div>
        <div class="f-group" style="display:flex;align-items:flex-end;">
          <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer;">
            <div class="tog-wrap"><input type="checkbox" name="is_active" checked><span class="tog-track"></span></div>
            Kích hoạt tài khoản
          </label>
        </div>
      </div>
      <div class="section-title">Phân quyền</div>
      <div class="perm-grid">
        <?php foreach ($adminPermissionDefinitions as $perm => $def): ?>
          <label class="perm-item">
            <input type="checkbox" name="<?= e($def['column']) ?>">
            <span><?= e($def['label']) ?></span>
          </label>
        <?php endforeach; ?>
      </div>
      <div class="actions-row"><button type="submit" class="btn-p">Tạo tài khoản</button><button type="button" class="btn-s modal-close">Hủy</button></div>
    </form>
  </div>
</div>

<!-- Modal Sửa -->
<div id="modalEditStaff" class="modal-overlay">
  <div class="modal">
    <div class="modal-header"><h3>Chỉnh sửa nhân viên</h3><button class="modal-close" type="button">✕</button></div>
    <form method="post" id="formEditStaff">
      <?php render_form_guard('admin_accounts'); ?>
      <input type="hidden" name="action" value="save_staff">
      <input type="hidden" name="staff_id" value="">
      <div class="f-grid2">
        <div class="f-group"><label class="f-label-sm">Tên đăng nhập *</label><input class="f-inp" name="username" required></div>
        <div class="f-group"><label class="f-label-sm">Họ tên *</label><input class="f-inp" name="full_name" required></div>
        <div class="f-group"><label class="f-label-sm">Bộ phận</label><input class="f-inp" name="department"></div>
        <div class="f-group"><label class="f-label-sm">Chức danh</label><input class="f-inp" name="role"></div>
        <div class="f-group"><label class="f-label-sm">Mật khẩu mới <span class="txt-muted">(để trống = giữ nguyên)</span></label><input class="f-inp" type="password" name="password"></div>
        <div class="f-group" style="display:flex;align-items:flex-end;">
          <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer;">
            <div class="tog-wrap"><input type="checkbox" name="is_active"><span class="tog-track"></span></div>
            Kích hoạt tài khoản
          </label>
        </div>
      </div>
      <div class="section-title">Phân quyền</div>
      <div class="perm-grid">
        <?php foreach ($adminPermissionDefinitions as $perm => $def): ?>
          <label class="perm-item">
            <input type="checkbox" name="<?= e($def['column']) ?>" data-perm="<?= e($def['column']) ?>">
            <span><?= e($def['label']) ?></span>
          </label>
        <?php endforeach; ?>
      </div>
      <div class="actions-row"><button type="submit" class="btn-p">Lưu thay đổi</button><button type="button" class="btn-s modal-close">Hủy</button></div>
    </form>
  </div>
</div>
<?php endif; ?>
