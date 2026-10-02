const fs = require('fs');
const path = require('path');

const rootDir = __dirname;
const distDir = path.join(rootDir, 'dist');
const BACKEND_URL = 'https://conghotrophongkhamphuthai.io.vn';

console.log('[BUILD] Đang đóng gói toàn bộ hệ thống giao diện tĩnh cho Cloudflare Pages...');

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

// 2. Sao chép toàn bộ tài nguyên tĩnh (assets, logo, robots, sitemap)
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

// 4. Tạo file cấu hình Cloudflare Pages _redirects (Chuyển hướng an toàn tuân thủ Cloudflare)
const redirectsContent = `# Cloudflare Pages _redirects configuration
# Chuyển hướng các trang động và biểu mẫu xác thực về Backend Render chính thức
/login.html  ${BACKEND_URL}/login.php  302
/register.html  ${BACKEND_URL}/register.php  302
/book_appointment.html  ${BACKEND_URL}/book_appointment.php  302
/login.php  ${BACKEND_URL}/login.php  302
/register.php  ${BACKEND_URL}/register.php  302
/book_appointment.php  ${BACKEND_URL}/book_appointment.php  302
/dashboard*  ${BACKEND_URL}/dashboard:splat  302
/admin_*  ${BACKEND_URL}/admin_:splat  302
/account*  ${BACKEND_URL}/account:splat  302
/download_*  ${BACKEND_URL}/download_:splat  302
/logout.php  ${BACKEND_URL}/logout.php  302
/api_chat_ai.php  ${BACKEND_URL}/api_chat_ai.php  302
/patient_chat_poll.php  ${BACKEND_URL}/patient_chat_poll.php  302
/api/*  ${BACKEND_URL}/api/:splat  302
`;
fs.writeFileSync(path.join(distDir, '_redirects'), redirectsContent, 'utf-8');

// Header & Navigation dùng chung
const commonHeader = `
  <header>
    <div class="nav-container">
      <a href="/" class="brand">
        <img src="/logo.png" alt="Phòng khám đa khoa Phú Thái" onerror="this.style.display='none'">
        <span>Phòng Khám Đa Khoa Phú Thái</span>
      </a>
      <div class="nav-links">
        <a href="/news.html">Tin tức</a>
        <a href="/resources.html">Tư liệu</a>
        <a href="${BACKEND_URL}/login.php">Đăng nhập</a>
        <a href="${BACKEND_URL}/book_appointment.php" class="btn">Đặt lịch khám</a>
      </div>
    </div>
  </header>
`;

const commonFooter = `
  <footer>
    <p><strong>Phòng Khám Đa Khoa Phú Thái</strong> — Xóm Hoà Bình 2, xã Phú Bình, tỉnh Thái Nguyên</p>
    <p style="margin-top:8px;">Hotline: <strong>0208 6289 888 / 0963 485 65</strong> | Email: pcnttphongkhamdakhoaphuthai@gmail.com</p>
    <p style="margin-top:14px; font-size:0.85rem; color:#64748b;">© 2026 Bản quyền thuộc về Phòng khám đa khoa Phú Thái. Tối ưu phân phối tĩnh Cloudflare Pages & Render Backend.</p>
  </footer>
  <script src="/assets/app.js"></script>
`;

