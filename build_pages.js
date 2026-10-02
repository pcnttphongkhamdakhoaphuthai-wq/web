const fs = require('fs');
const path = require('path');

const rootDir = __dirname;
const distDir = path.join(rootDir, 'dist');

console.log('[BUILD] Bắt đầu đóng gói giao diện tĩnh cho Cloudflare Pages...');

// 1. Tạo thư mục dist
if (fs.existsSync(distDir)) {
  fs.rmSync(distDir, { recursive: true, force: true });
}
fs.mkdirSync(distDir, { recursive: true });

// Hàm copy đệ quy
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

// 2. Sao chép các tệp tài nguyên tĩnh
console.log('[BUILD] Sao chép assets, logo, sitemap, robots...');
copyRecursive(path.join(rootDir, 'assets'), path.join(distDir, 'assets'));

const staticFiles = ['logo.png', 'robots.txt', 'sitemap.xml'];
for (const file of staticFiles) {
  const filePath = path.join(rootDir, file);
  if (fs.existsSync(filePath)) {
    fs.copyFileSync(filePath, path.join(distDir, file));
  }
}

// 3. Tạo file cấu hình headers tối ưu của Cloudflare Pages
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

// 4. Tạo trang chủ tĩnh index.html tối ưu SEO và kết nối cổng dịch vụ Render
const indexHtmlContent = `<!DOCTYPE html>
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
  <style>
    :root {
      --primary: #0077b6;
      --primary-hover: #023e8a;
      --secondary: #00b4d8;
      --surface: #ffffff;
      --bg: #f0f7f9;
      --text: #0f172a;
      --muted: #475569;
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
    .btn { background: var(--primary); color: #fff !important; padding: 10px 20px; border-radius: 10px; font-weight: 600; text-decoration: none; transition: background .2s, transform .1s; display: inline-block; }
    .btn:hover { background: var(--primary-hover); transform: translateY(-1px); }
    .hero { background: linear-gradient(135deg, #0077b6 0%, #0096c7 100%); color: #fff; padding: 70px 24px; text-align: center; }
    .hero h1 { font-size: 2.6rem; margin-bottom: 16px; font-weight: 800; }
    .hero p { font-size: 1.2rem; max-width: 760px; margin: 0 auto 28px; opacity: 0.95; }
    .container { max-width: 1200px; margin: 40px auto; padding: 0 20px; }
    .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px; margin-bottom: 40px; }
    .card { background: var(--surface); border-radius: 18px; padding: 28px; box-shadow: var(--shadow); border: 1px solid rgba(0, 119, 182, 0.08); transition: transform .2s; }
    .card:hover { transform: translateY(-4px); }
    .card h3 { color: var(--primary); margin-bottom: 10px; font-size: 1.3rem; }
    .card p { color: var(--muted); font-size: 0.98rem; margin-bottom: 20px; }
    footer { background: #0f172a; color: #94a3b8; text-align: center; padding: 36px 20px; font-size: 0.95rem; margin-top: 60px; }
    footer a { color: #38bdf8; text-decoration: none; }
  </style>
</head>
<body>
  <header>
    <div class="nav-container">
      <a href="/" class="brand">
        <img src="/logo.png" alt="Phòng khám đa khoa Phú Thái" onerror="this.style.display='none'">
        <span>Phòng Khám Đa Khoa Phú Thái</span>
      </a>
      <div class="nav-links">
        <a href="https://conghotrophongkhamphuthai.io.vn/news">Tin tức</a>
        <a href="https://conghotrophongkhamphuthai.io.vn/resources">Tư liệu</a>
        <a href="https://conghotrophongkhamphuthai.io.vn/login">Đăng nhập</a>
        <a href="https://conghotrophongkhamphuthai.io.vn/book_appointment" class="btn">Đặt lịch khám</a>
      </div>
    </div>
  </header>

  <section class="hero">
    <h1>Phòng Khám Đa Khoa Phú Thái</h1>
    <p>Hệ thống hỗ trợ bệnh nhân trực tuyến: Đặt lịch khám, quản lý hồ sơ và nhận kết quả nhanh chóng, chính xác.</p>
    <div>
      <a href="https://conghotrophongkhamphuthai.io.vn/book_appointment" class="btn" style="background:#fff; color:var(--primary)!important; font-size:1.1rem; padding:12px 28px;">📅 Đặt lịch khám ngay</a>
      <a href="https://conghotrophongkhamphuthai.io.vn/login" class="btn" style="background:rgba(255,255,255,0.2); border:1px solid #fff; margin-left:12px; font-size:1.1rem; padding:12px 28px;">Tra cứu hồ sơ</a>
    </div>
  </section>

  <main class="container">
    <div class="grid">
      <div class="card">
        <h3>1. Đặt lịch khám bệnh</h3>
        <p>Chủ động chọn bác sĩ chuyên khoa và giờ khám thuận tiện để không phải xếp hàng chờ đợi.</p>
        <a href="https://conghotrophongkhamphuthai.io.vn/book_appointment" class="btn">Đặt hẹn trực tuyến →</a>
      </div>
      <div class="card">
        <h3>2. Kết quả khám & Xét nghiệm</h3>
        <p>Tra cứu kết quả xét nghiệm, siêu âm, điện tim và tải phiếu kết quả điện tử PDF bảo mật.</p>
        <a href="https://conghotrophongkhamphuthai.io.vn/login" class="btn">Xem kết quả khám →</a>
      </div>
      <div class="card">
        <h3>3. Trợ lý y tế AI 24/7</h3>
        <p>Hỏi đáp thông tin thông tư BHYT, bảng giá dịch vụ và hướng dẫn chuẩn bị trước khi khám bệnh.</p>
        <a href="https://conghotrophongkhamphuthai.io.vn/" class="btn">Hỏi đáp trực tuyến →</a>
      </div>
    </div>
  </main>

  <footer>
    <p><strong>Phòng Khám Đa Khoa Phú Thái</strong> — Xóm Hoà Bình 2, xã Phú Bình, tỉnh Thái Nguyên</p>
    <p style="margin-top:8px;">Hotline: <strong>0208 6289 888 / 0963 485 65</strong> | Email: pcnttphongkhamdakhoaphuthai@gmail.com</p>
    <p style="margin-top:14px; font-size:0.85rem; color:#64748b;">© 2026 Bản quyền thuộc về Phòng khám đa khoa Phú Thái. Nền tảng phân phối Cloudflare Edge & Render PaaS.</p>
  </footer>
</body>
</html>
`;
fs.writeFileSync(path.join(distDir, 'index.html'), indexHtmlContent, 'utf-8');

console.log('[BUILD] Hoàn tất build Cloudflare Pages! Thư mục đầu ra: dist/');
