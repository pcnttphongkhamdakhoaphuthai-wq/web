const fs = require('fs');
const path = require('path');

const rootDir = __dirname;
const distDir = path.join(rootDir, 'dist');
const BACKEND_URL = 'https://conghotrophongkhamphuthai.io.vn';

console.log('[BUILD] Đang đóng gói toàn bộ hệ thống giao diện tĩnh Decoupled cho Cloudflare Pages...');

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

// 4. Tạo file cấu hình Cloudflare Pages _redirects (Chỉ chuyển hướng các khu vực quản trị động)
const redirectsContent = `# Cloudflare Pages _redirects configuration
# Các trang tĩnh (index, login, register, book_appointment, news, resources) phục vụ trực tiếp 100% từ Cloudflare Edge.
# Chỉ chuyển hướng các trang quản trị backend đặc thù:
/dashboard*  ${BACKEND_URL}/dashboard:splat  302
/admin_*  ${BACKEND_URL}/admin_:splat  302
/account*  ${BACKEND_URL}/account:splat  302
/download_*  ${BACKEND_URL}/download_:splat  302
/logout.php  ${BACKEND_URL}/logout.php  302
`;
fs.writeFileSync(path.join(distDir, '_redirects'), redirectsContent, 'utf-8');

// Common Header & Navigation
const commonHeader = `
  <header>
    <div class="nav-container">
      <a href="/" class="brand">
        <img src="/logo.png" alt="Phòng khám đa khoa Phú Thái" onerror="this.style.display='none'">
        <span>Phòng Khám Đa Khoa Phú Thái</span>
      </a>
      <nav class="nav-links">
        <a href="/">Trang chủ</a>
        <a href="/news.html">Tin tức</a>
        <a href="/resources.html">Cẩm nang</a>
        <a href="/book_appointment.html" class="btn btn-outline">Đặt lịch khám</a>
        <a href="/login.html" class="btn">Đăng nhập</a>
      </nav>
    </div>
  </header>
`;

const commonFooter = `
  <footer>
    <div style="max-width:1200px; margin:0 auto; display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:32px; text-align:left; margin-bottom:30px;">
      <div>
        <h3 style="color:#fff; margin-bottom:12px; font-size:1.15rem;">Phòng Khám Đa Khoa Phú Thái</h3>
        <p style="color:#94a3b8; font-size:0.92rem; line-height:1.6;">Đơn vị y tế uy tín hàng đầu tại Thái Nguyên với đội ngũ bác sĩ giàu kinh nghiệm, trang thiết bị chẩn đoán hiện đại và hệ thống lưu trữ kết quả trực tuyến 24/7.</p>
      </div>
      <div>
        <h3 style="color:#fff; margin-bottom:12px; font-size:1.15rem;">Thông tin liên hệ</h3>
        <p style="color:#cbd5e1; font-size:0.92rem; margin-bottom:8px;">📍 <strong>Địa chỉ:</strong> Xóm Hoà Bình 2, xã Phú Bình, tỉnh Thái Nguyên</p>
        <p style="color:#cbd5e1; font-size:0.92rem; margin-bottom:8px;">📞 <strong>Hotline:</strong> 0208 6289 888 / 0963 485 65</p>
        <p style="color:#cbd5e1; font-size:0.92rem;">✉️ <strong>Email:</strong> pcnttphongkhamdakhoaphuthai@gmail.com</p>
      </div>
      <div>
        <h3 style="color:#fff; margin-bottom:12px; font-size:1.15rem;">Giờ làm việc</h3>
        <p style="color:#cbd5e1; font-size:0.92rem; margin-bottom:6px;">🕒 Thứ 2 - Thứ 7: 07:00 - 17:30</p>
        <p style="color:#cbd5e1; font-size:0.92rem; margin-bottom:6px;">🕒 Chủ nhật: 07:30 - 12:00</p>
        <p style="color:#38bdf8; font-size:0.88rem; margin-top:10px;">★ Trực cấp cứu và hỗ trợ đặt hẹn trực tuyến 24/7</p>
      </div>
    </div>
    <div style="border-top:1px solid #334155; padding-top:20px; font-size:0.85rem; color:#64748b;">
      © 2026 Bản quyền thuộc về Phòng Khám Đa Khoa Phú Thái. Hệ thống tối ưu phân tán Cloudflare Edge & Render PaaS.
    </div>
  </footer>
  <script src="/assets/api-client.js"></script>
`;