const commonStyles = `
  :root {
    --primary: #0077b6;
    --primary-hover: #023e8a;
    --secondary: #00b4d8;
    --surface: #ffffff;
    --bg: #f0f7f9;
    --text: #0f172a;
    --muted: #475569;
    --border: #d7e4ef;
    --shadow: 0 10px 30px rgba(0, 119, 182, 0.08);
  }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Be Vietnam Pro', -apple-system, BlinkMacSystemFont, sans-serif; background: var(--bg); color: var(--text); line-height: 1.6; }
  header { background: #fff; border-bottom: 1px solid rgba(0, 119, 182, 0.12); padding: 14px 24px; position: sticky; top: 0; z-index: 100; box-shadow: 0 2px 12px rgba(0,0,0,0.04); }
  .nav-container { max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; }
  .brand { display: flex; align-items: center; gap: 12px; text-decoration: none; color: var(--primary); font-weight: 700; font-size: 1.15rem; }
  .brand img { height: 42px; width: auto; object-fit: contain; }
  .nav-links { display: flex; gap: 20px; align-items: center; }
  .nav-links a { text-decoration: none; color: var(--muted); font-weight: 500; transition: color .2s; }
  .nav-links a:hover { color: var(--primary); }
  .btn { background: var(--primary); color: #fff !important; padding: 10px 20px; border-radius: 10px; font-weight: 600; text-decoration: none; transition: background .2s, transform .1s; display: inline-block; border: none; cursor: pointer; text-align: center; }
  .btn:hover { background: var(--primary-hover); transform: translateY(-1px); }
  .btn-secondary { background: #e2e8f0; color: #1e293b !important; }
  .btn-secondary:hover { background: #cbd5e1; }
  .hero { background: linear-gradient(135deg, #0077b6 0%, #0096c7 100%); color: #fff; padding: 60px 24px; text-align: center; }
  .hero h1 { font-size: 2.5rem; margin-bottom: 16px; font-weight: 800; }
  .hero p { font-size: 1.15rem; max-width: 760px; margin: 0 auto 26px; opacity: 0.95; }
  .container { max-width: 1200px; margin: 36px auto; padding: 0 20px; }
  .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 22px; margin-bottom: 36px; }
  .grid-2 { grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); }
  .card { background: var(--surface); border-radius: 18px; padding: 26px; box-shadow: var(--shadow); border: 1px solid var(--border); transition: transform .2s; }
  .card:hover { transform: translateY(-3px); }
  .card h3 { color: var(--primary); margin-bottom: 10px; font-size: 1.25rem; }
  .card p { color: var(--muted); font-size: 0.96rem; margin-bottom: 18px; }
  .form-group { margin-bottom: 18px; }
  .form-group label { display: block; font-weight: 600; margin-bottom: 6px; font-size: 0.92rem; }
  .form-control { width: 100%; padding: 12px 14px; border: 1px solid var(--border); border-radius: 12px; font: inherit; background: #fff; }
  .form-control:focus { outline: none; border-color: var(--secondary); box-shadow: 0 0 0 3px rgba(0, 180, 216, 0.15); }
  footer { background: #0f172a; color: #94a3b8; text-align: center; padding: 36px 20px; font-size: 0.92rem; margin-top: 50px; }
  footer a { color: #38bdf8; text-decoration: none; }
`;

