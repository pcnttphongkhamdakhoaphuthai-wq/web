const fs = require('fs');
const path = require('path');

const rootDir = __dirname;
const distDir = path.join(rootDir, 'dist');
const BACKEND_URL = 'https://conghotrophongkhamphuthai.io.vn';

console.log('[BUILD] Đang đóng gói toàn bộ giao diện tĩnh đồng bộ 100% giao diện gốc cho Cloudflare Edge...');

// 1. Dọn dẹp và tạo mới thư mục dist/
if (fs.existsSync(distDir)) {
  fs.rmSync(distDir, { recursive: true, force: true });
}
fs.mkdirSync(distDir, { recursive: true });

// Hàm copy đệ quy an toàn
function copyRecursive(src, dest) {
  if (!fs.existsSync(src)) return;
  const stats = fs.statSync(src);
  if (stats.isDirectory()) {
    fs.mkdirSync(dest, { recursive: true });
    for (const item of fs.readdirSync(src)) {
      copyRecursive(path.join(src, item), path.join(dest, item));
    }
  } else {
    fs.copyFileSync(src, dest);
  }
}

// 2. Sao chép toàn bộ tài nguyên tĩnh
console.log('[BUILD] Sao chép toàn bộ thư mục assets (ảnh bác sĩ, branding, script JS)...');
copyRecursive(path.join(rootDir, 'assets'), path.join(distDir, 'assets'));

const staticFiles = ['logo.png', 'robots.txt', 'sitemap.xml'];
for (const file of staticFiles) {
  const filePath = path.join(rootDir, file);
  if (fs.existsSync(filePath)) {
    fs.copyFileSync(filePath, path.join(distDir, file));
  }
}

// 3. Tạo file cấu hình Cloudflare Pages _headers
const headersContent = `/*
  X-Frame-Options: SAMEORIGIN
  X-Content-Type-Options: nosniff
  Referrer-Policy: strict-origin-when-cross-origin
  Permissions-Policy: geolocation=(), microphone=(), camera=()

/assets/*
  Cache-Control: public, max-age=31536000, immutable

/logo.png
  Cache-Control: public, max-age=31536000, immutable
`;
fs.writeFileSync(path.join(distDir, '_headers'), headersContent, 'utf-8');

// 4. Tạo file cấu hình Cloudflare Pages _redirects (Chỉ chuyển hướng các khu vực quản trị động)
const redirectsContent = `# Cloudflare Pages _redirects configuration
/dashboard*  ${BACKEND_URL}/dashboard:splat  302
/admin_*  ${BACKEND_URL}/admin_:splat  302
/account*  ${BACKEND_URL}/account:splat  302
/download_*  ${BACKEND_URL}/download_:splat  302
/logout.php  ${BACKEND_URL}/logout.php  302
`;
fs.writeFileSync(path.join(distDir, '_redirects'), redirectsContent, 'utf-8');

