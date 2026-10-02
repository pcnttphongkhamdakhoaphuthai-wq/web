<?php if ($canManageDoctors): ?>
<div class="sbar">
  <input class="sinp" id="searchDoctors" placeholder="Tìm bác sĩ theo tên, chuyên khoa...">
  <button type="button" class="btn-p" data-open-modal="modalAddDoctor">+ Thêm bác sĩ</button>
</div>
<div class="g3">
  <div class="c"><div class="c-label">Tổng bác sĩ</div><div class="c-val"><?= $doctors->num_rows ?></div><div class="c-sub">Đang hành nghề</div></div>
  <div class="c"><div class="c-label">Chuyên khoa</div><div class="c-val"><?= (function() use ($doctors) { $doctors->data_seek(0); $s=[]; while($r=$doctors->fetch_assoc()) if(!empty($r['department'])) $s[$r['department']]=1; $doctors->data_seek(0); return count($s); })() ?></div><div class="c-sub">Khoa đang hoạt động</div></div>
  <div class="c"><div class="c-label">Có ảnh đại diện</div><div class="c-val"><?= (function() use ($doctors) { $doctors->data_seek(0); $n=0; while($r=$doctors->fetch_assoc()) if(!empty($r['photo_path'])) $n++; $doctors->data_seek(0); return $n; })() ?></div><div class="c-sub">Hồ sơ hoàn chỉnh</div></div>
</div>
<div class="c">
  <div class="tbl-wrap">
    <table>
      <thead><tr><th>Bác sĩ</th><th>Khoa</th><th>Chuyên môn</th><th>Thao tác</th></tr></thead>
      <tbody id="doctorsTbody">
      <?php $doctors->data_seek(0); while ($doc = $doctors->fetch_assoc()):
        $dd = htmlspecialchars(json_encode([
          'doctor_id'=>$doc['id'],'name'=>$doc['name'],'title'=>$doc['title']??'',
          'department'=>$doc['department'],'specialties'=>$doc['specialties']??'',
          'bio'=>$doc['bio']??'','current_photo'=>$doc['photo_path']??'',
        ], JSON_UNESCAPED_UNICODE), ENT_QUOTES);
        $photoUrl = e(doctor_photo_url($doc['photo_path'] ?? null));
      ?>
        <tr>
          <td>
            <div class="row-f">
              <img src="<?= $photoUrl ?>" alt="" style="width:28px;height:28px;border-radius:50%;object-fit:cover;border:0.5px solid var(--ct);">
              <div>
                <div style="font-weight:500"><?= e($doc['name']) ?></div>
                <?php if (!empty($doc['title'])): ?><div class="txt-sm txt-muted"><?= e($doc['title']) ?></div><?php endif; ?>
              </div>
            </div>
          </td>
          <td><?= e($doc['department']) ?></td>
          <td class="txt-sm txt-muted"><?= e($doc['specialties'] ?? '—') ?></td>
          <td>
            <div class="actions-row" style="margin:0;gap:6px;">
              <button type="button" class="btn-s"
                data-open-modal="modalEditDoctor"
                data-fill-form="formEditDoctor"
                data-fill="<?= $dd ?>"
                data-photo-src="<?= $photoUrl ?>"
                data-photo-target="editDocPhoto">Sửa</button>
              <form method="post" style="display:inline"
                data-confirm="Xóa bác sĩ «<?= e(addslashes($doc['name'])) ?>»?">
                <?php render_form_guard('admin_accounts'); ?>
                <input type="hidden" name="action" value="delete_doctor">
                <input type="hidden" name="target_id" value="<?= (int)$doc['id'] ?>">
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