const commonStyles = `
  :root {
    --primary: #0077b6;
    --primary-hover: #023e8a;
    --secondary: #00b4d8;
    --surface: #ffffff;
    --bg: #f8fafc;
    --text: #0f172a;
    --muted: #475569;
    --border: #e2e8f0;
    --shadow: 0 4px 20px rgba(0, 119, 182, 0.08);
  }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Be Vietnam Pro', -apple-system, BlinkMacSystemFont, sans-serif; background: var(--bg); color: var(--text); line-height: 1.6; }
  header { background: #fff; border-bottom: 1px solid rgba(0, 119, 182, 0.12); padding: 14px 24px; position: sticky; top: 0; z-index: 100; box-shadow: 0 2px 12px rgba(0,0,0,0.04); }
  .nav-container { max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; }
  .brand { display: flex; align-items: center; gap: 12px; text-decoration: none; color: var(--primary); font-weight: 700; font-size: 1.15rem; }
  .brand img { height: 42px; width: auto; object-fit: contain; }
  .nav-links { display: flex; gap: 16px; align-items: center; }
  .nav-links a { text-decoration: none; color: var(--muted); font-weight: 500; transition: color .2s; font-size: 0.95rem; }
  .nav-links a:hover { color: var(--primary); }
  .btn { background: var(--primary); color: #fff !important; padding: 10px 20px; border-radius: 10px; font-weight: 600; text-decoration: none; transition: all .2s; display: inline-flex; align-items: center; justify-content: center; gap: 8px; border: none; cursor: pointer; text-align: center; }
  .btn:hover { background: var(--primary-hover); transform: translateY(-1px); }
  .btn-outline { background: transparent; border: 1.5px solid var(--primary); color: var(--primary) !important; }
  .btn-outline:hover { background: var(--primary); color: #fff !important; }
  .btn-secondary { background: #e2e8f0; color: #1e293b !important; }
  .btn-secondary:hover { background: #cbd5e1; }
  .btn-block { width: 100%; }
  .hero { background: linear-gradient(135deg, #0077b6 0%, #0096c7 100%); color: #fff; padding: 64px 24px; text-align: center; }
  .hero h1 { font-size: 2.6rem; margin-bottom: 16px; font-weight: 800; letter-spacing: -0.5px; }
  .hero p { font-size: 1.15rem; max-width: 780px; margin: 0 auto 28px; opacity: 0.95; }
  .container { max-width: 1200px; margin: 36px auto; padding: 0 20px; }
  .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px; margin-bottom: 36px; }
  .grid-2 { grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); }
  .grid-3 { grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); }
  .card { background: var(--surface); border-radius: 18px; padding: 26px; box-shadow: var(--shadow); border: 1px solid var(--border); transition: transform .2s, box-shadow .2s; }
  .card:hover { transform: translateY(-2px); box-shadow: 0 8px 26px rgba(0, 119, 182, 0.12); }
  .card h3 { color: var(--primary); margin-bottom: 10px; font-size: 1.25rem; font-weight: 700; }
  .card p { color: var(--muted); font-size: 0.95rem; margin-bottom: 18px; line-height: 1.6; }
  .form-group { margin-bottom: 20px; text-align: left; }
  .form-group label { display: block; font-weight: 600; margin-bottom: 8px; font-size: 0.92rem; color: #1e293b; }
  .form-control { width: 100%; padding: 12px 16px; border: 1.5px solid var(--border); border-radius: 10px; font: inherit; background: #fff; color: #0f172a; transition: border-color .2s, box-shadow .2s; }
  .form-control:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(0, 119, 182, 0.15); }
  footer { background: #0f172a; color: #94a3b8; text-align: center; padding: 48px 24px 24px; margin-top: 60px; }
  .doctor-card { display: flex; gap: 18px; align-items: flex-start; }
  .doctor-photo { width: 84px; height: 84px; border-radius: 16px; object-fit: cover; background: #e0f2fe; flex-shrink: 0; border: 2px solid #bae6fd; }
  .badge { display: inline-block; padding: 4px 10px; border-radius: 6px; font-size: 0.8rem; font-weight: 600; background: #e0f2fe; color: #0369a1; margin-bottom: 8px; }
  @media(max-width: 768px) {
    .hero h1 { font-size: 2rem; }
    .nav-links { display: none; }
  }
`;