// ==========================================
// TOÀN BỘ CSS GỐC CỦA HỆ THỐNG (config.php dòng 3705 - 3768)
// ==========================================
const originalStyles = `
  :root {
      --primary: #0077b6;
      --secondary: #00b4d8;
      --surface: #fff;
      --soft: #f1faff;
      --ink: #1f2d3d;
      --muted: #667085;
      --border: #d7e4ef;
      --shadow: 0 18px 40px rgba(10,37,64,.08);
      
      /* New Design Tokens */
      --bg: #f8fafc;
      --bg2: #f1f5f9;
      --tx: #1e293b;
      --tx2: #64748b;
      --ct: #e2e8f0;
      --r: 12px;
      --err-tx: #b91c1c;
      
      color-scheme: light;
  }
  * { box-sizing: border-box; }
  html { scroll-behavior: smooth; }
  body { 
      margin: 0; 
      font-family: "Be Vietnam Pro", Segoe UI, Tahoma, sans-serif; 
      background: linear-gradient(180deg, #eef8fd 0, #f8fbfd 160px, #f4f8fb 100%); 
      color: var(--tx); 
      display: flex;
      flex-direction: column;
      min-height: 100vh;
  }
  main { flex: 1; }
  a{color:inherit}.site-header{position:sticky;top:0;z-index:20;background:rgba(255,255,255,.92);backdrop-filter:blur(12px);box-shadow:0 3px 18px rgba(0,0,0,.05)}
  .site-header-inner{max-width:1180px;margin:0 auto;padding:18px 20px;display:flex;justify-content:space-between;align-items:center;gap:20px}
  .logo{display:flex;align-items:center;gap:12px;font-size:22px;font-weight:800;color:var(--primary);text-decoration:none}.logo-mark{width:156px;height:86px;object-fit:contain;display:block}.logo-text{display:block;line-height:1.1}.nav{display:flex;gap:18px;align-items:center;flex-wrap:wrap}.nav a{text-decoration:none;color:#334155;font-weight:500}.nav a:hover{color:var(--primary)}.nav-pill{padding:10px 14px;background:var(--soft);border-radius:999px}
  .hero{background:linear-gradient(125deg,#0077b6 0,#00b4d8 55%,#73dff3 100%);color:#fff;padding:72px 20px 90px}.hero-inner{max-width:1180px;margin:0 auto;display:grid;grid-template-columns:minmax(0,1.2fr) minmax(300px,.8fr);gap:30px;align-items:center}
  .hero h1{font-size:clamp(34px,5vw,52px);line-height:1.08;margin:0 0 14px;font-weight:800;}.hero p{font-size:18px;line-height:1.7;max-width:640px;margin:0 0 22px}.hero-badges{display:flex;gap:12px;flex-wrap:wrap}.hero-badge{padding:10px 14px;border:1px solid rgba(255,255,255,.22);background:rgba(255,255,255,.14);border-radius:999px;font-size:14px;}
  .hero-panel{background:rgba(255,255,255,.16);border:1px solid rgba(255,255,255,.22);border-radius:24px;padding:24px;box-shadow:0 18px 32px rgba(0,0,0,.12)}.hero-panel h3{margin:0 0 12px}.hero-list{display:grid;gap:12px}.hero-item{padding:14px 16px;border-radius:16px;background:rgba(255,255,255,.14);font-size:15px;line-height:1.5;}
  .wrap{max-width:1180px;margin:-42px auto 0;padding:0 20px 50px;position:relative}.section{margin-bottom:28px}.section-title{font-size:30px;margin:0 0 10px;font-weight:800;}.section-lead{color:var(--muted);max-width:760px;line-height:1.7;margin:0 0 24px}
  .card{background:var(--surface);border-radius:24px;padding:26px;box-shadow:var(--shadow);margin-bottom:20px;border:1px solid rgba(215,228,239,.7)}.grid{display:grid;gap:18px}.grid-2{grid-template-columns:repeat(auto-fit,minmax(280px,1fr))}.grid-3{grid-template-columns:repeat(auto-fit,minmax(220px,1fr))}.grid-4{grid-template-columns:repeat(auto-fit,minmax(220px,1fr))}
  .service-card{background:linear-gradient(180deg,#fff 0,#f8fcff 100%);border:1px solid var(--border);border-radius:22px;padding:24px;box-shadow:var(--shadow)}.service-icon{width:56px;height:56px;border-radius:18px;display:grid;place-items:center;background:linear-gradient(135deg,#0077b6,#00b4d8);color:#fff;font-size:24px;font-weight:800;margin-bottom:16px}
  .service-card h3{margin:0 0 8px;font-size:20px;font-weight:700;}.service-card p{margin:0;color:var(--muted);line-height:1.7}
  .panel-title{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;margin-bottom:18px}h1,h2,h3{margin-top:0}
  label{display:block;font-weight:600;margin-bottom:7px}input,select,textarea{width:100%;padding:13px 14px;border:1px solid var(--border);border-radius:14px;font:inherit;background:#fff}input:focus,select:focus,textarea:focus{outline:none;border-color:var(--secondary);box-shadow:0 0 0 4px rgba(0,180,216,.12)}
  textarea{min-height:120px;resize:vertical}.search-box{display:flex;gap:10px;padding:10px;border-radius:999px;background:#fff;box-shadow:var(--shadow);border:1px solid rgba(255,255,255,.45)}.search-box input{border:none;padding:10px 14px;background:transparent;flex:1;font-size:15px;outline:none;}.search-box input:focus{box-shadow:none}
  button,.btn{display:inline-block;border:none;border-radius:14px;background:linear-gradient(135deg,#0077b6,#0096c7);color:#fff;padding:12px 18px;font:inherit;font-weight:600;text-decoration:none;cursor:pointer;box-shadow:0 12px 24px rgba(0,119,182,.18);text-align:center;}
  .btn-secondary{background:#475569;box-shadow:none}.btn-light{background:#eaf7ff;color:#0369a1;box-shadow:none}.actions{display:flex;gap:10px;flex-wrap:wrap}
  .muted{color:var(--muted)}.badge{display:inline-flex;align-items:center;padding:7px 12px;border-radius:999px;background:#eaf7ff;color:#0369a1;font-size:13px;font-weight:700;margin:4px 6px 0 0}
  .doctor-meta{display:flex;gap:10px;flex-wrap:wrap;margin:10px 0 14px}.doctor-meta span{padding:8px 12px;border-radius:999px;background:#eef8fd;color:#075985;font-size:13px;font-weight:600}.doctor-photo{width:92px;height:92px;border-radius:24px;object-fit:cover;border:1px solid var(--border);box-shadow:var(--shadow);background:#f8fafc}.doctor-card-head{display:flex;align-items:flex-start;gap:18px}
  .map-links{display:flex;gap:10px;flex-wrap:wrap;margin-top:12px}
  .footer{background:#003049;color:#fff;margin-top:40px}.footer-inner{max-width:1180px;margin:0 auto;padding:26px 20px;display:flex;justify-content:space-between;gap:16px;flex-wrap:wrap}
  @media (max-width:1100px){.hero-inner{grid-template-columns:1fr}.wrap{margin-top:0}.hero{padding-bottom:42px}}
  @media (max-width:900px){.hero-inner{grid-template-columns:1fr}.wrap{margin-top:0}.site-header-inner{padding:16px}.nav{gap:12px}.logo-mark{width:112px;height:68px}}
  @media (max-width:640px){.hero{padding:56px 16px 74px}.wrap{padding:0 16px 40px}.card,.service-card{padding:20px}.search-box{flex-direction:column;border-radius:22px}.search-box button,.btn{width:auto}.logo{gap:10px}.logo-mark{width:88px;height:54px}.logo-text{font-size:18px}}

  /* Floating Social Bubbles */
  .social-bubbles{position:fixed;bottom:90px;right:20px;display:flex;flex-direction:column;gap:14px;z-index:9998;}
  .sb{width:64px;height:64px;border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 20px rgba(0,0,0,.3);text-decoration:none;transition:transform .2s,box-shadow .2s;position:relative;border:none;cursor:pointer;}
  .sb:hover{transform:scale(1.1);box-shadow:0 8px 28px rgba(0,0,0,.35);}
  .sb svg{width:40px;height:40px;}
  .sb-zalo{background:#0068ff;}
  .sb-msg{background:linear-gradient(135deg,#00b2ff,#9b30ff);}
`;

// Common Header gốc từ render_header()
const commonHeader = `
  <header class="site-header">
    <div class="site-header-inner">
      <a class="logo" href="/">
        <img class="logo-mark" src="/logo.png" alt="Logo phòng khám">
        <span class="logo-text">PHÒNG KHÁM ĐA KHOA PHÚ THÁI</span>
      </a>
      <nav class="nav">
        <a href="/">Trang chủ</a>
        <a href="/book_appointment.html">Đặt lịch</a>
        <a href="/login.html">Kết quả</a>
        <a href="/news.html">Tin tức</a>
        <a href="/resources.html">Tư liệu</a>
        <a href="/#chatbot">Hỗ trợ</a>
        <a href="/login.html">Đăng nhập</a>
      </nav>
    </div>
  </header>
  <main>
`;

