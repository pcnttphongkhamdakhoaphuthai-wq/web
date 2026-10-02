<?php
declare(strict_types=1);

require_once 'config.php';

$clinic = site_settings([
    'clinic_name' => 'Phòng khám đa khoa Phú Thái',
    'clinic_intro' => 'Phòng khám cung cấp dịch vụ đặt lịch, trả kết quả và quản lý hồ sơ khám bệnh trên cùng một hệ thống trực tuyến.',
    'clinic_mission' => 'Tối ưu quy trình tiếp nhận và giúp bệnh nhân theo dõi hồ sơ nhanh hơn.',
    'clinic_facility' => 'Có khu khám, khu xét nghiệm và hệ thống lưu trữ hồ sơ điện tử phục vụ tra cứu kết quả.',
    'clinic_services' => "Siêu âm tổng quát\nXét nghiệm máu, nước tiểu\nKhám nội tổng quát\nĐiện tim\nTư vấn sức khỏe định kỳ",
    'clinic_support' => 'Hỗ trợ người bệnh từ đặt lịch, tiếp nhận hồ sơ đến trả kết quả trực tuyến.',
    'support_hotline' => '1900 0000',
    'support_email' => 'congnghethongtin247@gmail.com',
    'clinic_address' => '',
    'google_maps_url' => '',
    'apple_maps_url' => '',
    'chatbot_intro' => 'Chat hỗ trợ giúp bệnh nhân xem nhanh hướng dẫn thường gặp và gửi câu hỏi cho bộ phận hỗ trợ.',
]);

$doctors = $conn->query('SELECT id, name, title, department, specialties, bio, photo_path FROM doctors ORDER BY id ASC');
$quickReplies = get_active_quick_replies(6);
$newsPosts = get_recent_news_posts(3, true);
$customerResources = get_customer_resources(3, true);
$appointmentsEnabled = appointments_enabled();

