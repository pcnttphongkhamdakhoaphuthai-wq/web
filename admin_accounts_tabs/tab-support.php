<?php if ($canManageSupportChat): ?>
<div class="g2">
  <div class="c"><div class="c-label">Đang chờ xử lý</div><div class="c-val"><?= !empty($supportChatNotice['unread_count']) ? (int)$supportChatNotice['unread_count'] : 0 ?></div><div class="c-sub">Cuộc hội thoại mới</div></div>
  <div class="c"><div class="c-label">Tin nhắn gần đây</div><div class="c-val"><?= $recentChats->num_rows ?></div><div class="c-sub">Trong danh sách</div></div>
</div>

<div class="section-title">Hội thoại gần đây</div>
<div class="c" style="margin-bottom:16px">
  <?php if ($recentChats->num_rows === 0): ?>
    <div class="empty-note">Chưa có tin nhắn hỗ trợ nào.</div>
  <?php else: ?>
    <div class="tbl-wrap">
      <table>
        <thead><tr><th>Người dùng</th><th>Tin nhắn cuối</th><th>Thời gian</th><th>Loại</th><th>Thao tác</th></tr></thead>
        <tbody>
        <?php $recentChats->data_seek(0); while ($chat = $recentChats->fetch_assoc()): ?>
          <tr>
            <td><div class="row-f"><div class="av"><?= mb_strtoupper(mb_substr(e($chat['full_name']),0,2)) ?></div><div><div style="font-weight:500"><?= e($chat['full_name']) ?></div><div class="txt-sm txt-muted"><?= e($chat['cccd']) ?></div></div></div></td>
            <td class="txt-sm txt-muted"><?= e(mb_substr($chat['message'],0,50)) ?><?= mb_strlen($chat['message'])>50?'…':'' ?></td>
            <td class="txt-sm txt-muted"><?= e(date('d/m H:i', strtotime($chat['created_at']))) ?></td>
            <td><?= $chat['sender']==='patient' ? '<span class="tag tag-amber">Chờ phản hồi</span>' : '<span class="tag tag-green">Đã xử lý</span>' ?></td>
            <td>
              <?php if ((string)$chat['sender']==='patient'): ?>
                <button type="button" class="btn-s"
                  data-reply-modal="1"
                  data-patient-id="<?= (int)$chat['patient_id'] ?>"
                  data-chat-id="<?= (int)$chat['id'] ?>"
                  data-patient-name="<?= e($chat['full_name']) ?>">Mở chat</button>
              <?php else: ?>
                <button type="button" class="btn-s" disabled>Xem lại</button>
              <?php endif; ?>
            </td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<div class="section-title">Xem trước hội thoại bot</div>
<div class="chat-wrap">
  <div class="chat-bubble chat-bot">Xin chào! <?= e($clinicSettings['clinic_name'] ?? 'Phòng khám') ?> có thể giúp gì cho bạn hôm nay?</div>
  <div class="chat-bubble chat-user">Tôi muốn đặt lịch khám</div>
  <div class="chat-bubble chat-bot"><?= e(mb_substr($clinicSettings['chatbot_intro'] ?? 'Bạn vui lòng cho biết ngày giờ mong muốn, tôi sẽ kiểm tra lịch trống nhé!', 0, 120)) ?></div>
</div>

<!-- Modal reply -->
<div id="modalReplyChat" class="modal-overlay">
  <div class="modal">
    <div class="modal-header"><h3 id="replyChatTitle">Trả lời bệnh nhân</h3><button class="modal-close" type="button">✕</button></div>
    <form method="post" action="admin_accounts.php#tab-support">
      <?php render_form_guard('admin_accounts'); ?>
      <input type="hidden" name="action" value="reply_patient_chat">
      <input type="hidden" name="patient_id" id="replyChatPatId" value="">
      <input type="hidden" name="latest_chat_id" id="replyChatLatestId" value="">
      <div class="f-group"><label class="f-label-sm">Nội dung trả lời *</label><textarea class="f-ta" id="replyChatMsg" name="message" required placeholder="Nhập câu trả lời..."></textarea></div>
      <div class="actions-row"><button type="submit" class="btn-p">Gửi trả lời</button><button type="button" class="btn-s modal-close">Hủy</button></div>
    </form>
  </div>
</div>
<?php endif; ?>