// Common Footer gốc từ render_footer()
const commonFooter = `
  </main>
  <footer class="footer">
    <div class="footer-inner">
      <div>
        Hotline: 0208 6289 888 / 0963 485 65 | Email: pcnttphongkhamdakhoaphuthai@gmail.com
        <br>Địa chỉ: Xóm Hoà Bình 2, xã Phú Bình, tỉnh Thái Nguyên
        <div class="map-links">
          <a class="btn btn-light" href="https://maps.google.com" target="_blank" rel="noopener">Google Maps</a>
        </div>
      </div>
      <div>Phòng khám đa khoa Phú Thái - Cổng hỗ trợ dịch vụ khám chữa bệnh trực tuyến</div>
    </div>
  </footer>

  <!-- Floating Social Bubbles (Zalo + Messenger) -->
  <div class="social-bubbles">
    <a href="https://zalo.me/096348565" target="_blank" rel="noopener noreferrer" class="sb sb-zalo" title="Chat Zalo" aria-label="Chat Zalo">
      <svg viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg"><text x="24" y="34" text-anchor="middle" font-size="22" font-weight="900" fill="white" font-family="Arial Black,Arial,sans-serif" letter-spacing="-1">Zalo</text></svg>
    </a>
    <a href="https://m.me/pcnttphongkhamdakhoaphuthai" target="_blank" rel="noopener noreferrer" class="sb sb-msg" title="Chat Messenger" aria-label="Chat Messenger">
      <svg viewBox="0 0 32 32" fill="white" xmlns="http://www.w3.org/2000/svg"><path d="M16 2C8.268 2 2 7.82 2 14.91c0 3.862 1.74 7.32 4.504 9.73V30l4.14-2.29A14.86 14.86 0 0016 27.82c7.732 0 14-5.82 14-12.91C30 7.82 23.732 2 16 2zm1.4 17.37l-3.57-3.81-6.97 3.81 7.67-8.14 3.66 3.81 6.88-3.81-7.67 8.14z"/></svg>
    </a>
  </div>

  <script src="/assets/api-client.js"></script>
  <script src="/assets/app.js"></script>
`;