// ==========================================
// 5. Sinh trang index.html (Trang chủ tĩnh hoàn chỉnh)
// ==========================================
const indexHtml = `<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Phòng khám Đa khoa Phú Thái - Cổng thông tin y tế trực tuyến</title>
  <meta name="description" content="Phòng khám đa khoa Phú Thái - Đặt lịch khám bệnh, tra cứu kết quả xét nghiệm và tư vấn sức khỏe trực tuyến.">
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
    <p>Chăm sóc sức khỏe tận tâm, ứng dụng công nghệ số hoá y tế hiện đại giúp người bệnh tra cứu kết quả và đặt lịch khám nhanh chóng.</p>
    <div style="display:flex; gap:14px; justify-content:center; flex-wrap:wrap;">
      <a href="/book_appointment.html" class="btn" style="background:#fff; color:var(--primary)!important; font-size:1.05rem; padding:12px 28px; box-shadow:0 4px 15px rgba(0,0,0,0.1);">📅 Đặt lịch khám ngay</a>
      <a href="/login.html" class="btn btn-outline" style="border-color:#fff; color:#fff!important; font-size:1.05rem; padding:12px 28px;">Tra cứu hồ sơ bệnh án</a>
    </div>
  </section>

  <main class="container">
    <div style="text-align:center; margin-bottom:32px;">
      <h2 style="color:var(--primary); font-size:1.85rem; font-weight:800;">Dịch vụ trực tuyến</h2>
      <p style="color:var(--muted); font-size:1rem;">Tiện ích y tế điện tử phục vụ người bệnh 24/7</p>
    </div>

    <div class="grid">
      <div class="card">
        <span class="badge">Nhanh chóng</span>
        <h3>1. Đặt lịch khám bệnh</h3>
        <p>Chủ động chọn bác sĩ chuyên khoa, ngày giờ khám thuận tiện. Không cần xếp hàng chờ đợi lấy số.</p>
        <a href="/book_appointment.html" class="btn">Đặt hẹn trực tuyến →</a>
      </div>
      <div class="card">
        <span class="badge">Bảo mật</span>
        <h3>2. Kết quả xét nghiệm</h3>
        <p>Tra cứu kết quả siêu âm, xét nghiệm máu, đơn thuốc và lịch sử khám bệnh bằng CCCD an toàn.</p>
        <a href="/login.html" class="btn">Tra cứu ngay →</a>
      </div>
      <div class="card">
        <span class="badge">Trí tuệ nhân tạo</span>
        <h3>3. Trợ lý y tế AI 24/7</h3>
        <p>Hỏi đáp thông tin chuẩn bị trước khi khám, bảng giá dịch vụ và chính sách BHYT tức thời.</p>
        <a href="#ai-assistant" class="btn">Chat với AI →</a>
      </div>
      <div class="card">
        <span class="badge">Kiến thức</span>
        <h3>4. Cẩm nang sức khỏe</h3>
        <p>Xem quy trình tiếp nhận, tài liệu hướng dẫn và các bài viết tư vấn y khoa hữu ích.</p>
        <a href="/resources.html" class="btn">Xem cẩm nang →</a>
      </div>
    </div>

    <!-- Giới thiệu phòng khám -->
    <div class="card" style="margin-bottom:36px; padding:36px;">
      <h2 style="color:var(--primary); font-size:1.6rem; margin-bottom:12px;">Về chúng tôi</h2>
      <p style="color:var(--muted); font-size:1.05rem; line-height:1.7; margin-bottom:24px;">Phòng khám đa khoa Phú Thái toạ lạc tại trung tâm huyện Phú Bình, tỉnh Thái Nguyên, là địa chỉ tin cậy của hàng nghìn lượt người bệnh mỗi năm. Chúng tôi tự hào sở hữu hệ thống trang thiết bị chẩn đoán tiên tiến cùng đội ngũ y bác sĩ đầu ngành tâm huyết.</p>
      
      <div class="grid grid-3" style="margin-bottom:0;">
        <div style="background:#f1f5f9; padding:20px; border-radius:12px;">
          <h4 style="color:#0f172a; margin-bottom:6px;">🌟 Sứ mệnh</h4>
          <p style="font-size:0.9rem; margin-bottom:0;">Nâng cao chất lượng sống cộng đồng bằng dịch vụ y tế chuẩn mực, chu đáo và chi phí minh bạch, hợp lý.</p>
        </div>
        <div style="background:#f1f5f9; padding:20px; border-radius:12px;">
          <h4 style="color:#0f172a; margin-bottom:6px;">🏥 Cơ sở vật chất</h4>
          <p style="font-size:0.9rem; margin-bottom:0;">Khu khám riêng biệt, máy siêu âm màu 4D, hệ thống xét nghiệm tự động và phòng lưu bệnh nhân tiện nghi.</p>
        </div>
        <div style="background:#f1f5f9; padding:20px; border-radius:12px;">
          <h4 style="color:#0f172a; margin-bottom:6px;">📋 Chuyên khoa đa dạng</h4>
          <p style="font-size:0.9rem; margin-bottom:0;">Nội tổng quát, Ngoại khoa, Nhi khoa, Sản phụ khoa, Tai Mũi Họng, Chẩn đoán hình ảnh và Xét nghiệm.</p>
        </div>
      </div>
    </div>

    <!-- Đội ngũ bác sĩ -->
    <div style="margin-bottom:36px;">
      <div style="text-align:center; margin-bottom:28px;">
        <h2 style="color:var(--primary); font-size:1.85rem; font-weight:800;">Đội ngũ bác sĩ chuyên khoa</h2>
        <p style="color:var(--muted); font-size:1rem;">Các chuyên gia giàu kinh nghiệm luôn sẵn sàng tư vấn và điều trị</p>
      </div>

      <div class="grid grid-2">
        <div class="card doctor-card">
          <img class="doctor-photo" src="/assets/doctor-placeholder.svg" alt="Bác sĩ CK1">
          <div>
            <span class="badge">Nội khoa tổng quát</span>
            <h3>BS. CK1 Nguyễn Văn Thắng</h3>
            <p style="font-size:0.9rem; margin-bottom:10px;">Hơn 15 năm kinh nghiệm chẩn đoán và điều trị bệnh lý tim mạch, tiểu đường, hô hấp.</p>
            <a href="/book_appointment.html" class="btn btn-outline" style="padding:6px 14px; font-size:0.85rem;">Đặt lịch với bác sĩ</a>
          </div>
        </div>

        <div class="card doctor-card">
          <img class="doctor-photo" src="/assets/doctor-placeholder.svg" alt="Bác sĩ Nhi khoa">
          <div>
            <span class="badge">Nhi khoa</span>
            <h3>ThS. BS Trần Thị Thu Hà</h3>
            <p style="font-size:0.9rem; margin-bottom:10px;">Chuyên gia tư vấn dinh dưỡng, tiêm chủng và điều trị các bệnh lý thường gặp ở trẻ nhỏ.</p>
            <a href="/book_appointment.html" class="btn btn-outline" style="padding:6px 14px; font-size:0.85rem;">Đặt lịch với bác sĩ</a>
          </div>
        </div>
      </div>
    </div>

    <!-- Widget Trợ lý y tế AI Gemini -->
    <div class="card" id="ai-assistant" style="background:linear-gradient(135deg, #1e40af 0%, #3b82f6 100%); color:#fff; padding:36px; margin-bottom:36px;">
      <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:18px; margin-bottom:20px;">
        <div>
          <span style="background:rgba(255,255,255,0.2); padding:4px 12px; border-radius:20px; font-size:0.85rem; font-weight:600;">🤖 Trợ lý AI Thông Minh</span>
          <h2 style="color:#fff; font-size:1.6rem; margin-top:8px;">Hỏi đáp y tế & Hướng dẫn khám</h2>
        </div>
        <p style="max-width:500px; font-size:0.95rem; opacity:0.9; margin:0;">Nhập câu hỏi của bạn để được hỗ trợ tức thì về thời gian khám, bảng giá và thủ tục BHYT.</p>
      </div>

      <div style="background:#fff; border-radius:14px; padding:16px; box-shadow:0 10px 25px rgba(0,0,0,0.15);">
        <div id="ai-chat-history" style="max-height:220px; overflow-y:auto; margin-bottom:14px; display:flex; flex-direction:column; gap:10px; padding:4px;">
          <div style="background:#f1f5f9; color:#1e293b; padding:10px 14px; border-radius:10px; font-size:0.92rem; align-self:flex-start; max-width:85%;">
            Xin chào! Tôi là Trợ lý AI của Phòng Khám Phú Thái. Bạn cần tìm hiểu thông tin về dịch vụ khám, thời gian làm việc hay quy trình nào hôm nay?
          </div>
        </div>
        <form id="ai-chat-form" style="display:flex; gap:10px;">
          <input type="text" id="ai-chat-input" class="form-control" placeholder="Ví dụ: Phòng khám có làm việc ngày chủ nhật không?..." required style="margin:0;">
          <button type="submit" class="btn" style="white-space:nowrap; padding:10px 20px;">Gửi câu hỏi</button>
        </form>
      </div>
    </div>

    <!-- Tra cứu nhanh & Đăng nhập -->
    <div class="card" style="padding:32px;">
      <div class="grid grid-2" style="margin-bottom:0; align-items:center;">
        <div>
          <h3 style="color:var(--primary); font-size:1.4rem; margin-bottom:8px;">Bạn đã từng khám tại Phú Thái?</h3>
          <p style="color:var(--muted); font-size:0.95rem; margin-bottom:18px;">Đăng nhập bằng số Căn cước công dân (CCCD) để xem ngay đơn thuốc, kết quả xét nghiệm và lịch tái khám.</p>
          <div style="display:flex; gap:12px; flex-wrap:wrap;">
            <a href="/login.html" class="btn">Đăng nhập tài khoản</a>
            <a href="/register.html" class="btn btn-secondary">Đăng ký mới</a>
          </div>
        </div>
        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:14px; padding:20px;">
          <h4 style="color:#0f172a; margin-bottom:10px;">📞 Hỗ trợ khẩn cấp & Đặt hẹn</h4>
          <p style="font-size:0.92rem; margin-bottom:6px;"><strong>Hotline 1:</strong> <a href="tel:02086289888" style="color:var(--primary); text-decoration:none; font-weight:700;">0208 6289 888</a></p>
          <p style="font-size:0.92rem; margin-bottom:6px;"><strong>Hotline 2:</strong> <a href="tel:096348565" style="color:var(--primary); text-decoration:none; font-weight:700;">0963 485 65</a></p>
          <p style="font-size:0.85rem; color:#64748b; margin-top:8px;">Nhân viên tư vấn trực điện thoại từ 07:00 đến 21:00 hàng ngày.</p>
        </div>
      </div>
    </div>
  </main>

  ${commonFooter}

  <script>
    // Logic Chatbot AI
    const chatForm = document.getElementById('ai-chat-form');
    const chatInput = document.getElementById('ai-chat-input');
    const chatHistory = document.getElementById('ai-chat-history');

    if (chatForm) {
      chatForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const msg = chatInput.value.trim();
        if (!msg) return;

        // Thêm câu hỏi vào khung chat
        const userDiv = document.createElement('div');
        userDiv.style.cssText = 'background:#0077b6; color:#fff; padding:10px 14px; border-radius:10px; font-size:0.92rem; align-self:flex-end; max-width:85%;';
        userDiv.textContent = msg;
        chatHistory.appendChild(userDiv);
        chatInput.value = '';
        chatHistory.scrollTop = chatHistory.scrollHeight;

        // Trạng thái bot đang gõ
        const botDiv = document.createElement('div');
        botDiv.style.cssText = 'background:#f1f5f9; color:#1e293b; padding:10px 14px; border-radius:10px; font-size:0.92rem; align-self:flex-start; max-width:85%;';
        botDiv.textContent = 'Đang tra cứu thông tin y tế...';
        chatHistory.appendChild(botDiv);
        chatHistory.scrollTop = chatHistory.scrollHeight;

        const res = await ApiClient.call('/api_chat_ai.php', { message: msg });
        if (res.ok && res.data && res.data.reply) {
          botDiv.innerHTML = res.data.reply.replace(/\\n/g, '<br>');
        } else {
          botDiv.textContent = res.data?.error || 'Hệ thống AI đang phản hồi chậm. Quý khách vui lòng liên hệ hotline 0208 6289 888 để được hỗ trợ ngay.';
        }
        chatHistory.scrollTop = chatHistory.scrollHeight;
      });
    }
  </script>
</body>
</html>`;
fs.writeFileSync(path.join(distDir, 'index.html'), indexHtml, 'utf-8');

