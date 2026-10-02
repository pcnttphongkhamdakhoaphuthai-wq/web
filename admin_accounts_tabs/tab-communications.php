<?php if ($canManageChatbot): ?>
<div class="section-title">Câu trả lời nhanh (Quick replies)</div>
<div class="c" style="margin-bottom:16px">
  <div class="sbar" style="margin-bottom:0">
    <input class="sinp" id="searchReplies" placeholder="Tìm câu hỏi nhanh...">
    <button type="button" class="btn-p" data-open-modal="modalAddReply">+ Thêm câu trả lời</button>
  </div>
  <div class="tbl-wrap" style="margin-top:12px;">
    <table>
      <thead><tr><th>Câu hỏi</th><th>Câu trả lời</th><th>Thứ tự</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
      <tbody id="repliesTbody">
      <?php $quickReplies->data_seek(0); while ($r = $quickReplies->fetch_assoc()):
        $rd = htmlspecialchars(json_encode([
          'reply_id'=>$r['id'],'question'=>$r['question'],'answer'=>$r['answer'],
          'sort_order'=>$r['sort_order'],'is_active'=>(int)!empty($r['is_active']),
        ], JSON_UNESCAPED_UNICODE), ENT_QUOTES);
      ?>
        <tr>
          <td style="font-weight:500"><?= e($r['question']) ?></td>
          <td class="txt-sm txt-muted"><?= e(mb_substr($r['answer'], 0, 70)) ?><?= mb_strlen($r['answer'])>70?'…':'' ?></td>
          <td><?= (int)$r['sort_order'] ?></td>
          <td><?= !empty($r['is_active']) ? '<span class="tag tag-green">Hiển thị</span>' : '<span class="tag tag-gray">Ẩn</span>' ?></td>
          <td>
            <button type="button" class="btn-s"
              data-open-modal="modalEditReply"
              data-fill-form="formEditReply"
              data-fill="<?= $rd ?>">Sửa</button>
          </td>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="section-title">Cài đặt chatbot</div>
<div class="c">
  <div class="f-row"><span class="f-label">Trạng thái bot</span><span class="f-val"></span><div class="tog on"></div></div>
  <div class="f-row"><span class="f-label">Tự động trả lời ngoài giờ</span><span class="f-val"></span><div class="tog on"></div></div>
  <div class="f-row">
    <span class="f-label">Nội dung giới thiệu bot</span>
    <span class="f-val txt-muted"><?= e(mb_substr($clinicSettings['chatbot_intro'] ?? 'Chưa cập nhật', 0, 60)) ?></span>
    <button type="button" class="btn-s" data-open-modal="modalEditBotIntro">Sửa</button>
  </div>
  <div class="f-row">
    <span class="f-label">Câu trả lời mặc định</span>
    <span class="f-val txt-muted"><?= e(mb_substr($clinicSettings['chatbot_fallback'] ?? 'Chưa cập nhật', 0, 60)) ?></span>
    <button type="button" class="btn-s" data-open-modal="modalEditBotFallback">Sửa</button>
  </div>
</div>
<?php endif; ?>

<?php if ($canPublishAnnouncements): ?>
<div class="section-title" style="margin-top:16px">Gửi thông báo tới khách hàng</div>
<div class="c">
  <form method="post">
    <?php render_form_guard('admin_accounts'); ?>
    <input type="hidden" name="action" value="publish_patient_announcement">
    <div class="f-group"><label class="f-label-sm">Tiêu đề thông báo *</label><input class="f-inp" name="announcement_title" required placeholder="Vd: Lịch nghỉ lễ tháng 4"></div>
    <div class="f-group"><label class="f-label-sm">Nội dung *</label><textarea class="f-ta" name="announcement_message" required placeholder="Nội dung thông báo..."></textarea></div>
    <div style="display:flex;gap:20px;margin-bottom:12px;flex-wrap:wrap;">
      <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer;">
        <div class="tog-wrap"><input type="checkbox" name="push_to_chat" checked><span class="tog-track"></span></div>Gửi kèm vào bot chat
      </label>
      <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer;">
        <div class="tog-wrap"><input type="checkbox" name="send_to_email" checked><span class="tog-track"></span></div>Gửi qua Gmail
      </label>
    </div>
    <button type="submit" class="btn-p">Gửi thông báo</button>
  </form>
  <?php if (!empty($recentAnnouncements)): ?>
    <div class="section-title" style="margin-top:16px">Thông báo gần đây</div>
    <div class="tbl-wrap"><table>
      <thead><tr><th>Thời gian</th><th>Tiêu đề</th><th>Nội dung</th></tr></thead>
      <tbody>
      <?php foreach ($recentAnnouncements as $ann): ?>
        <tr>
          <td class="txt-sm txt-muted"><?= e(date('d/m H:i', strtotime((string)$ann['created_at']))) ?></td>
          <td style="font-weight:500"><?= e($ann['title']) ?></td>
          <td class="txt-sm"><?= e(mb_substr($ann['message'],0,80)) ?>…</td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
  <?php endif; ?>
