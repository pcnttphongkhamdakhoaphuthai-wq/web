<?php if ($canViewLogs): ?>
<div class="g3">
  <div class="c"><div class="c-label">Hành động hôm nay</div><div class="c-val"><?= count(array_filter($auditLogs, fn($l) => str_starts_with((string)($l['time']??''), date('Y-m-d')))) ?></div><div class="c-sub">Tất cả người dùng</div></div>
  <div class="c"><div class="c-label">Bản ghi audit</div><div class="c-val"><?= count($auditLogs) ?></div><div class="c-sub">Đã lọc</div></div>
  <div class="c"><div class="c-label">Sự kiện bảo mật</div><div class="c-val"><?= count($securityLogs) ?></div><div class="c-sub">Gần đây</div></div>
</div>

<div class="sbar">
  <input class="sinp" placeholder="Lọc nhật ký theo người dùng, hành động..." id="logSearchInp">
  <form method="get" style="display:flex;gap:6px;flex:none">
    <input class="sinp" style="flex:none;width:120px" type="date" name="date_from" value="<?= e($logFilters['date_from']) ?>" placeholder="Từ ngày">
    <input class="sinp" style="flex:none;width:120px" type="date" name="date_to" value="<?= e($logFilters['date_to']) ?>" placeholder="Đến ngày">
    <input type="hidden" name="tab" value="tab-logs">
    <button type="submit" class="btn-s">Lọc</button>
    <a href="admin_accounts.php#tab-logs" class="btn-s" style="text-decoration:none">Xóa</a>
  </form>
</div>

<div class="section-title">Nhật ký thao tác admin</div>
<div class="c">
  <?php if (empty($auditLogs)): ?>
    <div class="empty-note">Chưa có lưu vết thao tác nào.</div>
  <?php else: ?>
    <?php foreach ($auditLogs as $log):
      $ev = strtolower((string)($log['event'] ?? ''));
      if (str_contains($ev,'login')) $tc='tag-green';
      elseif (str_contains($ev,'creat')||str_contains($ev,'add')||str_contains($ev,'them')) $tc='tag-blue';
      elseif (str_contains($ev,'update')||str_contains($ev,'save')||str_contains($ev,'sua')) $tc='tag-amber';
      elseif (str_contains($ev,'delet')||str_contains($ev,'remove')||str_contains($ev,'xoa')) $tc='tag-red';
      elseif (str_contains($ev,'backup')) $tc='tag-blue';
      else $tc='tag-gray';
    ?>
      <div class="log-item">
        <div class="log-time"><?= e(date('d/m – H:i', strtotime((string)($log['time']??'now')))) ?></div>
        <div style="flex:1">
          <div class="log-msg"><?= e((string)($log['event']??'')) ?></div>
          <div class="log-user"><?= e((string)(($log['actor']['username']??'')!==''?$log['actor']['username']:($log['actor']['full_name']??'—'))) ?> · <?= e((string)($log['actor']['type']??'')) ?></div>
        </div>
        <span class="tag <?= $tc ?>" style="align-self:flex-start"><?= e((string)($log['event']??'')) ?></span>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<div class="section-title">Nhật ký bảo mật</div>
<div class="c">
  <?php if (empty($securityLogs)): ?>
    <div class="empty-note">Chưa có sự kiện bảo mật nào.</div>
  <?php else: ?>
    <?php foreach ($securityLogs as $log):
      $ev = strtolower((string)($log['event']??''));
      if (str_contains($ev,'fail')||str_contains($ev,'error')) $tc2='tag-red';
      elseif (str_contains($ev,'login')) $tc2='tag-green';
      else $tc2='tag-gray';
    ?>
      <div class="log-item">
        <div class="log-time"><?= e(date('d/m – H:i', strtotime((string)($log['time']??'now')))) ?></div>
        <div style="flex:1">
          <div class="log-msg"><?= e((string)($log['event']??'')) ?></div>
          <div class="log-user">IP: <code style="font-size:11px"><?= e((string)($log['ip']??'')) ?></code></div>
        </div>
        <span class="tag <?= $tc2 ?>" style="align-self:flex-start"><?= e((string)($log['event']??'')) ?></span>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
<script>
(function(){
  var inp=document.getElementById('logSearchInp');
  if(!inp)return;
  inp.addEventListener('input',function(){
    var q=this.value.toLowerCase();
    document.querySelectorAll('#tab-logs .log-item').forEach(function(item){
      item.style.display=item.textContent.toLowerCase().includes(q)?'':'none';
    });
  });
})();
</script>
<?php endif; ?>