// ==========================================
// 6. Sinh trang book_appointment.html (Đặt lịch khám độc lập)
// ==========================================
const bookAppointmentHtml = `<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Đặt lịch khám trực tuyến - Phòng khám Đa khoa Phú Thái</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="icon" type="image/png" href="/logo.png">
  <style>${commonStyles}</style>
</head>
<body>
  ${commonHeader}

  <main class="container" style="max-width:720px; margin:40px auto;">
    <div class="card" style="padding:36px;">
      <div style="text-align:center; margin-bottom:28px;">
        <span class="badge">Đăng ký khám bệnh</span>
        <h1 style="color:var(--primary); font-size:1.8rem; font-weight:800; margin-top:6px;">Đặt Lịch Khám Trực Tuyến</h1>
        <p style="color:var(--muted); font-size:0.95rem;">Điền thông tin bên dưới để đặt lịch khám nhanh chóng. Nhân viên y tế sẽ liên hệ xác nhận trong 15 phút.</p>
      </div>

      <form id="book-appointment-form">
        <div class="form-group">
          <label for="doctor_id">Bác sĩ / Chuyên khoa khám *</label>
          <select id="doctor_id" class="form-control" required>
            <option value="1">BS. CK1 Nguyễn Văn Thắng — Nội khoa tổng quát</option>
            <option value="2">ThS. BS Trần Thị Thu Hà — Nhi khoa & Dinh dưỡng</option>
          </select>
        </div>

        <div class="form-group">
          <label for="appointment_date">Ngày và giờ hẹn khám *</label>
          <input type="datetime-local" id="appointment_date" class="form-control" required>
        </div>

        <div class="grid grid-2" style="margin-bottom:0;">
          <div class="form-group">
            <label for="full_name">Họ và tên bệnh nhân *</label>
            <input type="text" id="full_name" class="form-control" placeholder="Nguyễn Văn A" required>
          </div>
          <div class="form-group">
            <label for="phone">Số điện thoại liên hệ *</label>
            <input type="tel" id="phone" class="form-control" placeholder="0912345678" pattern="0[0-9]{9}" required>
          </div>
        </div>

        <div class="form-group">
          <label for="cccd">Số CCCD (12 chữ số - để liên kết hồ sơ)</label>
          <input type="text" id="cccd" class="form-control" placeholder="019203000xxx" maxlength="12">
        </div>

        <div class="form-group">
          <label for="reason">Triệu chứng hoặc lý do khám *</label>
          <textarea id="reason" class="form-control" rows="3" placeholder="Mô tả sơ lược tình trạng sức khỏe, triệu chứng cần khám..." required></textarea>
        </div>

        <button type="submit" id="btn-submit-booking" class="btn btn-block" style="padding:14px; font-size:1.05rem;">
          📅 Xác nhận đặt lịch khám
        </button>
      </form>
    </div>
  </main>

  ${commonFooter}

  <script>
    // Thiết lập ngày giờ tối thiểu là ngày hôm nay + 1 giờ
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
      submitBtn.classList.add('btn-loading');
      submitBtn.disabled = true;
      submitBtn.textContent = 'Đang gửi yêu cầu đặt lịch...';

      const payload = {
        doctor_id: parseInt(document.getElementById('doctor_id').value, 10),
        date: document.getElementById('appointment_date').value,
        name: document.getElementById('full_name').value.trim(),
        phone: document.getElementById('phone').value.trim(),
        cccd: document.getElementById('cccd').value.trim(),
        reason: document.getElementById('reason').value.trim()
      };

      const res = await ApiClient.call('/api/book_appointment.php', payload);
      submitBtn.classList.remove('btn-loading');
      submitBtn.disabled = false;
      submitBtn.textContent = '📅 Xác nhận đặt lịch khám';

      if (res.ok && res.data && res.data.success) {
        ApiClient.showToast(res.data.message || 'Đặt lịch thành công!', 'success');
        form.reset();
        setTimeout(() => {
          alert('Cảm ơn bạn! Phiếu hẹn #' + (res.data.appointment_id || '') + ' đã được ghi nhận. Phòng khám sẽ gọi điện xác nhận theo số ' + payload.phone + '.');
          window.location.href = '/';
        }, 1500);
      } else {
        ApiClient.showToast(res.data?.error || 'Không thể đặt lịch. Vui lòng thử lại hoặc gọi hotline 0208 6289 888.', 'error');
      }
    });
  </script>
</body>
</html>`;
fs.writeFileSync(path.join(distDir, 'book_appointment.html'), bookAppointmentHtml, 'utf-8');