// ==========================================
// 5. Sinh trang index.html (Đồng bộ 100% giao diện index.php gốc)
// ==========================================
const indexHtml = `<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cổng hỗ trợ bệnh viện</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700;800&display=swap" media="print" onload="this.media='all'">
  <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700;800&display=swap"></noscript>
  <link rel="icon" type="image/png" href="/logo.png">
  <style>${originalStyles}</style>
</head>
<body>
  ${commonHeader}

  <!-- Hero Section gốc -->
  <section class="hero">
    <div class="hero-inner">
      <div>
        <h1>Phòng khám đa khoa Phú Thái</h1>
        <p>Phòng khám cung cấp dịch vụ đặt lịch, trả kết quả và quản lý hồ sơ khám bệnh trên cùng một hệ thống trực tuyến.</p>
        <div class="hero-badges">
          <span class="hero-badge">Đặt lịch trực tuyến</span>
          <span class="hero-badge">Tra cứu kết quả</span>
          <span class="hero-badge">Hỗ trợ hồ sơ</span>
        </div>
      </div>
      <div class="hero-panel">
        <h3>Dịch vụ nổi bật</h3>
        <div class="hero-list">
          <div class="hero-item">Đăng nhập bằng CCCD để tra cứu lịch hẹn và hồ sơ khám.</div>
          <div class="hero-item">Xem kết quả gần nhất, đơn thuốc và tệp PDF trên cùng một màn hình.</div>
          <div class="hero-item">Liên hệ hỗ trợ để cập nhật BHYT, thanh toán và hồ sơ bệnh án.</div>
        </div>
      </div>
    </div>
  </section>

  <!-- Khối nội dung .wrap gốc lồng lên mép Hero -->
  <div class="wrap">
    <!-- Tìm bác sĩ hoặc dịch vụ -->
    <section class="section card">
      <div class="panel-title">
        <div>
          <h2 class="section-title">Tìm bác sĩ hoặc dịch vụ</h2>
          <p class="section-lead">Chọn nhanh các nhóm chức năng chính để vào đúng luồng thao tác thay vì tìm thủ công từng trang.</p>
        </div>
      </div>
      <div class="search-box">
        <input value="Bác sĩ, dịch vụ, kết quả, hỗ trợ..." readonly>
        <a class="btn" href="/book_appointment.html">Tìm và đặt lịch</a>
      </div>
    </section>

    <!-- Dịch vụ trực tuyến -->
    <section class="section">
      <h2 class="section-title">Dịch vụ trực tuyến</h2>
      <div class="grid grid-4">
        <article class="service-card">
          <div class="service-icon">1</div>
          <h3>Đặt lịch khám</h3>
          <p>Chọn bác sĩ, thời gian khám và gửi yêu cầu trực tuyến nhanh chóng.</p>
          <div class="actions" style="margin-top:12px;">
            <a class="btn btn-light" href="/book_appointment.html">Đặt lịch ngay →</a>
          </div>
        </article>

        <article class="service-card">
          <div class="service-icon">2</div>
          <h3>Kết quả khám</h3>
          <p>Tra cứu hồ sơ, chẩn đoán, đơn thuốc và tệp kết quả PDF.</p>
          <div class="actions" style="margin-top:12px;">
            <a class="btn btn-light" href="/login.html">Xem kết quả →</a>
          </div>
        </article>

        <article class="service-card">
          <div class="service-icon">3</div>
          <h3>Quản lý tài khoản</h3>
          <p>Bệnh nhân tự chỉnh sửa thông tin; admin có khu vực vận hành và phân quyền riêng.</p>
          <div class="actions" style="margin-top:12px;">
            <a class="btn btn-light" href="/login.html">Đến bảng điều khiển →</a>
          </div>
        </article>

        <article class="service-card">
          <div class="service-icon">4</div>
          <h3>Bot chat hỗ trợ</h3>
          <p>Hỏi nhanh các câu thường gặp và nhận câu trả lời mẫu ngay trên hệ thống.</p>
          <div class="actions" style="margin-top:12px;">
            <a class="btn btn-light" href="#chatbot">Mở chat hỗ trợ →</a>
          </div>
        </article>
      </div>
    </section>

    <!-- Giới thiệu về phòng khám -->
    <section class="section card" id="clinic">
      <div class="panel-title">
        <div>
          <h2 class="section-title">Giới thiệu về phòng khám</h2>
          <p class="section-lead">Phòng khám cung cấp dịch vụ đặt lịch, trả kết quả và quản lý hồ sơ khám bệnh trên cùng một hệ thống trực tuyến.</p>
        </div>
      </div>
      <div class="grid grid-4">
        <article class="service-card">
          <h3>Sứ mệnh</h3>
          <p>Tối ưu quy trình tiếp nhận và giúp bệnh nhân theo dõi hồ sơ nhanh hơn.</p>
        </article>
        <article class="service-card">
          <h3>Cơ sở vật chất</h3>
          <p>Có khu khám, khu xét nghiệm và hệ thống lưu trữ hồ sơ điện tử phục vụ tra cứu kết quả.</p>
        </article>
        <article class="service-card">
          <h3>Dịch vụ khám</h3>
          <p>Siêu âm tổng quát<br>Xét nghiệm máu, nước tiểu<br>Khám nội tổng quát<br>Điện tim<br>Tư vấn sức khỏe định kỳ</p>
        </article>
        <article class="service-card">
          <h3>Hỗ trợ người bệnh</h3>
          <p>Hỗ trợ người bệnh từ đặt lịch, tiếp nhận hồ sơ đến trả kết quả trực tuyến.</p>
        </article>
      </div>
    </section>

    <!-- Tin tức mới nhất -->
    <section class="section" id="news">
      <div class="panel-title">
        <div>
          <h2 class="section-title">Tin tức mới nhất</h2>
          <p class="section-lead">Cập nhật các thông tin mới từ phòng khám.</p>
        </div>
        <a class="btn btn-secondary" href="/news.html">Xem tất cả →</a>
      </div>
      <div class="grid grid-3">
        <article class="service-card">
          <h3>Thông báo lịch trực khám bệnh và cấp cứu dịp lễ 2026</h3>
          <div class="muted" style="font-size:13px; margin: 4px 0 10px;">01/10/2026</div>
          <p>Phòng khám duy trì trực cấp cứu 24/7 và tiếp nhận khám chữa bệnh ngoại trú bình thường trong suốt các ngày lễ.</p>
        </article>
        <article class="service-card">
          <h3>Hướng dẫn phòng ngừa và chăm sóc cúm mùa thời điểm giao mùa</h3>
          <div class="muted" style="font-size:13px; margin: 4px 0 10px;">28/09/2026</div>
          <p>Các khuyến cáo thiết yếu từ bác sĩ chuyên khoa giúp bảo vệ hệ hô hấp cho người cao tuổi và trẻ nhỏ.</p>
        </article>
        <article class="service-card">
          <h3>Triển khai gói tầm soát phát hiện sớm tiểu đường và mỡ máu</h3>
          <div class="muted" style="font-size:13px; margin: 4px 0 10px;">25/09/2026</div>
          <p>Gói khám toàn diện với hệ thống máy sinh hóa tự động chuẩn xác và tư vấn phác đồ điều trị cá nhân hóa.</p>
        </article>
      </div>
    </section>

    <!-- Tư liệu khách hàng -->
    <section class="section card" id="resources">
      <div class="panel-title">
        <div>
          <h2 class="section-title">Tư liệu khách hàng</h2>
          <p class="section-lead">Tài liệu giúp khách hàng chuẩn bị trước khi sử dụng dịch vụ.</p>
        </div>
        <a class="btn btn-secondary" href="/resources.html">Xem tất cả →</a>
      </div>
      <div class="grid grid-3">
        <article class="service-card">
          <h3>Quy trình 5 bước khám chữa bệnh</h3>
          <p>Hướng dẫn từ tiếp đón, lấy số thứ tự, đăng ký CCCD đến nhận kết quả xét nghiệm và đơn thuốc.</p>
          <div class="actions" style="margin-top:12px;"><a class="btn btn-light" href="/resources.html">Mở tư liệu</a></div>
        </article>
        <article class="service-card">
          <h3>Chính sách quyền lợi Bảo hiểm y tế (BHYT)</h3>
          <p>Quy định thông tuyến BHYT toàn quốc và các giấy tờ cần xuất trình khi đến khám chữa bệnh.</p>
          <div class="actions" style="margin-top:12px;"><a class="btn btn-light" href="/resources.html">Mở tư liệu</a></div>
        </article>
        <article class="service-card">
          <h3>Lưu ý trước khi lấy máu làm xét nghiệm</h3>
          <p>Những xét nghiệm cần nhịn ăn sáng từ 8-12 tiếng để kết quả xét nghiệm sinh hóa đạt độ chuẩn xác cao nhất.</p>
          <div class="actions" style="margin-top:12px;"><a class="btn btn-light" href="/resources.html">Mở tư liệu</a></div>
        </article>
      </div>
    </section>

    <!-- Giới thiệu về bác sĩ -->
    <section class="section" id="doctors">
      <h2 class="section-title">Giới thiệu về bác sĩ</h2>
      <p class="section-lead">Danh sách bác sĩ và thông tin giới thiệu hiện được cập nhật trực tiếp từ tài khoản admin.</p>
      <div class="grid grid-2">
        <article class="service-card">
          <div class="doctor-card-head">
            <img class="doctor-photo" src="/assets/doctor-placeholder.svg" alt="BS. CK1 Nguyễn Văn Thắng" loading="lazy" width="92" height="92">
            <div>
              <div class="service-icon">1</div>
              <h3>BS. CK1 Nguyễn Văn Thắng</h3>
              <p><strong>Bác sĩ Chuyên khoa 1</strong></p>
            </div>
          </div>
          <div class="doctor-meta">
            <span>Khoa: Nội tổng quát</span>
            <span>Chuyên môn: Tim mạch, Hô hấp, Tiêu hóa</span>
          </div>
          <p>Bác sĩ phụ trách tiếp nhận khám, tư vấn và cập nhật hồ sơ điều trị cho người bệnh trong chuyên khoa tương ứng.</p>
        </article>

        <article class="service-card">
          <div class="doctor-card-head">
            <img class="doctor-photo" src="/assets/doctor-placeholder.svg" alt="ThS. BS Trần Thị Thu Hà" loading="lazy" width="92" height="92">
            <div>
              <div class="service-icon">2</div>
              <h3>ThS. BS Trần Thị Thu Hà</h3>
              <p><strong>Thạc sĩ - Bác sĩ</strong></p>
            </div>
          </div>
          <div class="doctor-meta">
            <span>Khoa: Nhi khoa</span>
            <span>Chuyên môn: Nhi khoa & Dinh dưỡng</span>
          </div>
          <p>Bác sĩ phụ trách tiếp nhận khám, tư vấn và cập nhật hồ sơ điều trị cho người bệnh trong chuyên khoa tương ứng.</p>
        </article>
      </div>
    </section>

    <!-- Section Chatbot AI gốc -->
    <section class="section" id="chatbot" style="padding: 0;">
      <div style="
        background: linear-gradient(135deg, #1e40af 0%, #4f46e5 50%, #7c3aed 100%);
        border-radius: 20px;
        padding: 48px 40px;
        position: relative;
        overflow: hidden;
      ">
        <div style="position:absolute;top:-60px;right:-60px;width:240px;height:240px;background:rgba(255,255,255,0.06);border-radius:50%;"></div>
        <div style="position:absolute;bottom:-40px;left:-40px;width:180px;height:180px;background:rgba(255,255,255,0.05);border-radius:50%;"></div>

        <div style="display:flex;align-items:center;gap:40px;flex-wrap:wrap;position:relative;z-index:1;">
          <div style="flex:1;min-width:260px;">
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

            <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:28px;">
              <div style="display:flex;align-items:center;gap:12px;">
                <span style="font-size:18px;">🔍</span>
                <span style="color:rgba(255,255,255,0.9);font-size:14px;">Tra cứu thông tư, nghị định về BHYT & y tế</span>
              </div>
              <div style="display:flex;align-items:center;gap:12px;">
                <span style="font-size:18px;">💰</span>
                <span style="color:rgba(255,255,255,0.9);font-size:14px;">Hỏi giá dịch vụ & quy trình khám bệnh</span>
              </div>
              <div style="display:flex;align-items:center;gap:12px;">
                <span style="font-size:18px;">📋</span>
                <span style="color:rgba(255,255,255,0.9);font-size:14px;">Hướng dẫn hồ sơ & thủ tục chi tiết</span>
              </div>
              <div style="display:flex;align-items:center;gap:12px;">
                <span style="font-size:18px;">⚡</span>
                <span style="color:rgba(255,255,255,0.9);font-size:14px;">Phản hồi tức thì, không cần chờ đợi</span>
              </div>
            </div>

            <div style="display:flex;gap:12px;flex-wrap:wrap;">
              <a href="/register.html" style="
                background: white;
                color: #4f46e5;
                padding: 13px 26px;
                border-radius: 12px;
                text-decoration: none;
                font-weight: 700;
                font-size: 15px;
                display:inline-block;
                box-shadow: 0 4px 16px rgba(0,0,0,0.2);
              ">
                🚀 Đăng ký miễn phí
              </a>
              <a href="/login.html" style="
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

          <!-- Mockup chat tương tác trực tiếp -->
          <div style="flex:0 0 auto;width:320px;max-width:100%;">
            <div style="
              background: rgba(255,255,255,0.1);
              border: 1px solid rgba(255,255,255,0.2);
              border-radius: 18px;
              padding: 18px;
              backdrop-filter: blur(12px);
            ">
              <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;padding-bottom:14px;border-bottom:1px solid rgba(255,255,255,0.15);">
                <div style="width:36px;height:36px;background:rgba(255,255,255,0.2);border-radius:50%;display:flex;align-items:center;justify-content:center;">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><path d="M12 2a2 2 0 0 1 2 2c0 .74-.4 1.39-1 1.73V7h1a7 7 0 0 1 7 7h1a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1a7 7 0 0 1-7 7H9a7 7 0 0 1-7-7H1a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h1a7 7 0 0 1 7-7h1V5.73A2 2 0 0 1 12 2z"/></svg>
                </div>
                <div>
                  <div style="color:white;font-weight:600;font-size:13px;">Trợ lý AI</div>
                  <div style="color:rgba(255,255,255,0.7);font-size:11px;">● Đang hoạt động</div>
                </div>
              </div>

              <div id="ai-chat-thread" style="display:flex;flex-direction:column;gap:10px;max-height:220px;overflow-y:auto;padding-right:4px;">
                <div style="background:rgba(255,255,255,0.15);border-radius:12px 12px 12px 4px;padding:10px 12px;color:white;font-size:13px;line-height:1.4;">
                  Thông tư 40/2021/TT-BYT quy định gì về chuyển tuyến BHYT?
                </div>
                <div style="background:rgba(255,255,255,0.9);border-radius:12px 12px 4px 12px;padding:10px 12px;color:#1e293b;font-size:12px;line-height:1.5;align-self:flex-end;max-width:90%;">
                  Theo quy định, người bệnh có thẻ BHYT đăng ký KCB ban đầu được chuyển tuyến khi vượt quá khả năng chuyên môn kỹ thuật của cơ sở khám chữa bệnh.
                </div>
              </div>

              <form id="ai-quick-form" style="margin-top:14px;display:flex;gap:8px;">
                <input type="text" id="ai-quick-input" placeholder="Hỏi AI bất kỳ điều gì..." required style="background:rgba(255,255,255,0.2);border:1px solid rgba(255,255,255,0.3);color:#fff;border-radius:20px;padding:8px 14px;font-size:13px;outline:none;width:100%;">
                <button type="submit" class="btn btn-light" style="padding:8px 12px;border-radius:20px;font-size:13px;white-space:nowrap;">Gửi</button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Đăng nhập nhanh bằng CCCD gốc -->
    <section class="section card">
      <div class="grid grid-2">
        <div>
          <h2 class="section-title">Đăng nhập nhanh bằng CCCD</h2>
          <p class="section-lead">Dành cho bệnh nhân đã có tài khoản. Nếu chưa có, tạo tài khoản mới để sử dụng cổng dịch vụ.</p>
          <div class="actions">
            <a class="btn" href="/login.html">Đăng nhập bệnh nhân</a>
            <a class="btn btn-secondary" href="/register.html">Đăng ký tài khoản</a>
            <a class="btn btn-light" href="/book_appointment.html">Đặt lịch khám</a>
          </div>
        </div>
        <div class="service-card">
          <h3>Liên hệ hỗ trợ</h3>
          <p><strong>Hotline:</strong> 0208 6289 888 / 0963 485 65</p>
          <p><strong>Email:</strong> pcnttphongkhamdakhoaphuthai@gmail.com</p>
          <p><strong>Địa chỉ:</strong> Xóm Hoà Bình 2, xã Phú Bình, tỉnh Thái Nguyên</p>
          <div class="map-links">
            <a class="btn btn-light" href="https://maps.google.com" target="_blank" rel="noopener">Google Maps</a>
          </div>
        </div>
      </div>
    </section>
  </div>

  ${commonFooter}

  <script>
    // Xử lý gửi câu hỏi cho trợ lý AI
    const aiForm = document.getElementById('ai-quick-form');
    const aiInput = document.getElementById('ai-quick-input');
    const aiThread = document.getElementById('ai-chat-thread');

    if (aiForm) {
      aiForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const text = aiInput.value.trim();
        if (!text) return;

        const uMsg = document.createElement('div');
        uMsg.style.cssText = 'background:rgba(255,255,255,0.15);border-radius:12px 12px 12px 4px;padding:10px 12px;color:white;font-size:13px;line-height:1.4;';
        uMsg.textContent = text;
        aiThread.appendChild(uMsg);
        aiInput.value = '';
        aiThread.scrollTop = aiThread.scrollHeight;

        const bMsg = document.createElement('div');
        bMsg.style.cssText = 'background:rgba(255,255,255,0.9);border-radius:12px 12px 4px 12px;padding:10px 12px;color:#1e293b;font-size:12px;line-height:1.5;align-self:flex-end;max-width:90%;';
        bMsg.textContent = 'Trợ lý AI đang tra cứu câu trả lời...';
        aiThread.appendChild(bMsg);
        aiThread.scrollTop = aiThread.scrollHeight;

        const res = await ApiClient.call('/api_chat_ai.php', { message: text });
        if (res.ok && res.data && res.data.reply) {
          bMsg.innerHTML = res.data.reply.replace(/\\n/g, '<br>');
        } else {
          bMsg.textContent = res.data?.error || 'Trợ lý AI đang bận. Quý khách vui lòng liên hệ hotline 0208 6289 888 để được giải đáp ngay.';
        }
        aiThread.scrollTop = aiThread.scrollHeight;
      });
    }
  </script>
</body>
</html>`;
fs.writeFileSync(path.join(distDir, 'index.html'), indexHtml, 'utf-8');