// ==========================================
// 5. Sinh trang index.html (Trang chủ tĩnh)
// ==========================================
const indexHtml = `<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Phòng khám Đa khoa Phú Thái - Cổng thông tin y tế trực tuyến</title>
  <meta name="description" content="Phòng khám đa khoa Phú Thái - Đặt lịch khám, tra cứu hồ sơ kết quả xét nghiệm và tư vấn sức khỏe trực tuyến.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="icon" type="image/png" href="/logo.png">
  <style>${commonStyles}</style>
</head>
<body>
  ${commonHeader}

  <section class="hero">
    <h1>Phòng Khám Đa Khoa Phú Thái</h1>
    <p>Tối ưu quy trình tiếp nhận, theo dõi hồ sơ y tế điện tử và nhận kết quả nhanh chóng, chuẩn xác.</p>
    <div style="display:flex; gap:12px; justify-content:center; flex-wrap:wrap;">
      <a href="/book_appointment.html" class="btn" style="background:#fff; color:var(--primary)!important; font-size:1.05rem; padding:12px 26px;">📅 Đặt lịch khám ngay</a>
      <a href="/login.html" class="btn" style="background:rgba(255,255,255,0.2); border:1px solid #fff; font-size:1.05rem; padding:12px 26px;">Tra cứu hồ sơ</a>
    </div>
  </section>

  <main class="container">
    <h2 style="color:var(--primary); font-size:1.7rem; margin-bottom:20px; text-align:center;">Dịch vụ trực tuyến</h2>
    <div class="grid">
      <div class="card">
        <h3>1. Đặt lịch khám bệnh</h3>
        <p>Chủ động chọn bác sĩ, giờ khám và gửi yêu cầu khám chữa bệnh trực tuyến không cần xếp hàng.</p>
        <a href="/book_appointment.html" class="btn">Đặt hẹn trực tuyến →</a>
      </div>
      <div class="card">
        <h3>2. Kết quả khám & Xét nghiệm</h3>
        <p>Tra cứu hồ sơ, chẩn đoán, đơn thuốc và tệp kết quả siêu âm, xét nghiệm PDF bảo mật.</p>
        <a href="/login.html" class="btn">Tra cứu kết quả →</a>
      </div>
      <div class="card">
        <h3>3. Trợ lý y tế AI 24/7</h3>
        <p>Hỏi đáp thông tin thông tư BHYT, bảng giá dịch vụ và hướng dẫn chuẩn bị trước khi khám bệnh.</p>
        <a href="${BACKEND_URL}/#ai-assistant" class="btn">Hỏi đáp trực tuyến →</a>
      </div>
      <div class="card">
        <h3>4. Tư liệu & Cẩm nang</h3>
        <p>Xem quy trình khám, hướng dẫn chuẩn bị hồ sơ bảo hiểm và các tài liệu y khoa thiết yếu.</p>
        <a href="/resources.html" class="btn">Xem tư liệu y khoa →</a>
      </div>
    </div>

    <!-- Đăng nhập nhanh CCCD -->
    <div class="card" style="margin-top:20px;">
      <div class="grid grid-2" style="margin-bottom:0;">
        <div>
          <h3>Đăng nhập nhanh bằng CCCD</h3>
          <p>Dành cho bệnh nhân đã có tài khoản để xem kết quả xét nghiệm và lịch hẹn.</p>
          <div style="display:flex; gap:12px; flex-wrap:wrap;">
            <a class="btn" href="/login.html">Đăng nhập bệnh nhân</a>
            <a class="btn btn-secondary" href="/register.html">Đăng ký tài khoản</a>
          </div>
        </div>
        <div>
          <h3>Thông tin liên hệ</h3>
          <p><strong>Hotline:</strong> 0208 6289 888 / 0963 485 65</p>
          <p><strong>Email:</strong> pcnttphongkhamdakhoaphuthai@gmail.com</p>
          <p><strong>Địa chỉ:</strong> Xóm Hoà Bình 2, xã Phú Bình, tỉnh Thái Nguyên</p>
        </div>
      </div>
    </div>
  </main>

  ${commonFooter}
</body>
</html>`;
fs.writeFileSync(path.join(distDir, 'index.html'), indexHtml, 'utf-8');

// ==========================================
// 6. Sinh trang login.html (Chuyển hướng xác thực an toàn)
// ==========================================
const loginHtml = `<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="refresh" content="0; url=${BACKEND_URL}/login.php">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Đăng nhập - Phòng khám Đa khoa Phú Thái</title>
  <script>window.location.replace("${BACKEND_URL}/login.php");</script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="icon" type="image/png" href="/logo.png">
  <style>${commonStyles}</style>
</head>
<body>
  ${commonHeader}
  <main class="container" style="max-width:480px; margin:60px auto;">
    <div class="card" style="padding:32px; text-align:center;">
      <h2 style="color:var(--primary); font-size:1.5rem; margin-bottom:14px;">Đang kết nối bảo mật...</h2>
      <p style="color:var(--muted); margin-bottom:24px;">Hệ thống đang chuyển bạn đến Cổng Đăng Nhập Chính Thức của Phòng Khám Đa Khoa Phú Thái.</p>
      <a href="${BACKEND_URL}/login.php" class="btn" style="padding:12px 24px;">Bấm vào đây nếu trình duyệt không tự chuyển</a>
    </div>
  </main>
  ${commonFooter}
</body>
</html>`;
fs.writeFileSync(path.join(distDir, 'login.html'), loginHtml, 'utf-8');