// ==========================================
// 7. Sinh trang login.html (Đăng nhập độc lập)
// ==========================================
const loginHtml = `<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Đăng nhập bệnh nhân - Phòng khám Đa khoa Phú Thái</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="icon" type="image/png" href="/logo.png">
  <style>${commonStyles}</style>
</head>
<body>
  ${commonHeader}

  <main class="container" style="max-width:480px; margin:50px auto;">
    <div class="card" style="padding:36px;">
      <div style="text-align:center; margin-bottom:26px;">
        <span class="badge">Cổng tra cứu hồ sơ</span>
        <h1 style="color:var(--primary); font-size:1.75rem; font-weight:800; margin-top:6px;">Đăng Nhập Bệnh Nhân</h1>
        <p style="color:var(--muted); font-size:0.92rem;">Tra cứu kết quả xét nghiệm, đơn thuốc và lịch sử khám</p>
      </div>

      <form id="login-form">
        <div class="form-group">
          <label for="cccd">Số Căn cước công dân (12 chữ số) *</label>
          <input type="text" id="cccd" class="form-control" placeholder="Nhập 12 số CCCD..." maxlength="12" pattern="[0-9]{12}" required>
        </div>

        <div class="form-group">
          <label for="password">Mật khẩu *</label>
          <input type="password" id="password" class="form-control" placeholder="Nhập mật khẩu..." required>
        </div>

        <button type="submit" id="btn-login" class="btn btn-block" style="padding:13px; font-size:1.02rem; margin-top:8px;">
          Đăng nhập ngay
        </button>

        <div style="margin-top:20px; text-align:center; font-size:0.92rem; color:var(--muted);">
          Chưa có tài khoản bệnh nhân? <a href="/register.html" style="color:var(--primary); font-weight:600; text-decoration:none;">Đăng ký tại đây</a>
        </div>
      </form>
    </div>
  </main>

  ${commonFooter}

  <script>
    const form = document.getElementById('login-form');
    const submitBtn = document.getElementById('btn-login');

    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      submitBtn.classList.add('btn-loading');
      submitBtn.disabled = true;
      submitBtn.textContent = 'Đang xác thực thông tin...';

      const payload = {
        cccd: document.getElementById('cccd').value.trim(),
        password: document.getElementById('password').value
      };

      const res = await ApiClient.call('/api/login.php', payload);
      submitBtn.classList.remove('btn-loading');
      submitBtn.disabled = false;
      submitBtn.textContent = 'Đăng nhập ngay';

      if (res.ok && res.data && res.data.success) {
        ApiClient.showToast('Đăng nhập thành công! Đang chuyển hướng...', 'success');
        setTimeout(() => {
          window.location.href = res.data.redirect || '/dashboard.php';
        }, 1000);
      } else {
        ApiClient.showToast(res.data?.error || 'Đăng nhập không thành công. Vui lòng kiểm tra lại CCCD và mật khẩu.', 'error');
      }
    });
  </script>
</body>
</html>`;
fs.writeFileSync(path.join(distDir, 'login.html'), loginHtml, 'utf-8');