// ==========================================
// 6. Sinh trang book_appointment.html (Giao diện đồng bộ)
// ==========================================
const bookAppointmentHtml = `<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Đặt lịch khám - Cổng hỗ trợ bệnh viện</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700;800&display=swap">
  <link rel="icon" type="image/png" href="/logo.png">
  <style>${originalStyles}</style>
</head>
<body>
  ${commonHeader}

  <section class="hero" style="padding:48px 20px 64px;">
    <div class="hero-inner">
      <div>
        <h1>Đặt lịch khám bệnh</h1>
        <p>Chủ động chọn bác sĩ chuyên khoa và giờ khám phù hợp để không phải xếp hàng chờ đợi.</p>
      </div>
    </div>
  </section>

  <div class="wrap" style="max-width:760px; margin:-24px auto 0;">
    <div class="card" style="padding:32px;">
      <form id="book-appointment-form">
        <div style="margin-bottom:18px;">
          <label for="doctor_id">Bác sĩ khám *</label>
          <select id="doctor_id" required>
            <option value="1">BS. CK1 Nguyễn Văn Thắng (Nội tổng quát)</option>
            <option value="2">ThS. BS Trần Thị Thu Hà (Nhi khoa)</option>
          </select>
        </div>

        <div style="margin-bottom:18px;">
          <label for="appointment_date">Ngày và giờ hẹn khám *</label>
          <input type="datetime-local" id="appointment_date" required>
        </div>

        <div style="margin-bottom:18px;">
          <label for="full_name">Họ và tên bệnh nhân *</label>
          <input type="text" id="full_name" placeholder="Nguyễn Văn A" required>
        </div>

        <div style="margin-bottom:18px;">
          <label for="phone">Số điện thoại liên hệ *</label>
          <input type="tel" id="phone" placeholder="0912345678" pattern="0[0-9]{9}" required>
        </div>

        <div style="margin-bottom:18px;">
          <label for="cccd">Số CCCD (12 chữ số - để liên kết hồ sơ)</label>
          <input type="text" id="cccd" placeholder="019203000xxx" maxlength="12">
        </div>

        <div style="margin-bottom:24px;">
          <label for="reason">Triệu chứng hoặc lý do khám *</label>
          <textarea id="reason" placeholder="Mô tả sơ lược tình trạng sức khỏe..." required></textarea>
        </div>

        <button type="submit" id="btn-submit-booking" class="btn" style="width:100%; padding:14px; font-size:16px;">
          Xác nhận đặt lịch khám
        </button>
      </form>
    </div>
  </div>

  ${commonFooter}

  <script>
    const dateInput = document.getElementById('appointment_date');
    const now = new Date();
    now.setHours(now.getHours() + 1);
    now.setMinutes(0);
    dateInput.min = now.toISOString().slice(0, 16);
    dateInput.value = now.toISOString().slice(0, 16);

    const form = document.getElementById('book-appointment-form');
    const submitBtn = document.getElementById('btn-submit-booking');

    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      submitBtn.disabled = true;
      submitBtn.textContent = 'Đang lưu lịch hẹn...';

      const payload = {
        doctor_id: parseInt(document.getElementById('doctor_id').value, 10),
        date: document.getElementById('appointment_date').value,
        name: document.getElementById('full_name').value.trim(),
        phone: document.getElementById('phone').value.trim(),
        cccd: document.getElementById('cccd').value.trim(),
        reason: document.getElementById('reason').value.trim()
      };

      const res = await ApiClient.call('/api/book_appointment.php', payload);
      submitBtn.disabled = false;
      submitBtn.textContent = 'Xác nhận đặt lịch khám';

      if (res.ok && res.data && res.data.success) {
        ApiClient.showToast(res.data.message || 'Đặt lịch thành công!', 'success');
        form.reset();
        setTimeout(() => {
          alert('Cảm ơn bạn! Phiếu hẹn #' + (res.data.appointment_id || '') + ' đã được ghi nhận thành công.');
          window.location.href = '/';
        }, 1500);
      } else {
        ApiClient.showToast(res.data?.error || 'Không thể đặt lịch lúc này. Vui lòng liên hệ hotline.', 'error');
      }
    });
  </script>
</body>
</html>`;
fs.writeFileSync(path.join(distDir, 'book_appointment.html'), bookAppointmentHtml, 'utf-8');