// ==========================================
// 7. Sinh trang register.html (Chuyển hướng xác thực an toàn)
// ==========================================
const registerHtml = `<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="refresh" content="0; url=${BACKEND_URL}/register.php">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Đăng ký tài khoản - Phòng khám Đa khoa Phú Thái</title>
  <script>window.location.replace("${BACKEND_URL}/register.php");</script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="icon" type="image/png" href="/logo.png">
  <style>${commonStyles}</style>
</head>
<body>
  ${commonHeader}
  <main class="container" style="max-width:480px; margin:60px auto;">
    <div class="card" style="padding:32px; text-align:center;">
      <h2 style="color:var(--primary); font-size:1.5rem; margin-bottom:14px;">Đang kết nối bảo mật...</h2>
      <p style="color:var(--muted); margin-bottom:24px;">Hệ thống đang chuyển bạn đến Cổng Đăng Ký Chính Thức của Phòng Khám Đa Khoa Phú Thái.</p>
      <a href="${BACKEND_URL}/register.php" class="btn" style="padding:12px 24px;">Bấm vào đây nếu trình duyệt không tự chuyển</a>
    </div>
  </main>
  ${commonFooter}
</body>
</html>`;
fs.writeFileSync(path.join(distDir, 'register.html'), registerHtml, 'utf-8');

// ==========================================
// 8. Sinh trang book_appointment.html (Chuyển hướng đặt lịch an toàn)
// ==========================================
const bookAppointmentHtml = `<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="refresh" content="0; url=${BACKEND_URL}/book_appointment.php">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Đặt lịch khám - Phòng khám Đa khoa Phú Thái</title>
  <script>window.location.replace("${BACKEND_URL}/book_appointment.php");</script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="icon" type="image/png" href="/logo.png">
  <style>${commonStyles}</style>
</head>
<body>
  ${commonHeader}
  <main class="container" style="max-width:540px; margin:60px auto;">
    <div class="card" style="padding:32px; text-align:center;">
      <h2 style="color:var(--primary); font-size:1.5rem; margin-bottom:14px;">Đang kết nối hệ thống đặt lịch...</h2>
      <p style="color:var(--muted); margin-bottom:24px;">Hệ thống đang chuyển bạn đến Cổng Đặt Lịch Khám Chính Thức để lựa chọn bác sĩ và khung giờ theo thời gian thực.</p>
      <a href="${BACKEND_URL}/book_appointment.php" class="btn" style="padding:12px 24px;">Bấm vào đây nếu trình duyệt không tự chuyển</a>
    </div>
  </main>
  ${commonFooter}
</body>
</html>`;
fs.writeFileSync(path.join(distDir, 'book_appointment.html'), bookAppointmentHtml, 'utf-8');

// ==========================================
// 9. Sinh trang news.html (Tin tức)
// ==========================================
const newsHtml = `<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tin tức & Thông báo y tế - Phòng khám Đa khoa Phú Thái</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="icon" type="image/png" href="/logo.png">
  <style>${commonStyles}</style>
</head>
<body>
  ${commonHeader}
  <main class="container">
    <h1 style="color:var(--primary); font-size:2rem; margin-bottom:12px;">Tin tức & Cẩm nang y tế</h1>
    <p style="color:var(--muted); margin-bottom:30px;">Cập nhật thông tin y khoa mới nhất, thông báo lịch làm việc và hướng dẫn chăm sóc sức khỏe.</p>

    <div class="grid">
      <div class="card">
        <span style="font-size:0.85rem; color:#0284c7; font-weight:600;">THÔNG BÁO</span>
        <h3 style="margin-top:6px;">Lịch khám bệnh và xét nghiệm định kỳ</h3>
        <p>Phòng khám đa khoa Phú Thái thông báo lịch trực khám bệnh tất cả các ngày trong tuần từ thứ 2 đến chủ nhật.</p>
        <a href="${BACKEND_URL}/news" class="btn btn-secondary">Đọc chi tiết →</a>
      </div>
      <div class="card">
        <span style="font-size:0.85rem; color:#0284c7; font-weight:600;">CẨM NANG SỨC KHỎE</span>
        <h3 style="margin-top:6px;">Chuẩn bị trước khi đi xét nghiệm máu</h3>
        <p>Những lưu ý quan trọng về việc nhịn ăn, uống nước và các yếu tố ảnh hưởng đến độ chuẩn xác của kết quả sinh hóa.</p>
        <a href="${BACKEND_URL}/news" class="btn btn-secondary">Đọc chi tiết →</a>
      </div>
      <div class="card">
        <span style="font-size:0.85rem; color:#0284c7; font-weight:600;">CHÍNH SÁCH BHYT</span>
        <h3 style="margin-top:6px;">Quyền lợi và thủ tục chuyển tuyến BHYT</h3>
        <p>Hướng dẫn chi tiết về quy định chuyển tuyến bảo hiểm y tế mới nhất để người bệnh hưởng mức thanh toán tối đa.</p>
        <a href="${BACKEND_URL}/news" class="btn btn-secondary">Đọc chi tiết →</a>
      </div>
    </div>
  </main>
  ${commonFooter}
</body>
</html>`;
fs.writeFileSync(path.join(distDir, 'news.html'), newsHtml, 'utf-8');