// ==========================================
// 8. Sinh trang register.html (Đăng ký tài khoản độc lập)
// ==========================================
const registerHtml = `<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Đăng ký tài khoản bệnh nhân - Phòng khám Đa khoa Phú Thái</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="icon" type="image/png" href="/logo.png">
  <style>${commonStyles}</style>
</head>
<body>
  ${commonHeader}

  <main class="container" style="max-width:520px; margin:40px auto;">
    <div class="card" style="padding:36px;">
      <div style="text-align:center; margin-bottom:26px;">
        <span class="badge">Tạo tài khoản mới</span>
        <h1 style="color:var(--primary); font-size:1.75rem; font-weight:800; margin-top:6px;">Đăng Ký Bệnh Nhân</h1>
        <p style="color:var(--muted); font-size:0.92rem;">Liên kết hồ sơ sức khỏe điện tử với số CCCD của bạn</p>
      </div>

      <form id="register-form">
        <div class="form-group">
          <label for="name">Họ và tên *</label>
          <input type="text" id="name" class="form-control" placeholder="Nguyễn Văn A" required>
        </div>

        <div class="form-group">
          <label for="cccd">Số Căn cước công dân (12 chữ số) *</label>
          <input type="text" id="cccd" class="form-control" placeholder="Nhập 12 số CCCD..." maxlength="12" pattern="[0-9]{12}" required>
        </div>

        <div class="form-group">
          <label for="phone">Số điện thoại *</label>
          <input type="tel" id="phone" class="form-control" placeholder="0912345678" pattern="0[0-9]{9}" required>
        </div>

        <div class="form-group">
          <label for="email">Địa chỉ Email (tùy chọn)</label>
          <input type="email" id="email" class="form-control" placeholder="vidu@gmail.com">
        </div>

        <div class="form-group">
          <label for="password">Mật khẩu (ít nhất 6 ký tự) *</label>
          <input type="password" id="password" class="form-control" placeholder="Tạo mật khẩu an toàn..." minlength="6" required>
        </div>

        <button type="submit" id="btn-register" class="btn btn-block" style="padding:13px; font-size:1.02rem; margin-top:8px;">
          Tạo tài khoản ngay
        </button>

        <div style="margin-top:20px; text-align:center; font-size:0.92rem; color:var(--muted);">
          Đã có tài khoản? <a href="/login.html" style="color:var(--primary); font-weight:600; text-decoration:none;">Đăng nhập tại đây</a>
        </div>
      </form>
    </div>
  </main>

  ${commonFooter}

  <script>
    const form = document.getElementById('register-form');
    const submitBtn = document.getElementById('btn-register');

    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      submitBtn.classList.add('btn-loading');
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
      submitBtn.classList.remove('btn-loading');
      submitBtn.disabled = false;
      submitBtn.textContent = 'Tạo tài khoản ngay';

      if (res.ok && res.data && res.data.success) {
        ApiClient.showToast('Đăng ký tài khoản thành công! Đang chuyển sang trang Đăng nhập...', 'success');
        setTimeout(() => {
          window.location.href = '/login.html';
        }, 1500);
      } else {
        ApiClient.showToast(res.data?.error || 'Đăng ký thất bại. Vui lòng kiểm tra lại thông tin.', 'error');
      }
    });
  </script>
</body>
</html>`;
fs.writeFileSync(path.join(distDir, 'register.html'), registerHtml, 'utf-8');