render_header('Cổng hỗ trợ bệnh viện');
render_hero($clinic['clinic_name'], $clinic['clinic_intro']);
?>
<div class="wrap">
  <section class="section card">
    <div class="panel-title">
      <div>
        <h2 class="section-title">Tìm bác sĩ hoặc dịch vụ</h2>
        <p class="section-lead">Chọn nhanh các nhóm chức năng chính để vào đúng luồng thao tác thay vì tìm thủ công từng trang.</p>
      </div>
    </div>
    <div class="search-box">
      <input value="Bác sĩ, dịch vụ, kết quả, hỗ trợ..." readonly>
      <?php if ($appointmentsEnabled): ?>
        <a class="btn" href="book_appointment.php">Tìm và đặt lịch</a>
      <?php else: ?>
        <span class="badge">Đặt lịch đang tạm ẩn</span>
      <?php endif; ?>
    </div>
  </section>

  <section class="section">
    <h2 class="section-title">Dịch vụ trực tuyến</h2>
    <div class="grid grid-4">
      <article class="service-card">
        <div class="service-icon">1</div><h3>Đặt lịch khám</h3>
        <p><?= $appointmentsEnabled ? 'Chọn bác sĩ, thời gian khám và gửi yêu cầu trực tuyến nhanh chóng.' : 'Tính năng đang được tạm ẩn và có thể bật lại từ tài khoản admin.' ?></p>
        <?php if ($appointmentsEnabled): ?>
          <div class="actions" style="margin-top:12px;"><a class="btn btn-light" href="book_appointment.php">Đặt lịch ngay →</a></div>
        <?php endif; ?>
      </article>
      <article class="service-card">
        <div class="service-icon">2</div><h3>Kết quả khám</h3>
        <p>Tra cứu hồ sơ, chẩn đoán, đơn thuốc và tệp kết quả PDF.</p>
        <div class="actions" style="margin-top:12px;"><a class="btn btn-light" href="dashboard.php">Xem kết quả →</a></div>
      </article>
      <article class="service-card">
        <div class="service-icon">3</div><h3>Quản lý tài khoản</h3>
        <p>Bệnh nhân tự chỉnh sửa thông tin; admin có khu vực vận hành và phân quyền riêng.</p>
        <div class="actions" style="margin-top:12px;"><a class="btn btn-light" href="dashboard.php">Đến bảng điều khiển →</a></div>
      </article>
      <article class="service-card">
        <div class="service-icon">4</div><h3>Bot chat hỗ trợ</h3>
        <p>Hỏi nhanh các câu thường gặp và nhận câu trả lời mẫu ngay trên hệ thống.</p>
        <div class="actions" style="margin-top:12px;"><a class="btn btn-light" href="dashboard.php#support">Mở chat hỗ trợ →</a></div>
      </article>
    </div>
  </section>

  <section class="section card" id="clinic">
    <div class="panel-title">
      <div>
        <h2 class="section-title">Giới thiệu về phòng khám</h2>
        <p class="section-lead"><?= e($clinic['clinic_intro']) ?></p>
      </div>
    </div>
    <div class="grid grid-4">
      <article class="service-card">
        <h3>Sứ mệnh</h3>
        <p><?= nl2br(e($clinic['clinic_mission'])) ?></p>
      </article>
      <article class="service-card">
        <h3>Cơ sở vật chất</h3>
        <p><?= nl2br(e($clinic['clinic_facility'])) ?></p>
      </article>
      <article class="service-card">
        <h3>Dịch vụ khám</h3>
        <p><?= nl2br(e($clinic['clinic_services'])) ?></p>
      </article>
      <article class="service-card">
        <h3>Hỗ trợ người bệnh</h3>
        <p><?= nl2br(e($clinic['clinic_support'])) ?></p>
      </article>
    </div>
  </section>

  <?php if ($newsPosts !== []): ?>
    <section class="section" id="news">
      <div class="panel-title">
        <div>
          <h2 class="section-title">Tin tức mới nhất</h2>
          <p class="section-lead">Cập nhật các thông tin mới từ phòng khám.</p>
        </div>
        <a class="btn btn-secondary" href="news.php">Xem tất cả →</a>
      </div>
      <div class="grid grid-3">
        <?php foreach ($newsPosts as $post): ?>
          <article class="service-card">
            <?php render_news_media($post); ?>
            <h3><?= e($post['title']) ?></h3>
            <div class="muted text-sm"><?= e(date('d/m/Y', strtotime((string) $post['created_at']))) ?></div>
            <p><?= nl2br(e((string) ($post['excerpt'] ?: $post['body']))) ?></p>
          </article>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

  <?php if ($customerResources !== []): ?>
    <section class="section card" id="resources">
      <div class="panel-title">
        <div>
          <h2 class="section-title">Tư liệu khách hàng</h2>
          <p class="section-lead">Tài liệu giúp khách hàng chuẩn bị trước khi sử dụng dịch vụ.</p>
        </div>
        <a class="btn btn-secondary" href="resources.php">Xem tất cả →</a>
      </div>
      <div class="grid grid-3">
        <?php foreach ($customerResources as $resource): ?>
          <article class="service-card">
            <h3><?= e($resource['title']) ?></h3>
            <?php if (!empty($resource['description'])): ?>
              <p><?= nl2br(e($resource['description'])) ?></p>
            <?php endif; ?>
            <?php if (!empty($resource['resource_url'])): ?>
              <div class="actions" style="margin-top:12px;"><a class="btn btn-light" href="<?= e($resource['resource_url']) ?>" target="_blank" rel="noopener">Mở tư liệu</a></div>
            <?php endif; ?>
          </article>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

  <section class="section" id="doctors">
    <h2 class="section-title">Giới thiệu về bác sĩ</h2>
    <p class="section-lead">Danh sách bác sĩ và thông tin giới thiệu hiện được cập nhật trực tiếp từ tài khoản admin.</p>
    <div class="grid grid-2">
      <?php while ($doctor = $doctors->fetch_assoc()): ?>
        <article class="service-card">
          <div class="doctor-card-head">
            <img class="doctor-photo" src="<?= e(doctor_photo_url($doctor['photo_path'] ?? null)) ?>" alt="<?= e($doctor['name']) ?>">
            <div>
              <div class="service-icon"><?= (int) $doctor['id'] ?></div>
              <h3><?= e($doctor['name']) ?></h3>
              <?php if (!empty($doctor['title'])): ?>
                <p><strong><?= e($doctor['title']) ?></strong></p>
              <?php endif; ?>
            </div>
          </div>
          <div class="doctor-meta">
            <span>Khoa: <?= e($doctor['department']) ?></span>
            <?php if (!empty($doctor['specialties'])): ?>
              <span>Chuyên môn: <?= e($doctor['specialties']) ?></span>
            <?php endif; ?>
          </div>
          <p><?= nl2br(e($doctor['bio'] !== '' ? $doctor['bio'] : 'Bác sĩ phụ trách tiếp nhận khám, tư vấn và cập nhật hồ sơ điều trị cho người bệnh trong chuyên khoa tương ứng.')) ?></p>
        </article>
      <?php endwhile; ?>
    </div>
  </section>

  <section class="section" id="chatbot" style="padding: 0;">
    <div style="
      background: linear-gradient(135deg, #1e40af 0%, #4f46e5 50%, #7c3aed 100%);
      border-radius: 20px;
      padding: 48px 40px;
      position: relative;
      overflow: hidden;
    ">
      <!-- Decorative blobs -->
      <div style="position:absolute;top:-60px;right:-60px;width:240px;height:240px;background:rgba(255,255,255,0.06);border-radius:50%;"></div>
      <div style="position:absolute;bottom:-40px;left:-40px;width:180px;height:180px;background:rgba(255,255,255,0.05);border-radius:50%;"></div>

      <div style="display:flex;align-items:center;gap:40px;flex-wrap:wrap;position:relative;z-index:1;">

        <!-- Left: Icon + Text -->
        <div style="flex:1;min-width:260px;">
          <!-- AI Icon -->
          <div style="
            width: 72px; height: 72px;
            background: rgba(255,255,255,0.15);
            border-radius: 20px;
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 20px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
          ">
            <svg width="38" height="38" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <path d="M12 2a2 2 0 0 1 2 2c0 .74-.4 1.39-1 1.73V7h1a7 7 0 0 1 7 7h1a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1a7 7 0 0 1-7 7H9a7 7 0 0 1-7-7H1a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h1a7 7 0 0 1 7-7h1V5.73A2 2 0 0 1 12 2z"/>
              <circle cx="9" cy="11" r="1" fill="white" stroke="none"/>
              <circle cx="15" cy="11" r="1" fill="white" stroke="none"/>
              <path d="M9 15a3 3 0 0 0 6 0" stroke="white"/>
            </svg>
          </div>

          <div style="display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.25);border-radius:20px;padding:5px 14px;margin-bottom:16px;backdrop-filter:blur(10px);">
            <span style="color:rgba(255,255,255,0.9);font-size:13px;font-weight:500;">● Đang hoạt động 24/7</span>
          </div>

          <h2 style="color:white;font-size:28px;font-weight:800;margin:0 0 12px 0;line-height:1.3;">
            Trợ lý AI phòng khám<br>thông minh
          </h2>
          <p style="color:rgba(255,255,255,0.85);font-size:15px;line-height:1.6;margin:0 0 24px 0;">
            Hỏi bất cứ điều gì về thủ tục khám chữa bệnh, bảng giá dịch vụ, quyền lợi BHYT hay các thông tư y tế mới nhất — AI sẽ tìm và trả lời ngay lập tức.
          </p>

          <!-- Feature bullets -->
          <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:28px;">
            <?php foreach([
              ['🔍', 'Tra cứu thông tư, nghị định về BHYT & y tế'],
              ['💰', 'Hỏi giá dịch vụ & quy trình khám bệnh'],
              ['📋', 'Hướng dẫn hồ sơ & thủ tục chi tiết'],
              ['⚡', 'Phản hồi tức thì, không cần chờ đợi'],
            ] as [$icon, $text]): ?>
            <div style="display:flex;align-items:center;gap:12px;">
              <span style="font-size:18px;"><?= $icon ?></span>
              <span style="color:rgba(255,255,255,0.9);font-size:14px;"><?= $text ?></span>
            </div>
            <?php endforeach; ?>
          </div>

          <!-- CTA buttons -->
          <div style="display:flex;gap:12px;flex-wrap:wrap;">
            <a href="register.php" style="
              background: white;
              color: #4f46e5;
              padding: 13px 26px;
              border-radius: 12px;
              text-decoration: none;
              font-weight: 700;
              font-size: 15px;
              transition: transform 0.15s, box-shadow 0.15s;
              box-shadow: 0 4px 16px rgba(0,0,0,0.2);
              display:inline-block;
            " onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 8px 24px rgba(0,0,0,0.25)'" onmouseout="this.style.transform='';this.style.boxShadow='0 4px 16px rgba(0,0,0,0.2)'">
              🚀 Đăng ký miễn phí
            </a>
            <a href="login.php" style="
              background: rgba(255,255,255,0.15);
              color: white;
              padding: 13px 26px;
              border-radius: 12px;
              text-decoration: none;
              font-weight: 600;
              font-size: 15px;
              border: 1px solid rgba(255,255,255,0.3);
              backdrop-filter: blur(10px);
              display:inline-block;
            ">
              Đăng nhập
            </a>
          </div>
        </div>

        <!-- Right: Chat preview mockup -->
        <div style="flex:0 0 auto;width:280px;">
          <div style="
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 18px;
            padding: 18px;
            backdrop-filter: blur(12px);
          ">
            <!-- Chat header -->
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;padding-bottom:14px;border-bottom:1px solid rgba(255,255,255,0.15);">
              <div style="width:36px;height:36px;background:rgba(255,255,255,0.2);border-radius:50%;display:flex;align-items:center;justify-content:center;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><path d="M12 2a2 2 0 0 1 2 2c0 .74-.4 1.39-1 1.73V7h1a7 7 0 0 1 7 7h1a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1a7 7 0 0 1-7 7H9a7 7 0 0 1-7-7H1a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h1a7 7 0 0 1 7-7h1V5.73A2 2 0 0 1 12 2z"/></svg>
              </div>
              <div>
                <div style="color:white;font-weight:600;font-size:13px;">Trợ lý AI</div>
                <div style="color:rgba(255,255,255,0.7);font-size:11px;">● Đang hoạt động</div>
              </div>
            </div>
            <!-- Messages preview -->
            <div style="display:flex;flex-direction:column;gap:10px;">
              <div style="background:rgba(255,255,255,0.15);border-radius:12px 12px 12px 4px;padding:10px 12px;color:white;font-size:13px;line-height:1.4;">
                Thông tư 40/2021/TT-BYT quy định gì về chuyển tuyến BHYT?
              </div>
              <div style="background:rgba(255,255,255,0.9);border-radius:12px 12px 4px 12px;padding:10px 12px;color:#1e293b;font-size:12px;line-height:1.5;align-self:flex-end;max-width:90%;">
                Theo Thông tư 40/2021/TT-BYT, người bệnh được chuyển tuyến khi vượt quá khả năng chuyên môn của cơ sở điều trị...
              </div>
              <div style="background:rgba(255,255,255,0.15);border-radius:12px 12px 12px 4px;padding:10px 12px;color:white;font-size:13px;">
                Giá khám nội tổng quát?
              </div>
              <div style="background:rgba(255,255,255,0.9);border-radius:12px 12px 4px 12px;padding:10px 12px;color:#1e293b;font-size:12px;align-self:flex-end;">
                💰 Khám nội tổng quát: <strong>100.000 VNĐ</strong>
              </div>
            </div>
            <!-- Input preview -->
            <div style="margin-top:14px;background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.2);border-radius:24px;padding:10px 14px;color:rgba(255,255,255,0.5);font-size:13px;display:flex;align-items:center;justify-content:space-between;">
              <span>Hỏi ngay...</span>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.6)" stroke-width="2.5"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
            </div>
          </div>
        </div>

      </div>
    </div>
    <style>
      @keyframes pulse-ai {
        0%, 100% { box-shadow: 0 0 0 3px rgba(74,222,128,0.3); }
        50% { box-shadow: 0 0 0 6px rgba(74,222,128,0); }
      }
    </style>
  </section>

  <section class="section card">
    <div class="grid grid-2">
      <div>
        <h2 class="section-title">Đăng nhập nhanh bằng CCCD</h2>
        <p class="section-lead">Dành cho bệnh nhân đã có tài khoản. Nếu chưa có, tạo tài khoản mới để sử dụng cổng dịch vụ.</p>
        <div class="actions">
          <a class="btn" href="login.php">Đăng nhập bệnh nhân</a>
          <a class="btn btn-secondary" href="register.php">Đăng ký tài khoản</a>
          <?php if ($appointmentsEnabled): ?>
            <a class="btn btn-light" href="book_appointment.php">Đặt lịch khám</a>
          <?php endif; ?>
        </div>
      </div>
      <div class="service-card">
        <h3>Liên hệ hỗ trợ</h3>
        <p><strong>Hotline:</strong> <?= e($clinic['support_hotline']) ?></p>
        <p><strong>Email:</strong> <?= e($clinic['support_email']) ?></p>
        <?php if ($clinic['clinic_address'] !== ''): ?>
          <p><strong>Địa chỉ:</strong> <?= e($clinic['clinic_address']) ?></p>
          <div class="map-links">
            <?php if ($clinic['google_maps_url'] !== ''): ?>
              <a class="btn btn-light" href="<?= e($clinic['google_maps_url']) ?>" target="_blank" rel="noopener">Google Maps</a>
            <?php endif; ?>
            <?php if ($clinic['apple_maps_url'] !== ''): ?>
              <a class="btn btn-light" href="<?= e($clinic['apple_maps_url']) ?>" target="_blank" rel="noopener">Apple Maps</a>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>
</div>