// ==========================================
// 7. Sinh trang login.html (Giao diện đồng bộ)
// ==========================================
const loginHtml = `<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Đăng nhập bệnh nhân - Cổng hỗ trợ bệnh viện</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700;800&display=swap">
  <link rel="icon" type="image/png" href="/logo.png">
  <style>${originalStyles}</style>
</head>
<body>
  ${commonHeader}

  <section class="hero" style="padding:48px 20px 64px;">
    <div class="hero-inner">
      <div>
        <h1>Đăng nhập bệnh nhân</h1>
        <p>Tra cứu kết quả xét nghiệm, lịch hẹn và hồ sơ y tế trực tuyến.</p>
      </div>
    </div>
  </section>

  <div class="wrap" style="max-width:480px; margin:-24px auto 0;">
    <div class="card" style="padding:32px;">
      <h2 style="font-size:22px; margin-bottom:20px; color:var(--primary); font-weight:800;">Thông tin tài khoản</h2>
      <form id="login-form">
        <div style="margin-bottom:18px;">
          <label for="cccd">Số Căn cước công dân (12 chữ số) *</label>
          <input type="text" id="cccd" placeholder="Nhập 12 số CCCD..." maxlength="12" pattern="[0-9]{12}" required>
        </div>

        <div style="margin-bottom:24px;">
          <label for="password">Mật khẩu *</label>
          <input type="password" id="password" placeholder="Nhập mật khẩu..." required>
        </div>

        <button type="submit" id="btn-login" class="btn" style="width:100%; padding:14px; font-size:16px;">
          Đăng nhập
        </button>

        <div style="margin-top:20px; text-align:center; font-size:14px; color:var(--muted);">
          Chưa có tài khoản? <a href="/register.html" style="color:var(--primary); font-weight:600; text-decoration:none;">Đăng ký tại đây</a>
        </div>
      </form>
    </div>
  </div>

  ${commonFooter}

  <script>
    const form = document.getElementById('login-form');
    const submitBtn = document.getElementById('btn-login');

    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      submitBtn.disabled = true;
      submitBtn.textContent = 'Đang xác thực...';

      const payload = {
        cccd: document.getElementById('cccd').value.trim(),
        password: document.getElementById('password').value
      };

      const res = await ApiClient.call('/api/login.php', payload);
      submitBtn.disabled = false;
      submitBtn.textContent = 'Đăng nhập';

      if (res.ok && res.data && res.data.success) {
        ApiClient.showToast('Đăng nhập thành công! Đang chuyển hướng...', 'success');
        setTimeout(() => {
          window.location.href = res.data.redirect || '/dashboard.php';
        }, 1000);
      } else {
        ApiClient.showToast(res.data?.error || 'Đăng nhập thất bại. Kiểm tra lại CCCD và mật khẩu.', 'error');
      }
    });
  </script>
</body>
</html>`;
fs.writeFileSync(path.join(distDir, 'login.html'), loginHtml, 'utf-8');