// ==========================================
// 9. Sinh trang news.html (Tin tức y khoa)
// ==========================================
const newsHtml = `<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tin tức y tế - Phòng khám Đa khoa Phú Thái</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="icon" type="image/png" href="/logo.png">
  <style>${commonStyles}</style>
</head>
<body>
  ${commonHeader}

  <section class="hero" style="padding:48px 24px;">
    <h1>Tin Tức & Hoạt Động Y Tế</h1>
    <p>Cập nhật những thông báo mới nhất, lịch tiêm chủng và hoạt động chuyên môn của Phòng khám Đa khoa Phú Thái.</p>
  </section>

  <main class="container">
    <div class="grid grid-3">
      <article class="card">
        <span class="badge">Thông báo</span>
        <h3>Lịch làm việc và trực khám dịp lễ 2026</h3>
        <p style="font-size:0.85rem; color:#64748b; margin-bottom:8px;">Ngày đăng: 01/10/2026</p>
        <p>Phòng khám duy trì trực cấp cứu 24/7 và tiếp nhận khám ngoại trú bình thường trong suốt các ngày lễ.</p>
      </article>

      <article class="card">
        <span class="badge">Sức khỏe cộng đồng</span>
        <h3>Hướng dẫn phòng ngừa cúm mùa thời điểm giao mùa</h3>
        <p style="font-size:0.85rem; color:#64748b; margin-bottom:8px;">Ngày đăng: 28/09/2026</p>
        <p>Những lưu ý quan trọng để bảo vệ sức khỏe hệ hô hấp cho người cao tuổi và trẻ nhỏ khi thời tiết thay đổi.</p>
      </article>

      <article class="card">
        <span class="badge">Dịch vụ mới</span>
        <h3>Triển khai gói khám tầm soát tiểu đường và mỡ máu</h3>
        <p style="font-size:0.85rem; color:#64748b; margin-bottom:8px;">Ngày đăng: 25/09/2026</p>
        <p>Gói khám toàn diện giúp phát hiện sớm các nguy cơ tim mạch và chuyển hoá với chi phí ưu đãi.</p>
      </article>
    </div>
  </main>

  ${commonFooter}
</body>
</html>`;
fs.writeFileSync(path.join(distDir, 'news.html'), newsHtml, 'utf-8');