</div>
<?php endif; ?>

<!-- Modal Thêm quick reply -->
<div id="modalAddReply" class="modal-overlay">
  <div class="modal">
    <div class="modal-header"><h3>Thêm câu hỏi nhanh</h3><button class="modal-close" type="button">✕</button></div>
    <form method="post">
      <?php render_form_guard('admin_accounts'); ?>
      <input type="hidden" name="action" value="save_quick_reply">
      <input type="hidden" name="reply_id" value="0">
      <div class="f-group"><label class="f-label-sm">Câu hỏi *</label><input class="f-inp" name="question" required placeholder="Vd: Giờ làm việc?"></div>
      <div class="f-group"><label class="f-label-sm">Câu trả lời *</label><textarea class="f-ta" name="answer" required placeholder="Nội dung câu trả lời..."></textarea></div>
      <div class="f-grid2">
        <div class="f-group"><label class="f-label-sm">Thứ tự</label><input class="f-inp" type="number" name="sort_order" value="0"></div>
        <div class="f-group" style="display:flex;align-items:flex-end;">
          <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer;">
            <div class="tog-wrap"><input type="checkbox" name="is_active" checked><span class="tog-track"></span></div>Hiển thị
          </label>
        </div>
      </div>
      <div class="actions-row"><button type="submit" class="btn-p">Thêm</button><button type="button" class="btn-s modal-close">Hủy</button></div>
    </form>
  </div>
</div>

<!-- Modal Sửa quick reply -->
<div id="modalEditReply" class="modal-overlay">
  <div class="modal">
    <div class="modal-header"><h3>Sửa câu hỏi nhanh</h3><button class="modal-close" type="button">✕</button></div>
    <form method="post" id="formEditReply">
      <?php render_form_guard('admin_accounts'); ?>
      <input type="hidden" name="action" value="save_quick_reply">
      <input type="hidden" name="reply_id" value="">
      <div class="f-group"><label class="f-label-sm">Câu hỏi *</label><input class="f-inp" name="question" required></div>
      <div class="f-group"><label class="f-label-sm">Câu trả lời *</label><textarea class="f-ta" name="answer" required></textarea></div>
      <div class="f-grid2">
        <div class="f-group"><label class="f-label-sm">Thứ tự</label><input class="f-inp" type="number" name="sort_order" value="0"></div>
        <div class="f-group" style="display:flex;align-items:flex-end;">
          <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer;">
            <div class="tog-wrap"><input type="checkbox" name="is_active"><span class="tog-track"></span></div>Hiển thị
          </label>
        </div>
      </div>
      <div class="actions-row"><button type="submit" class="btn-p">Lưu</button><button type="button" class="btn-s modal-close">Hủy</button></div>
    </form>
  </div>
</div>

<!-- Modal Sửa bot intro -->
<div id="modalEditBotIntro" class="modal-overlay">
  <div class="modal">
    <div class="modal-header"><h3>Nội dung giới thiệu bot</h3><button class="modal-close" type="button">✕</button></div>
    <form method="post">
      <?php render_form_guard('admin_accounts'); ?>
      <input type="hidden" name="action" value="save_clinic_content">
      <div class="f-group"><label class="f-label-sm">Câu chào/giới thiệu bot</label><textarea class="f-ta" name="chatbot_intro"><?= e($clinicSettings['chatbot_intro'] ?? '') ?></textarea></div>
      <div class="actions-row"><button type="submit" class="btn-p">Lưu</button><button type="button" class="btn-s modal-close">Hủy</button></div>
    </form>
  </div>
</div>

<!-- Modal Sửa bot fallback -->
<div id="modalEditBotFallback" class="modal-overlay">
  <div class="modal">
    <div class="modal-header"><h3>Câu trả lời mặc định</h3><button class="modal-close" type="button">✕</button></div>
    <form method="post">
      <?php render_form_guard('admin_accounts'); ?>
      <input type="hidden" name="action" value="save_clinic_content">
      <div class="f-group"><label class="f-label-sm">Câu trả lời khi không khớp</label><textarea class="f-ta" name="chatbot_fallback"><?= e($clinicSettings['chatbot_fallback'] ?? '') ?></textarea></div>
      <div class="actions-row"><button type="submit" class="btn-p">Lưu</button><button type="button" class="btn-s modal-close">Hủy</button></div>
    </form>
  </div>
</div>
