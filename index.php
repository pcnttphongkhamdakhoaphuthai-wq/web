<?php
declare(strict_types=1);

require_once 'config.php';

$clinic = [
    'clinic_name' => site_setting('clinic_name', 'Phòng khám đa khoa Phú Thái'),
    'clinic_intro' => site_setting(
        'clinic_intro',
        'Phòng khám đa khoa Phú Thái cung cấp dịch vụ thăm khám, chẩn đoán hình ảnh và xét nghiệm chất lượng cao. Cổng thông tin người bệnh hỗ trợ tra cứu kết quả khám bệnh, lịch sử xét nghiệm và đơn thuốc trực tuyến an toàn, bảo mật.'
    ),
    'clinic_address' => site_setting('clinic_address', 'Xóm Hoà Bình 2, xã Phú Bình, tỉnh Thái Nguyên'),
    'support_hotline' => site_setting('support_hotline', '0208 628 9888'),
    'support_email' => site_setting('support_email', 'pkdkphuthai@gmail.com'),
    'google_maps_url' => site_setting('google_maps_url', 'https://maps.app.goo.gl/yM4YQ'),
];

// Cơ chế Cache nhẹ 180s cho dữ liệu trang chủ
$homeData = null;
$cacheHomeFile = APP_RUNTIME_ROOT . '/homepage_data.json';
if (is_file($cacheHomeFile) && (time() - filemtime($cacheHomeFile) < 180)) {
    $raw = @file_get_contents($cacheHomeFile);
    if ($raw !== false) {
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
    @file_put_contents($cacheHomeFile, json_encode($homeData, JSON_UNESCAPED_UNICODE));
}

$doctors = $homeData['doctors'] ?? [];
$quickReplies = $homeData['quickReplies'] ?? [];
$newsPosts = $homeData['newsPosts'] ?? [];
$customerResources = $homeData['customerResources'] ?? [];
$appointmentsEnabled = appointments_enabled();

render_header('Phòng khám đa khoa Phú Thái - Cổng thông tin & dịch vụ người bệnh', 'home');
render_hero('Phòng khám đa khoa Phú Thái', $clinic['clinic_intro']);
?>

<div class="wrap">
  <!-- KHU VỰC DỊCH VỤ TRỰC TUYẾN DÀNH CHO NGƯỜI BỆNH -->
  <section class="section" id="services">
    <div class="section-header">
      <span class="section-kicker">TIỆN ÍCH SỐ NGƯỜI BỆNH</span>
      <h2 class="section-title">Dịch vụ trực tuyến tại Phòng khám</h2>
      <p class="section-lead">Chủ động tra cứu kết quả khám, theo dõi hồ sơ bệnh án và nhận hướng dẫn y tế thuận tiện ngay trên điện thoại hoặc máy tính.</p>
    </div>

    <div class="grid grid-4">
      <!-- Dịch vụ 1: Tra cứu kết quả -->
      <article class="service-card">
        <div class="service-icon-box">
          <svg class="icon" aria-hidden="true"><use href="#i-file"/></svg>
        </div>
        <h3 class="service-title">Tra cứu kết quả khám</h3>
        <p class="service-desc">
          Xem và tải kết quả chẩn đoán hình ảnh, siêu âm, xét nghiệm máu và đơn thuốc điện tử an toàn, bảo mật.
        </p>
        <div class="service-action">
          <?php if (isset($_SESSION['user_id'])): ?>
            <a class="btn btn-primary btn-block" href="dashboard.php#records">Xem kết quả ngay <svg class="icon" style="width:16px;height:16px;" aria-hidden="true"><use href="#i-arrow"/></svg></a>
          <?php else: ?>
            <a class="btn btn-primary btn-block" href="login.php?redirect=records">Tra cứu kết quả <svg class="icon" style="width:16px;height:16px;" aria-hidden="true"><use href="#i-arrow"/></svg></a>
          <?php endif; ?>
        </div>
      </article>

      <!-- Dịch vụ 2: Đăng ký lịch khám -->
      <article class="service-card">
        <div class="service-icon-box">
          <svg class="icon" aria-hidden="true"><use href="#i-book"/></svg>
        </div>
        <h3 class="service-title">Đăng ký lịch khám</h3>
        <p class="service-desc">
          <?php if ($appointmentsEnabled): ?>
            Chủ động chọn bác sĩ và thời gian khám thuận tiện, giảm thiểu thời gian chờ đợi tại quầy tiếp đón.
          <?php else: ?>
            Hệ thống đặt lịch trực tuyến đang bảo trì. Quý khách vui lòng gọi tổng đài tiếp đón để được hỗ trợ nhanh nhất.
          <?php endif; ?>
        </p>
        <div class="service-action">
          <?php if ($appointmentsEnabled): ?>
            <a class="btn btn-secondary btn-block" href="book_appointment.php">Đặt lịch hẹn <svg class="icon" style="width:16px;height:16px;" aria-hidden="true"><use href="#i-arrow"/></svg></a>
          <?php else: ?>
            <a class="btn btn-secondary btn-block" href="tel:02086289888"><svg class="icon" style="width:16px;height:16px;" aria-hidden="true"><use href="#i-phone"/></svg> 0208 628 9888</a>
          <?php endif; ?>
        </div>
      </article>

      <!-- Dịch vụ 3: Hồ sơ sức khỏe cá nhân -->
      <article class="service-card">
        <div class="service-icon-box">
          <svg class="icon" aria-hidden="true"><use href="#i-folder"/></svg>
        </div>
        <h3 class="service-title">Hồ sơ bệnh án điện tử</h3>
        <p class="service-desc">
          Lưu trữ lịch sử khám bệnh qua từng đợt, giúp bác sĩ dễ dàng theo dõi diễn tiến sức khỏe trong các lần tái khám.
        </p>
        <div class="service-action">
          <?php if (isset($_SESSION['user_id'])): ?>
            <a class="btn btn-outline btn-block" href="dashboard.php">Mở hồ sơ của tôi</a>
          <?php else: ?>
            <a class="btn btn-outline btn-block" href="login.php">Đăng nhập tài khoản</a>
          <?php endif; ?>
        </div>
      </article>

      <!-- Dịch vụ 4: Hướng dẫn & Hỗ trợ y tế -->
      <article class="service-card">
        <div class="service-icon-box">
          <svg class="icon" aria-hidden="true"><use href="#i-help"/></svg>
        </div>
        <h3 class="service-title">Hướng dẫn & Trợ giúp</h3>
        <p class="service-desc">
          Xem quy trình khám bệnh, chuẩn bị xét nghiệm, cấp lại mật khẩu và liên hệ bộ phận hỗ trợ khách hàng.
        </p>
        <div class="service-action">
          <a class="btn btn-light btn-block" href="support.php">Xem hướng dẫn chi tiết</a>
        </div>
      </article>
    </div>
  </section>

  <!-- KHU VỰC HƯỚNG DẪN BẢO HIỂM Y TẾ (BHYT) THỰC TẾ -->
  <section class="section" id="bhyt" style="padding-top: 12px;">
    <div class="section-header">
      <span class="section-kicker">CHÍNH SÁCH BHYT</span>
      <h2 class="section-title">Khám chữa bệnh Bảo hiểm Y tế (BHYT)</h2>
      <p class="section-lead">Phòng khám đa khoa Phú Thái tiếp nhận và thực hiện khám chữa bệnh BHYT đúng tuyến và thông tuyến theo quy định của Bảo hiểm Xã hội Việt Nam.</p>
    </div>

    <div class="grid grid-3">
      <div class="bhyt-box">
        <div class="bhyt-box-header">
          <div class="bhyt-box-icon"><svg class="icon" aria-hidden="true"><use href="#i-file"/></svg></div>
          <h3 class="bhyt-box-title">1. Giấy tờ cần chuẩn bị</h3>
        </div>
        <div class="bhyt-box-content">
          <ul>
            <li>Căn cước công dân gắn chip hoặc ứng dụng VNeID (đã tích hợp thẻ BHYT).</li>
            <li>Thẻ BHYT giấy còn thời hạn kèm giấy tờ tùy thân có dán ảnh.</li>
            <li>Giấy chuyển tuyến khám chữa bệnh BHYT (nếu thuộc diện chuyển tuyến).</li>
          </ul>
        </div>
      </div>

      <div class="bhyt-box">
        <div class="bhyt-box-header">
          <div class="bhyt-box-icon"><svg class="icon" aria-hidden="true"><use href="#i-check"/></svg></div>
          <h3 class="bhyt-box-title">2. Quyền lợi chi trả BHYT</h3>
        </div>
        <div class="bhyt-box-content">
          <ul>
            <li>Người bệnh được hưởng đầy đủ quyền lợi BHYT đối với công khám, xét nghiệm, siêu âm, nội soi, chụp X-quang.</li>
            <li>Danh mục thuốc điều trị được bảo hiểm chi trả theo đúng quy định hiện hành của Bộ Y tế.</li>
            <li>Thủ tục nhanh gọn, minh bạch chi phí công khai tại quầy viện phí.</li>
          </ul>
        </div>
      </div>

      <div class="bhyt-box">
        <div class="bhyt-box-header">
          <div class="bhyt-box-icon"><svg class="icon" aria-hidden="true"><use href="#i-arrow"/></svg></div>
          <h3 class="bhyt-box-title">3. Hỗ trợ chuyển tuyến</h3>
        </div>
        <div class="bhyt-box-content">
          <ul>
            <li>Đối với các ca bệnh lý cần điều trị chuyên sâu, phòng khám hỗ trợ làm thủ tục chuyển viện tuyến trên kịp thời.</li>
            <li>Bảo toàn tối đa quyền lợi bảo hiểm liên tục của người bệnh.</li>
            <li>Có đội ngũ nhân viên hướng dẫn chi tiết từng bước hồ sơ chuyển tuyến.</li>
          </ul>
        </div>
      </div>
    </div>
  </section>

  <!-- ĐỘI NGŨ Y BÁC SĨ (TÁCH VAI TRÒ ADMIN KHỎI CHUYÊN KHOA) -->
  <section class="section" id="doctors">
    <div class="section-header">
      <span class="section-kicker">ĐỘI NGŨ CHUYÊN MÔN</span>
      <h2 class="section-title">Đội ngũ y bác sĩ phụ trách</h2>
      <p class="section-lead">Các bác sĩ giàu kinh nghiệm, chuyên môn sâu, luôn tận tâm đồng hành vì sức khỏe người bệnh.</p>
    </div>

    <div class="grid grid-2">
      <?php foreach ($doctors as $doctor): 
        $dept = trim((string) ($doctor['department'] ?? ''));
        if ($dept === '' || strcasecmp($dept, 'Administrator') === 0 || strcasecmp($dept, 'Admin') === 0) {
            $dept = 'Khoa Khám bệnh & Nội tổng quát';
        }
      ?>
        <article class="doctor-card" style="align-items: stretch; text-align: left;">
          <div style="display: flex; gap: 20px; align-items: flex-start;">
            <div class="doctor-avatar-wrap" style="flex: none; width: 92px; height: 92px; margin-bottom: 0;">
              <img class="doctor-avatar" 
                   src="<?= e(doctor_photo_url($doctor['photo_path'] ?? null)) ?>" 
                   alt="<?= e($doctor['name']) ?>" 
                   loading="lazy" 
                   width="92" 
                   height="92">
            </div>
            <div style="flex: 1; min-width: 0;">
              <h3 class="doctor-name" style="margin-bottom: 4px;"><?= e($doctor['name']) ?></h3>
              <?php if (!empty($doctor['title'])): ?>
                <div style="color: var(--blue); font-size: 13.5px; font-weight: 600; margin-bottom: 6px;"><?= e($doctor['title']) ?></div>
              <?php endif; ?>
              <span class="doctor-dept">Chuyên khoa: <?= e($dept) ?></span>
            </div>
          </div>
          <?php if (!empty($doctor['specialties'])): ?>
            <div style="margin-top: 14px; font-size: 13.5px; color: var(--text);">
              <strong>Lĩnh vực chuyên môn:</strong> <?= e($doctor['specialties']) ?>
            </div>
          <?php endif; ?>
          <p class="doctor-schedule" style="margin-top: 8px;">
            <?= nl2br(e($doctor['bio'] !== '' ? $doctor['bio'] : 'Bác sĩ phụ trách thăm khám, tư vấn phác đồ điều trị và theo dõi sức khỏe cho người bệnh.')) ?>
          </p>
        </article>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- TRỢ LÝ HỎI ĐÁP Y TẾ & THÔNG TIN TIỆN ÍCH -->
  <section class="section" id="chatbot" style="padding: 0;">
    <div style="
      background: linear-gradient(115deg, #072e56 0%, #005fa0 100%);
      border-radius: 20px;
      padding: 40px 36px;
      color: #fff;
      box-shadow: 0 12px 30px rgba(0, 95, 160, 0.16);
    ">
      <div style="display:flex;align-items:center;gap:36px;flex-wrap:wrap;">
        <div style="flex:1;min-width:280px;">
          <div style="display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.25);border-radius:20px;padding:6px 14px;margin-bottom:14px;">
            <svg class="icon" style="width:16px;height:16px;color:#e7f5ff;" aria-hidden="true"><use href="#i-chat"/></svg>
            <span style="font-size:13px;font-weight:600;color:#e7f5ff;">Hỏi đáp y tế & Hướng dẫn khám</span>
          </div>

          <h2 style="font-size:clamp(22px, 3vw, 32px);font-weight:700;margin:0 0 12px;line-height:1.25;">
            Thông tin y tế & Thủ tục phòng khám
          </h2>
          <p style="color:rgba(255,255,255,0.9);font-size:15px;line-height:1.6;margin:0 0 24px;max-width:560px;">
            Tìm hiểu nhanh về chuẩn bị trước khi xét nghiệm máu, quy trình thanh toán BHYT và thời gian làm việc của các chuyên khoa tại Phòng khám Phú Thái.
          </p>
          
          <div style="display:flex;gap:12px;flex-wrap:wrap;">
            <a href="support.php" class="btn" style="background:#ffffff;color:#005fa0 !important;font-weight:700;">Xem trung tâm trợ giúp</a>
            <a href="tel:02086289888" class="btn" style="background:rgba(255,255,255,0.2);color:#ffffff !important;border:1px solid rgba(255,255,255,0.4);"><svg class="icon" style="width:16px;height:16px;" aria-hidden="true"><use href="#i-phone"/></svg> Gọi tư vấn: 0208 628 9888</a>
          </div>
        </div>

        <div style="flex:1;min-width:280px;max-width:440px;">
          <div style="background:rgba(255,255,255,0.12);border:1px solid rgba(255,255,255,0.2);border-radius:16px;padding:20px;">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;">
              <span style="width:36px;height:36px;border-radius:50%;background:#ffffff;display:grid;place-items:center;color:#005fa0;">
                <svg class="icon" style="width:20px;height:20px;" aria-hidden="true"><use href="#i-chat"/></svg>
              </span>
              <div>
                <div style="color:#fff;font-weight:700;font-size:14px;">Câu hỏi thường gặp</div>
                <div style="color:#e0f2fe;font-size:12px;">Giải đáp nhanh cho người bệnh</div>
              </div>
            </div>
            
            <div style="display:flex;flex-direction:column;gap:10px;font-size:13px;">
              <div style="background:rgba(255,255,255,0.18);padding:10px 14px;border-radius:12px 12px 12px 4px;color:#fff;">
                Thời gian làm việc của phòng khám như thế nào?
              </div>
              <div style="background:#ffffff;color:#082d56;padding:10px 14px;border-radius:12px 12px 4px 12px;line-height:1.5;">
                Phòng khám tiếp nhận khám bệnh từ <strong>07:00 đến 17:30</strong> tất cả các ngày trong tuần (kể cả Thứ 7 và Chủ Nhật).
              </div>
              <div style="background:rgba(255,255,255,0.18);padding:10px 14px;border-radius:12px 12px 12px 4px;color:#fff;">
                Có cần nhịn ăn trước khi làm xét nghiệm máu không?
              </div>
              <div style="background:#ffffff;color:#082d56;padding:10px 14px;border-radius:12px 12px 4px 12px;line-height:1.5;">
                Quý người bệnh nên nhịn ăn từ 6 - 8 tiếng trước khi làm xét nghiệm đường huyết, mỡ máu hoặc chức năng gan thận.
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
          <span class="section-kicker">CẨM NANG Y TẾ</span>
          <h2 class="section-title">Hướng dẫn dành cho người bệnh</h2>
          <p class="section-lead">Tài liệu và chỉ dẫn hữu ích giúp người bệnh chuẩn bị tốt nhất trước khi đến thăm khám.</p>
        </div>
        <a class="btn btn-outline" href="resources.php">Xem tất cả hướng dẫn ➔</a>
      </div>
      <div class="grid grid-3">
        <?php foreach ($customerResources as $resource): ?>
          <article class="service-card" style="justify-content: space-between;">
            <div>
              <div class="service-icon-box" style="margin-bottom: 14px;">
                <svg class="icon" aria-hidden="true"><use href="#i-book"/></svg>
              </div>
              <h3 class="service-title" style="font-size: 17px;"><?= e($resource['title']) ?></h3>
              <?php if (!empty($resource['description'])): ?>
                <p class="service-desc"><?= nl2br(e($resource['description'])) ?></p>
              <?php endif; ?>
            </div>
            <?php if (!empty($resource['resource_url'])): ?>
              <div class="service-action">
                <a class="btn btn-light btn-block" href="<?= e($resource['resource_url']) ?>" target="_blank" rel="noopener">Xem chi tiết ↗</a>
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
          <span class="section-kicker">TIN TỨC & THÔNG BÁO</span>
          <h2 class="section-title">Tin tức y tế & Hoạt động</h2>
          <p class="section-lead">Cập nhật thông tin sức khỏe định kỳ và hoạt động cộng đồng từ Phòng khám đa khoa Phú Thái.</p>
        </div>
        <a class="btn btn-outline" href="news.php">Xem tất cả tin tức ➔</a>
      </div>
      <div class="grid grid-3">
        <?php foreach ($newsPosts as $post): ?>
          <article class="service-card" style="padding: 20px;">
            <?php render_news_media($post); ?>
            <div style="font-size:12px;color:var(--muted);margin: 10px 0 6px;"><?= e(date('d/m/Y', strtotime((string) $post['created_at']))) ?></div>
            <h3 class="service-title" style="font-size: 17px; margin-bottom: 8px;"><?= e($post['title']) ?></h3>
            <p class="service-desc" style="font-size: 14px;"><?= nl2br(e((string) ($post['excerpt'] ?: $post['body']))) ?></p>
          </article>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

  <!-- THÔNG TIN LIÊN HỆ & CHỈ ĐƯỜNG -->
  <section class="section" style="padding-top: 12px; margin-bottom: 36px;">
    <div style="background:#ffffff;border:1px solid var(--line);border-radius:20px;padding:36px;box-shadow:0 6px 20px rgba(8,45,86,0.04);">
      <div class="grid grid-2" style="align-items:center;">
        <div>
          <span class="section-kicker">LIÊN HỆ PHÒNG KHÁM</span>
          <h2 style="font-size:26px;font-weight:700;color:var(--ink);margin:0 0 12px;">Phòng khám đa khoa Phú Thái</h2>
          <p style="font-size:15px;color:var(--muted);margin:0 0 22px;line-height:1.6;">
            Phòng khám luôn sẵn lòng lắng nghe và hỗ trợ quý người bệnh. Quý vị có thể liên hệ với chúng tôi bất cứ lúc nào qua các kênh dưới đây:
          </p>
          
          <div style="display:grid;gap:14px;font-size:15px;color:var(--ink);">
            <div style="display:flex;align-items:center;gap:10px;">
              <svg class="icon" style="color:var(--blue);width:20px;height:20px;" aria-hidden="true"><use href="#i-pin"/></svg>
              <span><strong>Địa chỉ:</strong> <?= e($clinic['clinic_address']) ?></span>
            </div>
            <div style="display:flex;align-items:center;gap:10px;">
              <svg class="icon" style="color:var(--blue);width:20px;height:20px;" aria-hidden="true"><use href="#i-phone"/></svg>
              <span><strong>Hotline tiếp đón:</strong> <a href="tel:02086289888" style="color:var(--blue);font-weight:700;">0208 628 9888</a></span>
            </div>
            <div style="display:flex;align-items:center;gap:10px;">
              <svg class="icon" style="color:var(--blue);width:20px;height:20px;" aria-hidden="true"><use href="#i-phone"/></svg>
              <span><strong>Cấp cứu 24/7 & CSKH:</strong> <a href="tel:0963485651" style="color:var(--blue);font-weight:700;">0963 485 651</a></span>
            </div>
            <div style="display:flex;align-items:center;gap:10px;">
              <svg class="icon" style="color:var(--blue);width:20px;height:20px;" aria-hidden="true"><use href="#i-chat"/></svg>
              <span><strong>Email:</strong> <a href="mailto:<?= e($clinic['support_email']) ?>" style="color:var(--blue);"><?= e($clinic['support_email']) ?></a></span>
            </div>
            <div style="display:flex;align-items:center;gap:10px;">
              <svg class="icon" style="color:var(--blue);width:20px;height:20px;" aria-hidden="true"><use href="#i-check"/></svg>
              <span><strong>Giờ làm việc:</strong> 07:00 – 17:30 (Tất cả các ngày trong tuần)</span>
            </div>
          </div>

          <div style="margin-top:24px;display:flex;gap:12px;flex-wrap:wrap;">
            <a class="btn btn-primary" href="<?= e($clinic['google_maps_url']) ?>" target="_blank" rel="noopener">
              <svg class="icon" style="width:16px;height:16px;" aria-hidden="true"><use href="#i-pin"/></svg> Chỉ đường trên Google Maps ↗
            </a>
            <a class="btn btn-outline" href="support.php">
              Trung tâm hỗ trợ người bệnh ➔
            </a>
          </div>
        </div>

        <div style="background:var(--soft);border-radius:18px;padding:32px 28px;border:1px solid #c8e4fa;text-align:center;">
          <div style="width:64px;height:64px;border-radius:50%;background:#ffffff;margin:0 auto 16px;display:grid;place-items:center;color:var(--blue);box-shadow:0 4px 12px rgba(0,95,160,0.1);">
            <svg class="icon" style="width:32px;height:32px;" aria-hidden="true"><use href="#i-folder"/></svg>
          </div>
          <h3 style="font-size:20px;font-weight:700;color:var(--ink);margin:0 0 10px;">Tra cứu hồ sơ y tế trực tuyến</h3>
          <p style="font-size:14px;color:var(--muted);line-height:1.6;margin:0 0 22px;">
            Đăng nhập bằng số Căn cước công dân để xem kết quả xét nghiệm, lịch sử khám bệnh và nhận kết quả nhanh chóng, chính xác.
          </p>
          <?php if (isset($_SESSION['user_id'])): ?>
            <a class="btn btn-primary btn-block" href="dashboard.php">Đến khu vực cá nhân <svg class="icon" style="width:16px;height:16px;" aria-hidden="true"><use href="#i-arrow"/></svg></a>
          <?php else: ?>
            <div style="display:flex;gap:12px;justify-content:center;">
              <a class="btn btn-primary" href="login.php" style="flex:1;">Đăng nhập</a>
              <a class="btn btn-outline" href="register.php" style="flex:1;">Đăng ký</a>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>
</div>

<?php render_footer(); ?>