<!-- Modal Thêm bác sĩ -->
<div id="modalAddDoctor" class="modal-overlay">
  <div class="modal">
    <div class="modal-header"><h3>Thêm bác sĩ mới</h3><button class="modal-close" type="button">✕</button></div>
    <form method="post" enctype="multipart/form-data">
      <?php render_form_guard('admin_accounts'); ?>
      <input type="hidden" name="action" value="save_doctor">
      <input type="hidden" name="doctor_id" value="0">
      <input type="hidden" name="current_photo" value="">
      <div class="f-grid2">
        <div class="f-group"><label class="f-label-sm">Tên bác sĩ *</label><input class="f-inp" name="name" required placeholder="BS. Nguyễn Văn A"></div>
        <div class="f-group"><label class="f-label-sm">Chức danh</label><input class="f-inp" name="title" placeholder="BS CKI, ThS…"></div>
        <div class="f-group"><label class="f-label-sm">Khoa *</label><input class="f-inp" name="department" required placeholder="Nội, Ngoại, Sản…"></div>
        <div class="f-group"><label class="f-label-sm">Chuyên môn</label><input class="f-inp" name="specialties" placeholder="Tim mạch, Siêu âm…"></div>
      </div>
      <div class="f-group">
        <label class="f-label-sm">Ảnh bác sĩ</label>
        <input type="file" name="photo" accept=".jpg,.jpeg,.png,.webp" class="img-preview-input" data-preview="addDocPrev">
        <img id="addDocPrev" class="img-prev" src="" alt="">
      </div>
      <div class="f-group"><label class="f-label-sm">Giới thiệu</label><textarea class="f-ta" name="bio" placeholder="Kinh nghiệm, chuyên môn…"></textarea></div>
      <div class="actions-row"><button type="submit" class="btn-p">Thêm bác sĩ</button><button type="button" class="btn-s modal-close">Hủy</button></div>
    </form>
  </div>
</div>

<!-- Modal Sửa bác sĩ -->
<div id="modalEditDoctor" class="modal-overlay">
  <div class="modal">
    <div class="modal-header"><h3>Chỉnh sửa bác sĩ</h3><button class="modal-close" type="button">✕</button></div>
    <form method="post" enctype="multipart/form-data" id="formEditDoctor">
      <?php render_form_guard('admin_accounts'); ?>
      <input type="hidden" name="action" value="save_doctor">
      <input type="hidden" name="doctor_id" value="">
      <input type="hidden" name="current_photo" value="">
      <div class="f-grid2">
        <div class="f-group"><label class="f-label-sm">Tên bác sĩ *</label><input class="f-inp" name="name" required></div>
        <div class="f-group"><label class="f-label-sm">Chức danh</label><input class="f-inp" name="title"></div>
        <div class="f-group"><label class="f-label-sm">Khoa *</label><input class="f-inp" name="department" required></div>
        <div class="f-group"><label class="f-label-sm">Chuyên môn</label><input class="f-inp" name="specialties"></div>
      </div>
      <div class="f-group">
        <label class="f-label-sm">Ảnh hiện tại</label>
        <img id="editDocPhoto" src="" alt="" style="width:56px;height:56px;border-radius:var(--r);object-fit:cover;border:0.5px solid var(--ct);display:block;margin-bottom:8px;">
        <label style="display:flex;align-items:center;gap:8px;font-size:12px;cursor:pointer;margin-bottom:8px;">
          <input type="checkbox" name="remove_photo" style="width:auto;"> Xóa ảnh hiện tại
        </label>
        <label class="f-label-sm">Thay ảnh mới</label>
        <input type="file" name="photo" accept=".jpg,.jpeg,.png,.webp" class="img-preview-input" data-preview="editDocPrev">
        <img id="editDocPrev" class="img-prev" src="" alt="">
      </div>
      <div class="f-group"><label class="f-label-sm">Giới thiệu</label><textarea class="f-ta" name="bio"></textarea></div>
      <div class="actions-row"><button type="submit" class="btn-p">Lưu bác sĩ</button><button type="button" class="btn-s modal-close">Hủy</button></div>
    </form>
  </div>
</div>
<?php endif; ?>