// ==========================================
// 8. Sinh trang register.html (Giao diện đồng bộ)
// ==========================================
const registerHtml = `<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Đăng ký tài khoản - Cổng hỗ trợ bệnh viện</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700;800&display=swap">
  <link rel="icon" type="image/png" href="/logo.png">
  <style>${originalStyles}</style>
</head>
<body>
  ${commonHeader}

  <section class="hero" style="padding:48px 20px 64px;">
    <div class="hero-inner">
      <div>
        <h1>Đăng ký tài khoản</h1>
        <p>Tạo tài khoản mới để theo dõi hồ sơ sức khỏe trực tuyến và nhận kết quả nhanh chóng.</p>
      </div>
    </div>
  </section>

  <div class="wrap" style="max-width:520px; margin:-24px auto 0;">
    <div class="card" style="padding:32px;">
      <h2 style="font-size:22px; margin-bottom:20px; color:var(--primary); font-weight:800;">Thông tin cá nhân</h2>
      <form id="register-form">
        <div style="margin-bottom:16px;">
          <label for="name">Họ và tên *</label>
          <input type="text" id="name" placeholder="Nguyễn Văn A" required>
        </div>

        <div style="margin-bottom:16px;">
          <label for="cccd">Số Căn cước công dân (12 chữ số) *</label>
          <input type="text" id="cccd" placeholder="Nhập 12 số CCCD..." maxlength="12" pattern="[0-9]{12}" required>
        </div>

        <div style="margin-bottom:16px;">
          <label for="phone">Số điện thoại *</label>
          <input type="tel" id="phone" placeholder="0912345678" pattern="0[0-9]{9}" required>
        </div>

        <div style="margin-bottom:16px;">
          <label for="email">Địa chỉ Email (tùy chọn)</label>
          <input type="email" id="email" placeholder="vidu@gmail.com">
        </div>

        <div style="margin-bottom:24px;">
          <label for="password">Mật khẩu (ít nhất 6 ký tự) *</label>
          <input type="password" id="password" placeholder="Tạo mật khẩu..." minlength="6" required>
        </div>

        <button type="submit" id="btn-register" class="btn" style="width:100%; padding:14px; font-size:16px;">
          Đăng ký tài khoản
        </button>

        <div style="margin-top:20px; text-align:center; font-size:14px; color:var(--muted);">
          Đã có tài khoản? <a href="/login.html" style="color:var(--primary); font-weight:600; text-decoration:none;">Đăng nhập tại đây</a>
        </div>
      </form>
    </div>
  </div>

  ${commonFooter}

  <script>
    const form = document.getElementById('register-form');
    const submitBtn = document.getElementById('btn-register');

    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      submitBtn.disabled = true;
      submitBtn.textContent = 'Đang xử lý đăng ký...';

      const payload = {
        name: document.getElementById('name').value.trim(),
        cccd: document.getElementById('cccd').value.trim(),
        phone: document.getElementById('phone').value.trim(),
        email: document.getElementById('email').value.trim(),
        password: document.getElementById('password').value
      };

      const res = await ApiClient.call('/api/register.php', payload);
      submitBtn.disabled = false;
      submitBtn.textContent = 'Đăng ký tài khoản';

      if (res.ok && res.data && res.data.success) {
        ApiClient.showToast('Đăng ký tài khoản thành công! Đang chuyển sang trang Đăng nhập...', 'success');
        setTimeout(() => {
          window.location.href = '/login.html';
        }, 1500);
      } else {
        ApiClient.showToast(res.data?.error || 'Đăng ký thất bại. Kiểm tra lại thông tin.', 'error');
      }
    });
  </script>
</body>
</html>`;
fs.writeFileSync(path.join(distDir, 'register.html'), registerHtml, 'utf-8');

