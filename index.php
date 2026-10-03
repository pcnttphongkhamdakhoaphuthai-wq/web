<?php
declare(strict_types=1);

require_once 'config.php';

$clinic = site_settings([
    'clinic_name' => 'Phòng khám đa khoa Phú Thái',
    'clinic_intro' => 'Đồng hành chăm sóc sức khỏe toàn diện cho người bệnh với hệ thống tra cứu hồ sơ và kết quả khám tiện lợi, an toàn.',
    'clinic_mission' => 'Mang đến dịch vụ y tế chất lượng cao, tận tâm, tối ưu hóa thời gian chờ đợi và hỗ trợ người bệnh theo dõi sức khỏe liên tục.',
    'clinic_facility' => 'Trang thiết bị hiện đại: máy siêu âm màu 4D, hệ thống xét nghiệm tự động, máy điện tim kỹ thuật số và khu khám khang trang, sạch sẽ.',
    'clinic_services' => "Khám nội tổng quát\nSiêu âm tổng quát & tim mạch\nXét nghiệm huyết học, sinh hóa\nĐiện tim vi tính\nTư vấn và quản lý bệnh mạn tính",
    'clinic_support' => 'Đội ngũ nhân viên y tế sẵn sàng hướng dẫn người bệnh từ khâu tiếp đón, làm thủ tục đến tra cứu kết quả trực tuyến.',
    'support_hotline' => '0208 628 9888',
    'support_email' => 'pcnttphongkhamdakhoaphuthai@gmail.com',
    'clinic_address' => 'Xóm Hoà Bình 2, xã Phú Bình, tỉnh Thái Nguyên',
    'google_maps_url' => 'https://maps.google.com/?q=' . urlencode('Phòng khám đa khoa Phú Thái Xóm Hoà Bình 2, xã Phú Bình, tỉnh Thái Nguyên'),
    'chatbot_intro' => 'Trợ lý AI hỗ trợ giải đáp nhanh các câu hỏi thường gặp về thủ tục khám, bảng giá dịch vụ và chính sách BHYT.',
]);

$cacheFile = APP_RUNTIME_ROOT . DIRECTORY_SEPARATOR . 'homepage_data.json';
$homeData = null;
if (is_file($cacheFile) && (time() - (int) filemtime($cacheFile) < 180)) {
    $raw = @file_get_contents($cacheFile);
    if ($raw !== false && $raw !== '') {
        $homeData = json_decode($raw, true);
    }
}

if (!is_array($homeData)) {
    $doctorsRes = $conn->query('SELECT id, name, title, department, specialties, bio, photo_path FROM doctors ORDER BY id ASC');
    $doctorsList = $doctorsRes ? $doctorsRes->fetch_all(MYSQLI_ASSOC) : [];
    $quickRepliesList = get_active_quick_replies(6);
    $newsPostsList = get_recent_news_posts(3, true);
    $customerResourcesList = get_customer_resources(3, true);

    $homeData = [
        'doctors' => $doctorsList,
        'quickReplies' => $quickRepliesList,
        'newsPosts' => $newsPostsList,
        'customerResources' => $customerResourcesList,
    ];

    @file_put_contents($cacheFile, json_encode($homeData, JSON_UNESCAPED_UNICODE));
}

$doctors = $homeData['doctors'] ?? [];
$quickReplies = $homeData['quickReplies'] ?? [];
$newsPosts = $homeData['newsPosts'] ?? [];
$customerResources = $homeData['customerResources'] ?? [];
$appointmentsEnabled = appointments_enabled();

render_header('Phòng khám đa khoa Phú Thái - Trang chủ', 'home');
render_hero('Phòng khám đa khoa Phú Thái', $clinic['clinic_intro']);
?>

