<?php
declare(strict_types=1);

require_once 'config.php';

$customerResources = get_customer_resources(50, true);
$clinicName = site_setting('clinic_name', 'Phòng khám đa khoa Phú Thái');

render_header('Hướng dẫn người bệnh · ' . $clinicName, 'guide');
?>
<div class="wrap">
  <!-- TIÊU ĐỀ TRANG HƯỚNG DẪN -->
  <section class="section card" style="background:linear-gradient(135deg, #f0f7fb 0%, #ffffff 100%);">
    <div class="panel-title" style="margin-bottom:0;">
      <div>
        <div style="font-size:12px;font-weight:700;color:var(--primary);text-transform:uppercase;letter-spacing:1px;margin-bottom:6px;">
          CẨM NANG Y TẾ & CHỈ DẪN KHÁM CHỮA BỆNH
        </div>
        <h1 class="section-title" style="font-size:28px;">Hướng dẫn người bệnh</h1>
        <p class="section-lead" style="margin-bottom:0;">
          Các chỉ dẫn chi tiết về quy trình khám bệnh, chuẩn bị xét nghiệm, thủ tục BHYT và cách tra cứu kết quả trực tuyến.
        </p>
      </div>
      <div>
        <a class="btn btn-outline" href="index.php">← Trang chủ</a>
      </div>
    </div>
  </section>

  <!-- DANH SÁCH BÀI HƯỚNG DẪN CỐT LÕI THỰC TẾ -->
  <section class="section">
    <div class="grid grid-3">
      <!-- Bài 1: Quy trình tra cứu kết quả -->
      <article class="card" style="margin-bottom:0;display:flex;flex-direction:column;justify-content:space-between;">
        <div>
          <div style="font-size:28px;margin-bottom:12px;">📑</div>
          <h3 style="font-size:17px;font-weight:700;color:#0f2942;margin:0 0 8px;">
            Hướng dẫn tra cứu kết quả khám & đơn thuốc điện tử
          </h3>
          <p style="font-size:13.5px;color:var(--muted);line-height:1.6;margin:0 0 16px;">
            Sử dụng số CCCD (12 chữ số) đã đăng ký tại quầy tiếp đón để đăng nhập. Sau khi vào hệ thống, chọn đợt khám tương ứng để xem chẩn đoán, kết quả xét nghiệm máu/nước tiểu, hình ảnh siêu âm và tải đơn thuốc PDF.
          </p>
        </div>
        <div>
          <a class="btn btn-light" style="width:100%;box-sizing:border-box;" href="login.php?redirect=records">Đăng nhập tra cứu ➔</a>
        </div>
      </article>

      <!-- Bài 2: Chuẩn bị trước khi đi khám -->
      <article class="card" style="margin-bottom:0;display:flex;flex-direction:column;justify-content:space-between;">
        <div>
          <div style="font-size:28px;margin-bottom:12px;">🩺</div>
          <h3 style="font-size:17px;font-weight:700;color:#0f2942;margin:0 0 8px;">
            Lưu ý cần chuẩn bị trước khi xét nghiệm và siêu âm
          </h3>
          <p style="font-size:13.5px;color:var(--muted);line-height:1.6;margin:0 0 16px;">
            Đối với xét nghiệm sinh hóa máu (đường huyết, mỡ máu, chức năng gan thận), quý khách nên nhịn ăn từ 6 - 8 tiếng. Khi siêu âm ổ bụng tổng quát hoặc vùng tiểu khung, cần uống nhiều nước và nhịn tiểu để kết quả hình ảnh rõ nét nhất.
          </p>
        </div>
        <div>
          <a class="btn btn-light" style="width:100%;box-sizing:border-box;" href="support.php">Liên hệ tư vấn thêm ➔</a>
        </div>
      </article>

      <!-- Bài 3: Quyền lợi BHYT -->
      <article class="card" style="margin-bottom:0;display:flex;flex-direction:column;justify-content:space-between;">
        <div>
          <div style="font-size:28px;margin-bottom:12px;">🏥</div>
          <h3 style="font-size:17px;font-weight:700;color:#0f2942;margin:0 0 8px;">
            Thủ tục hưởng quyền lợi Bảo hiểm Y tế (BHYT)
          </h3>
          <p style="font-size:13.5px;color:var(--muted);line-height:1.6;margin:0 0 16px;">
            Phòng khám áp dụng chính sách BHYT thông tuyến toàn quốc cho các dịch vụ khám chữa bệnh ngoại trú. Người bệnh chỉ cần xuất trình thẻ CCCD gắn chip (hoặc app VNeID) khi làm thủ tục đăng ký ban đầu.
          </p>
        </div>
        <div>
          <a class="btn btn-light" style="width:100%;box-sizing:border-box;" href="index.php#bhyt">Xem chính sách BHYT ➔</a>
        </div>
      </article>
    </div>
  </section>

  <!-- DANH SÁCH TƯ LIỆU BỔ SUNG TỪ HỆ THỐNG QUẢN TRỊ (NẾU CÓ) -->
  <?php if ($customerResources !== []): ?>
    <section class="section">
      <div class="section-header">
        <h2 class="section-title">Tài liệu và biểu mẫu bổ sung</h2>
        <p class="section-lead">Các tệp tài liệu chuyên đề được phòng khám cập nhật định kỳ.</p>
      </div>
      <div class="grid grid-3">
        <?php foreach ($customerResources as $resource): ?>
          <article class="card" style="margin-bottom:0;display:flex;flex-direction:column;justify-content:space-between;">
            <div>
              <h3 style="font-size:16px;font-weight:700;color:#0f2942;margin:0 0 8px;"><?= e($resource['title']) ?></h3>
              <?php if (!empty($resource['description'])): ?>
                <p style="font-size:13.5px;color:var(--muted);line-height:1.6;margin:0 0 14px;"><?= nl2br(e($resource['description'])) ?></p>
              <?php endif; ?>
            </div>
            <?php if (!empty($resource['resource_url'])): ?>
              <div>
                <a class="btn btn-light" style="width:100%;box-sizing:border-box;" href="<?= e($resource['resource_url']) ?>" target="_blank" rel="noopener">Xem chi tiết ↗</a>
              </div>
            <?php endif; ?>
          </article>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

  <!-- KHỐI HỖ TRỢ TRỰC TIẾP -->
  <section class="section card" style="background:#fff;border-radius:18px;text-align:center;padding:36px 20px;">
    <h3 style="font-size:20px;font-weight:700;color:#0f2942;margin:0 0 8px;">Bạn cần thêm hướng dẫn hoặc giải đáp riêng?</h3>
    <p style="font-size:14px;color:var(--muted);max-width:600px;margin:0 auto 20px;">
      Đội ngũ nhân viên y tế của Phòng khám đa khoa Phú Thái luôn sẵn sàng lắng nghe và hỗ trợ người bệnh qua đường dây nóng.
    </p>
    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
      <a class="btn" href="tel:02086289888">📞 Gọi hotline 0208 628 9888</a>
      <a class="btn btn-outline" href="support.php">Gửi yêu cầu hỗ trợ ➔</a>
    </div>
  </section>
</div>

<?php render_footer(); ?>