// ==========================================
// 10. Sinh trang resources.html (Tư liệu khách hàng)
// ==========================================
const resourcesHtml = `<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cẩm nang & Tư liệu y tế - Phòng khám Đa khoa Phú Thái</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="icon" type="image/png" href="/logo.png">
  <style>${commonStyles}</style>
</head>
<body>
  ${commonHeader}

  <section class="hero" style="padding:48px 24px;">
    <h1>Cẩm Nang & Tài Liệu Cho Bệnh Nhân</h1>
    <p>Các tài liệu hướng dẫn chuẩn bị trước khi khám, thủ tục thanh toán bảo hiểm y tế và bảng giá niêm yết.</p>
  </section>

  <main class="container">
    <div class="grid grid-3">
      <div class="card">
        <span class="badge">Hướng dẫn</span>
        <h3>Quy trình khám bệnh 5 bước</h3>
        <p>Các bước tiếp nhận: Tiếp đón → Đăng ký lấy số → Khám chuyên khoa → Cận lâm sàng → Nhận kết quả và đơn thuốc.</p>
      </div>

      <div class="card">
        <span class="badge">Bảo hiểm</span>
        <h3>Chính sách Bảo hiểm y tế (BHYT)</h3>
        <p>Hướng dẫn quyền lợi BHYT đúng tuyến, thông tuyến và các giấy tờ cần xuất trình khi đến khám.</p>
      </div>

      <div class="card">
        <span class="badge">Chuẩn bị xét nghiệm</span>
        <h3>Lưu ý trước khi lấy máu xét nghiệm</h3>
        <p>Những xét nghiệm cần nhịn ăn sáng từ 8-12 tiếng, không uống nước ngọt và các chất kích thích để kết quả chính xác nhất.</p>
      </div>
    </div>
  </main>

  ${commonFooter}
</body>
</html>`;
fs.writeFileSync(path.join(distDir, 'resources.html'), resourcesHtml, 'utf-8');

// ==========================================
// 11. Sinh trang 404.html
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
      <h1 style="font-size:3.5rem; color:var(--primary); margin-bottom:12px;">404</h1>
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

console.log('[BUILD] Hoàn tất đóng gói toàn bộ Frontend Decoupled vào thư mục dist/ thành công!');