<div class="wrap">
  <!-- KHU VỰC DỊCH VỤ TRỰC TUYẾN GỘP NHẤT QUÁN -->
  <section class="section" id="services">
    <div class="section-header">
      <h2 class="section-title">Dịch vụ trực tuyến dành cho người bệnh</h2>
      <p class="section-lead">Hệ thống tiện ích số giúp người bệnh chủ động theo dõi sức khỏe và kết quả điều trị mọi lúc, mọi nơi.</p>
    </div>

    <div class="grid grid-4">
      <!-- Dịch vụ 1: Tra cứu kết quả -->
      <article class="card" style="margin-bottom:0;display:flex;flex-direction:column;justify-content:space-between;">
        <div>
          <div style="width:48px;height:48px;border-radius:12px;background:#e0f2fe;color:#0284c7;display:flex;align-items:center;justify-content:center;font-size:22px;margin-bottom:14px;">
            📄
          </div>
          <h3 style="font-size:17px;font-weight:700;margin:0 0 8px;color:#0f2942;">Tra cứu kết quả khám</h3>
          <p style="font-size:14px;color:var(--muted);line-height:1.6;margin:0 0 16px;">
            Xem chi tiết kết quả chẩn đoán, xét nghiệm, đơn thuốc điện tử và tải tệp hồ sơ PDF lưu về máy.
          </p>
        </div>
        <div>
          <?php if (isset($_SESSION['user_id'])): ?>
            <a class="btn btn-light" style="width:100%;box-sizing:border-box;" href="dashboard.php#records">Xem kết quả ngay ➔</a>
          <?php else: ?>
            <a class="btn btn-light" style="width:100%;box-sizing:border-box;" href="login.php?redirect=records">Tra cứu kết quả ➔</a>
          <?php endif; ?>
        </div>
      </article>

      <!-- Dịch vụ 2: Đặt lịch khám -->
      <article class="card" style="margin-bottom:0;display:flex;flex-direction:column;justify-content:space-between;">
        <div>
          <div style="width:48px;height:48px;border-radius:12px;background:#fef3c7;color:#d97706;display:flex;align-items:center;justify-content:center;font-size:22px;margin-bottom:14px;">
            📅
          </div>
          <h3 style="font-size:17px;font-weight:700;margin:0 0 8px;color:#0f2942;">Đăng ký lịch khám</h3>
          <p style="font-size:14px;color:var(--muted);line-height:1.6;margin:0 0 16px;">
            <?php if ($appointmentsEnabled): ?>
              Chủ động chọn bác sĩ và thời gian khám phù hợp để được tiếp đón chu đáo không phải chờ đợi.
            <?php else: ?>
              Đặt lịch online đang bảo trì. Quý khách vui lòng gọi hotline <strong>0208 628 9888</strong> để được xếp lịch nhanh nhất.
            <?php endif; ?>
          </p>
        </div>
        <div>
          <?php if ($appointmentsEnabled): ?>
            <a class="btn btn-light" style="width:100%;box-sizing:border-box;" href="book_appointment.php">Đặt lịch khám ➔</a>
          <?php else: ?>
            <a class="btn btn-light" style="width:100%;box-sizing:border-box;" href="tel:02086289888">Gọi hotline đặt lịch 📞</a>
          <?php endif; ?>
        </div>
      </article>

      <!-- Dịch vụ 3: Hồ sơ sức khỏe cá nhân -->
      <article class="card" style="margin-bottom:0;display:flex;flex-direction:column;justify-content:space-between;">
        <div>
          <div style="width:48px;height:48px;border-radius:12px;background:#dcfce7;color:#16a34a;display:flex;align-items:center;justify-content:center;font-size:22px;margin-bottom:14px;">
            📁
          </div>
          <h3 style="font-size:17px;font-weight:700;margin:0 0 8px;color:#0f2942;">Hồ sơ bệnh án điện tử</h3>
          <p style="font-size:14px;color:var(--muted);line-height:1.6;margin:0 0 16px;">
            Lưu trữ lịch sử khám bệnh qua các đợt, hỗ trợ bác sĩ theo dõi diễn tiến sức khỏe khi tái khám.
          </p>
        </div>
        <div>
          <?php if (isset($_SESSION['user_id'])): ?>
            <a class="btn btn-light" style="width:100%;box-sizing:border-box;" href="dashboard.php#overview">Xem hồ sơ ➔</a>
          <?php else: ?>
            <a class="btn btn-light" style="width:100%;box-sizing:border-box;" href="login.php">Đăng nhập tài khoản ➔</a>
          <?php endif; ?>
        </div>
      </article>

      <!-- Dịch vụ 4: Hướng dẫn & Hỗ trợ công khai -->
      <article class="card" style="margin-bottom:0;display:flex;flex-direction:column;justify-content:space-between;">
        <div>
          <div style="width:48px;height:48px;border-radius:12px;background:#ede9fe;color:#7c3aed;display:flex;align-items:center;justify-content:center;font-size:22px;margin-bottom:14px;">
            💡
          </div>
          <h3 style="font-size:17px;font-weight:700;margin:0 0 8px;color:#0f2942;">Hướng dẫn & Trợ giúp</h3>
          <p style="font-size:14px;color:var(--muted);line-height:1.6;margin:0 0 16px;">
            Quy trình khám, cách tra cứu kết quả, xử lý sự cố tài khoản và thông tin liên hệ phòng khám.
          </p>
        </div>
        <div>
          <a class="btn btn-light" style="width:100%;box-sizing:border-box;" href="support.php">Xem hướng dẫn ➔</a>
        </div>
      </article>
    </div>
  </section>

  <!-- KHU VỰC HƯỚNG DẪN BẢO HIỂM Y TẾ (BHYT) THỰC TẾ -->
  <section class="section card" id="bhyt" style="background:linear-gradient(180deg, #f0fdf4 0%, #ffffff 100%);border-color:#bbf7d0;">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">
      <span style="font-size:24px;">🏥</span>
      <div>
        <h2 style="font-size:20px;font-weight:700;color:#166534;margin:0;">Hướng dẫn khám chữa bệnh Bảo hiểm Y tế (BHYT)</h2>
        <p style="font-size:13.5px;color:#15803d;margin:2px 0 0;">Quyền lợi và thủ tục khám chữa bệnh BHYT tại Phòng khám đa khoa Phú Thái</p>
      </div>
    </div>
    <div class="grid grid-3" style="margin-top:16px;">
      <div style="padding:14px;background:#fff;border-radius:12px;border:1px solid #dcfce7;">
        <h4 style="margin:0 0 6px;color:#14532d;font-size:14.5px;">1. Giấy tờ cần chuẩn bị</h4>
        <p style="font-size:13px;color:#475569;margin:0;line-height:1.6;">Xuất trình Căn cước công dân gắn chip hoặc ứng dụng VNeID (đã tích hợp thẻ BHYT), hoặc thẻ BHYT giấy còn hạn sử dụng kèm giấy tờ tùy thân có ảnh.</p>
      </div>
      <div style="padding:14px;background:#fff;border-radius:12px;border:1px solid #dcfce7;">
        <h4 style="margin:0 0 6px;color:#14532d;font-size:14.5px;">2. Quyền lợi hưởng BHYT</h4>
        <p style="font-size:13px;color:#475569;margin:0;line-height:1.6;">Người bệnh được thanh toán đầy đủ các danh mục khám bệnh, xét nghiệm, siêu âm và thuốc điều trị thuộc phạm vi chi trả theo quy định hiện hành của Bộ Y tế.</p>
      </div>
      <div style="padding:14px;background:#fff;border-radius:12px;border:1px solid #dcfce7;">
        <h4 style="margin:0 0 6px;color:#14532d;font-size:14.5px;">3. Hỗ trợ chuyển tuyến</h4>
        <p style="font-size:13px;color:#475569;margin:0;line-height:1.6;">Trong trường hợp bệnh lý cần điều trị chuyên sâu, phòng khám thực hiện thủ tục chuyển tuyến lên bệnh viện tuyến trên nhanh chóng, đảm bảo quyền lợi liên tục.</p>
      </div>
    </div>
  </section>

  <!-- ĐỘI NGŨ BÁC SĨ (TÁCH VAI TRÒ ADMIN KHỎI CHUYÊN KHOA) -->
  <section class="section" id="doctors">
    <div class="section-header">
      <h2 class="section-title">Đội ngũ y bác sĩ phụ trách</h2>
      <p class="section-lead">Các bác sĩ giàu kinh nghiệm, chuyên môn vững vàng, luôn tận tâm vì sức khỏe người bệnh.</p>
    </div>
    <div class="grid grid-2">
      <?php foreach ($doctors as $doctor): 
        $dept = trim((string) ($doctor['department'] ?? ''));
        // Sửa triệt để lỗi "Khoa: Administrator" (Mục 9)
        if ($dept === '' || strcasecmp($dept, 'Administrator') === 0 || strcasecmp($dept, 'Admin') === 0) {
            $dept = 'Khoa Khám bệnh & Nội tổng quát';
        }
      ?>
        <article class="card" style="margin-bottom:0;display:flex;flex-direction:column;justify-content:space-between;">
          <div style="display:flex;gap:18px;align-items:flex-start;">
            <img class="doctor-photo" 
                 src="<?= e(doctor_photo_url($doctor['photo_path'] ?? null)) ?>" 
                 alt="<?= e($doctor['name']) ?>" 
                 loading="lazy" 
                 width="92" 
                 height="92" 
                 style="width:92px;height:92px;border-radius:16px;object-fit:cover;border:1.5px solid var(--border);flex-shrink:0;">
            <div style="flex:1;">
              <h3 style="font-size:18px;font-weight:700;color:#0f2942;margin:0 0 4px;"><?= e($doctor['name']) ?></h3>
              <?php if (!empty($doctor['title'])): ?>
                <div style="color:var(--primary);font-size:13.5px;font-weight:600;margin-bottom:6px;"><?= e($doctor['title']) ?></div>
              <?php endif; ?>
              <div style="display:inline-block;padding:4px 10px;background:#f0f7fb;color:#0284c7;border-radius:6px;font-size:12.5px;font-weight:600;">
                Chuyên khoa: <?= e($dept) ?>
              </div>
            </div>
          </div>
          <?php if (!empty($doctor['specialties'])): ?>
            <div style="margin-top:12px;font-size:13px;color:#475569;">
              <strong>Lĩnh vực chuyên môn:</strong> <?= e($doctor['specialties']) ?>
            </div>
          <?php endif; ?>
          <p style="margin:10px 0 0;font-size:13.5px;color:var(--muted);line-height:1.6;">
            <?= nl2br(e($doctor['bio'] !== '' ? $doctor['bio'] : 'Bác sĩ phụ trách thăm khám, tư vấn phác đồ điều trị và theo dõi sức khỏe cho người bệnh.')) ?>
          </p>
        </article>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- TRỢ LÝ TƯ VẤN THÔNG TIN TỰ ĐỘNG (GẮN NHÃN MINH HỌA RÕ RÀNG) -->
  <section class="section" id="chatbot" style="padding: 0;">
    <div style="
      background: linear-gradient(135deg, #0f3d61 0%, #0077b6 60%, #0096c7 100%);
      border-radius: 20px;
      padding: 40px 36px;
      position: relative;
      overflow: hidden;
      color: #fff;
    ">
      <div style="display:flex;align-items:center;gap:36px;flex-wrap:wrap;position:relative;z-index:1;">
        <div style="flex:1;min-width:280px;">
          <div style="display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,0.18);border:1px solid rgba(255,255,255,0.3);border-radius:20px;padding:5px 14px;margin-bottom:14px;">
            <span style="font-size:12.5px;font-weight:600;color:#e0f2fe;">🤖 Trợ lý giải đáp tự động (Ví dụ minh họa & Tư vấn thông tin)</span>
          </div>

          <h2 style="font-size:clamp(22px, 3vw, 30px);font-weight:800;margin:0 0 12px;line-height:1.3;">
            Hỏi đáp thông tin y tế & Thủ tục phòng khám
          </h2>
          <p style="color:rgba(255,255,255,0.9);font-size:14.5px;line-height:1.65;margin:0 0 20px;max-width:560px;">
            Tìm hiểu nhanh về bảng giá khám, hướng dẫn chuẩn bị trước khi xét nghiệm máu, quy trình chuyển tuyến BHYT và các câu hỏi thường gặp.
          </p>

          <div style="display:flex;flex-direction:column;gap:8px;margin-bottom:24px;">
            <div style="display:flex;align-items:center;gap:10px;font-size:13.5px;color:#f0f9ff;">
              <span>✓</span> <span>Tra cứu thủ tục hành chính và bảo hiểm y tế</span>
            </div>
            <div style="display:flex;align-items:center;gap:10px;font-size:13.5px;color:#f0f9ff;">
              <span>✓</span> <span>Tham khảo thời gian làm việc và bảng giá các gói khám</span>
            </div>
            <div style="display:flex;align-items:center;gap:10px;font-size:13.5px;color:#f0f9ff;">
              <span>✓</span> <span>Hệ thống phản hồi tức thì, hỗ trợ 24/7</span>
            </div>
          </div>

          <div style="display:flex;gap:12px;flex-wrap:wrap;">
            <a href="support.php" style="background:#fff;color:#0077b6;padding:11px 22px;border-radius:10px;font-weight:700;font-size:14px;display:inline-flex;align-items:center;gap:6px;box-shadow:0 4px 14px rgba(0,0,0,0.15);">
              Xem hỏi đáp & Hướng dẫn ➔
            </a>
            <a href="tel:02086289888" style="background:rgba(255,255,255,0.15);color:#fff;padding:11px 20px;border-radius:10px;font-weight:600;font-size:14px;border:1px solid rgba(255,255,255,0.3);display:inline-flex;align-items:center;gap:6px;">
              📞 Hotline 0208 628 9888
            </a>
          </div>
        </div>

        <!-- Khung chat minh họa -->
        <div style="flex:0 0 auto;width:min(320px, 100%);">
          <div style="background:rgba(255,255,255,0.12);border:1px solid rgba(255,255,255,0.22);border-radius:16px;padding:18px;backdrop-filter:blur(10px);">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;padding-bottom:10px;border-bottom:1px solid rgba(255,255,255,0.15);">
              <div style="width:32px;height:32px;background:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#0077b6;font-size:16px;">
                💬
              </div>
              <div>
                <div style="color:#fff;font-weight:700;font-size:13px;">Trợ lý tư vấn Phú Thái</div>
                <div style="color:#e0f2fe;font-size:11px;">Minh họa câu hỏi thường gặp</div>
              </div>
            </div>
            
            <div style="display:flex;flex-direction:column;gap:10px;font-size:12.5px;">
              <div style="background:rgba(255,255,255,0.2);padding:9px 12px;border-radius:12px 12px 12px 4px;color:#fff;">
                Thời gian làm việc của phòng khám như thế nào?
              </div>
              <div style="background:#fff;color:#1e293b;padding:9px 12px;border-radius:12px 12px 4px 12px;line-height:1.45;">
                Phòng khám làm việc từ <strong>7:00 đến 17:00</strong> tất cả các ngày trong tuần (kể cả Thứ 7 và Chủ Nhật).
              </div>
              <div style="background:rgba(255,255,255,0.2);padding:9px 12px;border-radius:12px 12px 12px 4px;color:#fff;">
                Tôi có cần nhịn ăn trước khi xét nghiệm máu không?
              </div>
              <div style="background:#fff;color:#1e293b;padding:9px 12px;border-radius:12px 12px 4px 12px;line-height:1.45;">
                Quý khách nên nhịn ăn từ 6-8 tiếng trước khi làm xét nghiệm đường huyết, mỡ máu hoặc chức năng gan.
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- HƯỚNG DẪN NGƯỜI BỆNH -->
  <?php if ($customerResources !== []): ?>
    <section class="section" id="resources">
      <div class="section-header" style="display:flex;justify-content:space-between;align-items:baseline;flex-wrap:wrap;gap:12px;">
        <div>
          <h2 class="section-title">Hướng dẫn dành cho người bệnh</h2>
          <p class="section-lead">Tài liệu và chỉ dẫn hữu ích giúp người bệnh chuẩn bị tốt nhất trước khi đến khám.</p>
        </div>
        <a class="btn btn-outline" href="resources.php">Xem tất cả hướng dẫn ➔</a>
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

  <!-- TIN TỨC & HOẠT ĐỘNG PHÒNG KHÁM -->
  <?php if ($newsPosts !== []): ?>
    <section class="section" id="news">
      <div class="section-header" style="display:flex;justify-content:space-between;align-items:baseline;flex-wrap:wrap;gap:12px;">
        <div>
          <h2 class="section-title">Tin tức y tế & Hoạt động</h2>
          <p class="section-lead">Thông tin sức khỏe định kỳ và thông báo từ Phòng khám đa khoa Phú Thái.</p>
        </div>
        <a class="btn btn-outline" href="news.php">Xem tất cả tin ➔</a>
      </div>
      <div class="grid grid-3">
        <?php foreach ($newsPosts as $post): ?>
          <article class="card" style="margin-bottom:0;">
            <?php render_news_media($post); ?>
            <div style="font-size:12px;color:var(--muted);margin-bottom:6px;"><?= e(date('d/m/Y', strtotime((string) $post['created_at']))) ?></div>
            <h3 style="font-size:16px;font-weight:700;color:#0f2942;margin:0 0 8px;"><?= e($post['title']) ?></h3>
            <p style="font-size:13.5px;color:var(--muted);line-height:1.6;margin:0;"><?= nl2br(e((string) ($post['excerpt'] ?: $post['body']))) ?></p>
          </article>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

  <!-- THÔNG TIN LIÊN HỆ & BẢN ĐỒ CHỈ ĐƯỜNG -->
  <section class="section card" style="background:#fff;border-radius:18px;">
    <div class="grid grid-2" style="align-items:center;">
      <div>
        <h2 style="font-size:22px;font-weight:800;color:#0f2942;margin:0 0 10px;">Liên hệ Phòng khám đa khoa Phú Thái</h2>
        <p style="font-size:14.5px;color:var(--muted);margin:0 0 20px;line-height:1.6;">
          Phòng khám hân hạnh được đồng hành và chăm sóc sức khỏe của bạn và gia đình. Hãy liên hệ với chúng tôi bất cứ khi nào bạn cần hỗ trợ y tế.
        </p>
        
        <div style="display:grid;gap:12px;font-size:14px;color:#334155;">
          <div>📍 <strong>Địa chỉ:</strong> <?= e($clinic['clinic_address']) ?></div>
          <div>📞 <strong>Hotline tư vấn:</strong> <a href="tel:02086289888" style="color:var(--primary);font-weight:700;">0208 628 9888</a></div>
          <div>🚑 <strong>Cấp cứu & CSKH:</strong> <a href="tel:0963485651" style="color:var(--primary);font-weight:700;">0963 485 651</a></div>
          <div>✉️ <strong>Email:</strong> <a href="mailto:<?= e($clinic['support_email']) ?>" style="color:var(--primary);"><?= e($clinic['support_email']) ?></a></div>
          <div>⏰ <strong>Giờ làm việc:</strong> 7:00 – 17:00 (Tất cả các ngày trong tuần)</div>
        </div>

        <div style="margin-top:24px;display:flex;gap:12px;flex-wrap:wrap;">
          <a class="btn" href="<?= e($clinic['google_maps_url']) ?>" target="_blank" rel="noopener">
            📍 Chỉ đường trên Google Maps ↗
          </a>
          <a class="btn btn-outline" href="support.php">
            Gửi yêu cầu hỗ trợ ➔
          </a>
        </div>
      </div>

      <div style="background:var(--soft);border-radius:14px;padding:24px;border:1px solid var(--border);text-align:center;">
        <div style="font-size:36px;margin-bottom:10px;">🏥</div>
        <h3 style="font-size:18px;font-weight:700;color:#0f2942;margin:0 0 8px;">Cổng dịch vụ người bệnh trực tuyến</h3>
        <p style="font-size:13.5px;color:var(--muted);line-height:1.6;margin:0 0 18px;">
          Đăng nhập ngay để xem hồ sơ bệnh án và nhận thông báo kết quả khám mới nhất một cách bảo mật và an toàn.
        </p>
        <?php if (isset($_SESSION['user_id'])): ?>
          <a class="btn" style="width:100%;box-sizing:border-box;" href="dashboard.php">Đến khu vực người bệnh ➔</a>
        <?php else: ?>
          <div style="display:flex;gap:10px;justify-content:center;">
            <a class="btn" href="login.php" style="flex:1;">Đăng nhập</a>
            <a class="btn btn-outline" href="register.php" style="flex:1;">Đăng ký</a>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>
</div>

<?php render_footer(); ?>
