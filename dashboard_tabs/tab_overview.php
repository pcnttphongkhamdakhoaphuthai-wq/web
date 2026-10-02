<?php /* TAB: Tổng quan */ ?>
<div id="tab-overview" class="dashboard-tab tab-active">
  <section class="card">
    <div class="panel-title">
      <div>
        <h2 class="section-title">Tổng quan tài khoản</h2>
        <p class="muted">Bệnh nhân được phép đặt lịch, xem kết quả, tải tệp kết quả và chỉnh sửa thông tin cá nhân.</p>
      </div>
    </div>
    <div class="metric-strip">
      <div class="metric"><strong><?= count($appointments) ?></strong><span>Lịch khám</span></div>
      <div class="metric"><strong><?= count($records) ?></strong><span>Hồ sơ khám</span></div>
      <div class="metric"><strong><?= count($chatMessages) ?></strong><span>Tin nhắn hỗ trợ</span></div>
    </div>
  </section>

  <div class="grid grid-2" style="margin-top:20px;">
    <?php /* Lịch hẹn gần đây */ ?>
    <section class="card">
      <div class="panel-title">
        <div>
          <h2>Lịch hẹn gần đây</h2>
        </div>
        <a href="#appointments" class="btn btn-light" onclick="document.querySelector('[data-tab=\'tab-appointments\']').click()">Xem tất cả</a>
      </div>
      <?php $recentAppts = array_slice($appointments, 0, 3); ?>
      <?php if (count($recentAppts) === 0): ?>
        <p class="muted">Chưa có lịch hẹn nào.</p>
      <?php else: ?>
        <div class="records-list">
          <?php foreach ($recentAppts as $appt): ?>
            <div class="record-card">
              <div class="record-header" style="background:#fff; border-bottom:none; padding-bottom:0;">
                <div>
                  <div class="record-date">📅 <?= e(date('d/m/Y H:i', strtotime($appt['appointment_date']))) ?></div>
                  <div class="record-doctor" style="margin-top:4px;">👨‍⚕️ <?= e($appt['doctor_name']) ?> - <?= e($appt['department']) ?></div>
                </div>
                <span class="badge"><?= e($appt['status']) ?></span>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <?php /* Kết quả khám gần đây */ ?>
    <section class="card">
      <div class="panel-title">
        <div>
          <h2>Kết quả khám gần đây</h2>
        </div>
        <a href="#records" class="btn btn-light" onclick="document.querySelector('[data-tab=\'tab-records\']').click()">Xem tất cả</a>
      </div>
      <?php $recentRecords = array_slice($records, 0, 3); ?>
      <?php if (count($recentRecords) === 0): ?>
        <p class="muted">Chưa có kết quả khám nào.</p>
      <?php else: ?>
        <div class="records-list">
          <?php foreach ($recentRecords as $rec): ?>
            <details class="record-card" style="cursor:pointer;">
              <summary class="record-header" style="background:#fff; border-bottom:none; padding-bottom:12px; outline:none; list-style:none;">
                <div style="display:flex; justify-content:space-between; align-items:center; width:100%;">
                  <div>
                    <div class="record-date">📅 <?= e(date('d/m/Y', strtotime($rec['visit_date']))) ?></div>
                    <div class="record-doctor" style="margin-top:4px;">👨‍⚕️ <?= e($rec['doctor_name']) ?></div>
                  </div>
                  <?php if (!empty($rec['result_file'])): ?>
                    <a href="<?= e(result_download_url((int) $rec['id'])) ?>" target="_blank" class="btn btn-pdf" style="padding:4px 8px; font-size:12px;">PDF</a>
                  <?php else: ?>
                    <span class="muted text-sm">Xem ▼</span>
                  <?php endif; ?>
                </div>
              </summary>
              <div class="record-body" style="padding:0 16px 16px; border-top:1px solid #e2e8f0; margin-top:-4px; padding-top:12px;">
                <div class="record-section">
                  <div class="record-label">Kết luận / Chẩn đoán</div>
                  <div class="record-content"><?= nl2br(e($rec['diagnosis'])) ?></div>
                </div>
                <?php if (!empty($rec['prescription'])): ?>
                <div class="record-section" style="margin-top:10px;">
                  <div class="record-label">Đơn thuốc / Ghi chú</div>
                  <div class="record-content"><?= nl2br(e($rec['prescription'])) ?></div>
                </div>
                <?php endif; ?>
              </div>
            </details>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  </div>


  <section class="card announcement-card" style="margin-top:20px;">
    <div class="panel-title">
      <div>
        <h2>Thông báo tới khách hàng</h2>
        <p class="muted">Các thông báo do admin gửi sẽ hiện tại đây và được đưa vào bot chat nếu admin bật chế độ gửi kèm.</p>
      </div>
    </div>
    <?php if ($announcements === []): ?>
      <div class="empty-state">Chưa có thông báo nào mới.</div>
    <?php else: ?>
      <div class="grid">
        <?php foreach ($announcements as $announcement): ?>
          <article class="service-card">
            <h3><?= e($announcement['title']) ?></h3>
            <div><?= nl2br(e($announcement['message'])) ?></div>
            <div class="muted text-sm" style="margin-top:10px;"><?= e(date('d/m/Y H:i', strtotime((string) $announcement['created_at']))) ?></div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
</div>