// ==========================================
// 10. Sinh trang resources.html (Tư liệu)
// ==========================================
const resourcesHtml = `<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tư liệu & Hướng dẫn khám bệnh - Phòng khám Đa khoa Phú Thái</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="icon" type="image/png" href="/logo.png">
  <style>${commonStyles}</style>
</head>
<body>
  ${commonHeader}
  <main class="container">
    <h1 style="color:var(--primary); font-size:2rem; margin-bottom:12px;">Tư liệu & Hướng dẫn khám chữa bệnh</h1>
    <p style="color:var(--muted); margin-bottom:30px;">Tài liệu, biểu mẫu tiếp nhận và thông tin tra cứu cho người bệnh.</p>

    <div class="grid">
      <div class="card">
        <h3>Quy trình tiếp nhận khám bệnh</h3>
        <p>Hướng dẫn các bước từ lấy số thứ tự, đăng ký thông tin bằng CCCD, đến phòng khám chuyên khoa và nhận kết quả.</p>
        <a href="${BACKEND_URL}/resources" class="btn btn-secondary">Xem quy trình →</a>
      </div>
      <div class="card">
        <h3>Bảng giá dịch vụ khám & xét nghiệm</h3>
        <p>Bảng niêm yết công khai chi phí khám bệnh, siêu âm, điện tim, chụp X-quang và các gói xét nghiệm tổng quát.</p>
        <a href="${BACKEND_URL}/resources" class="btn btn-secondary">Xem bảng giá →</a>
      </div>
      <div class="card">
        <h3>Biểu mẫu giấy khám sức khỏe</h3>
        <p>Tải mẫu giấy đăng ký khám sức khỏe lái xe, học tập, công tác hoặc khám sức khỏe định kỳ theo mẫu Bộ Y tế.</p>
        <a href="${BACKEND_URL}/resources" class="btn btn-secondary">Tải biểu mẫu →</a>
      </div>
    </div>
  </main>
  ${commonFooter}
</body>
</html>`;
fs.writeFileSync(path.join(distDir, 'resources.html'), resourcesHtml, 'utf-8');

// ==========================================
// 11. Sinh trang 404.html (Xử lý fallback theo chuẩn Cloudflare Static Assets)
// ==========================================
const notFoundHtml = `<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Trang không tìm thấy (404) - Phòng khám Đa khoa Phú Thái</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="icon" type="image/png" href="/logo.png">
  <style>${commonStyles}</style>
</head>
<body>
  ${commonHeader}
  <main class="container" style="max-width:600px; margin:70px auto; text-align:center;">
    <div class="card" style="padding:40px 24px;">
      <h1 style="font-size:3rem; color:var(--primary); margin-bottom:12px;">404</h1>
      <h2 style="font-size:1.4rem; color:var(--text); margin-bottom:12px;">Không tìm thấy trang yêu cầu</h2>
      <p style="color:var(--muted); margin-bottom:28px;">Đường dẫn bạn truy cập có thể đã thay đổi hoặc không tồn tại trên hệ thống.</p>
      <div style="display:flex; gap:12px; justify-content:center; flex-wrap:wrap;">
        <a href="/" class="btn">Về trang chủ</a>
        <a href="/book_appointment.html" class="btn btn-secondary">Đặt lịch khám</a>
      </div>
    </div>
  </main>
  ${commonFooter}
</body>
</html>`;
fs.writeFileSync(path.join(distDir, '404.html'), notFoundHtml, 'utf-8');

console.log('[BUILD] Đóng gói thành công toàn bộ hệ thống giao diện tĩnh cho Cloudflare Pages tại thư mục dist/!');