// ==========================================
// 9. Sinh trang news.html (Giao diện đồng bộ)
// ==========================================
const newsHtml = `<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tin tức - Cổng hỗ trợ bệnh viện</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700;800&display=swap">
  <link rel="icon" type="image/png" href="/logo.png">
  <style>${originalStyles}</style>
</head>
<body>
  ${commonHeader}

  <section class="hero" style="padding:48px 20px 64px;">
    <div class="hero-inner">
      <div>
        <h1>Tin tức y tế</h1>
        <p>Cập nhật những thông báo mới nhất, lịch tiêm chủng và hoạt động của Phòng khám đa khoa Phú Thái.</p>
      </div>
    </div>
  </section>

  <div class="wrap" style="margin-top:-24px;">
    <div class="grid grid-3">
      <article class="service-card">
        <h3>Thông báo lịch trực khám bệnh và cấp cứu dịp lễ 2026</h3>
        <div class="muted" style="font-size:13px; margin:4px 0 10px;">01/10/2026</div>
        <p>Phòng khám duy trì trực cấp cứu 24/7 và tiếp nhận khám chữa bệnh ngoại trú bình thường trong suốt các ngày lễ.</p>
      </article>

      <article class="service-card">
        <h3>Hướng dẫn phòng ngừa và chăm sóc cúm mùa thời điểm giao mùa</h3>
        <div class="muted" style="font-size:13px; margin:4px 0 10px;">28/09/2026</div>
        <p>Các khuyến cáo thiết yếu từ bác sĩ chuyên khoa giúp bảo vệ hệ hô hấp cho người cao tuổi và trẻ nhỏ.</p>
      </article>

      <article class="service-card">
        <h3>Triển khai gói tầm soát phát hiện sớm tiểu đường và mỡ máu</h3>
        <div class="muted" style="font-size:13px; margin:4px 0 10px;">25/09/2026</div>
        <p>Gói khám toàn diện với hệ thống máy sinh hóa tự động chuẩn xác và tư vấn phác đồ điều trị cá nhân hóa.</p>
      </article>
    </div>
  </div>

  ${commonFooter}
</body>
</html>`;
fs.writeFileSync(path.join(distDir, 'news.html'), newsHtml, 'utf-8');

// ==========================================
// 10. Sinh trang resources.html (Giao diện đồng bộ)
// ==========================================
const resourcesHtml = `<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tư liệu khách hàng - Cổng hỗ trợ bệnh viện</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700;800&display=swap">
  <link rel="icon" type="image/png" href="/logo.png">
  <style>${originalStyles}</style>
</head>
<body>
  ${commonHeader}

  <section class="hero" style="padding:48px 20px 64px;">
    <div class="hero-inner">
      <div>
        <h1>Tư liệu & Cẩm nang</h1>
        <p>Tài liệu hướng dẫn quy trình, chính sách bảo hiểm y tế và biểu mẫu phục vụ người bệnh.</p>
      </div>
    </div>
  </section>

  <div class="wrap" style="margin-top:-24px;">
    <div class="grid grid-3">
      <article class="service-card">
        <h3>Quy trình 5 bước khám chữa bệnh</h3>
        <p>Hướng dẫn từ tiếp đón, lấy số thứ tự, đăng ký CCCD đến nhận kết quả xét nghiệm và đơn thuốc.</p>
      </article>

      <article class="service-card">
        <h3>Chính sách quyền lợi Bảo hiểm y tế (BHYT)</h3>
        <p>Quy định thông tuyến BHYT toàn quốc và các giấy tờ cần xuất trình khi đến khám chữa bệnh.</p>
      </article>

      <article class="service-card">
        <h3>Lưu ý trước khi lấy máu làm xét nghiệm</h3>
        <p>Những xét nghiệm cần nhịn ăn sáng từ 8-12 tiếng để kết quả xét nghiệm sinh hóa đạt độ chuẩn xác cao nhất.</p>
      </article>
    </div>
  </div>

  ${commonFooter}
</body>
</html>`;
fs.writeFileSync(path.join(distDir, 'resources.html'), resourcesHtml, 'utf-8');

// ==========================================
// 11. Sinh trang 404.html (Giao diện đồng bộ)
// ==========================================
const notFoundHtml = `<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Không tìm thấy trang - Cổng hỗ trợ bệnh viện</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700;800&display=swap">
  <link rel="icon" type="image/png" href="/logo.png">
  <style>${originalStyles}</style>
</head>
<body>
  ${commonHeader}
  <div class="wrap" style="max-width:600px; margin:60px auto; text-align:center;">
    <div class="card" style="padding:40px 24px;">
      <h1 style="font-size:3.5rem; color:var(--primary); margin-bottom:12px; font-weight:800;">404</h1>
      <h2 style="font-size:1.4rem; margin-bottom:12px;">Không tìm thấy trang yêu cầu</h2>
      <p style="color:var(--muted); margin-bottom:28px;">Đường dẫn bạn truy cập có thể đã thay đổi hoặc không tồn tại trên hệ thống.</p>
      <div style="display:flex; gap:12px; justify-content:center; flex-wrap:wrap;">
        <a href="/" class="btn">Về trang chủ</a>
        <a href="/book_appointment.html" class="btn btn-secondary">Đặt lịch khám</a>
      </div>
    </div>
  </div>
  ${commonFooter}
</body>
</html>`;
fs.writeFileSync(path.join(distDir, '404.html'), notFoundHtml, 'utf-8');

console.log('[BUILD] Hoàn tất đóng gói toàn bộ giao diện tĩnh đồng bộ 100% giao diện gốc vào dist/ thành công!');
