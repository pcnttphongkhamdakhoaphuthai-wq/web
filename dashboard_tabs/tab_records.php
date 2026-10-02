<?php /* TAB: Kết quả xét nghiệm & Hồ sơ bệnh án */ ?>
<div id="tab-records" class="dashboard-tab">

  <?php /* ── Phần 1: Lịch hẹn đã đặt ── */ ?>
  <section class="card" style="margin-bottom:20px;">
    <div class="panel-title">
      <div>
        <h2>📅 Lịch hẹn đã đặt</h2>
        <p class="muted">Xem lịch khám và xóa lịch đặt nhầm ngay tại đây.</p>
      </div>
      <div class="actions">
        <a href="book_appointment.php" class="btn">+ Đặt lịch mới</a>
      </div>
    </div>

    <?php if (count($appointments) === 0): ?>
      <p class="muted" style="margin-top:10px;">Chưa có lịch hẹn nào được đặt.</p>
    <?php else: ?>
      <table style="margin-top:10px;">
        <thead>
          <tr>
            <th>Thời gian</th>
            <th>Bác sĩ</th>
            <th>Khoa</th>
            <th>Lý do</th>
            <th>Trạng thái</th>
            <th>Thao tác</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($appointments as $appt): ?>
            <tr>
              <td><?= e(date('d/m/Y H:i', strtotime($appt['appointment_date']))) ?></td>
              <td><?= e($appt['doctor_name']) ?></td>
              <td><?= e($appt['department']) ?></td>
              <td><?= nl2br(e($appt['reason'])) ?></td>
              <td><?= e($appt['status']) ?></td>
              <td>
                <?php if (appointment_is_editable($appt)): ?>
                  <div class="actions">
                    <a class="btn btn-light" href="book_appointment.php?edit=<?= (int) $appt['id'] ?>">Sửa</a>
                    <form method="post" class="inline-form" onsubmit="return confirm('Xóa lịch hẹn này?');">
                      <?php render_form_guard('patient_appointment_manage'); ?>
                      <input type="hidden" name="action" value="delete_appointment">
                      <input type="hidden" name="appointment_id" value="<?= (int) $appt['id'] ?>">
                      <button type="submit" class="danger-btn">Xóa</button>
                    </form>
                  </div>
                <?php else: ?>
                  <span class="muted">Đã khóa</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </section>

  <?php /* ── Phần 2: Kết quả khám & hồ sơ bệnh án ── */ ?>
  <section class="card" id="records">
    <div class="panel-title">
      <div>
        <h2>🩺 Kết quả khám & hồ sơ bệnh án</h2>
        <p class="muted">Kết quả được hiển thị đầy đủ. Nếu có tệp PDF, bấm nút xanh để xem hoặc tải về.</p>
      </div>
    </div>

    <?php if (count($records) === 0): ?>
      <p class="muted" style="margin-top:10px;">Chưa có hồ sơ khám nào.</p>
    <?php else: ?>
      <div class="records-list" style="margin-top:14px;">
        <?php foreach ($records as $row): ?>
          <div class="record-card">
            <div class="record-header">
              <div>
                <span class="record-date">📅 <?= e(date('d/m/Y', strtotime($row['visit_date']))) ?></span>
                <span class="record-doctor">👨‍⚕️ <?= e($row['doctor_name']) ?></span>
              </div>
              <?php if (!empty($row['result_file'])): ?>
                <a href="<?= e(result_download_url((int) $row['id'])) ?>" target="_blank" rel="noopener" class="btn btn-pdf">
                  📄 Xem tệp PDF
                </a>
              <?php endif; ?>
            </div>
            <div class="record-body">
              <div class="record-section">
                <div class="record-label">Kết luận / Chẩn đoán</div>
                <div class="record-content"><?= nl2br(e($row['diagnosis'])) ?></div>
              </div>
              <?php if (!empty($row['prescription'])): ?>
              <div class="record-section">
                <div class="record-label">Đơn thuốc / Ghi chú</div>
                <div class="record-content"><?= nl2br(e($row['prescription'])) ?></div>
              </div>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

</div>
