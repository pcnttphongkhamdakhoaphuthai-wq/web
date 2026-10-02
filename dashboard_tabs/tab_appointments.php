<?php /* TAB: Lịch khám */ ?>
<div id="tab-appointments" class="dashboard-tab">
  <section class="card">
    <h2>Lịch khám của bạn</h2>
    <?php if (!$appointmentsEnabled): ?>
      <div class="empty-state">Tính năng đặt lịch khám hiện đang được tạm ẩn bởi quản trị viên.</div>
    <?php elseif (count($appointments) === 0): ?>
      <p class="muted">Chưa có lịch hẹn nào. <a href="book_appointment.php">Đặt lịch ngay</a></p>
    <?php else: ?>
      <table>
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
          <?php foreach ($appointments as $row): ?>
            <tr>
              <td><?= e(date('d/m/Y H:i', strtotime($row['appointment_date']))) ?></td>
              <td><?= e($row['doctor_name']) ?></td>
              <td><?= e($row['department']) ?></td>
              <td><?= nl2br(e($row['reason'])) ?></td>
              <td><?= e($row['status']) ?></td>
              <td>
                <?php if (appointment_is_editable($row)): ?>
                  <div class="actions">
                    <a class="btn btn-light" href="book_appointment.php?edit=<?= (int) $row['id'] ?>">Sửa</a>
                    <form method="post" class="inline-form" onsubmit="return confirm('Xóa lịch hẹn này?');">
                      <?php render_form_guard('patient_appointment_manage'); ?>
                      <input type="hidden" name="action" value="delete_appointment">
                      <input type="hidden" name="appointment_id" value="<?= (int) $row['id'] ?>">
                      <button type="submit" class="danger-btn">Xóa</button>
                    </form>
                  </div>
                <?php else: ?>
                  <span class="muted">Đã khóa thao tác</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </section>
</div>
